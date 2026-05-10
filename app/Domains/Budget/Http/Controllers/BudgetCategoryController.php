<?php

namespace App\Domains\Budget\Http\Controllers;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetDefaultCategory;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Budget\Services\BudgetAccountService;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Budget\Services\BudgetTargetService;
use App\Domains\Today\Services\TodayService;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Transaction\Services\TransactionService;
use App\Http\Resources\CategoryGroupCollection;
use App\Models\Setting;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class BudgetCategoryController extends InertiaController
{
    protected $authorizedUser = false;

    protected $authorizedTeam = true;

    public function __construct(Category $category, public BudgetAccountService $accountService)
    {
        $this->model = $category;
        $this->templates = [
            'index' => 'Finance/Budget',
            'edit' => 'Finance/BudgetCategory',
            'show' => 'Finance/BudgetCategory',
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required|string|max:255|unique:categories',
        ];
        $this->sorts = ['index'];
        $this->includes = [];
        $this->filters = [
            'parent_id' => '$null',
            'resource_type' => 'transactions',
        ];
        $this->resourceName = 'budgets';
    }

    protected function index(Request $request)
    {
        $resourceName = $this->resourceName ?? $this->model->getTable();
        $queryParams = request()->query();
        $teamId = auth()->user()->current_team_id;
        $settings = Setting::getByTeam($teamId);
        $timeZone = $settings['team_timezone'] ?? config('app.timezone');
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters, $timeZone);

        $resources = $this->parser($this->getModelQuery($request, null, function ($model) use ($startDate) {
            return $model->withCurrentSavings($startDate);
        }));

        return inertia($this->templates['index'],
            [
                $resourceName => $resources,
                'serverSearchOptions' => $this->getServerParams(),
                'accountTotal' => $this->accountService->getBalanceAs($teamId, $endDate),
                'scheduledTotal' => $this->getScheduledTotal($teamId, $startDate, $endDate),
                'budgetDefaults' => BudgetCategoryService::getDefaultsByRole($teamId),
                'categoryAverages' => TransactionService::getCategoryAverages($teamId, 3),
            ]);
    }

    public function moveToGroup(Request $request, Category $category, Category $group): RedirectResponse
    {
        $teamId = auth()->user()->current_team_id;

        abort_unless(
            $category->team_id === $teamId && $group->team_id === $teamId,
            403
        );
        abort_unless($group->parent_id === null, 422, 'Target must be a group.');

        $index = $request->input('index', $group->subCategories()->count());

        $category->update([
            'parent_id' => $group->id,
            'index' => $index,
        ]);

        return Redirect::back();
    }

    public function setDefaultRole(Request $request, BudgetCategoryService $service, Category $category)
    {
        $data = $request->validate([
            'role' => ['nullable', 'string', Rule::in(BudgetDefaultCategory::SUPPORTED_ROLES)],
        ]);

        $service->setDefaultRole($category, $data['role'] ?? null);

        return Redirect::back();
    }

    protected function getScheduledTotal(int $teamId, string $startDate, string $endDate): float
    {
        return app(NextPaymentsService::class)
            ->getNextPayments($teamId, $startDate)
            ->filter(fn ($payment) => isset($payment['due_date'])
                && $payment['due_date'] >= $startDate
                && $payment['due_date'] <= $endDate
            )
            ->sum('total');
    }

    /**
     * JSON payload for the right-side Budget widget. Built around three
     * decision-shaped sections, not just status numbers:
     *   - topCategories: "can I spend on X right now?" (top-5-by-activity +
     *     each one's available amount this month)
     *   - money + upcoming: "did I log today / what's coming?"
     *   - overspending: existing alert list, kept for completeness
     */
    public function summary(Request $request, TodayService $todayService, NextPaymentsService $nextPayments): JsonResponse
    {
        $teamId = $request->user()->current_team_id;
        $settings = Setting::getByTeam($teamId);
        $timeZone = $settings['team_timezone'] ?? config('app.timezone');
        $today = Carbon::now($timeZone);
        $monthStart = $today->copy()->startOfMonth()->format('Y-m-d');

        $money = $todayService->money($teamId, $today);

        $overspending = BudgetMonth::getMonthOverspendingCategories($teamId, $monthStart);

        return response()->json([
            'money' => $money,
            'topCategories' => $this->topSpendCategoriesWithAvailable($teamId, $monthStart),
            'upcoming' => $this->upcomingBills($teamId, $today, $nextPayments),
            'overspending' => $overspending,
        ]);
    }

    /**
     * Top-5 spending categories ranked by 3-month average, joined with this
     * month's available amount. Answers "can I spend on Y right now?".
     *
     * @return array<int, array{id:int,name:string,group:?string,available:float,monthly_avg:float}>
     */
    private function topSpendCategoriesWithAvailable(int $teamId, string $monthStart): array
    {
        $averages = TransactionService::getCategoryAverages($teamId, 3);
        if (empty($averages)) {
            return [];
        }

        arsort($averages);
        $topIds = array_slice(array_keys($averages), 0, 5, true);

        $rows = BudgetMonth::query()
            ->where('budget_months.team_id', $teamId)
            ->where('month', $monthStart)
            ->whereIn('category_id', $topIds)
            ->join('categories', 'categories.id', 'budget_months.category_id')
            ->leftJoin('categories as g', 'g.id', 'categories.parent_id')
            ->get([
                'budget_months.category_id as id',
                'categories.name as name',
                'g.name as group_name',
                'budget_months.available as available',
            ])
            ->keyBy('id');

        // Preserve the average-ranked ordering. Categories without a budget row
        // for the current month still show — `available` defaults to 0 so the
        // user sees the gap rather than the row vanishing.
        $result = [];
        foreach ($topIds as $id) {
            $row = $rows->get($id);
            $result[] = [
                'id' => (int) $id,
                'name' => $row?->name ?? '—',
                'group' => $row?->group_name,
                'available' => (float) ($row?->available ?? 0),
                'monthly_avg' => (float) $averages[$id],
            ];
        }

        return $result;
    }

    /**
     * Bills due in the next 7 days. Slim payload — name, total, due_at, and
     * `days_until` so the widget can render "Internet (Tue)" without parsing
     * dates client-side.
     *
     * @return array{count:int, total:float, items:array<int,array<string,mixed>>}
     */
    private function upcomingBills(int $teamId, Carbon $today, NextPaymentsService $nextPayments): array
    {
        $cutoff = $today->copy()->addDays(7)->endOfDay();

        $items = $nextPayments->getNextPayments($teamId, $today->format('Y-m-d'))
            ->filter(function ($payment) use ($today, $cutoff) {
                $due = $payment['due_date'] ?? null;
                if (! $due) {
                    return false;
                }
                $dueAt = Carbon::parse($due);

                return $dueAt->between($today->copy()->startOfDay(), $cutoff);
            })
            ->take(5)
            ->map(function ($payment) use ($today) {
                $dueAt = Carbon::parse($payment['due_date']);

                return [
                    'name' => $payment['name'] ?? $payment['description'] ?? '—',
                    'total' => (float) ($payment['total'] ?? 0),
                    'due_at' => $dueAt->format('Y-m-d'),
                    'days_until' => (int) $today->copy()->startOfDay()->diffInDays($dueAt->startOfDay(), false),
                ];
            })
            ->values()
            ->all();

        return [
            'count' => count($items),
            'total' => array_sum(array_column($items, 'total')),
            'items' => $items,
        ];
    }

    protected function budgetAlerts()
    {
        $queryParams = request()->query();
        $teamId = auth()->user()->current_team_id;
        $settings = Setting::getByTeam($teamId);
        $timeZone = $settings['team_timezone'] ?? config('app.timezone');
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate] = $this->getFilterDates($filters, $timeZone);

        return BudgetMonth::getMonthOverspendingCategories($teamId, $startDate);
    }

    public function show(int $categoryId)
    {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);
        $category = Category::with(['budget'])->find($categoryId);
        $statStartDate = now()->subMonth(3)->startOfMonth()->format('Y-m-d');

        $stats = ReportService::getExpensesByCategoriesInPeriod(
            $category->team_id,
            $statStartDate,
            $endDate,
            [$category->id]
        )->groupBy('date')->map(function ($monthItems) {
            return [
                'date' => $monthItems->first()->date,
                'total' => $monthItems->sum('total'),
            ];
        })->sortBy('date');

        return inertia($this->templates['show'], [
            'sectionTitle' => $category->name,
            'categoryId' => $category->id,
            'resource' => BudgetCategoryService::withBudgetInfo($category),
            'transactions' => $category->resource_type_id ? $category->creditLines()->whereBetween('date', [
                $startDate,
                $endDate,
            ])->get() : $category->transactionLines()->whereBetween('date', [
                $startDate,
                $endDate,
            ])->get(),
            'stats' => $stats,
        ]);
    }

    // Category Budget targets
    public function addCategoryBudget(Request $request, $categoryId, BudgetTargetService $budgetTargetService)
    {
        $category = Category::find($categoryId);

        $budgetTargetService->add(
            $category,
            request()->user(),
            $request->post()
        );

        return Redirect::back();
    }

    public function updateCategoryBudget($categoryId, BudgetTargetService $budgetTargetService)
    {
        $category = Category::find($categoryId);
        $budgetTargetService->update(
            $category,
            $category->budget,
            request()->user(),
            request()->post()
        );

        return Redirect::back();
    }

    // Parsing and formatting
    protected function parser($results)
    {
        return CategoryGroupCollection::collection($results);
    }

    // Validations
    protected function validateDelete(Request $request, $resource)
    {
        $transactions = Transaction::where('category_id', $resource->id)->count();
        $budgetMovements = BudgetMovement::where('destination_category_id', $resource->id)
            ->orWhere('source_category_id', $resource->id)->count();
        if ($transactions > 0 || $budgetMovements > 0 || $resource->subCategories->count() > 0) {
            return false;
        }

        return true;
    }

    protected function getValidationRules($postData)
    {
        return $this->validationRules = [
            'name' => 'required|string|max:255|',
            Rule::unique('categories')->where(fn ($query) => $query->where('team_id', $postData['team_id'])),
        ];
    }
}
