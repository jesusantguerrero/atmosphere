<?php

namespace App\Http\Controllers\Api;

use App\Domains\Today\Services\TodayService;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Domains\Transaction\Services\TransactionService;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Thin, token-authenticated surface for the mobile app. Everything here
 * reuses the same services the web renders from; it only reshapes the
 * payload so the phone can draw its home screen in a single call.
 */
class MobileController extends Controller
{
    /**
     * The same daily glance the web "Today" page renders: money pace,
     * things needing attention, due today, upcoming and this week's meals.
     */
    public function today(Request $request, TodayService $today): JsonResponse
    {
        $user = $request->user();

        return response()->json($today->buildPayload($user->current_team_id, $user->id));
    }

    /**
     * One-call home payload: account balances, the current net-worth
     * position, upcoming payments, and the cards that still need paying
     * this cycle.
     */
    public function overview(Request $request, NextPaymentsService $nextPayments): JsonResponse
    {
        $teamId = $request->user()->current_team_id;
        $today = Carbon::now();
        $monthStart = $today->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $today->copy()->endOfMonth()->format('Y-m-d');

        $accounts = collect(Account::getByDetailTypes($teamId))->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'current_balance' => (float) $a->current_balance,
            'balance_type' => $a->balance_type,
            'currency_code' => $a->currency_code,
            'account_detail_type_id' => $a->account_detail_type_id,
            'credit_closing_day' => $a->credit_closing_day,
            'type' => $a->type,
        ])->values();

        // getNetWorth returns running totals, newest row first.
        $netWorthRows = TransactionService::getNetWorth($teamId, $monthStart, $monthEnd);
        $latest = $netWorthRows[0] ?? null;
        $assets = $latest ? (float) $latest->assets : 0.0;
        $debts = $latest ? (float) $latest->debts : 0.0;

        $payments = $nextPayments->getNextPayments($teamId, $today->format('Y-m-d'));

        return response()->json([
            'accounts' => $accounts,
            'netWorth' => [
                'assets' => $assets,
                'debts' => $debts,
                'net' => $assets + $debts,
            ],
            'nextPayments' => $payments->take(8)->values(),
            'cardsToPay' => $payments
                ->filter(fn ($p) => ($p['type'] ?? null) === 'credit_card_payment')
                ->values(),
        ]);
    }
}
