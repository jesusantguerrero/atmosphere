<?php

namespace App\Http\Controllers\Api;

use App\Domains\AppCore\Models\Category;
use App\Domains\AppCore\Models\Planner;
use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Data\BudgetMovementData;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Budget\Services\BudgetMovementService;
use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Integration\Actions\TransactionCreateEntry;
use App\Domains\Integration\Actions\UniversalBankParser;
use App\Domains\Integration\Services\UniversalBankAutomationService;
use App\Domains\Journal\Actions\TransactionDelete;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealType;
use App\Domains\Meal\Services\MealService;
use App\Domains\Today\Services\CalendarService;
use App\Domains\Today\Services\TodayService;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\CreditCardReportService;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Domains\Transaction\Services\TransactionService;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlannedMealResource;
use App\Models\Account;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Jetstream\Contracts\DeletesUsers;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
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
                        $data = $service->getBudgetData($cat, $month);
                    } catch (\Throwable) {
                        $data = [];
                    }

                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'budgeted' => (float) ($data['budgeted'] ?? 0),
                        'activity' => (float) ($data['activity'] ?? 0),
                        'available' => (float) ($data['available'] ?? 0),
                        'left_from_last_month' => (float) ($data['left_from_last_month'] ?? 0),
                        'funded_spending' => (float) ($data['funded_spending'] ?? 0),
                        'payments' => (float) ($data['payments'] ?? 0),
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

        $totalAssigned = $groups->sum('budgeted');
        $readyToAssign = (float) ($inflowData['activity'] ?? 0)
            + (float) ($inflowData['left_from_last_month'] ?? 0)
            - $totalAssigned;

        return response()->json([
            'month' => substr($month, 0, 7),
            'ready_to_assign' => round($readyToAssign, 2),
            'groups' => $groups,
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
            ],
        ], 201);
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

        return response()->json([
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
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

        return response()->json([
            'id' => $item->id,
            'state' => $item->state,
            'is_done' => (bool) $item->is_done,
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

    // ─── Account deletion (store requirement) ───────────────────

    /**
     * Permanently delete the authenticated user's account, their tokens,
     * and — if they are the sole owner — their team and all its data.
     */
    public function deleteAccount(Request $request, DeletesUsers $deletesUsers): JsonResponse
    {
        $user = $request->user();

        // If the user has a password (not social-only), require it for confirmation.
        if ($user->password) {
            $request->validate(['password' => ['required', 'string']]);

            if (! Hash::check($request->input('password'), $user->password)) {
                return response()->json([
                    'message' => 'The provided password is incorrect.',
                    'errors' => ['password' => ['The provided password is incorrect.']],
                ], 422);
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
