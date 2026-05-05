<?php

namespace App\Domains\Today\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Transaction\Models\BillingCycle;
use App\Domains\Transaction\Services\TransactionService;
use App\Notifications\WatchlistThresholdAlert;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;

/**
 * TODAY-1 v0.1 — aggregates the four widgets on /today using only primitives that
 * already exist in v2.0. Widgets enrich automatically as feeders mature
 * (HM-1 utilities, FM-1 reminders, FD-1 favorites). See tasks.md TODAY-1.
 */
class TodayService
{
    public function __construct() {}

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(int $teamId, int $userId): array
    {
        $today = Carbon::now();

        return [
            'money' => $this->money($teamId, $today),
            'attention' => $this->attention($userId, $today),
            'today' => $this->today($teamId, $today),
            'upcoming' => $this->upcoming($teamId, $today),
        ];
    }

    /**
     * MONEY: today's spend + this month's assigned/spent/remaining.
     * Uses BudgetMonth aggregates and TransactionService for the daily total.
     *
     * @return array{today_spent: float, month_assigned: float, month_spent: float, month_remaining: float, currency_code: ?string}
     */
    private function money(int $teamId, Carbon $today): array
    {
        $todayRow = TransactionService::getExpensesTotal(
            $teamId,
            $today->copy()->startOfDay()->format('Y-m-d'),
            $today->copy()->endOfDay()->format('Y-m-d')
        );
        $todaySpent = (float) abs($todayRow?->total_amount ?? 0);

        $monthAggregates = BudgetMonth::getMonthAssignmentTotal($teamId, $today->format('Y-m-d'));
        $current = collect($monthAggregates)->last() ?? [];

        $monthAssigned = (float) ($current['total'] ?? 0);
        $monthSpent = (float) ($current['spending'] ?? 0);
        $monthRemaining = $monthAssigned - $monthSpent;

        return [
            'today_spent' => $todaySpent,
            'month_assigned' => $monthAssigned,
            'month_spent' => $monthSpent,
            'month_remaining' => $monthRemaining,
            'currency_code' => null,
        ];
    }

    /**
     * ATTENTION: unread watchlist threshold alerts triggered this month.
     * The WL-1 engine writes these to `notifications`; we surface them here too
     * so the user doesn't have to open the bell to see them.
     *
     * @return array<int, array<string, mixed>>
     */
    private function attention(int $userId, Carbon $today): array
    {
        return DatabaseNotification::query()
            ->where('notifiable_id', $userId)
            ->where('type', WatchlistThresholdAlert::class)
            ->whereNull('read_at')
            ->where('data->month', $today->format('Y-m'))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? '',
                'cta' => $n->data['cta'] ?? null,
                'link' => $n->data['link'] ?? null,
            ])
            ->all();
    }

    /**
     * TODAY: items with a Planner record dated today that aren't completed.
     * Generic filter — covers planned transactions and anything else hooked into
     * the Planner morphTo (FM-1 will land here when relationship reminders ship).
     *
     * @return array<int, array<string, mixed>>
     */
    private function today(int $teamId, Carbon $today): array
    {
        return Planner::query()
            ->where('team_id', $teamId)
            ->whereDate('date', $today->format('Y-m-d'))
            ->whereNull('completed_at')
            ->orderBy('date')
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'dateable_type' => $p->dateable_type,
                'date' => $p->date,
                'status' => $p->status,
            ])
            ->all();
    }

    /**
     * UPCOMING: bills + utilities due in the next 7 days.
     *
     * Two sources:
     *   - BillingCycle (credit cards) — `due_at` BETWEEN today and +7d, not paid
     *   - Occurrence with type=utility (HM-1) — recurrence due by `last_date + avg_days_passed` ≤ +7d
     *
     * Each item is tagged with `kind` so the UI can render appropriate icon/copy.
     *
     * @return array<int, array<string, mixed>>
     */
    private function upcoming(int $teamId, Carbon $today): array
    {
        $windowEnd = $today->copy()->addDays(7)->endOfDay();

        $cycles = BillingCycle::query()
            ->where('team_id', $teamId)
            ->whereBetween('due_at', [
                $today->copy()->startOfDay()->format('Y-m-d'),
                $windowEnd->format('Y-m-d'),
            ])
            ->where('status', '!=', BillingCycle::STATUS_PAID)
            ->with('account:id,name')
            ->orderBy('due_at')
            ->get()
            ->map(fn ($cycle) => [
                'kind' => 'billing_cycle',
                'id' => 'cycle-'.$cycle->id,
                'name' => $cycle->account?->name,
                'account_id' => $cycle->account_id,
                'total' => (float) $cycle->total,
                'due_at' => optional($cycle->due_at)->format('Y-m-d'),
                'days_until' => $today->copy()->startOfDay()->diffInDays($cycle->due_at, false),
            ]);

        $utilities = Occurrence::query()
            ->byTeam($teamId)
            ->ofType(Occurrence::TYPE_UTILITY)
            ->where('is_active', true)
            ->whereNotNull('last_date')
            ->where('avg_days_passed', '>', 0)
            ->get()
            ->filter(function ($occurrence) {
                $days = $occurrence->daysUntilNext();

                // Include overdue (≤0) AND within next 7 days. Overdue stays surfaced
                // until the user logs the next occurrence so it doesn't silently fall off.
                return $days !== null && $days <= 7;
            })
            ->map(function ($occurrence) {
                $nextDate = $occurrence->last_date->copy()->addDays((int) $occurrence->avg_days_passed);

                return [
                    'kind' => 'utility',
                    'id' => 'utility-'.$occurrence->id,
                    'name' => $occurrence->name,
                    'account_id' => null,
                    'total' => null,
                    'due_at' => $nextDate->format('Y-m-d'),
                    'days_until' => $occurrence->daysUntilNext(),
                ];
            });

        return $cycles
            ->concat($utilities)
            ->sortBy('days_until')
            ->take(10)
            ->values()
            ->all();
    }
}
