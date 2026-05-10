<?php

namespace App\Http\Controllers\Api;

use App\Domains\Transaction\Services\NextPaymentsService;
use App\Models\Account;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * JSON payload for the right-side Credit Card widget. Surfaces the two
 * decision-shaped slices that need cycle-aware backend logic:
 *
 *   - payInFull: cards with an unpaid statement and a near/passed due date.
 *     Reuses NextPaymentsService::getUpcomingCreditCardPayments which already
 *     compares the relevant statement cut against actual cash payments —
 *     not just balance > 0 — so cards already paid this cycle won't show.
 *
 *   - inactive: cards with zero balance and no transaction activity in 6+
 *     months. Cancellation candidates.
 *
 * Total position and closing-soon are derived client-side from the accounts
 * prop the AppLayout already loads — no need to round-trip them.
 */
class CreditCardSummaryController
{
    public function __invoke(Request $request, NextPaymentsService $nextPayments): JsonResponse
    {
        $teamId = $request->user()->current_team_id;
        $settings = Setting::getByTeam($teamId);
        $timeZone = $settings['team_timezone'] ?? config('app.timezone');
        $today = Carbon::now($timeZone);

        return response()->json([
            'payInFull' => $this->payInFull($teamId, $today, $nextPayments),
            'inactive' => $this->inactiveCards($teamId, $today),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function payInFull(int $teamId, Carbon $today, NextPaymentsService $nextPayments): array
    {
        return $nextPayments->getNextPayments($teamId, $today->format('Y-m-d'))
            ->filter(fn ($payment) => ($payment['type'] ?? null) === 'credit_card_payment')
            ->map(function ($payment) use ($today) {
                $dueAt = Carbon::parse($payment['due_date']);
                $isOverdue = ($payment['status'] ?? null) === 'overdue';

                return [
                    'account_id' => $payment['account_id'],
                    'account_name' => $payment['account_name'],
                    'total' => (float) ($payment['total'] ?? 0),
                    'due_at' => $dueAt->format('Y-m-d'),
                    // Negative when overdue, positive when in the future.
                    'days_until' => (int) $today->copy()->startOfDay()->diffInDays($dueAt->copy()->startOfDay(), false),
                    'is_overdue' => $isOverdue,
                ];
            })
            ->sortBy('days_until')
            ->values()
            ->all();
    }

    /**
     * Cards safe to consider closing: zero balance + no transaction activity
     * in 6+ months + not already closed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function inactiveCards(int $teamId, Carbon $today): array
    {
        $cutoff = $today->copy()->subMonths(6)->format('Y-m-d');

        $cards = Account::query()
            ->where('team_id', $teamId)
            ->whereNull('closed_at')
            ->whereNotNull('credit_closing_day')
            ->get();

        return $cards
            ->map(function (Account $card) use ($teamId, $cutoff) {
                $hasRecentActivity = DB::table('transaction_lines')
                    ->where('account_id', $card->id)
                    ->where('team_id', $teamId)
                    ->where('date', '>=', $cutoff)
                    ->exists();

                if ($hasRecentActivity || abs((float) $card->balance) > 0.01) {
                    return null;
                }

                $lastUsed = DB::table('transaction_lines')
                    ->where('account_id', $card->id)
                    ->where('team_id', $teamId)
                    ->max('date');

                return [
                    'account_id' => $card->id,
                    'account_name' => $card->name,
                    'last_used_at' => $lastUsed,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
