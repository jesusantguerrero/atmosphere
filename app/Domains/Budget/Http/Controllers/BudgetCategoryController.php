<?php

namespace App\Domains\Budget\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Domains\AppCore\Models\Category;
use Illuminate\Support\Facades\Redirect;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Transaction\Models\Transaction;
use App\Http\Resources\CategoryGroupCollection;
use Freesgen\Atmosphere\Http\InertiaController;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Budget\Services\BudgetTargetService;
use App\Domains\Budget\Services\BudgetAccountService;
use App\Domains\Budget\Services\BudgetCategoryService;

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

    protected function index(Request $request) {
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
            $resourceName => $this->parser($resources),
            "serverSearchOptions" => $this->getServerParams(),
            "accountTotal" => $this->accountService->getBalanceAs($teamId, $endDate),
            "distribution" => fn () => BudgetMonth::getMonthAssignmentTotal($teamId, $startDate)
        ]);
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
