<?php

namespace App\Domains\Budget\Http\Controllers;

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
            'index' => 'Finance/Budgets',
        ];
        $this->searchable = ['name'];
        $this->validationRules = [
            'name' => 'required|string|max:255|unique:categories',
        ];
        $this->sorts = [];
        $this->includes = ['subCategories', 'subCategories.budget', 'subCategories.budgets'];
        $dates = $this->getFilterDates();
        $this->filters = [
            'parent_id' => '$null',
            'resource_type' => 'transactions',
            'date' => "{$dates['0']}~{$dates['1']}",
        ];
        $this->resourceName = 'budgets';
    }
}
