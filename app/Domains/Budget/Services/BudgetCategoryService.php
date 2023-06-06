<?php

namespace App\Domains\Budget\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Helpers\RequestQueryHelper;
use Illuminate\Support\Carbon;
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
        ->whereIn('budget_targets.target_type', ['saving_balance'])
        ->whereRaw("date_format(month, '%Y-%m') <= '$endMonth'")
        ->from('budget_months')
        ->join('budget_targets', 'budget_targets.category_id', 'budget_months.category_id')
        ->sum(DB::raw("budgeted + activity"));
    }

    public static function getNextBudgetItems($teamId) {
       BudgetTarget::getNextTargets($teamId);
    }


    public static function withBudgetInfo(Category $category) {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [ $startDate ] = RequestQueryHelper::getFilterDates($filters);

        $month = $startDate ?? date('Y-m-01');
        return array_merge($category->toArray(), [ 'month' => $month ], $category->getBudgetInfo($month));
    }

    public function updateActivity(Category $category, string $month) {
        $monthDate = Carbon::createFromFormat("Y-m-d", $month);
        $activity = $category->getMonthBalance($monthDate->format('Y-m'))?->balance;

        BudgetMonth::updateOrCreate([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $month,
            'name' => $month,
        ], [
            'user_id' => $category->user_id,
            'activity' => $activity
        ]);

        echo "{$category->name} updated to {$activity}" . PHP_EOL;
    }
}


