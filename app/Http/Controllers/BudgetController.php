<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Atmosphere\Http\InertiaController;
use Illuminate\Http\Request;
use Insane\Journal\Category;

class BudgetController extends InertiaController
{
    public function __construct(Budget $budget)
    {
        $this->model = $budget;
        $this->templates = [
            "index" => 'Budget',
            "create" => 'BudgetCreate',
            "edit" => 'BudgetCreate'
        ];
        $this->searchable = ['name'];
        $this->validationRules = [];
        $this->includes = ['account'];
        $this->filters = [];
    }

    protected function getIndexProps(Request $request)
    {
        $queryParams = $request->query() ?? [];
        $queryParams['limit'] = $queryParams['limit'] ?? 50;
        $teamId = $request->user()->current_team_id;

        return [
            'budgets' => $this->getModelQuery($request),
            "categories" => Category::where([
                'depth' => 0,
                'display_id' => 'expenses'
            ])->with([
                'subCategories',
                'subcategories.accounts' => function ($query) use ($teamId) {
                    $query->where('team_id', '=', $teamId);
                }
            ])->get(),
        ];
    }
}
