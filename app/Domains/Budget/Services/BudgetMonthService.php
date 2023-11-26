<?php

namespace App\Domains\Budget\Services;

use App\Events\BudgetAssigned;
use Illuminate\Support\Facades\DB;
use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Data\BudgetReservedNames;

class BudgetMonthService
{
    public function __construct()
    {
    }

    public static function getSavingsBalance($teamId, $until, $from = null)
    {
        $startDate = $from ? substr((string) $from, 0, 7) : null;
        $endDate = substr((string) $until, 0, 7);

        return DB::query()
            ->where('budget_targets.team_id', $teamId)
            ->whereIn('budget_targets.target_type', ['saving_balance', 'savings_monthly'])
            ->whereRaw("date_format(month, '%Y-%m') <= '$endDate'")
            ->when($from, fn ($q) => $q->whereRaw("date_format(month, '%Y-%m') >= '$startDate'"))
            ->from('budget_months')
            ->join('budget_targets', 'budget_targets.category_id', 'budget_months.category_id')
            ->sum(DB::raw('budgeted + activity'));
    }

    public function getMonthByCategory($category, string $month)
    {
        return BudgetMonth::where([
            'team_id' => $category->team_id,
            'category_id' => $category->id,
            'month' => $month,
        ])->first();
    }
}
