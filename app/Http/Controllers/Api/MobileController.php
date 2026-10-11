<?php

namespace App\Http\Controllers\Api;

use App\Domains\AppCore\Models\Category;
use App\Domains\AppCore\Models\Planner;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Today\Services\CalendarService;
use App\Domains\Today\Services\TodayService;
use App\Domains\Transaction\Models\Transaction;
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

        // Closed accounts stay out of the phone's lists and the account picker. Only
        // closed_at is reliable: `archived` defaults to 1 in the accounts table.
        $accounts = collect(Account::getByDetailTypes($teamId)->loadMissing('detailType'))
            ->reject(fn ($a) => $a->closed_at !== null)
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'current_balance' => (float) $a->balance,
                'balance_type' => $a->balance_type,
                'currency_code' => $a->currency_code,
                'account_detail_type_id' => $a->account_detail_type_id,
                // bank | cash | cash_on_hand | savings | credit_card | property | loan — lets the app group accounts.
                'detail_type' => $a->detailType?->name,
                'credit_closing_day' => $a->credit_closing_day,
                'type' => $a->type,
            ])
            ->values();

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

    /**
     * Budget snapshot for a given month: RTA, category groups with children,
     * each carrying budgeted / activity / available for the requested month.
     */
    public function budget(Request $request): JsonResponse
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        $teamId = $request->user()->current_team_id;
        $month = ($request->query('month') ?? now()->format('Y-m')).'-01';
        $service = new BudgetCategoryService;

        $readyToAssign = 0.0;
        $readyCategory = Category::where('team_id', $teamId)
            ->where('display_id', 'ready_to_assign')
            ->first();

        if ($readyCategory) {
            $info = $service->getBudgetInfo($readyCategory, $month);
            $readyToAssign = (float) ($info['available'] ?? 0);
        }

        $groups = Category::where([
            'team_id' => $teamId,
            'resource_type' => 'transactions',
        ])
            ->whereNull('parent_id')
            ->whereNot('name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->whereNot('name', BudgetReservedNames::INFLOW->value)
            ->whereNot('name', BudgetReservedNames::CREDIT_CARD_PAYMENTS->value)
            ->orderBy('index')
            ->with(['subCategories', 'subCategories.budget', 'subCategories.account'])
            ->get()
            ->map(function (Category $group) use ($service, $month) {
                $children = $group->subCategories->map(function (Category $cat) use ($service, $month) {
                    try {
                        $info = $service->getBudgetInfo($cat, $month);
                    } catch (\Throwable) {
                        $info = [];
                    }

                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'budgeted' => (float) ($info['budgeted'] ?? 0),
                        'activity' => (float) ($info['activity'] ?? 0),
                        'available' => (float) ($info['available'] ?? 0),
                        'has_target' => $cat->budget !== null,
                    ];
                });

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'budgeted' => $children->sum('budgeted'),
                    'activity' => $children->sum('activity'),
                    'available' => $children->sum('available'),
                    'children' => $children->values(),
                ];
            })
            ->values();

        return response()->json([
            'month' => substr($month, 0, 7),
            'ready_to_assign' => round($readyToAssign, 2),
            'groups' => $groups,
        ]);
    }

    /**
     * Calendar events for a given month — planner items, billing cycles,
     * and recurring occurrences merged into one timeline.
     */
    public function calendar(Request $request, CalendarService $calendarService): JsonResponse
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        $teamId = $request->user()->current_team_id;
        $monthStr = $request->query('month') ?? now()->format('Y-m');
        $start = Carbon::parse($monthStr.'-01')->startOfMonth()->format('Y-m-d');
        $end = Carbon::parse($monthStr.'-01')->endOfMonth()->format('Y-m-d');

        return response()->json([
            'month' => $monthStr,
            'events' => $calendarService->getEvents($teamId, $start, $end),
        ]);
    }

    /**
     * Create a planner item (task / event) from the mobile FAB.
     */
    public function storeEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        $planner = Planner::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => $validated['name'],
            'date' => $validated['date'],
            'end_date' => $validated['end_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'total' => $validated['total'] ?? null,
            'source' => $validated['source'] ?? null,
            'status' => Planner::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $planner->id,
                'name' => $planner->name,
                'date' => $planner->date->format('Y-m-d'),
                'status' => $planner->status,
            ],
        ], 201);
    }

    /**
     * Mark a planner item as completed from the mobile app.
     */
    public function completeEvent(Request $request, Planner $planner): JsonResponse
    {
        abort_if($planner->team_id !== $request->user()->current_team_id, 403);

        $planner->markAsCompleted(null, $request->input('notes'));

        return response()->json(['success' => true]);
    }

    /**
     * Create an account-to-account transfer. Builds a transaction with
     * a counter account so the journal engine debits/credits both sides.
     */
    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_account_id' => ['required', 'exists:accounts,id'],
            'to_account_id' => ['required', 'exists:accounts,id', 'different:from_account_id'],
            'total' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
        ]);

        $user = $request->user();
        $teamId = $user->current_team_id;

        $fromAccount = Account::where('team_id', $teamId)->findOrFail($validated['from_account_id']);
        $toAccount = Account::where('team_id', $teamId)->findOrFail($validated['to_account_id']);

        $description = $validated['description']
            ?? "Transfer: {$fromAccount->name} → {$toAccount->name}";

        $transaction = Transaction::create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'account_id' => $fromAccount->id,
            'counter_account_id' => $toAccount->id,
            'total' => $validated['total'],
            'description' => $description,
            'date' => $validated['date'],
            'direction' => 'WITHDRAW',
            'currency_code' => $fromAccount->currency_code,
            'status' => 'verified',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $transaction->id,
                'description' => $transaction->description,
                'total' => (float) $transaction->total,
                'date' => $transaction->date,
            ],
        ], 201);
    }
}
