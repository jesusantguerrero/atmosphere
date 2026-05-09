<?php

namespace App\Console\Commands;

use App\Domains\Transaction\Models\BillingCycle;
use App\Models\Team;
use App\Services\OneSignalService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Daily push-notification scheduler for credit-card billing cycles. Three
 * triggers, each fires exactly once per cycle (single-fire flags on the
 * `billing_cycles` table):
 *
 *   1. POST_CUT   (T+1 from end_at)        — "verify your statement balance"
 *   2. PRE_DUE    (T-3 to T from due_at)   — "due in N days"
 *   3. OVERDUE    (T+10 from due_at, !paid) — "did you forget to log the payment?"
 *
 * Targeted at the team owner via OneSignal external ID. Status PAID and
 * CANCELLED cycles are excluded entirely.
 */
class NudgeCreditCards extends Command
{
    protected $signature = 'loger:nudge-credit-cards {--team= : Restrict to a single team_id (debugging)}';

    protected $description = 'Push credit-card billing-cycle reminders (post-cut, pre-due, overdue).';

    public function __construct(private OneSignalService $oneSignal)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today();
        $teamId = $this->option('team');

        $cycles = BillingCycle::query()
            ->whereNotIn('status', [BillingCycle::STATUS_PAID, BillingCycle::STATUS_CANCELLED])
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->with('account:id,name')
            ->get();

        $sent = 0;
        $skipped = 0;
        $teamOwners = $this->teamOwnerMap($cycles->pluck('team_id')->unique()->all());

        foreach ($cycles as $cycle) {
            $ownerId = $teamOwners[$cycle->team_id] ?? null;
            if (! $ownerId) {
                $skipped++;

                continue;
            }

            $fired = $this->maybePostCut($cycle, $ownerId, $today)
                || $this->maybePreDue($cycle, $ownerId, $today)
                || $this->maybeOverdue($cycle, $ownerId, $today);

            $fired ? $sent++ : $skipped++;
        }

        $this->info("Credit-card nudges: sent={$sent} skipped={$skipped}");

        return self::SUCCESS;
    }

    private function maybePostCut(BillingCycle $cycle, int $ownerId, Carbon $today): bool
    {
        if ($cycle->nudged_post_cut_at) {
            return false;
        }
        if (! $cycle->end_at) {
            return false;
        }

        $cutDay = Carbon::parse($cycle->end_at)->startOfDay();
        if ($today->lt($cutDay->copy()->addDay())) {
            return false;
        }

        $name = $this->cardName($cycle);
        $this->oneSignal->sendToUser(
            userId: $ownerId,
            heading: "{$name} statement closed",
            message: 'Confirm the new balance on the report.',
            url: ['web' => url("/finance/accounts/{$cycle->account_id}")],
        );

        $cycle->update(['nudged_post_cut_at' => now()]);

        return true;
    }

    private function maybePreDue(BillingCycle $cycle, int $ownerId, Carbon $today): bool
    {
        if ($cycle->nudged_pre_due_at) {
            return false;
        }
        if (! $cycle->due_at) {
            return false;
        }

        $dueDay = Carbon::parse($cycle->due_at)->startOfDay();
        $window = $dueDay->copy()->subDays(3);

        // Fire only inside the [due-3 .. due) window so we never push a stale
        // "due in 3 days" line after the due date has already passed.
        if ($today->lt($window) || $today->gte($dueDay)) {
            return false;
        }

        $name = $this->cardName($cycle);
        $daysLeft = $today->diffInDays($dueDay);
        $message = $daysLeft === 1
            ? 'Due tomorrow.'
            : "Due in {$daysLeft} days.";

        $this->oneSignal->sendToUser(
            userId: $ownerId,
            heading: "{$name} payment due soon",
            message: $message,
            url: ['web' => url("/finance/accounts/{$cycle->account_id}")],
        );

        $cycle->update(['nudged_pre_due_at' => now()]);

        return true;
    }

    private function maybeOverdue(BillingCycle $cycle, int $ownerId, Carbon $today): bool
    {
        if ($cycle->nudged_overdue_at) {
            return false;
        }
        if (! $cycle->due_at) {
            return false;
        }
        // PARTIALLY_PAID still counts as "I logged something" — don't nag.
        if (in_array($cycle->status, [BillingCycle::STATUS_PAID, BillingCycle::STATUS_PARTIALLY_PAID, BillingCycle::STATUS_CANCELLED], true)) {
            return false;
        }

        $threshold = Carbon::parse($cycle->due_at)->startOfDay()->addDays(10);
        if ($today->lt($threshold)) {
            return false;
        }

        $name = $this->cardName($cycle);
        $this->oneSignal->sendToUser(
            userId: $ownerId,
            heading: "{$name} overdue",
            message: "If you've already paid, log it now to clear the alert.",
            url: ['web' => url("/finance/accounts/{$cycle->account_id}")],
        );

        $cycle->update(['nudged_overdue_at' => now()]);

        return true;
    }

    /**
     * @param  array<int, int>  $teamIds
     * @return array<int, int>
     */
    private function teamOwnerMap(array $teamIds): array
    {
        if (empty($teamIds)) {
            return [];
        }

        return Team::query()
            ->whereIn('id', $teamIds)
            ->pluck('user_id', 'id')
            ->all();
    }

    private function cardName(BillingCycle $cycle): string
    {
        return $cycle->account?->name ?: 'Credit card';
    }
}
