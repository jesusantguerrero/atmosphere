<?php

namespace App\Http\Controllers\Api;

use App\Domains\AppCore\Models\Category;
use App\Domains\AppCore\Models\Planner;
use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Data\BudgetMovementData;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Budget\Services\BudgetMovementService;
use App\Domains\Budget\Services\BudgetTargetService;
use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Integration\Actions\TransactionCreateEntry;
use App\Domains\Integration\Actions\UniversalBankParser;
use App\Domains\Integration\Services\UniversalBankAutomationService;
use App\Domains\Journal\Actions\AccountCreate;
use App\Domains\Journal\Actions\AccountUpdate;
use App\Domains\Journal\Actions\TransactionDelete;
use App\Domains\Journal\Actions\TransactionUpdate;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealPlan;
use App\Domains\Meal\Models\MealType;
use App\Domains\Meal\Services\MealService;
use App\Domains\Today\Services\CalendarService;
use App\Domains\Today\Services\TodayService;
use App\Domains\Transaction\Data\ReconciliationParamsData;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\CreditCardReportService;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Domains\Transaction\Services\ReconciliationService;
use App\Domains\Transaction\Services\TransactionService;
use App\Domains\Transaction\Services\TransactionSplits;
use App\Http\Controllers\Controller;
use App\Http\Requests\DraftTransactionsRangeRequest;
use App\Http\Requests\UpdateReconciliationRequest;
use App\Http\Requests\UpdateShoppingItemRequest;
use App\Http\Requests\UpdateShoppingVisitRequest;
use App\Http\Resources\PlannedMealResource;
use App\Models\Account;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use App\Services\ShoppingListReviewService;
use App\Services\ShoppingVisitLinker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Payee;
use Laravel\Jetstream\Contracts\DeletesUsers;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanStage;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;

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
        $payload = $today->buildPayload($user->current_team_id, $user->id);
        $payload['pending_review_count'] = TransactionService::getDraftCount($user->current_team_id);

        return response()->json($payload);
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
                'credit_limit' => $a->credit_limit ? (float) $a->credit_limit : null,
                'type' => $a->type,
                'reconciliation_last' => $a->reconciliationLast ? [
                    'status' => $a->reconciliationLast->status,
                    'amount' => (float) $a->reconciliationLast->amount,
                ] : null,
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
        $baseCurrency = strtoupper(trim((string) (Setting::getByTeam($teamId)['team_primary_currency_code'] ?? ''))) ?: 'DOP';

        // Inflow category holds activity (income) and left_from_last_month.
        // RTA = inflow.activity + inflow.left_from_last_month - totalAssigned
        // This matches the web frontend's useBudget.ts calculation.
        $inflowData = ['activity' => 0, 'left_from_last_month' => 0];
        $inflowGroup = Category::where([
            'team_id' => $teamId,
            'resource_type' => 'transactions',
            'name' => BudgetReservedNames::INFLOW->value,
        ])->whereNull('parent_id')->with('subCategories')->first();

        if ($inflowGroup) {
            $rtaChild = $inflowGroup->subCategories->first();
            if ($rtaChild) {
                $inflowData = $service->getBudgetData($rtaChild, $month);
            }
        }

        // All outflow groups (everything except Ready to Assign and Inflow).
        // This INCLUDES Credit Card Payments — matching the web's useBudget.ts
        // where outflow = data.filter(g => g.name != 'Inflow').
        $allOutflow = Category::where([
            'team_id' => $teamId,
            'resource_type' => 'transactions',
        ])
            ->whereNull('parent_id')
            ->whereNot('name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->whereNot('name', BudgetReservedNames::INFLOW->value)
            ->orderBy('index')
            ->with(['subCategories', 'subCategories.budget', 'subCategories.account'])
            ->get();

        // Pre-load all accounts for CC payment enrichment.
        $accountsById = Account::getByDetailTypes($teamId)->keyBy('id');

        $availableByCurrency = [];
        $mapChildren = function (Category $group, bool $isCreditCardGroup = false) use ($service, $month, $baseCurrency, &$availableByCurrency, $accountsById) {
            return $group->subCategories->map(function (Category $cat) use ($service, $month, $baseCurrency, &$availableByCurrency, $accountsById, $isCreditCardGroup) {
                try {
                    $data = $service->getBudgetData($cat, $month);
                } catch (\Throwable) {
                    $data = [];
                }

                // Route available to the account's currency (for multi-currency reconciliation).
                $catCurrency = $cat->account?->currency_code ?? $baseCurrency;
                $availableByCurrency[$catCurrency] = ($availableByCurrency[$catCurrency] ?? 0) + (float) ($data['available'] ?? 0);

                $target = null;
                if ($cat->budget) {
                    $bt = $cat->budget;
                    $available = (float) ($data['available'] ?? 0);
                    $targetAmount = (float) $bt->amount;

                    // For spending-type targets (spending, debt, loan, savings_monthly),
                    // "needed" should be based on what was funded (budgeted + left_from_last_month),
                    // not on available (which drops as money is spent). This prevents
                    // fully-funded-and-spent targets from showing as "still needs X".
                    $spendingTypes = ['spending', 'debt_monthly_payment', 'loan', 'savings_monthly'];
                    if (in_array($bt->target_type, $spendingTypes, true)) {
                        $funded = (float) ($data['budgeted'] ?? 0) + (float) ($data['left_from_last_month'] ?? 0);
                        $needed = max(0, $targetAmount - $funded);
                    } else {
                        $needed = max(0, $targetAmount - $available);
                    }

                    $dueDate = null;
                    $freq = strtolower($bt->frequency ?? '');
                    if ($freq === 'monthly' && $bt->frequency_month_date) {
                        $monthCarbon = \Carbon\Carbon::parse($month);
                        $day = min((int) $bt->frequency_month_date, $monthCarbon->daysInMonth);
                        $dueDate = $monthCarbon->copy()->day($day)->format('Y-m-d');
                    } elseif ($bt->frequency_month_date) {
                        // Fallback: frequency_month_date present without explicit 'monthly'
                        $monthCarbon = \Carbon\Carbon::parse($month);
                        $day = min((int) $bt->frequency_month_date, $monthCarbon->daysInMonth);
                        $dueDate = $monthCarbon->copy()->day($day)->format('Y-m-d');
                    } elseif ($bt->frequency_date) {
                        $dueDate = \Carbon\Carbon::parse($bt->frequency_date)->format('Y-m-d');
                    }

                    $target = [
                        'id' => $bt->id,
                        'amount' => $targetAmount,
                        'needed' => round($needed, 2),
                        'due_date' => $dueDate,
                        'target_type' => $bt->target_type,
                        'frequency' => strtoupper($bt->frequency ?? 'MONTHLY'),
                        'frequency_month_date' => $bt->frequency_month_date ? (int) $bt->frequency_month_date : null,
                        'frequency_week_day' => $bt->frequency_week_day,
                        'frequency_date' => $bt->frequency_date,
                        'notify' => (bool) $bt->notify,
                        'snoozed_month' => $bt->snoozed_month,
                        'completed_at' => $bt->completed_at,
                        'loan' => $bt->target_type === 'loan' ? [
                            'principal' => (float) ($bt->principal ?? 0),
                            'interest_rate' => (float) ($bt->interest_rate ?? 0),
                            'term_months' => (int) ($bt->term_months ?? 0),
                            'loan_start_date' => $bt->loan_start_date ? Carbon::parse($bt->loan_start_date)->format('Y-m-d') : null,
                        ] : null,
                    ];
                }

                // CC payment enrichment: if category is linked to a credit card account.
                $payment = null;
                if ($isCreditCardGroup && $cat->account_id) {
                    $ccAccount = $accountsById->get($cat->account_id);
                    if ($ccAccount) {
                        $owed = max(0, -1 * (float) $ccAccount->balance);
                        $setAside = (float) ($data['available'] ?? 0);
                        $gap = max(0, round($owed - $setAside, 2));
                        $coveragePct = $owed > 0 ? min(100, (int) round(($setAside / $owed) * 100)) : 100;
                        $payment = [
                            'account_id' => $ccAccount->id,
                            'owed' => round($owed, 2),
                            'set_aside' => round($setAside, 2),
                            'gap' => $gap,
                            'coverage_pct' => $coveragePct,
                            'ready_to_pay' => $gap <= 0,
                        ];
                    }
                }

                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'note' => $cat->description,
                    'budgeted' => (float) ($data['budgeted'] ?? 0),
                    'activity' => (float) ($data['activity'] ?? 0),
                    'available' => (float) ($data['available'] ?? 0),
                    'left_from_last_month' => (float) ($data['left_from_last_month'] ?? 0),
                    'funded_spending' => (float) ($data['funded_spending'] ?? 0),
                    'payments' => (float) ($data['payments'] ?? 0),
                    'has_target' => $cat->budget !== null,
                    'target' => $target,
                    'payment' => $payment,
                ];
            });
        };

        $groups = $allOutflow
            ->map(function (Category $group) use ($mapChildren) {
                $isCreditCards = $group->name === BudgetReservedNames::CREDIT_CARD_PAYMENTS->value;
                $children = $mapChildren($group, $isCreditCards);

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'kind' => $isCreditCards ? 'credit_card_payments' : 'expense',
                    'budgeted' => $children->sum('budgeted'),
                    'activity' => $children->sum('activity'),
                    'available' => $children->sum('available'),
                    'children' => $children->values(),
                ];
            })
            ->values();

        // RTA uses ALL outflow budgeted (including Credit Card Payments) — same as web.
        $totalAssigned = $groups->sum('budgeted');
        $readyToAssign = (float) ($inflowData['activity'] ?? 0)
            + (float) ($inflowData['left_from_last_month'] ?? 0)
            - $totalAssigned;

        // ── Reconciliation summary (mirrors BudgetReconcileBanner.vue) ──
        // Per-currency rows: route each category's available to its account's
        // currency, add RTA to base currency, compare vs on-budget cash.
        $assignedByCurrency = $availableByCurrency;
        $assignedByCurrency[$baseCurrency] = ($assignedByCurrency[$baseCurrency] ?? 0) + $readyToAssign;

        // Cash in on-budget accounts by currency (exclude credit cards and resource accounts).
        // `balance` is a computed accessor (SUM of transaction_lines), not a DB column —
        // load via getByDetailTypes() which batch-precomputes balances, then filter in PHP.
        $cashByCurrency = Account::getByDetailTypes($teamId)
            ->reject(fn (Account $a) => $a->closed_at || $a->credit_closing_day || $a->resource_type_id)
            ->groupBy('currency_code')
            ->map(fn ($group) => (float) $group->sum('balance'))
            ->all();

        $allCurrencies = array_unique(array_merge(
            array_keys($availableByCurrency),
            array_keys($cashByCurrency),
        ));

        $rows = [];
        foreach ($allCurrencies as $cur) {
            $avail = round($availableByCurrency[$cur] ?? 0, 2);
            $toAssign = $cur === $baseCurrency ? round($readyToAssign, 2) : 0.0;
            $budgetTotal = round($avail + $toAssign, 2);
            $cash = round($cashByCurrency[$cur] ?? 0, 2);

            if (abs($budgetTotal) < 0.01 && abs($cash) < 0.01) {
                continue;
            }

            $rows[] = [
                'currency_code' => $cur,
                'available' => $avail,
                'to_assign' => $toAssign,
                'budget_total' => $budgetTotal,
                'cash_in_accounts' => $cash,
                'difference' => round($budgetTotal - $cash, 2),
            ];
        }

        // Uncategorized expenses (all dates, same query as BudgetCategoryController).
        $uncategorized = Transaction::query()->uncategorizedExpenses()
            ->where('transactions.team_id', $teamId)
            ->where('transactions.user_id', $request->user()->id)
            ->selectRaw('currency_code, SUM(total) as total, COUNT(*) as count')
            ->groupBy('currency_code')
            ->get()
            ->map(fn ($row) => [
                'currency_code' => $row->currency_code,
                'total' => round((float) $row->total, 2),
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        // Ready to Assign info for allocate/cover actions.
        $rtaStatus = $readyToAssign > 0.01 ? 'available' : ($readyToAssign < -0.01 ? 'overspent' : 'empty');

        $scheduledTotal = 0.0;
        try {
            $monthEnd = Carbon::parse($month)->endOfMonth()->format('Y-m-d');
            $scheduledTotal = (float) app(NextPaymentsService::class)
                ->getNextPayments($teamId, $month)
                ->filter(fn ($p) => isset($p['due_date']) && $p['due_date'] >= $month && $p['due_date'] <= $monthEnd)
                ->sum('total');
        } catch (\Throwable) {
        }

        $defaults = BudgetCategoryService::getDefaultsByRole($teamId);

        return response()->json([
            'month' => substr($month, 0, 7),
            'ready_to_assign' => round($readyToAssign, 2),
            'ready_to_assign_info' => [
                'status' => $rtaStatus,
                'amount' => round($readyToAssign, 2),
                'scheduled_total' => round($scheduledTotal, 2),
                'after_scheduled' => round($readyToAssign - $scheduledTotal, 2),
                'default_savings_category_id' => $defaults['savings'] ?? null,
                'default_spending_category_id' => $defaults['spending'] ?? null,
            ],
            'groups' => $groups,
            'summary' => [
                'rows' => $rows,
                'uncategorized' => $uncategorized,
            ],
        ]);
    }

    /**
     * Assign money to a category for a month, exactly like the web: `budgeted` is the
     * category's new total for the month and the amount comes out of Ready to Assign.
     */
    public function assignBudget(Request $request, BudgetMovementService $service): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer'],
            'month' => ['required', 'date_format:Y-m'],
            'budgeted' => ['required', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $category = Category::where('team_id', $user->current_team_id)->findOrFail($validated['category_id']);

        $service->registerAssignment(new BudgetAssignData(
            $user->current_team_id,
            $user->id,
            $validated['month'].'-01',
            $category->id,
            (float) $validated['budgeted'],
        ));

        return response()->json(['success' => true]);
    }

    /**
     * Move money from one category to another within a month. The service caps the
     * amount at what the source category has available.
     */
    public function moveBudget(Request $request, BudgetMovementService $service): JsonResponse
    {
        $validated = $request->validate([
            'source_category_id' => ['required', 'integer'],
            'destination_category_id' => ['required', 'integer', 'different:source_category_id'],
            'month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();
        $teamId = $user->current_team_id;
        $source = Category::where('team_id', $teamId)->findOrFail($validated['source_category_id']);
        $destination = Category::where('team_id', $teamId)->findOrFail($validated['destination_category_id']);

        $service->registerMovement(new BudgetMovementData(
            null,
            $teamId,
            $user->id,
            $source->id,
            $destination->id,
            'movement',
            $validated['month'].'-01',
            (float) $validated['amount'],
        ));

        return response()->json(['success' => true]);
    }

    /**
     * Copy budget assignments from the previous month into the target month.
     * Returns 409 if the month already has assignments and overwrite is not set.
     */
    public function copyFromPrevious(Request $request, BudgetMovementService $service, string $month): JsonResponse
    {
        $request->validate([
            'overwrite' => ['sometimes', 'boolean'],
        ]);

        $teamId = $request->user()->current_team_id;
        $targetMonth = $month.'-01';
        $overwrite = $request->boolean('overwrite');

        // Pre-check: if month already has assignments and overwrite is off, return 409.
        if (! $overwrite) {
            $reserved = array_map(fn (BudgetReservedNames $n) => $n->value, BudgetReservedNames::cases());

            $existingCount = BudgetMonth::query()
                ->where('budget_months.team_id', $teamId)
                ->where('budget_months.month', $targetMonth)
                ->where('budget_months.budgeted', '>', 0)
                ->join('categories', 'categories.id', 'budget_months.category_id')
                ->whereNotIn('categories.name', $reserved)
                ->count();

            if ($existingCount > 0) {
                return response()->json([
                    'has_assignments' => true,
                    'count' => $existingCount,
                ], 409);
            }
        }

        $result = $service->copyFromPrevious(
            $teamId,
            $request->user()->id,
            $targetMonth,
            $overwrite,
        );

        return response()->json([
            'success' => true,
            'copied' => $result['copied'],
            'skipped' => $result['skipped'],
        ]);
    }

    // ── Ready to Assign: allocate & cover ──────────────────────────

    /**
     * Allocate leftover (split Ready to Assign into one or more categories).
     * Mirrors the web's BudgetBalanceAssign "All to Savings / Spending / 50-50".
     */
    public function allocateLeftover(Request $request, BudgetMovementService $service): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'splits' => ['required', 'array', 'min:1'],
            'splits.*.destination_category_id' => ['required', 'integer'],
            'splits.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();
        $teamId = $user->current_team_id;

        // Validate all destination categories belong to the team.
        $catIds = collect($validated['splits'])->pluck('destination_category_id')->unique();
        $existing = Category::where('team_id', $teamId)->whereIn('id', $catIds)->pluck('id');
        if ($existing->count() !== $catIds->count()) {
            return response()->json(['message' => 'One or more categories not found.'], 422);
        }

        // Source = Inflow's first subcategory (Ready to Assign).
        $inflowGroup = Category::where([
            'team_id' => $teamId,
            'resource_type' => 'transactions',
            'name' => BudgetReservedNames::INFLOW->value,
        ])->whereNull('parent_id')->with('subCategories')->first();

        $sourceCategoryId = $inflowGroup?->subCategories->first()?->id;

        $service->registerSplit(
            $teamId,
            $user->id,
            $sourceCategoryId,
            $validated['month'].'-01',
            $validated['splits'],
        );

        return response()->json(['success' => true]);
    }

    /**
     * Cover overspend ("Fix this"): move money from a source category to
     * Ready to Assign to bring the RTA balance back to zero.
     */
    public function coverOverspend(Request $request, BudgetMovementService $service): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'source_category_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();
        $teamId = $user->current_team_id;

        Category::where('team_id', $teamId)->findOrFail($validated['source_category_id']);

        // Destination = Ready to Assign category.
        $rtaCategoryId = Category::findOrCreateByName(
            ['team_id' => $teamId, 'user_id' => $user->id],
            BudgetReservedNames::READY_TO_ASSIGN->value,
        );

        $service->registerMovement(new BudgetMovementData(
            null,
            $teamId,
            $user->id,
            (int) $validated['source_category_id'],
            $rtaCategoryId,
            'movement',
            $validated['month'].'-01',
            (float) $validated['amount'],
        ));

        return response()->json(['success' => true]);
    }

    // ── Budget Groups CRUD ──────────────────────────────────────────

    public function createBudgetGroup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $teamId = $request->user()->current_team_id;

        $maxIndex = Category::where('team_id', $teamId)
            ->where('resource_type', 'transactions')
            ->whereNull('parent_id')
            ->max('index') ?? 0;

        $group = Category::create([
            'name' => $validated['name'],
            'team_id' => $teamId,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'index' => $maxIndex + 1,
            'depth' => 0,
        ]);

        return response()->json([
            'id' => $group->id,
            'name' => $group->name,
        ], 201);
    }

    public function updateBudgetGroup(Request $request, int $groupId): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $teamId = $request->user()->current_team_id;
        $group = Category::where('team_id', $teamId)
            ->whereNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($groupId);

        $reserved = array_map(fn (BudgetReservedNames $n) => $n->value, BudgetReservedNames::cases());
        if (in_array($group->name, $reserved, true)) {
            return response()->json(['message' => 'Cannot rename a reserved group.'], 422);
        }

        $group->update(['name' => $validated['name']]);

        return response()->json(['id' => $group->id, 'name' => $group->name]);
    }

    public function deleteBudgetGroup(Request $request, int $groupId): JsonResponse
    {
        $teamId = $request->user()->current_team_id;
        $group = Category::where('team_id', $teamId)
            ->whereNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($groupId);

        $reserved = array_map(fn (BudgetReservedNames $n) => $n->value, BudgetReservedNames::cases());
        if (in_array($group->name, $reserved, true)) {
            return response()->json(['message' => 'Cannot delete a reserved group.'], 422);
        }

        // Move orphan subcategories to the first non-reserved group.
        if ($group->subCategories()->count() > 0) {
            $fallback = Category::where('team_id', $teamId)
                ->whereNull('parent_id')
                ->where('resource_type', 'transactions')
                ->whereNotIn('name', $reserved)
                ->where('id', '!=', $group->id)
                ->orderBy('index')
                ->first();

            if ($fallback) {
                $group->subCategories()->update(['parent_id' => $fallback->id]);
            } else {
                return response()->json(['message' => 'No other group to move categories into.'], 422);
            }
        }

        $group->delete();

        return response()->json(['success' => true]);
    }

    // ── Budget Categories CRUD ──────────────────────────────────────

    public function createBudgetCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'group_id' => ['required', 'integer'],
        ]);

        $teamId = $request->user()->current_team_id;

        // Verify the group belongs to this team.
        $group = Category::where('team_id', $teamId)
            ->whereNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($validated['group_id']);

        $maxIndex = $group->subCategories()->max('index') ?? 0;

        $category = Category::create([
            'name' => $validated['name'],
            'team_id' => $teamId,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'index' => $maxIndex + 1,
            'depth' => 1,
        ]);

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'group_id' => $group->id,
        ], 201);
    }

    public function updateBudgetCategory(Request $request, int $categoryId): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'note' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'group_id' => ['sometimes', 'integer'],
        ]);

        $teamId = $request->user()->current_team_id;
        $category = Category::where('team_id', $teamId)
            ->whereNotNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        $updates = [];

        if (array_key_exists('name', $validated)) {
            $updates['name'] = $validated['name'];
        }

        if (array_key_exists('note', $validated)) {
            $updates['description'] = $validated['note'];
        }

        if (array_key_exists('group_id', $validated)) {
            $group = Category::where('team_id', $teamId)
                ->whereNull('parent_id')
                ->where('resource_type', 'transactions')
                ->findOrFail($validated['group_id']);
            $updates['parent_id'] = $group->id;
        }

        if (! empty($updates)) {
            $category->update($updates);
        }

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
            'note' => $category->description,
            'group_id' => $category->parent_id,
        ]);
    }

    public function deleteBudgetCategory(Request $request, int $categoryId): JsonResponse
    {
        $teamId = $request->user()->current_team_id;
        $category = Category::where('team_id', $teamId)
            ->whereNotNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        // Prevent deleting categories with transactions or budget movements (same as web).
        $hasTransactions = Transaction::where('category_id', $category->id)->exists();
        $hasMovements = BudgetMovement::where('destination_category_id', $category->id)
            ->orWhere('source_category_id', $category->id)
            ->exists();

        if ($hasTransactions || $hasMovements) {
            return response()->json([
                'message' => 'Cannot delete a category with transactions or budget movements.',
            ], 422);
        }

        $category->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Set or clear the default role (savings / spending) for a category.
     * Same as the web's `PATCH /budgets/{category}/default-role`.
     */
    public function setDefaultRole(Request $request, int $categoryId, BudgetCategoryService $service): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['nullable', 'string', 'in:savings,spending'],
        ]);

        $teamId = $request->user()->current_team_id;
        $category = Category::where('team_id', $teamId)
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        $service->setDefaultRole($category, $validated['role'] ?? null);

        return response()->json([
            'id' => $category->id,
            'role' => $validated['role'] ?? null,
        ]);
    }

    // ── Budget Targets CRUD ─────────────────────────────────────

    public function createBudgetTarget(Request $request, int $categoryId, BudgetTargetService $service): JsonResponse
    {
        $validated = $request->validate([
            'target_type' => ['required', 'string', 'in:spending,saving_balance,savings_monthly,debt_monthly_payment,loan'],
            'amount' => ['required_unless:target_type,loan', 'numeric', 'min:0'],
            'frequency' => ['sometimes', 'string', 'in:MONTHLY,WEEKLY,DATE'],
            'frequency_month_date' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'frequency_week_day' => ['sometimes', 'nullable', 'string'],
            'frequency_date' => ['sometimes', 'nullable', 'date'],
            'notify' => ['sometimes', 'boolean'],
            'principal' => ['sometimes', 'numeric', 'min:0'],
            'interest_rate' => ['sometimes', 'numeric', 'min:0', 'max:1000'],
            'term_months' => ['sometimes', 'integer', 'min:0', 'max:1200'],
            'loan_start_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $teamId = $request->user()->current_team_id;
        $category = Category::where('team_id', $teamId)
            ->whereNotNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        if ($category->budget) {
            return response()->json(['message' => 'Category already has a target. Use PUT to update.'], 422);
        }

        $target = $service->add($category, $request->user(), $validated);

        return response()->json($this->formatTarget($target), 201);
    }

    public function updateBudgetTarget(Request $request, int $categoryId, int $targetId, BudgetTargetService $service): JsonResponse
    {
        $validated = $request->validate([
            'target_type' => ['sometimes', 'string', 'in:spending,saving_balance,savings_monthly,debt_monthly_payment,loan'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'frequency' => ['sometimes', 'string', 'in:MONTHLY,WEEKLY,DATE'],
            'frequency_month_date' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'frequency_week_day' => ['sometimes', 'nullable', 'string'],
            'frequency_date' => ['sometimes', 'nullable', 'date'],
            'notify' => ['sometimes', 'boolean'],
            'principal' => ['sometimes', 'numeric', 'min:0'],
            'interest_rate' => ['sometimes', 'numeric', 'min:0', 'max:1000'],
            'term_months' => ['sometimes', 'integer', 'min:0', 'max:1200'],
            'loan_start_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $teamId = $request->user()->current_team_id;
        $category = Category::where('team_id', $teamId)
            ->whereNotNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        $target = $category->budget;
        abort_unless($target && $target->id === $targetId, 404);

        $service->update($category, $target, $request->user(), $validated);

        return response()->json($this->formatTarget($target->fresh()));
    }

    public function completeBudgetTarget(Request $request, int $categoryId, int $targetId, BudgetTargetService $service): JsonResponse
    {
        $request->validate([
            'completed_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $teamId = $request->user()->current_team_id;
        $category = Category::where('team_id', $teamId)
            ->whereNotNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        $target = $category->budget;
        abort_unless($target && $target->id === $targetId, 404);

        $service->complete($target, $category, $request->all());

        // If the service couldn't find a last transaction month, fall back to now.
        $target = $target->fresh();
        if (! $target->completed_at) {
            $target->update(['completed_at' => now()->format('Y-m-d')]);
            $target = $target->fresh();
        }

        return response()->json($this->formatTarget($target));
    }

    /**
     * Take a target down. It is archived, not deleted: the row and its history stay, but the category no longer
     * has a target (no "still needs", no alerts) and can be given a new one.
     */
    public function archiveBudgetTarget(Request $request, int $categoryId, int $targetId): JsonResponse
    {
        $category = Category::where('team_id', $request->user()->current_team_id)
            ->whereNotNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        $target = $category->budget;
        abort_unless($target && $target->id === $targetId, 404);

        $target->forceFill(['archived_at' => now()])->save();

        return response()->json(['id' => $target->id, 'archived_at' => $target->archived_at]);
    }

    /** Brings an archived target back, as long as the category has no live target taking its place. */
    public function restoreBudgetTarget(Request $request, int $categoryId, int $targetId): JsonResponse
    {
        $category = Category::where('team_id', $request->user()->current_team_id)
            ->whereNotNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        $target = BudgetTarget::archived()
            ->where(['team_id' => $category->team_id, 'category_id' => $category->id])
            ->findOrFail($targetId);

        abort_if($category->budget()->exists(), 422, 'This category already has a target. Archive it before restoring another one.');

        $target->forceFill(['archived_at' => null])->save();

        return response()->json([...$this->formatTarget($target), 'archived_at' => null]);
    }

    /** The category's archived targets, newest first: the history of what it used to aim for. */
    public function archivedBudgetTargets(Request $request, int $categoryId): JsonResponse
    {
        $category = Category::where('team_id', $request->user()->current_team_id)
            ->whereNotNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        return response()->json([
            'targets' => BudgetTarget::archived()
                ->where(['team_id' => $category->team_id, 'category_id' => $category->id])
                ->orderByDesc('archived_at')
                ->get()
                ->map(fn (BudgetTarget $target) => [...$this->formatTarget($target), 'archived_at' => $target->archived_at])
                ->values(),
        ]);
    }

    public function snoozeBudgetTarget(Request $request, int $categoryId): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $teamId = $request->user()->current_team_id;
        $category = Category::where('team_id', $teamId)
            ->whereNotNull('parent_id')
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        $target = $category->budget;
        abort_unless($target, 404, 'This category has no target to snooze.');

        $target->snoozed_month = $target->snoozed_month === $validated['month'] ? null : $validated['month'];
        $target->save();

        return response()->json($this->formatTarget($target));
    }

    /** @return array<string, mixed> */
    private function formatTarget(BudgetTarget $target): array
    {
        return [
            'id' => $target->id,
            'target_type' => $target->target_type,
            'amount' => (float) $target->amount,
            'frequency' => strtoupper($target->frequency ?? 'MONTHLY'),
            'frequency_month_date' => $target->frequency_month_date ? (int) $target->frequency_month_date : null,
            'frequency_week_day' => $target->frequency_week_day,
            'frequency_date' => $target->frequency_date,
            'notify' => (bool) $target->notify,
            'snoozed_month' => $target->snoozed_month,
            'completed_at' => $target->completed_at,
            'loan' => $target->target_type === 'loan' ? [
                'principal' => (float) ($target->principal ?? 0),
                'interest_rate' => (float) ($target->interest_rate ?? 0),
                'term_months' => (int) ($target->term_months ?? 0),
                'loan_start_date' => $target->loan_start_date ? Carbon::parse($target->loan_start_date)->format('Y-m-d') : null,
            ] : null,
        ];
    }

    // ── Category Transactions ─────────────────────────────────

    public function categoryTransactions(Request $request, int $categoryId): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $teamId = $request->user()->current_team_id;
        $category = Category::where('team_id', $teamId)
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        $monthStart = $validated['month'].'-01';
        $monthEnd = Carbon::parse($monthStart)->endOfMonth()->format('Y-m-d');
        $limit = $validated['limit'] ?? 50;

        $inThisCategory = fn ($lines) => $lines->where('category_id', $category->id);

        $transactions = Transaction::with(['account', 'payee', 'splits' => $inThisCategory])
            ->where('team_id', $teamId)
            ->where(fn (Builder $query) => $query
                ->where('category_id', $category->id)
                ->orWhereHas('splits', $inThisCategory))
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($limit);

        return response()->json([
            'data' => $transactions->map(fn (Transaction $t) => [
                'id' => $t->id,
                'description' => $t->description,
                'total' => (float) $t->total,
                'currency_code' => $t->currency_code,
                'date' => $t->date,
                'direction' => $t->direction,
                'status' => $t->status,
                'has_splits' => (bool) $t->has_splits,
                // For a split purchase only the line that falls in this category counts here, not the whole total.
                'split_amount' => $t->has_splits && $t->splits->isNotEmpty() ? (float) $t->splits->sum('amount') : null,
                'account' => $t->account ? ['id' => $t->account->id, 'name' => $t->account->name] : null,
                'payee' => $t->payee ? ['name' => $t->payee->name] : null,
            ]),
            'last_page' => $transactions->lastPage(),
        ]);
    }

    // ── Budget Movements (history + undo) ───────────────────────

    public function categoryMovements(Request $request, int $categoryId): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $teamId = $request->user()->current_team_id;
        Category::where('team_id', $teamId)
            ->where('resource_type', 'transactions')
            ->findOrFail($categoryId);

        $monthStart = $validated['month'].'-01';
        $monthEnd = Carbon::parse($monthStart)->endOfMonth()->format('Y-m-d');

        $movements = BudgetMovement::where('team_id', $teamId)
            ->where(function ($q) use ($categoryId) {
                $q->where('source_category_id', $categoryId)
                    ->orWhere('destination_category_id', $categoryId);
            })
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (BudgetMovement $m) => [
                'id' => $m->id,
                'type' => $m->source_category_id ? 'movement' : 'assignment',
                'amount' => (float) $m->amount,
                'date' => $m->date,
                'from' => $m->source_category_id ? [
                    'id' => $m->source_category_id,
                    'name' => $m->source_category_name,
                ] : null,
                'to' => [
                    'id' => $m->destination_category_id,
                    'name' => $m->destination_category_name,
                ],
                'created_at' => $m->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $movements]);
    }

    public function destroyMovement(Request $request, int $movementId, BudgetMovementService $service): JsonResponse
    {
        $movement = BudgetMovement::findOrFail($movementId);

        if (! $request->user()->allTeams()->pluck('id')->contains($movement->team_id)) {
            abort(403);
        }

        $service->revertMovement($movement);

        return response()->json(['success' => true]);
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
     * Update a planner event (name, date, notes, total).
     */
    public function updateEvent(Request $request, Planner $planner): JsonResponse
    {
        abort_if($planner->team_id !== $request->user()->current_team_id, 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ]);

        $planner->update($validated);

        return response()->json([
            'success' => true,
            'event' => [
                'id' => $planner->id,
                'planner_id' => $planner->id,
                'title' => $planner->name,
                'start' => $planner->date->format('Y-m-d'),
                'end' => $planner->end_date?->format('Y-m-d') ?? $planner->date->format('Y-m-d'),
                'status' => $planner->status,
                'total' => $planner->total ? (float) $planner->total : null,
                'notes' => $planner->notes,
                'completed_at' => $planner->completed_at?->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Delete a planner event.
     */
    public function deleteEvent(Request $request, Planner $planner): JsonResponse
    {
        abort_if($planner->team_id !== $request->user()->current_team_id, 403);

        $planner->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Cancel a planner event.
     */
    public function cancelEvent(Request $request, Planner $planner): JsonResponse
    {
        abort_if($planner->team_id !== $request->user()->current_team_id, 403);

        $planner->markAsCancelled($request->input('notes'));

        return response()->json(['success' => true]);
    }

    /**
     * Reopen a completed/cancelled event back to pending.
     */
    public function reopenEvent(Request $request, Planner $planner): JsonResponse
    {
        abort_if($planner->team_id !== $request->user()->current_team_id, 403);

        $planner->update([
            'status' => Planner::STATUS_PENDING,
            'completed_at' => null,
            'completed_by' => null,
            'completion_notes' => null,
        ]);

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

        // The row alone moves nothing: the journal lines (one on each account) are what balances are summed from.
        $transaction = DB::transaction(function () use ($teamId, $user, $fromAccount, $toAccount, $validated, $description) {
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
            $transaction->createLines([], $transaction->toArray());

            return $transaction;
        });

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

    /**
     * Weekly routine: the full time-block template (7 days) plus the
     * block happening right now and the next one today.
     */
    public function routine(Request $request, PlanService $planService): JsonResponse
    {
        $user = $request->user();
        $teamId = $user->current_team_id;
        $plan = $planService->getPlanTypeModel($teamId, PlanTypes::ROUTINE);

        if (! $plan) {
            return response()->json([
                'plan' => null,
                'current' => null,
                'next' => null,
            ]);
        }

        $timeZone = Setting::query()
            ->where('team_id', $teamId)
            ->where('name', 'team_timezone')
            ->value('value') ?: 'America/Santo_Domingo';

        $localNow = Carbon::now($timeZone);
        $dow = $localNow->dayOfWeekIso - 1;
        $nowMinutes = $localNow->hour * 60 + $localNow->minute;

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        // Build the full weekly template (undated blocks only).
        $blocks = [];
        foreach ($plan->stages as $stage) {
            $day = (int) $stage->order;
            foreach ($stage->items()->orderBy('order')->get() as $item) {
                $f = $item->fields->pluck('value', 'field_name')->toArray();
                if (! empty($f['date'])) {
                    continue; // skip dated exceptions
                }
                $blocks[] = [
                    'id' => $item->id,
                    'day' => $day,
                    'title' => $item->title,
                    'start' => $f['start'] ?? '00:00',
                    'end' => $f['end'] ?? '00:00',
                    'color' => $f['color'] ?? '#6E9BE6',
                    'member_id' => isset($f['member']) && $f['member'] !== '' ? (int) $f['member'] : null,
                    'note' => $f['note'] ?? '',
                ];
            }
        }

        // Current & next block for today.
        $todayBlocks = collect($blocks)
            ->filter(fn ($b) => $b['day'] === $dow)
            ->sortBy(fn ($b) => $this->toMinutes($b['start']))
            ->values();

        $current = null;
        $next = null;
        foreach ($todayBlocks as $b) {
            $s = $this->toMinutes($b['start']);
            $e = $this->toMinutes($b['end']);
            if ($s <= $nowMinutes && $nowMinutes < $e) {
                $current = $b;
            }
            if ($s > $nowMinutes && $next === null) {
                $next = $b;
            }
        }

        return response()->json([
            'plan' => [
                'id' => $plan->id,
                'blocks' => $blocks,
                'days' => $days,
            ],
            'current' => $current,
            'next' => $next,
        ]);
    }

    /**
     * Routine categories (color → name legend).
     */
    public function routineCategories(Request $request, Plan $plan): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);

        $setting = Setting::where([
            'team_id' => $request->user()->current_team_id,
            'name' => 'routine_categories',
        ])->first();

        $categories = [];
        if ($setting && $setting->value) {
            $decoded = json_decode($setting->value, true);
            $categories = is_array($decoded) ? $decoded : [];
        }

        return response()->json(['categories' => $categories]);
    }

    /**
     * Household chores: the family task board. Same data the web's
     * /housing/chores page renders, filtered to pending + completed-today
     * by default so the mobile list matches the web.
     */
    public function chores(Request $request, PlanService $planService): JsonResponse
    {
        $user = $request->user();
        $teamId = $user->current_team_id;

        // Mirror the web ChoreController default: show pending + done-today.
        if (! $request->has('done')) {
            $request->merge(['done' => 'today']);
        }

        $chores = $planService->getPlanType($teamId, PlanTypes::CHORES, $request);

        $members = LogerProfile::where('team_id', $teamId)
            ->get(['id', 'name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
            ->values();

        return response()->json([
            'chores' => $chores,
            'members' => $members,
        ]);
    }

    /**
     * Toggle a chore item's done state. For recurring chores (rrule),
     * the PlanItem boot logic handles commit_date stamping automatically.
     */
    public function toggleChore(Request $request, PlanItem $item): JsonResponse
    {
        abort_if((int) $item->team_id !== (int) $request->user()->current_team_id, 403);

        $item->is_done = ! $item->is_done;
        $item->save();

        return response()->json([
            'success' => true,
            'is_done' => (bool) $item->is_done,
            'commit_date' => $item->commit_date,
        ]);
    }

    /**
     * Create a new chore item in the team's CHORES plan board.
     */
    public function storeChore(Request $request, PlanService $planService): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'owner' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'recurrence' => ['nullable', 'string', 'in:daily,weekdays,weekly,once'],
        ]);

        $user = $request->user();
        $teamId = $user->current_team_id;

        $plan = $planService->getPlanTypeModel($teamId, PlanTypes::CHORES);
        if (! $plan) {
            $plan = $planService->createPlanBoard($user->currentTeam, PlanTypes::CHORES);
        }

        $stage = $plan->stages()->first();
        abort_if(! $stage, 422, 'Chore board has no stage.');

        $itemData = [
            'plan_id' => $plan->id,
            'team_id' => $teamId,
            'user_id' => $user->id,
            'stage_id' => $stage->id,
            'title' => $validated['title'],
        ];

        if (! empty($validated['recurrence'])) {
            $itemData['rrule'] = PlanItem::rruleForPreset($validated['recurrence']);
        }

        if (array_key_exists('due_date', $validated)) {
            $itemData['commit_date'] = $validated['due_date'];
        }

        $item = PlanItem::create($itemData);

        // Save the owner field if provided.
        if (! empty($validated['owner'])) {
            $item->saveFields([
                ['name' => 'Owner', 'value' => $validated['owner'], 'field_name' => 'owner'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $item->id,
                'title' => $item->title,
                'is_done' => (bool) $item->is_done,
                'rrule' => $item->rrule,
                'commit_date' => $item->commit_date,
            ],
        ], 201);
    }

    /**
     * Update a chore item (title, owner, recurrence).
     */
    public function updateChore(Request $request, PlanItem $item): JsonResponse
    {
        abort_if((int) $item->team_id !== (int) $request->user()->current_team_id, 403);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'owner' => ['sometimes', 'nullable', 'string', 'max:255'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'recurrence' => ['sometimes', 'nullable', 'string', 'in:daily,weekdays,weekly,once'],
        ]);

        if (array_key_exists('title', $validated)) {
            $item->title = $validated['title'];
        }

        if (array_key_exists('recurrence', $validated)) {
            $item->rrule = $validated['recurrence']
                ? PlanItem::rruleForPreset($validated['recurrence'])
                : null;
        }

        $item->save();

        // commit_date set via withoutEvents to bypass the PlanItem::updating
        // hook which nullifies it when is_done is false.
        if (array_key_exists('due_date', $validated)) {
            PlanItem::withoutEvents(function () use ($item, $validated) {
                $item->update(['commit_date' => $validated['due_date']]);
            });
        }

        if (array_key_exists('owner', $validated)) {
            $item->load('stage.board');
            $item->saveFields([
                ['name' => 'Owner', 'value' => $validated['owner'] ?? '', 'field_name' => 'owner'],
            ]);
            $item->load('fields');
        }

        $ownerField = $item->fields->first(fn ($f) => strtolower($f->field_name) === 'owner');

        return response()->json([
            'id' => $item->id,
            'title' => $item->title,
            'is_done' => (bool) $item->is_done,
            'rrule' => $item->rrule,
            'commit_date' => $item->commit_date,
            'owner' => $ownerField?->value ?: null,
        ]);
    }

    /**
     * Delete a chore item.
     */
    public function deleteChore(Request $request, PlanItem $item): JsonResponse
    {
        abort_if((int) $item->team_id !== (int) $request->user()->current_team_id, 403);

        $item->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Meal planner: planned meals for a date range (defaults to current week).
     * Returns the same shape as PlannedMealResource used on the dashboard,
     * plus meal types so the app can group by breakfast/lunch/dinner.
     */
    public function meals(Request $request, MealService $mealService): JsonResponse
    {
        $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
        ]);

        $teamId = $request->user()->current_team_id;
        $start = $request->query('start') ?? now()->startOfWeek()->format('Y-m-d');
        $end = $request->query('end') ?? now()->endOfWeek()->format('Y-m-d');

        $plans = $mealService->getMealScheduleInFrame($teamId, $start, $end);

        return response()->json([
            'start' => $start,
            'end' => $end,
            'meals' => PlannedMealResource::collection($plans),
            'mealTypes' => MealType::where('team_id', $teamId)->get(['id', 'name']),
        ]);
    }

    /**
     * Recipe list: all saved meals/recipes for the team.
     */
    public function recipes(Request $request): JsonResponse
    {
        $meals = Meal::where('team_id', $request->user()->current_team_id)
            ->with('mealType')
            ->get()
            ->map(fn (Meal $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'meal_type' => $m->mealType?->name,
                'meal_type_id' => $m->meal_type_id,
                'is_liked' => (bool) $m->is_liked,
            ]);

        return response()->json(['recipes' => $meals]);
    }

    /**
     * Schedule a meal on a date. Uses MealService::scheduleMealOnDate
     * which creates both MealPlan and Planner records.
     */
    public function storeMealPlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'meal_id' => 'required|integer|exists:meals,id',
            'date' => 'required|date',
            'meal_type_id' => 'required|integer|exists:meal_types,id',
        ]);

        $meal = Meal::findOrFail($validated['meal_id']);
        abort_if((int) $meal->team_id !== (int) $request->user()->current_team_id, 403);

        MealService::scheduleMealOnDate($meal->id, $validated['date'], $validated);

        $planner = Planner::where([
            'team_id' => $request->user()->current_team_id,
            'dateable_type' => MealPlan::class,
        ])->latest('id')->first();

        return response()->json([
            'success' => true,
            'plan' => $planner ? (new PlannedMealResource($planner->load('dateable.mealType')))->resolve() : null,
        ], 201);
    }

    /**
     * Update a planned meal (move date, change meal type).
     */
    public function updateMealPlan(Request $request, Planner $planner): JsonResponse
    {
        abort_if((int) $planner->team_id !== (int) $request->user()->current_team_id, 403);
        abort_if($planner->dateable_type !== MealPlan::class, 422, 'Not a meal plan.');

        $validated = $request->validate([
            'date' => 'nullable|date',
            'meal_type_id' => 'nullable|integer|exists:meal_types,id',
            'meal_id' => 'nullable|integer|exists:meals,id',
        ]);

        if (isset($validated['meal_id'])) {
            $meal = Meal::findOrFail($validated['meal_id']);
            abort_if((int) $meal->team_id !== (int) $request->user()->current_team_id, 403);
        }

        $mealPlan = $planner->dateable;

        if (isset($validated['date'])) {
            $date = Carbon::parse($validated['date'])->setTimezone('UTC')->toDateTimeString();
            $planner->update(['date' => $date]);
            $mealPlan->update(['date' => $date]);
        }

        if (isset($validated['meal_type_id'])) {
            $mealPlan->update(['meal_type_id' => $validated['meal_type_id']]);
        }

        if (isset($validated['meal_id'])) {
            $mealPlan->update([
                'meal_id' => $validated['meal_id'],
                'name' => $meal->name,
            ]);
        }

        return response()->json([
            'success' => true,
            'plan' => (new PlannedMealResource($planner->fresh()->load('dateable.mealType')))->resolve(),
        ]);
    }

    /**
     * Remove a planned meal from the schedule.
     */
    public function deleteMealPlan(Request $request, Planner $planner): JsonResponse
    {
        abort_if((int) $planner->team_id !== (int) $request->user()->current_team_id, 403);
        abort_if($planner->dateable_type !== MealPlan::class, 422, 'Not a meal plan.');

        $planner->dateable->delete();
        $planner->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Create a new recipe (meal).
     */
    public function storeRecipe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'meal_type_id' => 'nullable|integer|exists:meal_types,id',
            'notes' => 'nullable|string',
            'ingredients' => 'nullable|array',
            'ingredients.*.name' => 'required_with:ingredients|string',
            'ingredients.*.quantity' => 'nullable|numeric',
            'ingredients.*.unit' => 'nullable|string',
        ]);

        $meal = Meal::create([
            'team_id' => $request->user()->current_team_id,
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'meal_type_id' => $validated['meal_type_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if (! empty($validated['ingredients'])) {
            $items = collect($validated['ingredients'])->map(fn ($i) => [
                'product_id' => 'new::'.$i['name'],
                'quantity' => $i['quantity'] ?? 1,
                'unit' => $i['unit'] ?? '',
            ])->toArray();
            $meal->saveIngredients($items);
        }

        $meal->load('ingredients', 'mealType');

        return response()->json([
            'success' => true,
            'recipe' => [
                'id' => $meal->id,
                'name' => $meal->name,
                'meal_type' => $meal->mealType?->name,
                'meal_type_id' => $meal->meal_type_id,
                'notes' => $meal->notes,
                'is_liked' => (bool) $meal->is_liked,
                'ingredients' => $meal->ingredients->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'quantity' => (float) $i->quantity,
                    'unit' => $i->unit,
                ]),
            ],
        ], 201);
    }

    /**
     * Update an existing recipe.
     */
    public function updateRecipe(Request $request, Meal $meal): JsonResponse
    {
        abort_if((int) $meal->team_id !== (int) $request->user()->current_team_id, 403);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'meal_type_id' => 'nullable|integer|exists:meal_types,id',
            'notes' => 'nullable|string',
            'ingredients' => 'nullable|array',
            'ingredients.*.name' => 'required_with:ingredients|string',
            'ingredients.*.quantity' => 'nullable|numeric',
            'ingredients.*.unit' => 'nullable|string',
        ]);

        $meal->update(array_filter([
            'name' => $validated['name'] ?? null,
            'meal_type_id' => $validated['meal_type_id'] ?? null,
            'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : null,
        ], fn ($v) => $v !== null));

        if (isset($validated['ingredients'])) {
            $items = collect($validated['ingredients'])->map(fn ($i) => [
                'product_id' => 'new::'.$i['name'],
                'quantity' => $i['quantity'] ?? 1,
                'unit' => $i['unit'] ?? '',
            ])->toArray();
            $meal->saveIngredients($items);
        }

        $meal->load('ingredients', 'mealType');

        return response()->json([
            'success' => true,
            'recipe' => [
                'id' => $meal->id,
                'name' => $meal->name,
                'meal_type' => $meal->mealType?->name,
                'meal_type_id' => $meal->meal_type_id,
                'notes' => $meal->notes,
                'is_liked' => (bool) $meal->is_liked,
                'ingredients' => $meal->ingredients->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'quantity' => (float) $i->quantity,
                    'unit' => $i->unit,
                ]),
            ],
        ]);
    }

    /**
     * Delete a recipe.
     */
    public function deleteRecipe(Request $request, Meal $meal): JsonResponse
    {
        abort_if((int) $meal->team_id !== (int) $request->user()->current_team_id, 403);

        $meal->ingredients()->delete();
        $meal->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Toggle a recipe's favorite status.
     */
    public function toggleRecipeFavorite(Request $request, Meal $meal): JsonResponse
    {
        abort_if((int) $meal->team_id !== (int) $request->user()->current_team_id, 403);

        $isLiked = $meal->toggleFavorite();

        return response()->json(['success' => true, 'is_liked' => $isLiked]);
    }

    /**
     * Add a recipe's ingredients to the shopping list.
     */
    public function recipeToShoppingList(Request $request, Meal $meal, MealService $mealService): JsonResponse
    {
        abort_if((int) $meal->team_id !== (int) $request->user()->current_team_id, 403);

        $mealService->addToShoppingList($meal->ingredients->toArray());

        return response()->json(['success' => true]);
    }

    /**
     * Random meal suggestion, biased toward favorites.
     */
    public function randomMeal(Request $request): JsonResponse
    {
        $teamId = $request->user()->current_team_id;
        $mealTypeId = $request->query('meal_type_id');

        $query = Meal::where('team_id', $teamId);
        if ($mealTypeId) {
            $query->where('meal_type_id', $mealTypeId);
        }

        $favorites = (clone $query)->favorites()->get();
        $pool = $favorites->isNotEmpty() ? $favorites : $query->get();

        if ($pool->isEmpty()) {
            return response()->json(['meal' => null]);
        }

        $meal = $pool->random();

        return response()->json([
            'meal' => [
                'id' => $meal->id,
                'name' => $meal->name,
                'meal_type' => $meal->mealType?->name,
                'meal_type_id' => $meal->meal_type_id,
                'is_liked' => (bool) $meal->is_liked,
                'notes' => $meal->notes,
            ],
        ]);
    }

    /**
     * Shopping list: the active list with stages and items (pending/buy/skip).
     * Reuses the same Plan-based structure as the web's /shopping page.
     */
    public function shoppingList(Request $request, PlanService $planService): JsonResponse
    {
        $user = $request->user();
        $teamId = $user->current_team_id;

        $requestedId = $request->query('plan');
        $plan = null;

        if ($requestedId) {
            $plan = Plan::where([
                'id' => $requestedId,
                'team_id' => $teamId,
                'plan_type_name' => PlanTypes::SHOPPING_LIST,
            ])->first();
        }

        if (! $plan) {
            $plan = $planService->getPlanTypeModel($teamId, PlanTypes::SHOPPING_LIST);
        }

        if (! $plan) {
            $plan = $planService->createPlanBoard(
                $user->currentTeam,
                PlanTypes::SHOPPING_LIST,
                'Shopping List',
            );
        }

        $allLists = Plan::where([
            'team_id' => $teamId,
            'plan_type_name' => PlanTypes::SHOPPING_LIST,
        ])->orderBy('id')->get()->map(function (Plan $p) {
            $items = $p->stages->flatMap(fn ($s) => $s->items);

            return [
                'id' => $p->id,
                'name' => $p->name,
                'total' => $items->count(),
                'pending' => $items->where('state', PlanItem::STATE_PENDING)->count(),
                'shared' => (bool) $p->share_token,
            ];
        })->values();

        $reviews = app(ShoppingListReviewService::class);

        return response()->json([
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'share_url' => $plan->share_token ? route('shared.list', $plan->share_token) : null,
                'session' => $reviews->activeSession($plan),
                'visits' => $reviews->visits($plan),
                'stages' => $plan->stages->map(fn ($stage) => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'items' => $stage->items()->orderBy('order')->get()->map(fn ($item) => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'state' => $item->state ?? PlanItem::STATE_PENDING,
                        'is_done' => (bool) $item->is_done,
                        'order' => (int) $item->order,
                    ]),
                ])->values(),
            ],
            'lists' => $allLists,
            'activeListId' => $plan->id,
        ]);
    }

    /**
     * Add an item to a shopping list.
     */
    public function addShoppingItem(Request $request, Plan $plan): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'stage_id' => ['nullable', 'integer'],
        ]);

        $stage = null;
        if (! empty($data['stage_id'])) {
            $stage = $plan->stages()->where('id', $data['stage_id'])->first();
        }
        $stage = $stage ?? $plan->stages->first();
        abort_if(! $stage, 422, 'List has no stages.');

        $item = PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'user_id' => $request->user()->id,
            'stage_id' => $stage->id,
            'title' => trim($data['title']),
            'state' => PlanItem::STATE_PENDING,
            'order' => ((int) $stage->items()->max('order')) + 1,
        ]);

        return response()->json([
            'id' => $item->id,
            'title' => $item->title,
            'state' => $item->state,
            'order' => (int) $item->order,
            'stage_id' => $item->stage_id,
        ], 201);
    }

    /**
     * Cycle a shopping item's state: pending → buy → skip → pending.
     */
    public function cycleShoppingItem(Request $request, Plan $plan, PlanItem $item): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);
        abort_if($item->stage?->plan_id !== $plan->id, 404);

        $next = $request->input('state');
        if ($next === null) {
            $item->cycleState();
        } else {
            $item->setState($next);
        }

        app(ShoppingListReviewService::class)->updateSessionCart($plan, $item->fresh());

        return response()->json([
            'id' => $item->id,
            'state' => $item->state,
            'is_done' => (bool) $item->is_done,
        ]);
    }

    /**
     * Create a new stage (category column) on a shopping list.
     */
    public function addShoppingStage(Request $request, Plan $plan): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);

        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);

        $stage = $plan->stages()->create([
            'user_id' => $request->user()->id,
            'team_id' => $plan->team_id,
            'name' => trim($data['name']),
            'order' => ((int) $plan->stages()->max('order')) + 1,
        ]);

        return response()->json([
            'id' => $stage->id,
            'name' => $stage->name,
            'items' => [],
        ], 201);
    }

    /**
     * Rename or delete a shopping list stage (category column).
     */
    public function updateShoppingStage(Request $request, Plan $plan, PlanStage $stage): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);
        abort_if((int) $stage->plan_id !== (int) $plan->id, 404);

        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $stage->update(['name' => trim($data['name'])]);

        return response()->json(['id' => $stage->id, 'name' => $stage->name]);
    }

    public function deleteShoppingStage(Request $request, Plan $plan, PlanStage $stage): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);
        abort_if((int) $stage->plan_id !== (int) $plan->id, 404);

        // Move orphan items to the first remaining stage before deleting.
        $fallback = $plan->stages()->where('id', '!=', $stage->id)->orderBy('order')->first();
        if ($fallback) {
            PlanItem::where('stage_id', $stage->id)->update(['stage_id' => $fallback->id]);
        }

        $stage->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Update a shopping item (move to another stage, rename).
     */
    /**
     * Rename an item, change its category, or move it to another list of the team (`plan_id`): it lands in the
     * first stage of the destination, or in `stage_id` when that is given.
     */
    public function updateShoppingItem(UpdateShoppingItemRequest $request, Plan $plan, PlanItem $item, ShoppingListReviewService $reviews): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);
        abort_if($item->stage?->plan_id !== $plan->id, 404);

        $data = $request->validated();
        $destination = isset($data['plan_id']) && (int) $data['plan_id'] !== $plan->id ? Plan::findOrFail($data['plan_id']) : $plan;

        if (isset($data['stage_id']) || $destination->isNot($plan)) {
            $stage = isset($data['stage_id'])
                ? $destination->stages()->where('id', $data['stage_id'])->first()
                : $destination->stages()->first();

            if (! $stage) {
                throw ValidationException::withMessages(['stage_id' => 'La categoría no pertenece a la lista de destino.']);
            }

            $data['stage_id'] = $stage->id;
        }

        if ($destination->isNot($plan)) {
            $details = $reviews->itemDetails($plan)[$item->id] ?? null;
            $data['order'] = ((int) $stage->items()->max('order')) + 1;

            $item->update($data);
            $reviews->unmarkReviewed($plan, $item);
            if ($details) {
                $reviews->saveItemDetails($plan, $item, '', '');
                $reviews->saveItemDetails($destination, $item, $details['quantity'] ?? '', $details['note'] ?? '');
            }
        } else {
            $item->update(Arr::except($data, ['plan_id']));
        }

        return response()->json([
            'id' => $item->id,
            'plan_id' => $item->plan_id,
            'title' => $item->title,
            'state' => $item->state,
            'stage_id' => $item->stage_id,
        ]);
    }

    public function deleteShoppingItem(Request $request, Plan $plan, PlanItem $item): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);
        abort_if($item->stage?->plan_id !== $plan->id, 404);

        $item->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Reset all items in a shopping list back to pending.
     */
    public function resetShoppingList(Request $request, Plan $plan): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);

        PlanItem::query()
            ->whereHas('stage', fn ($q) => $q->where('plan_id', $plan->id))
            ->update([
                'state' => PlanItem::STATE_PENDING,
                'is_done' => false,
                'commit_date' => null,
            ]);

        return response()->json(['reset' => true]);
    }

    /**
     * Mark all pending items in a shopping list as "buy".
     */
    public function checkAllShoppingItems(Request $request, Plan $plan): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);

        PlanItem::query()
            ->whereHas('stage', fn ($q) => $q->where('plan_id', $plan->id))
            ->where('state', PlanItem::STATE_PENDING)
            ->update([
                'state' => PlanItem::STATE_BUY,
                'is_done' => true,
            ]);

        return response()->json(['checked' => true]);
    }

    /**
     * Enable sharing for a shopping list (idempotent — reuses existing token).
     */
    public function shareShoppingList(Request $request, Plan $plan): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);

        if (! $plan->share_token) {
            $plan->share_token = (string) Str::uuid();
            $plan->save();
        }

        return response()->json([
            'shared' => true,
            'url' => route('shared.list', $plan->share_token),
        ]);
    }

    /**
     * Disable sharing for a shopping list.
     */
    public function unshareShoppingList(Request $request, Plan $plan): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);

        $plan->share_token = null;
        $plan->save();

        return response()->json(['shared' => false]);
    }

    /**
     * Change the store of a finished trip and, if no charge is linked yet, try to auto-link it again.
     */
    public function updateShoppingVisit(UpdateShoppingVisitRequest $request, Plan $plan, string $session, ShoppingListReviewService $reviews): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);

        $payeeId = $request->validated('payee_id');
        $visit = $reviews->setVisitPayee($plan, $session, $payeeId === null ? null : (int) $payeeId);

        if (empty($visit['transaction_id'])) {
            app(ShoppingVisitLinker::class)->linkVisit($plan, $visit);
        }

        return response()->json([
            'visit' => $reviews->decorate($plan->team_id, [$reviews->visit($plan, $session) ?? $visit])[0],
        ]);
    }

    // ─── Shopping sessions ──────────────────────────────────────

    /**
     * Start a shopping session (trip). Returns existing if one is active.
     */
    public function startShoppingSession(Request $request, Plan $plan, ShoppingListReviewService $reviews): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);

        $session = $reviews->startSession($plan, $request->user()->id);

        return response()->json(['session' => $session], $session ? 201 : 200);
    }

    /**
     * Finish a shopping session: record the visit, reset the list.
     */
    public function finishShoppingSession(Request $request, Plan $plan, ShoppingListReviewService $reviews): JsonResponse
    {
        abort_if((int) $plan->team_id !== (int) $request->user()->current_team_id, 403);

        $validated = $request->validate([
            'session_id' => ['required', 'uuid'],
            'total' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'currency' => ['required', 'string', 'in:DOP,USD,EUR'],
            'note' => ['nullable', 'string', 'max:2000'],
            // The store the trip was at; with its amount and date it is what ties the visit to the card charge.
            'payee_id' => ['nullable', 'integer', Rule::exists('payees', 'id')->where('team_id', $plan->team_id)],
        ]);

        $visit = $reviews->finishSession($plan, $validated);
        // The charge may already be in (a bank import, or paid before saving the trip).
        app(ShoppingVisitLinker::class)->linkVisit($plan, $visit);
        $visit = $reviews->decorate($plan->team_id, [$reviews->visit($plan, $visit['id']) ?? $visit])[0];

        return response()->json([
            'visit' => $visit,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'session' => $reviews->activeSession($plan->fresh()),
                'visits' => $reviews->visits($plan),
                'stages' => $plan->fresh()->stages->map(fn ($stage) => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'items' => $stage->items()->orderBy('order')->get()->map(fn ($item) => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'state' => $item->state ?? PlanItem::STATE_PENDING,
                        'is_done' => (bool) $item->is_done,
                        'order' => (int) $item->order,
                    ]),
                ])->values(),
            ],
        ]);
    }

    // ─── Inbox (draft transactions) ──────────────────────────────

    /**
     * Approve a draft transaction, changing its status to verified.
     */
    public function approveTransaction(Request $request, Transaction $transaction): JsonResponse
    {
        abort_if((int) $transaction->team_id !== (int) $request->user()->current_team_id, 403);

        $transaction->approve();

        return response()->json(['success' => true]);
    }

    /**
     * Delete a single draft transaction.
     */
    public function deleteTransaction(Request $request, Transaction $transaction, TransactionDelete $transactionDelete): JsonResponse
    {
        abort_if((int) $transaction->team_id !== (int) $request->user()->current_team_id, 403);

        $transactionDelete->delete($request->user(), $transaction);

        return response()->json(['success' => true]);
    }

    /**
     * Approve every draft of the team, optionally only those dated within start..end.
     */
    public function approveAllDrafts(DraftTransactionsRangeRequest $request): JsonResponse
    {
        $drafts = $this->draftsInRange($request)->get();

        $drafts->each(fn (Transaction $transaction) => $transaction->approve());

        return response()->json(['success' => true, 'approved' => $drafts->count()]);
    }

    /**
     * Delete every draft of the team, optionally only those dated within start..end.
     */
    public function removeAllDrafts(DraftTransactionsRangeRequest $request, TransactionDelete $transactionDelete): JsonResponse
    {
        $drafts = $this->draftsInRange($request)->get();

        $drafts->each(fn (Transaction $transaction) => $transactionDelete->delete($request->user(), $transaction));

        return response()->json(['success' => true, 'deleted' => $drafts->count()]);
    }

    private function draftsInRange(DraftTransactionsRangeRequest $request): Builder
    {
        return Transaction::query()
            ->where('team_id', $request->user()->current_team_id)
            ->where('status', Transaction::STATUS_DRAFT)
            ->when($request->validated('start'), fn (Builder $query, string $start) => $query->whereDate('date', '>=', $start))
            ->when($request->validated('end'), fn (Builder $query, string $end) => $query->whereDate('date', '<=', $end));
    }

    /**
     * Bulk-delete draft transactions by ID.
     */
    public function bulkDeleteTransactions(Request $request, TransactionDelete $transactionDelete): JsonResponse
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $user = $request->user();
        $transactions = Transaction::whereIn('id', $data['ids'])
            ->where('team_id', $user->current_team_id)
            ->get();

        foreach ($transactions as $transaction) {
            $transactionDelete->delete($user, $transaction);
        }

        return response()->json(['success' => true, 'deleted' => $transactions->count()]);
    }

    /**
     * Show a single transaction with full detail (account, category, payee).
     */
    public function showTransaction(Request $request, Transaction $transaction): JsonResponse
    {
        abort_if((int) $transaction->team_id !== (int) $request->user()->current_team_id, 403);

        $transaction->load(['account', 'counterAccount', 'category', 'payee']);

        return response()->json([
            'id' => $transaction->id,
            'description' => $transaction->description,
            'total' => (float) $transaction->total,
            'currency_code' => $transaction->currency_code,
            'date' => $transaction->date,
            'direction' => $transaction->direction,
            'status' => $transaction->status,
            'notes' => $transaction->notes,
            'is_transfer' => (bool) $transaction->is_transfer,
            'has_splits' => (bool) $transaction->has_splits,
            'splits' => TransactionSplits::of($transaction),
            'account' => $transaction->account ? [
                'id' => $transaction->account->id,
                'name' => $transaction->account->name,
            ] : null,
            'counter_account' => $transaction->counterAccount ? [
                'id' => $transaction->counterAccount->id,
                'name' => $transaction->counterAccount->name,
            ] : null,
            'category' => $transaction->category ? [
                'id' => $transaction->category->id,
                'name' => $transaction->category->name,
            ] : null,
            'payee' => $transaction->payee ? [
                'id' => $transaction->payee->id,
                'name' => $transaction->payee->name,
            ] : null,
            'exchange_rate' => $transaction->exchange_rate ? (float) $transaction->exchange_rate : null,
            'exchange_amount' => $transaction->exchange_amount ? (float) $transaction->exchange_amount : null,
            'created_at' => $transaction->created_at,
        ]);
    }

    /**
     * Update a transaction (description, total, date, account, category, payee, direction).
     */
    public function updateTransaction(Request $request, Transaction $transaction, TransactionUpdate $transactionUpdate): JsonResponse
    {
        abort_if((int) $transaction->team_id !== (int) $request->user()->current_team_id, 403);

        if ($transaction->is_transfer) {
            return response()->json([
                'message' => 'Transfers cannot be edited directly. Delete and recreate instead.',
            ], 422);
        }

        $validated = $request->validate([
            'description' => ['sometimes', 'string', 'max:255'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'date' => ['sometimes', 'date'],
            'account_id' => ['sometimes', 'integer'],
            'category_id' => ['sometimes', 'nullable', 'integer'],
            'payee_id' => ['sometimes', 'nullable', 'integer'],
            'direction' => ['sometimes', 'in:DEPOSIT,WITHDRAW'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            ...TransactionSplits::rules($request->user()->current_team_id),
        ]);

        // Editing a purchase split across categories. The journal rebuilds a transaction's lines from `items` on
        // every update, so without them the split would silently collapse into one line (no category): keep the
        // existing lines unless the app sends new ones or an explicit single category.
        $total = (float) ($validated['total'] ?? $transaction->total);
        $payeeId = array_key_exists('payee_id', $validated) ? $validated['payee_id'] : $transaction->payee_id;
        if (! empty($validated['items'])) {
            $validated['items'] = TransactionSplits::lines($validated['items'], $total, $payeeId);
            $validated['has_splits'] = true;
            $validated['category_id'] = null;
        } elseif ($transaction->has_splits) {
            if (! empty($validated['category_id'])) {
                $validated['has_splits'] = false; // the user chose one category: it stops being a split
            } else {
                $validated['items'] = TransactionSplits::lines(TransactionSplits::of($transaction)->all(), $total, $payeeId);
                $validated['has_splits'] = true;
            }
        } else {
            unset($validated['items']);
        }

        // The journal package's sanitizeData requires account_id, currency_code
        // and payee_id/counter_account_id — merge from existing transaction.
        $data = array_merge([
            'account_id' => $transaction->account_id,
            'currency_code' => $transaction->currency_code,
            'payee_id' => $transaction->payee_id,
            'counter_account_id' => $transaction->counter_account_id,
        ], $validated);

        // The journal's updateTransaction() mass-assigns every key it gets and `items` is not a column, so the split
        // lines are kept out of it and rebuilt right after (its own update leaves plain lines in their place).
        $items = $data['items'] ?? [];
        unset($data['items']);

        DB::transaction(function () use ($request, $transaction, $transactionUpdate, $data, $items) {
            $transactionUpdate->update($request->user(), $transaction, $data);
            if ($items) {
                $transaction->refresh()->createLines($items, $transaction->toArray());
            }
        });
        $transaction->refresh()->load(['account', 'category', 'payee']);

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'description' => $transaction->description,
                'total' => (float) $transaction->total,
                'currency_code' => $transaction->currency_code,
                'date' => $transaction->date,
                'direction' => $transaction->direction,
                'status' => $transaction->status,
                'account' => $transaction->account ? ['id' => $transaction->account->id, 'name' => $transaction->account->name] : null,
                'category' => $transaction->category ? ['id' => $transaction->category->id, 'name' => $transaction->category->name] : null,
                'payee' => $transaction->payee ? ['id' => $transaction->payee->id, 'name' => $transaction->payee->name] : null,
            ],
        ]);
    }

    /**
     * List payees for the team, optionally filtered by search term.
     */
    public function payees(Request $request): JsonResponse
    {
        $teamId = $request->user()->current_team_id;
        $search = $request->query('search');
        $limit = min((int) ($request->query('limit') ?? 50), 100);

        $query = Payee::where('team_id', $teamId)
            ->whereNotNull('name')
            ->where('name', '!=', '');

        if ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $payees = $query->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);

        return response()->json($payees);
    }

    /**
     * Create a new payee for the team.
     */
    public function createPayee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $payee = Payee::findOrCreateByName([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
        ], $validated['name']);

        return response()->json([
            'id' => $payee->id,
            'name' => $payee->name,
        ], 201);
    }

    /**
     * Flat list of transaction categories for picker dropdowns.
     */
    public function categoriesFlat(Request $request): JsonResponse
    {
        $teamId = $request->user()->current_team_id;

        $categories = Category::where('team_id', $teamId)
            ->where('resource_type', 'transactions')
            ->whereNotNull('parent_id')
            ->with('group:id,name')
            ->orderBy('index')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'group' => $cat->group ? [
                        'id' => $cat->group->id,
                        'name' => $cat->group->name,
                    ] : null,
                ];
            });

        return response()->json($categories);
    }

    // ─── Accounts CRUD ───────────────────────────────────────────

    /**
     * Create a new account for the team.
     */
    public function storeAccount(Request $request, AccountCreate $accountCreate): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'detail_type' => ['required', 'string'],
            'currency_code' => ['required', 'string', 'size:3'],
            'opening_balance' => ['nullable', 'numeric'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_closing_day' => ['nullable', 'integer', 'between:1,31'],
            'credit_payment_days' => ['nullable', 'integer', 'between:0,90'],
        ]);

        $detailType = AccountDetailType::where('name', $validated['detail_type'])->first();
        abort_if(! $detailType, 422, 'Invalid detail_type.');

        $data = array_merge($validated, [
            'account_detail_type_id' => $detailType->id,
            'display_id' => Str::slug($validated['name']),
        ]);
        unset($data['detail_type']);

        $account = $accountCreate->create($request->user(), $data);
        $account->loadMissing('detailType');

        return response()->json([
            'success' => true,
            'account' => $this->formatAccount($account),
        ], 201);
    }

    /**
     * Show a single account with detail.
     */
    public function showAccount(Request $request, Account $account): JsonResponse
    {
        abort_if((int) $account->team_id !== (int) $request->user()->current_team_id, 403);

        $account->loadMissing(['detailType', 'reconciliationLast']);

        return response()->json($this->formatAccount($account));
    }

    /**
     * Update an account's editable fields.
     */
    public function updateAccount(Request $request, Account $account, AccountUpdate $accountUpdate): JsonResponse
    {
        abort_if((int) $account->team_id !== (int) $request->user()->current_team_id, 403);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'credit_limit' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'credit_closing_day' => ['sometimes', 'nullable', 'integer', 'between:1,31'],
            'credit_payment_days' => ['sometimes', 'nullable', 'integer', 'between:0,90'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
        ]);

        $accountUpdate->update($request->user(), $account, $validated);
        $account->refresh()->loadMissing(['detailType', 'reconciliationLast']);

        return response()->json([
            'success' => true,
            'account' => $this->formatAccount($account),
        ]);
    }

    /**
     * Close or reopen an account.
     */
    public function closeAccount(Request $request, Account $account): JsonResponse
    {
        abort_if((int) $account->team_id !== (int) $request->user()->current_team_id, 403);

        $validated = $request->validate([
            'closed' => ['required', 'boolean'],
        ]);

        $account->update([
            'closed_at' => $validated['closed'] ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'closed_at' => $account->fresh()->closed_at,
        ]);
    }

    /**
     * Format an account for mobile API responses.
     */
    private function formatAccount(Account $account): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'current_balance' => (float) $account->balance,
            'currency_code' => $account->currency_code,
            'balance_type' => $account->balance_type,
            'type' => $account->type,
            'detail_type' => $account->detailType?->name,
            'account_detail_type_id' => $account->account_detail_type_id,
            'credit_limit' => $account->credit_limit ? (float) $account->credit_limit : null,
            'credit_closing_day' => $account->credit_closing_day,
            'credit_payment_days' => $account->credit_payment_days,
            'opening_balance' => (float) ($account->opening_balance ?? 0),
            'closed_at' => $account->closed_at,
            'reconciliation_last' => $account->reconciliationLast ? [
                'status' => $account->reconciliationLast->status,
                'amount' => (float) $account->reconciliationLast->amount,
            ] : null,
        ];
    }

    // ─── Reconciliation ───────────────────────────────────────────

    /**
     * Current reconciliation state for an account: last reconciliation,
     * ledger balance as-of-date, and pending entries.
     */
    public function getReconciliation(Request $request, Account $account, ReconciliationService $service): JsonResponse
    {
        abort_if((int) $account->team_id !== (int) $request->user()->current_team_id, 403);

        $last = $account->reconciliationLast;
        $date = $last?->date ?? now()->toDateString();
        $clearedBalance = $service->balanceAsOf($account, $date);

        $entries = [];
        if ($last && $last->status === Reconciliation::STATUS_PENDING) {
            $entries = $this->enrichedReconciliationEntries($last);
        }

        return response()->json([
            'reconciliation' => $last ? $this->reconciliationPayload($last, $service, $entries) : null,
            'cleared_balance' => $clearedBalance,
        ]);
    }

    /**
     * Start a new reconciliation for an account.
     */
    public function storeReconciliation(Request $request, Account $account, ReconciliationService $service): JsonResponse
    {
        abort_if((int) $account->team_id !== (int) $request->user()->current_team_id, 403);

        $validated = $request->validate([
            'balance' => ['required', 'numeric'],
            'date' => ['required', 'date'],
        ]);

        $reconciliation = $service->create($account, new ReconciliationParamsData(
            account_id: $account->id,
            balance: $validated['balance'],
            date: $validated['date'],
            user_id: $request->user()->id,
        ));

        return response()->json([
            'reconciliation' => $this->reconciliationPayload($reconciliation, $service, $this->enrichedReconciliationEntries($reconciliation)),
        ], 201);
    }

    /**
     * Toggle a reconciliation entry's matched flag.
     */
    public function checkReconciliationEntry(
        Request $request,
        Reconciliation $reconciliation,
        ReconciliationEntry $entry,
        ReconciliationService $service,
    ): JsonResponse {
        abort_if((int) $reconciliation->team_id !== (int) $request->user()->current_team_id, 403);
        abort_if((int) $entry->reconciliation_id !== (int) $reconciliation->id, 404);

        $validated = $request->validate([
            'matched' => ['required', 'boolean'],
        ]);

        $service->checkLine($reconciliation, $entry, $validated['matched']);

        return response()->json([
            'id' => $entry->id,
            'matched' => $validated['matched'],
        ]);
    }

    /**
     * Book an adjustment transaction and complete the reconciliation.
     */
    public function saveReconciliationAdjustment(
        Request $request,
        Reconciliation $reconciliation,
        ReconciliationService $service,
    ): JsonResponse {
        abort_if((int) $reconciliation->team_id !== (int) $request->user()->current_team_id, 403);

        $validated = $request->validate([
            'balance' => ['required', 'numeric'],
        ]);

        $service->saveAdjustment($reconciliation, new ReconciliationParamsData(
            account_id: $reconciliation->account_id,
            balance: $validated['balance'],
            date: $reconciliation->date,
            user_id: $request->user()->id,
        ));

        $reconciliation->refresh();

        return response()->json([
            'id' => $reconciliation->id,
            'status' => $reconciliation->status,
            'difference' => (float) $reconciliation->difference,
        ]);
    }

    /**
     * Delete a reconciliation, pending or completed. Its transactions stay; they just stop being matched.
     */
    public function deleteReconciliation(
        Request $request,
        Reconciliation $reconciliation,
        ReconciliationService $service,
    ): JsonResponse {
        abort_if((int) $reconciliation->team_id !== (int) $request->user()->current_team_id, 403);

        $service->delete($reconciliation);

        return response()->json(['deleted' => true]);
    }

    /**
     * A reconciliation as the app reads it. On top of what it always had: the book balance as of its date
     * (`ledger_balance`, so the app does not have to derive it) and how many of its movements are matched.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<string, mixed>
     */
    private function reconciliationPayload(Reconciliation $reconciliation, ReconciliationService $service, array $entries): array
    {
        return [
            'id' => $reconciliation->id,
            'date' => $reconciliation->date,
            'amount' => (float) $reconciliation->amount,
            'difference' => (float) $reconciliation->difference,
            'status' => $reconciliation->status,
            'ledger_balance' => $service->balanceAsOf($reconciliation->account, $reconciliation->date),
            'matched_count' => $reconciliation->entries()->where('matched', true)->count(),
            'total_entries' => $reconciliation->entries()->count(),
            'entries' => $entries,
        ];
    }

    /** Cross-account hub: every account with its latest reconciliation and what is still unreconciled. */
    public function reconciliationHub(Request $request, ReconciliationService $service): JsonResponse
    {
        return response()->json(['accounts' => $service->hubRows($request->user()->current_team_id)]);
    }

    /** One account's reconciliations, newest first, and how many movements are unreconciled since the last. */
    public function accountReconciliations(Request $request, Account $account, ReconciliationService $service): JsonResponse
    {
        abort_if((int) $account->team_id !== (int) $request->user()->current_team_id, 403);

        $history = $service->listHistoryOf($account);
        $last = $history->first();

        return response()->json([
            'reconciliations' => $history->map(fn ($reconciliation) => [
                'id' => $reconciliation->id,
                'date' => $reconciliation->date,
                'amount' => (float) $reconciliation->amount,
                'difference' => (float) $reconciliation->difference,
                'status' => $reconciliation->status,
                'total_transactions' => (int) $reconciliation->total_transactions,
                'matched_transactions' => (int) $reconciliation->matched_transactions,
            ])->values(),
            'last_id' => $last?->id,
            'unreconciled_count' => $last ? $account->transactionsToReconcile(null, $last->date)->count() : 0,
        ]);
    }

    /** The book balance of an account as of a date (default today): what the statement should be compared with. */
    public function accountBalanceAt(Request $request, Account $account, ReconciliationService $service): JsonResponse
    {
        abort_if((int) $account->team_id !== (int) $request->user()->current_team_id, 403);
        $validated = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = $validated['date'] ?? now()->toDateString();

        return response()->json(['date' => $date, 'balance' => $service->balanceAsOf($account, $date)]);
    }

    /** One reconciliation, pending or completed, with its entries enriched with the transaction they point to. */
    public function showReconciliation(Request $request, Reconciliation $reconciliation, ReconciliationService $service): JsonResponse
    {
        abort_if((int) $reconciliation->team_id !== (int) $request->user()->current_team_id, 403);

        return response()->json(['reconciliation' => $this->reconciliationPayload($reconciliation, $service, $this->enrichedReconciliationEntries($reconciliation))]);
    }

    /** Back to pending, nothing matched. Only the latest reconciliation of an account can be reopened. */
    public function reopenReconciliation(Request $request, Reconciliation $reconciliation, ReconciliationService $service): JsonResponse
    {
        abort_if((int) $reconciliation->team_id !== (int) $request->user()->current_team_id, 403);
        abort_if($reconciliation->status !== Reconciliation::STATUS_COMPLETED, 422, 'Only a completed reconciliation can be reopened.');
        abort_if(! $service->isLatestOfAccount($reconciliation), 422, 'Only the most recent reconciliation of an account can be reopened.');

        $service->reopen($reconciliation);

        return response()->json(['reconciliation' => $this->reconciliationPayload($reconciliation->refresh(), $service, $this->enrichedReconciliationEntries($reconciliation))]);
    }

    /**
     * Correct the statement balance and/or the date of a reconciliation in progress. Unlike the web's "Complete" this
     * never closes it: the balance only moves `amount` and recomputes `difference`; the adjustment endpoint is what
     * finishes it. A new date rebuilds the entries for that date range.
     */
    public function updateReconciliation(UpdateReconciliationRequest $request, Reconciliation $reconciliation, ReconciliationService $service): JsonResponse
    {
        abort_if((int) $reconciliation->team_id !== (int) $request->user()->current_team_id, 403);
        abort_if($reconciliation->status === Reconciliation::STATUS_COMPLETED, 422, 'A completed reconciliation cannot be edited.');

        $validated = $request->validated();

        if (isset($validated['balance'])) {
            $reconciliation->update([
                'amount' => $validated['balance'],
                'difference' => round($service->balanceAsOf($reconciliation->account, $reconciliation->date) - (float) $validated['balance'], 2),
            ]);
        }

        if (isset($validated['date']) && $validated['date'] !== Carbon::parse($reconciliation->date)->toDateString()) {
            $service->changeDate($reconciliation, $validated['date']);
        }

        return response()->json(['reconciliation' => $this->reconciliationPayload($reconciliation->refresh(), $service, $this->enrichedReconciliationEntries($reconciliation))]);
    }

    /** Pull the movements that arrived since the reconciliation was opened ("Pull new" on the web). */
    public function syncReconciliation(Request $request, Reconciliation $reconciliation, ReconciliationService $service): JsonResponse
    {
        abort_if((int) $reconciliation->team_id !== (int) $request->user()->current_team_id, 403);

        $service->syncTransactions($reconciliation);

        return response()->json(['reconciliation' => $this->reconciliationPayload($reconciliation->refresh(), $service, $this->enrichedReconciliationEntries($reconciliation))]);
    }

    /**
     * Reconciliation entries enriched with transaction description, date, total and direction.
     *
     * @return array<int, array<string, mixed>>
     */
    private function enrichedReconciliationEntries(Reconciliation $reconciliation): array
    {
        return $reconciliation->entries()
            ->join('transaction_lines', 'transaction_lines.id', 'reconciliation_entries.transaction_line_id')
            ->join('transactions', 'transactions.id', 'reconciliation_entries.transaction_id')
            ->select([
                'reconciliation_entries.id',
                'reconciliation_entries.transaction_id',
                'reconciliation_entries.transaction_line_id',
                'reconciliation_entries.matched',
                'transactions.description',
                'transactions.date',
                'transactions.total',
                'transactions.direction',
            ])
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'transaction_id' => $e->transaction_id,
                'transaction_line_id' => $e->transaction_line_id,
                'matched' => (bool) $e->matched,
                'description' => $e->description,
                'date' => $e->date,
                'total' => (float) $e->total,
                'direction' => $e->direction,
            ])
            ->values()
            ->toArray();
    }

    // ─── Occurrences ──────────────────────────────────────────────

    /**
     * All occurrences for the team with computed cadence fields the app needs
     * to render the "Explorar" carousel and overdue badges.
     */
    public function occurrences(Request $request): JsonResponse
    {
        $teamId = $request->user()->current_team_id;

        $occurrences = Occurrence::byTeam($teamId)
            ->where('is_active', true)
            ->whereNotNull('last_date')
            ->orderBy('name')
            ->get()
            ->map(function (Occurrence $o) {
                $daysSinceLast = $o->last_date
                    ? (int) $o->last_date->diffInDays(now(), true)
                    : null;
                $avg = (float) $o->avg_days_passed;
                $isOverdue = $daysSinceLast !== null && $avg > 0 && $daysSinceLast >= $avg + Occurrence::DAYS_BEFORE;

                return [
                    'id' => $o->id,
                    'name' => $o->name,
                    'type' => $o->type,
                    'last_date' => $o->last_date?->format('Y-m-d'),
                    'avg_days_passed' => round($avg, 1),
                    'occurrence_count' => (int) $o->occurrence_count,
                    'days_since_last' => $daysSinceLast,
                    'is_overdue' => $isOverdue,
                    'days_until_next' => $o->daysUntilNext(),
                ];
            })
            ->values();

        return response()->json(['occurrences' => $occurrences]);
    }

    /**
     * Record "happened now" for an occurrence.
     */
    public function addOccurrenceInstance(Request $request, Occurrence $occurrence, RegisterOccurrence $registerOccurrence): JsonResponse
    {
        abort_if((int) $occurrence->team_id !== (int) $request->user()->current_team_id, 403);

        $registerOccurrence->add(
            $occurrence->team_id,
            $occurrence->name,
            Carbon::now()->format('Y-m-d')
        );

        $occurrence->refresh();

        return response()->json([
            'success' => true,
            'last_date' => $occurrence->last_date?->format('Y-m-d'),
            'occurrence_count' => (int) $occurrence->occurrence_count,
        ]);
    }

    /**
     * Remove the last instance of an occurrence.
     */
    public function removeOccurrenceInstance(Request $request, Occurrence $occurrence, RegisterOccurrence $registerOccurrence): JsonResponse
    {
        abort_if((int) $occurrence->team_id !== (int) $request->user()->current_team_id, 403);

        $registerOccurrence->remove($occurrence->id);

        $occurrence->refresh();

        return response()->json([
            'success' => true,
            'last_date' => $occurrence->last_date?->format('Y-m-d'),
            'occurrence_count' => (int) $occurrence->occurrence_count,
        ]);
    }

    // ─── Insights (Tendencias) ────────────────────────────────────

    /**
     * Financial insights/trends reshaped for the mobile Insights screen.
     * Same calculation as FinanceTrendController::insights, different payload.
     */
    public function insights(Request $request): JsonResponse
    {
        $teamId = $request->user()->current_team_id;

        $months = (int) $request->query('months', 6);
        $months = ($months >= 1 && $months <= 12) ? $months : 6;
        $range = $request->query('range');
        $range = in_array($range, ['1M', '3M', '6M', 'YTD', '1Y'], true) ? $range : null;

        $anchor = $this->resolveInsightsAnchor($request->query('end'));
        if ($range === 'YTD') {
            $months = $anchor->month;
        }

        $endDate = $anchor->copy()->endOfMonth()->format('Y-m-d');
        $startDate = $anchor->copy()->startOfMonth()->subMonths($months - 1)->format('Y-m-d');

        // Monthly income vs expenses
        $incomeExpenses = TransactionService::getIncomeVsExpenses($teamId, max(0, $months - 1), 'month', 'expenses', $anchor);

        // Build month-by-month flow
        $monthlyFlow = [];
        $cursor = Carbon::parse($startDate)->startOfMonth();
        for ($i = 0; $i < $months; $i++) {
            $month = $cursor->format('Y-m-d');
            $expense = (float) collect($incomeExpenses['expenses'] ?? [])->sum(fn ($row) => (float) (string) ($row[$month] ?? 0));
            $income = (float) collect($incomeExpenses['incomes'] ?? [])->sum(fn ($row) => (float) (string) ($row[$month] ?? 0));
            $monthlyFlow[] = ['month' => $month, 'income' => $income, 'expense' => $expense];
            $cursor->addMonth();
        }

        // Net worth (assets vs debts) by month
        $netWorthHistory = collect(TransactionService::getNetWorth($teamId, $startDate, $endDate))->sortByDesc('date_unit');
        $netWorthMonths = [];
        $cursor = Carbon::parse($startDate)->startOfMonth();
        for ($i = 0; $i < $months; $i++) {
            $monthEnd = $cursor->copy()->endOfMonth()->format('Y-m-d');
            $balance = $netWorthHistory->first(fn ($row) => $row->date_unit <= $monthEnd);
            $netWorthMonths[] = [
                'month' => $cursor->format('Y-m-d'),
                'assets' => (float) (string) ($balance->assets ?? 0),
                'debts' => (float) (string) ($balance->debts ?? 0),
            ];
            $cursor->addMonth();
        }

        // Merge flow + net worth into a single months array
        $monthsData = [];
        foreach ($monthlyFlow as $idx => $flow) {
            $monthsData[] = array_merge($flow, [
                'assets' => $netWorthMonths[$idx]['assets'] ?? 0,
                'debts' => $netWorthMonths[$idx]['debts'] ?? 0,
            ]);
        }

        // Expense categories from incomeExpenses (already per-category with totals as arrays)
        // Totals may be BigDecimal objects — cast through string first.
        $expenseCategories = collect($incomeExpenses['expenses'] ?? [])->map(fn ($row) => [
            'id' => $row['id'] ?? null,
            'name' => $row['name'] ?? 'Unknown',
            'total' => (float) (string) ($row['total'] ?? 0),
        ])->filter(fn ($c) => $c['total'] > 0)->sortByDesc('total')->values();

        // Income categories from incomeExpenses
        $incomeCategories = collect($incomeExpenses['incomes'] ?? [])->map(fn ($row) => [
            'id' => $row['id'] ?? null,
            'name' => $row['name'] ?? 'Unknown',
            'total' => (float) (string) ($row['total'] ?? 0),
        ])->filter(fn ($c) => $c['total'] > 0)->sortByDesc('total')->values();

        // Payee breakdowns (grouped by name; payee id not selected by the query)
        $expensePayees = TransactionService::getExpensePayeesInPeriod($teamId, $startDate, $endDate)
            ->groupBy('name')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'total' => (float) (string) $rows->sum('total'),
            ])
            ->values()->sortByDesc('total')->values();

        $incomePayees = TransactionService::getTransactionsByPayeeInPeriod($teamId, $startDate, $endDate, Transaction::DIRECTION_DEBIT)
            ->groupBy('name')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'total' => (float) (string) $rows->sum('total'),
            ])
            ->values()->sortByDesc('total')->values();

        // Spending by account
        $expenseAccounts = TransactionService::getExpensesByAccountInPeriod($teamId, $startDate, $endDate)
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'total' => (float) (string) $r->total])
            ->sortByDesc('total')->values();

        // Credit card summary (same service the web Insights "Tarjetas" tab uses)
        $ccService = app(CreditCardReportService::class);
        $creditCards = $ccService->creditCards($teamId, $endDate, $startDate);

        $creditCardData = [
            'has_credit_cards' => $creditCards['hasCreditCards'],
            'credit_total' => (float) (string) ($creditCards['creditTotal'] ?? 0),
            'credit_total_previous' => (float) (string) ($creditCards['creditTotalPrevious'] ?? 0),
            'credit_total_delta' => (float) (string) ($creditCards['creditTotalDelta'] ?? 0),
            'credit_capacity' => (float) (string) ($creditCards['creditCapacity'] ?? 0),
            'credit_line_usage' => (float) ($creditCards['creditLineUsage'] ?? 0),
            'cards' => collect($creditCards['lastCycleBalances'] ?? [])->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'credit_limit' => (float) (string) ($c->credit_limit ?? 0),
                'cycle_total' => (float) (string) ($c->total ?? 0),
                'cycle_from' => $c->from ?? null,
                'cycle_until' => $c->until ?? null,
            ])->values(),
            'top_categories' => collect($creditCards['topCategoriesByCard'] ?? [])->map(fn ($r) => [
                'card_name' => $r->name,
                'category' => $r->cat_name,
                'total' => (float) (string) ($r->total ?? 0),
            ])->values(),
            'top_payees' => collect($creditCards['topPayeesByCard'] ?? [])->map(fn ($r) => [
                'card_name' => $r->name ?? null,
                'payee' => $r->cat_name ?? null,
                'total' => (float) (string) ($r->total ?? 0),
            ])->values(),
        ];

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'months' => $months,
                'anchor' => $anchor->format('Y-m'),
                'is_current' => $anchor->isSameMonth(Carbon::now()),
            ],
            'months' => $monthsData,
            'expense_categories' => $expenseCategories,
            'income_categories' => $incomeCategories,
            'expense_payees' => $expensePayees,
            'income_payees' => $incomePayees,
            'expense_accounts' => $expenseAccounts,
            'credit_cards' => $creditCardData,
        ]);
    }

    // ─── Notifications ──────────────────────────────────────────

    /**
     * List notifications for the authenticated user.
     *
     * GET /api/mobile/notifications?filter=unread|all&page=1
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $filter = $request->query('filter', 'unread');

        $query = $filter === 'all'
            ? $user->notifications()
            : $user->unreadNotifications();

        $paginated = $query->latest()->paginate(20);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'data' => collect($paginated->items())->map(fn ($n) => [
                'id' => $n->id,
                'type' => class_basename($n->type),
                'message' => $n->data['message'] ?? '',
                'cta' => $n->data['cta'] ?? null,
                'link' => $n->data['link'] ?? null,
                'data' => collect($n->data)->except(['message', 'cta', 'link'])->all(),
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
        ]);
    }

    /**
     * Mark a single notification as read.
     *
     * PUT /api/mobile/notifications/{id}/read
     */
    public function markNotificationRead(Request $request, string $notificationId): JsonResponse
    {
        $request->user()->unreadNotifications()
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     *
     * PATCH /api/mobile/notifications/read-all
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    // ─── Session & account ──────────────────────────────────────

    /**
     * Revoke the current access token (logout).
     *
     * DELETE /api/mobile/session
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Return the authenticated user with mobile-specific fields.
     *
     * GET /api/mobile/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo_url' => $user->profile_photo_url,
            'has_password' => (bool) $user->password,
            'auth_provider' => $user->google_id ? 'google' : ($user->apple_id ? 'apple' : null),
            'language' => $user->language,
            'current_team_id' => $user->current_team_id,
        ]);
    }

    /**
     * Permanently delete the authenticated user's account, their tokens,
     * and — if they are the sole owner — their team and all its data.
     *
     * Password-based accounts: requires { password }.
     * Social-only accounts: requires { provider, id_token } to re-verify identity.
     */
    public function deleteAccount(Request $request, DeletesUsers $deletesUsers): JsonResponse
    {
        $user = $request->user();

        if ($user->password) {
            // Password-based: confirm with password.
            $request->validate(['password' => ['required', 'string']]);

            if (! Hash::check($request->input('password'), $user->password)) {
                return response()->json([
                    'message' => 'The provided password is incorrect.',
                    'errors' => ['password' => ['The provided password is incorrect.']],
                ], 422);
            }
        } else {
            // Social-only: re-verify the provider token.
            $request->validate([
                'provider' => ['required', 'string', 'in:google,apple'],
                'id_token' => ['required', 'string'],
            ]);

            $provider = $request->input('provider');
            $payload = $provider === 'google'
                ? $this->verifyGoogleToken($request->input('id_token'))
                : $this->verifyAppleToken($request->input('id_token'));

            if (! $payload) {
                return response()->json(['message' => 'Invalid provider token.'], 401);
            }

            // Ensure the token's subject matches the user's linked ID.
            $sub = $payload['sub'] ?? null;
            $expected = $provider === 'google' ? $user->google_id : $user->apple_id;

            if (! $sub || $sub !== $expected) {
                return response()->json(['message' => 'Provider identity does not match this account.'], 422);
            }
        }

        // Guard: if the user owns a team with OTHER members, block deletion.
        foreach ($user->ownedTeams as $team) {
            if ($team->allUsers()->count() > 1) {
                return response()->json([
                    'message' => 'You own a team with other members. Transfer ownership before deleting your account.',
                ], 422);
            }
        }

        $deletesUsers->delete($user->fresh());

        return response()->json(['success' => true]);
    }

    // ─── Social auth (store requirement) ──────────────────────

    /**
     * Sign in (or register) with a Google id_token.
     * Verifies the token against Google's tokeninfo endpoint,
     * links by email to an existing user or creates a new one.
     */
    public function authGoogle(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = $this->verifyGoogleToken($request->input('id_token'));

        if (! $payload) {
            return response()->json(['message' => 'Invalid Google token.'], 401);
        }

        $email = $payload['email'];
        $googleId = $payload['sub'];
        $name = $request->input('name') ?? $payload['name'] ?? explode('@', $email)[0];

        $user = User::where('google_id', $googleId)->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            // Link google_id if not already set.
            if (! $user->google_id) {
                $user->update(['google_id' => $googleId]);
            }
        } else {
            // New user — register + create personal team.
            $user = $this->createSocialUser($name, $email, ['google_id' => $googleId]);
        }

        $token = $user->createToken($request->input('device_name'))->plainTextToken;

        return response()->json($token);
    }

    /**
     * Sign in (or register) with an Apple id_token.
     * Verifies the JWT against Apple's public JWKS.
     */
    public function authApple(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = $this->verifyAppleToken($request->input('id_token'));

        if (! $payload) {
            return response()->json(['message' => 'Invalid Apple token.'], 401);
        }

        $appleId = $payload['sub'];
        // Apple only sends email on first auth; may be a private relay address.
        $email = $payload['email'] ?? null;
        $name = $request->input('name');

        $user = User::where('apple_id', $appleId)->first();

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            if (! $user->apple_id) {
                $user->update(['apple_id' => $appleId]);
            }
        } else {
            if (! $email) {
                return response()->json(['message' => 'Email is required for first-time Apple sign in.'], 422);
            }
            $name = $name ?? explode('@', $email)[0];
            $user = $this->createSocialUser($name, $email, ['apple_id' => $appleId]);
        }

        $token = $user->createToken($request->input('device_name'))->plainTextToken;

        return response()->json($token);
    }

    // ─── Email parsing (Share-to-app + Email forwarding) ────────

    /**
     * Share-to-app: mobile user shares a bank email, we parse + create draft.
     *
     * POST /api/mobile/parse-email  (Bearer)
     * { from: "alertas@bhd.com.do", subject?: "...", body: "<html>..." }
     */
    public function parseEmail(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'string'],
            'subject' => ['nullable', 'string'],
            'body' => ['required', 'string'],
        ]);

        return $this->parseAndCreateBankTransaction(
            $request->user(),
            $request->input('from'),
            $request->input('subject', ''),
            $request->input('body')
        );
    }

    /**
     * Inbound email webhook: Mailgun/Postmark forwards bank emails here.
     *
     * POST /api/mobile/inbound-email  (public — user resolved from recipient)
     * Mailgun sends: from, sender, recipient, subject, body-html, body-plain, etc.
     */
    public function inboundEmail(Request $request): JsonResponse
    {
        $recipient = $request->input('recipient', '');
        $user = $this->resolveUserFromInboundAddress($recipient);

        if (! $user) {
            return response()->json(['error' => 'Unknown recipient.'], 404);
        }

        $from = $request->input('from', $request->input('sender', ''));
        $subject = $request->input('subject', '');
        $body = $request->input('body-html',
            $request->input('stripped-html',
                $request->input('body-plain',
                    $request->input('body', ''))));

        if (! $from || ! $body) {
            return response()->json(['error' => 'Missing from or body.'], 422);
        }

        return $this->parseAndCreateBankTransaction($user, $from, $subject, $body);
    }

    /**
     * Return the authenticated user's unique inbound email address.
     *
     * GET /api/mobile/inbound-email/address  (Bearer)
     */
    public function inboundEmailAddress(Request $request): JsonResponse
    {
        $user = $request->user();
        $domain = config('services.inbound_email.domain', 'parse.loger.app');
        $sig = substr(hash_hmac('sha256', (string) $user->id, config('app.key')), 0, 8);

        return response()->json([
            'address' => "txns-{$user->id}-{$sig}@{$domain}",
        ]);
    }

    /**
     * Core logic shared by parse-email and inbound-email.
     *
     * Detects the bank, parses the email through the existing Universal Bank
     * Parser → TransactionCreateEntry pipeline, returns the created transaction.
     */
    private function parseAndCreateBankTransaction(
        User $user,
        string $from,
        string $subject,
        string $body
    ): JsonResponse {
        // Ensure the user's bank automation exists (needed for the parser pipeline).
        $automation = UniversalBankAutomationService::find($user, $user->current_team_id)
            ?? UniversalBankAutomationService::setup($user);

        if (! $automation) {
            return response()->json(['parsed' => false, 'message' => 'Could not set up email parsing.'], 500);
        }

        // Build the same payload shape that GmailReceived produces.
        $payload = [
            'from' => $from,
            'subject' => $subject,
            'message' => $body,
            'id' => 'share-'.now()->timestamp.'-'.random_int(1000, 9999),
            'messageId' => null,
            'date' => now()->toRfc822String(),
        ];

        // Resolve automation tasks (trigger → parser → creator).
        $tasks = $automation->tasks;
        $trigger = $tasks->where('task_type', 'trigger')->first();
        $parserTask = $tasks->where('task_type', 'component')->first();
        $createTask = $tasks->where('task_type', 'action')->first();

        if (! $trigger || ! $parserTask || ! $createTask) {
            return response()->json(['parsed' => false, 'message' => 'Email automation not properly configured.'], 500);
        }

        // Step 1 — parse the email.
        $parsed = UniversalBankParser::handle($automation, $payload, $parserTask, $trigger, $trigger);

        if (! $parsed) {
            return response()->json([
                'parsed' => false,
                'message' => 'Could not recognize the bank email format.',
            ]);
        }

        // Step 2 — create the draft transaction.
        $transaction = TransactionCreateEntry::handle($automation, $parsed, $createTask, $parserTask, $trigger);

        if (! $transaction) {
            return response()->json([
                'parsed' => false,
                'message' => 'Could not create transaction from parsed email.',
            ]);
        }

        return response()->json([
            'parsed' => true,
            'transaction' => [
                'id' => $transaction->id,
                'date' => $transaction->date,
                'payee' => $transaction->payee_name ?? $parsed['payee'] ?? null,
                'amount' => (float) ($parsed['amount'] ?? $transaction->total),
                'currency_code' => $parsed['currencyCode'] ?? $transaction->currency_code,
                'description' => $transaction->description,
                'status' => $transaction->status,
            ],
        ]);
    }

    /**
     * Resolve user from an inbound email recipient address.
     * Format: txns-{userId}-{8-char hmac}@domain
     */
    private function resolveUserFromInboundAddress(string $recipient): ?User
    {
        if (! preg_match('/^txns-(\d+)-([a-f0-9]{8})@/', $recipient, $matches)) {
            return null;
        }

        $userId = (int) $matches[1];
        $sig = $matches[2];
        $expected = substr(hash_hmac('sha256', (string) $userId, config('app.key')), 0, 8);

        if (! hash_equals($expected, $sig)) {
            return null;
        }

        return User::find($userId);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Create a new user from a social provider and set up their personal team.
     */
    private function createSocialUser(string $name, string $email, array $extra): User
    {
        $accept = request()?->getPreferredLanguage(['en', 'es']);
        $language = in_array($accept, ['en', 'es'], true) ? $accept : 'en';

        $user = User::create(array_merge([
            'name' => $name,
            'email' => $email,
            'password' => null,
            'language' => $language,
        ], $extra));

        // Create personal team (same pattern as Jetstream/Fortify registration).
        $teamName = explode(' ', $user->name, 2)[0]."'s Team";
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => $teamName,
            'personal_team' => true,
        ]));

        $user->switchTeam($user->fresh()->ownedTeams()->first());

        return $user->fresh();
    }

    /**
     * Verify a Google id_token via Google's tokeninfo endpoint.
     *
     * @return array|null The decoded payload or null if invalid.
     */
    private function verifyGoogleToken(string $idToken): ?array
    {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);

        if (! $response->ok()) {
            return null;
        }

        $payload = $response->json();

        // Verify the audience matches one of our configured client IDs.
        $allowedClientIds = config('services.google_auth.client_ids', []);
        if (! empty($allowedClientIds) && ! in_array($payload['aud'] ?? '', $allowedClientIds, true)) {
            return null;
        }

        if (empty($payload['email_verified']) || $payload['email_verified'] !== 'true') {
            return null;
        }

        return $payload;
    }

    /**
     * Verify an Apple id_token JWT against Apple's public JWKS.
     *
     * @return array|null The decoded payload or null if invalid.
     */
    private function verifyAppleToken(string $idToken): ?array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            return null;
        }

        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        if (! $header || ! $payload) {
            return null;
        }

        // Verify issuer and audience.
        if (($payload['iss'] ?? '') !== 'https://appleid.apple.com') {
            return null;
        }

        $bundleId = config('services.apple_auth.bundle_id');
        if ($bundleId && ($payload['aud'] ?? '') !== $bundleId) {
            return null;
        }

        // Verify expiration.
        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        // Verify signature against Apple's JWKS.
        $kid = $header['kid'] ?? null;
        if (! $kid || ! $this->verifyAppleJwtSignature($idToken, $kid)) {
            return null;
        }

        return $payload;
    }

    /**
     * Verify the Apple JWT signature using their public JWKS endpoint.
     */
    private function verifyAppleJwtSignature(string $jwt, string $kid): bool
    {
        $jwksResponse = Http::get('https://appleid.apple.com/auth/keys');

        if (! $jwksResponse->ok()) {
            return false;
        }

        $keys = $jwksResponse->json('keys', []);
        $matchedKey = collect($keys)->firstWhere('kid', $kid);

        if (! $matchedKey) {
            return false;
        }

        // Build the public key from JWK components.
        $n = $this->base64UrlDecode($matchedKey['n']);
        $e = $this->base64UrlDecode($matchedKey['e']);

        $publicKey = openssl_pkey_get_public([
            'n' => $n,
            'e' => $e,
            'kty' => 'RSA',
        ]);

        // If openssl can't build from components, use the DER-encoded approach.
        if (! $publicKey) {
            $publicKey = $this->rsaPublicKeyFromComponents($n, $e);
        }

        if (! $publicKey) {
            return false;
        }

        $parts = explode('.', $jwt);
        $dataToVerify = $parts[0].'.'.$parts[1];
        $signature = $this->base64UrlDecode($parts[2]);

        $alg = $matchedKey['alg'] ?? 'RS256';
        $opensslAlg = match ($alg) {
            'RS256' => OPENSSL_ALGO_SHA256,
            'RS384' => OPENSSL_ALGO_SHA384,
            'RS512' => OPENSSL_ALGO_SHA512,
            default => OPENSSL_ALGO_SHA256,
        };

        return openssl_verify($dataToVerify, $signature, $publicKey, $opensslAlg) === 1;
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Build an RSA public key PEM from raw n and e components.
     *
     * @return \OpenSSLAsymmetricKey|false
     */
    private function rsaPublicKeyFromComponents(string $n, string $e)
    {
        // ASN.1 DER encoding of RSA public key
        $modulus = "\x00".$n;
        $modLen = strlen($modulus);
        $expLen = strlen($e);

        $modSeq = "\x02".chr($modLen).$modulus;
        if ($modLen > 127) {
            $modSeq = "\x02"."\x82".pack('n', $modLen).$modulus;
        }

        $expSeq = "\x02".chr($expLen).$e;

        $keySeq = "\x30".chr(strlen($modSeq.$expSeq)).$modSeq.$expSeq;
        if (strlen($modSeq.$expSeq) > 127) {
            $innerLen = strlen($modSeq.$expSeq);
            $keySeq = "\x30"."\x82".pack('n', $innerLen).$modSeq.$expSeq;
        }

        $bitString = "\x03".chr(strlen($keySeq) + 1)."\x00".$keySeq;
        if (strlen($keySeq) + 1 > 127) {
            $bsLen = strlen($keySeq) + 1;
            $bitString = "\x03"."\x82".pack('n', $bsLen)."\x00".$keySeq;
        }

        // RSA algorithm OID
        $oid = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

        $totalSeq = $oid.$bitString;
        $der = "\x30"."\x82".pack('n', strlen($totalSeq)).$totalSeq;

        $pem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n";

        return openssl_pkey_get_public($pem);
    }

    private function resolveInsightsAnchor(?string $end): Carbon
    {
        $now = Carbon::now()->startOfMonth();
        if (! $end || ! preg_match('/^\d{4}-\d{2}$/', $end)) {
            return $now;
        }

        try {
            $anchor = Carbon::createFromFormat('Y-m-d', $end.'-01')->startOfMonth();
        } catch (\Throwable) {
            return $now;
        }

        return $anchor->greaterThan($now) ? $now : $anchor;
    }

    private function toMinutes(string $hhmm): int
    {
        $parts = explode(':', trim($hhmm));

        return ((int) ($parts[0] ?? 0)) * 60 + (int) ($parts[1] ?? 0);
    }
}
