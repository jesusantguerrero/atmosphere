<?php

namespace App\Http\Controllers\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Budget\Services\BudgetAccountService;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\ReportService;
use App\Http\Resources\CategoryGroupCollection;
use App\Models\Setting;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
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
        $this->includes = ['subCategories', 'subCategories.budget', 'subCategories.budgets'];
        $this->filters = [
            'parent_id' => '$null',
            'resource_type' => 'transactions',
        ];
        $this->resourceName = 'budgets';
    }

    protected function getIndexProps(Request $request, $resources = null): array
    {
        $queryParams = request()->query();
        $settings = Setting::getByTeam(auth()->user()->current_team_id);
        $timeZone = $settings['team_timezone'] ?? config('app.timezone');
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters, $timeZone);

        $accountTotalBalance = $this->accountService->getBalanceAs($request->user()->current_team_id, $endDate);

        return [
            'accountTotal' => $accountTotalBalance,
        ];
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
    public function addCategoryBudget(Request $request, $categoryId)
    {
        $category = Category::find($categoryId);
        $postData = $request->post();
        $category->budget()->create(array_merge($postData, [
            'name' => $category->name,
            'user_id' => $request->user()->id,
            'team_id' => $request->user()->current_team_id,
        ]));

        return Redirect::back();
    }

    public function updateCategoryBudget(Request $request, $categoryId)
    {
        $category = Category::find($categoryId);
        $postData = $request->post();
        $category->budget->update($postData);

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
