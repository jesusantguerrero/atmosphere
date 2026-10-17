<?php

namespace App\Http\Controllers\Api;

use App\Domains\AppCore\Models\Category;
use App\Domains\AppCore\Models\Planner;
use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Data\BudgetMovementData;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Budget\Services\BudgetMovementService;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealType;
use App\Domains\Meal\Services\MealService;
use App\Domains\Today\Services\CalendarService;
use App\Domains\Today\Services\TodayService;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Domains\Transaction\Services\TransactionService;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlannedMealResource;
use App\Models\Account;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    private function toMinutes(string $hhmm): int
    {
        $parts = explode(':', trim($hhmm));

        return ((int) ($parts[0] ?? 0)) * 60 + (int) ($parts[1] ?? 0);
    }
}
