<?php

namespace App\Http\Controllers\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Transaction\Models\Transaction;
use App\Http\Resources\CategoryGroupCollection;
use Freesgen\Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class BudgetCategoryController extends InertiaController
{
    protected $authorizedUser = false;
    protected $authorizedTeam = true;

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
            'resource_type' => 'transactions',
            'data' => date('Y-m-01')
        ];
        $this->resourceName= "budgets";
    }

    // Category Budget targets
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
            Rule::unique('categories',)->where(fn ($query) => $query->where('team_id', $postData['team_id'])),
        ];
    }
}
