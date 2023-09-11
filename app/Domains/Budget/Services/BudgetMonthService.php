<?php

namespace App\Domains\Budget\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\CategoryData;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Events\BudgetAssigned;
use App\Helpers\RequestQueryHelper;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category as CoreCategory;

class BudgetMonthService {
    function __construct() { }

    public function addTarget(Category $category, mixed $postData) {
        return $category->budget()->create(array_merge($postData, [
            "name" => $category->name ?? $category->display_id,
            "team_id" => $category->team_id
        ]));
    }

    public function updateTarget(Category $category,mixed $postData) {
        return $category->budget()->update(array_merge($postData, [
            "name" => $category->name ?? $category->display_id,
        ]));
    }

    public static function getSavings($teamId, $startDate, $endDate) {
        $startMonth = substr((string) $startDate, 0, 7);
        $endMonth = substr((string) $endDate, 0, 7);

        return DB::query()
        ->where("budget_targets.team_id", $teamId)
        ->whereIn('budget_targets.target_type', ['saving_balance'])
        ->whereRaw("date_format(month, '%Y-%m') <= '$endMonth'")
        ->from('budget_months')
        ->join('budget_targets', 'budget_targets.category_id', 'budget_months.category_id')
        ->sum(DB::raw("budgeted + activity"));
    }


    public function assignBudget(Category $category, string $month, mixed $postData) {
        $amount = (double) $postData['budgeted'];
        $type = $postData['type'] ?? 'budgeted';
        $shouldAggregate = $category->name === BudgetReservedNames::READY_TO_ASSIGN->value|| $type === 'movement';

        $month = BudgetMonth::updateOrCreate([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $month,
            'name' => $month,
        ], [
            'user_id' => $category->user_id,
            'budgeted' => $shouldAggregate ? DB::raw("budgeted + $amount") : $amount,
        ]);

        BudgetAssigned::dispatch($month, $postData);
        return $month;
    }

    public function getMonthByCategory($category, string $month) {
        return BudgetMonth::where([
            "team_id" => $category->team_id,
            "category_id" => $category->id,
            "month" => $month
        ])->first();
    }

}


