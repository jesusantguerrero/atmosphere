<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryGroupCollection;
use App\Models\BudgetMovement;
use App\Models\Category;
use App\Models\Planner;
use App\Models\Transaction;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class BudgetCategoryController extends InertiaController
{
    protected $autorizedUser = false;
    protected $autorizedTeam = true;

    public function __construct(Category $category)
    {
        $this->model = $category;
        $this->templates = [
            "index" => 'Finance/Budget'
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required|string|max:255|unique:categories',
        ];
        $this->sorts = ['index'];
        $this->includes = ['subCategories', 'subCategories.budget', 'subCategories.budgets'];
        $this->filters = [
            'parent_id' => '$null',
            'resource_type' => 'transactions'
        ];
    }

    protected function getIndexProps(Request $request)
    {
        $queryParams = $request->query() ?? [];
        $queryParams['limit'] = $queryParams['limit'] ?? 50;
        $queryParams['date'] = $queryParams['date'] ?? date('Y-m-01');
        $budget = CategoryGroupCollection::collection($this->getModelQuery($request));

        return [
            'budgets' => $budget
        ];
    }

    protected function validateDelete(Request $request, $resource)
    {
        $transactions = Transaction::where('transaction_category_id', $resource->id)->count();
        $budgetMovements = BudgetMovement::where('destination_category_id', $resource->id)
        ->orWhere('source_category_id', $resource->id)->count();
        if ($transactions > 0 || $budgetMovements > 0 || $resource->subCategories->count() > 0) {
            return false;
        }
        return true;
    }

    public function addCategoryBudget(Request $request, $categoryId)
    {
        $category = Category::find($categoryId);
        $postData = $request->post();
        $category->budget()->create(array_merge($postData,[
            'name' => $category->name,
            'user_id' => $request->user()->id,
            'team_id' => $request->user()->current_team_id
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

    public function assignMonthBudget(Request $request, $categoryId, $month)
    {
        $category = Category::find($categoryId);
        $postData = $request->post();
        $monthBalance = $category->assignBudget($month, $postData);
        BudgetMovement::registerMovement($monthBalance, $postData);
        return Redirect::back();
    }

    protected function getValidationRules($postData)
    {
        return $this->validationRules = [
            'name' => 'required|string|max:255|',
            Rule::unique('categories',)->where(fn ($query) => $query->where('team_id', $postData['team_id'])),
        ];
    }
}
