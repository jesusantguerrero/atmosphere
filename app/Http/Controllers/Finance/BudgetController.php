<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Budget\Models\Budget;
use Freesgen\Atmosphere\Http\InertiaController;

class BudgetController extends InertiaController
{
    protected $authorizedUser = false;
    protected $authorizedTeam = true;

    public function __construct(Budget $budget)
    {
        $this->model = $budget;
        $this->templates = [
            "index" => 'Finance/Budgets'
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required|string|max:255|unique:categories',
        ];
        $this->sorts = [];
        $this->includes = ['subCategories', 'subCategories.budget', 'subCategories.budgets'];
        $this->filters = [
            'parent_id' => '$null',
            'resource_type' => 'transactions',
            'data' => date('Y-m-01')
        ];
        $this->resourceName= "budgets";
    }
}
