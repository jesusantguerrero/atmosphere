<?php

namespace App\Domains\Budget\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\Budget;
use Illuminate\Support\Facades\DB;

class BudgetCategoryService {
    function __construct(Category $category)
    {
        $this->model = $category;
    }


    public function addTarget($postData) {
        return $this->model->budget()->create(array_merge($postData, [
            "name" => $this->model->name ?? $this->model->display_id,
            "team_id" => $this->model->team_id
        ]));
    }

    public function updateTarget($postData) {
        return $this->model->budget()->update(array_merge($postData, [
            "name" => $this->model->name ?? $this->model->display_id,
        ]));
    }

    public static function getSavings($teamId, $startDate, $endDate) {
        $startMonth = substr((string) $startDate, 0, 7);
        $endMonth = substr((string) $endDate, 0, 7);

        return DB::query()
        ->whereIn('budgets.target_type', ['saving_balance'])
        ->whereRaw("date_format(month, '%Y-%m') <= '$endMonth'")
        ->from('budget_months')
        ->join('budgets', 'budgets.category_id', 'budget_months.category_id')
        ->sum(DB::raw("budgeted + activity"));
    }

    public static function getNextBudgetItems($teamId) {
       Budget::getNextTargets($teamId);
    }
}
