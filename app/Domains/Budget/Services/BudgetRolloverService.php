<?php

namespace App\Domains\Budget\Services;

use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category;

class BudgetRolloverService {
    public function rollMonth($teamId, $month, $categories = null) {
        if (!$categories) {
            $categories = Category::where([
                'team_id' => $teamId,
            ])
            ->whereNot('name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->get();
        }

        foreach ($categories as $category) {
            $this->setNewMonthBudget($category, $month);
        }
        $this->moveReadyToAssign($teamId, $month);

    }

    private function setNewMonthBudget($category, $month) {
        $activity = (new BudgetCategoryService($category))->getCategoryActivity($category, $month);
        $budgetMonth = BudgetMonth::where([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $month,
            'name' => $month,
        ])->first();
        $available = ($budgetMonth?->budgeted ?? 0) + ($budgetMonth->left_from_last_month ?? 0) + $activity;
        if ($category->account_id) {
            echo $category->name . ":" . $activity . "/" . $available  . PHP_EOL;
        }
        $this->movePositiveAmounts($category, $month, $available);
        // $this->resetAssignedAmounts();
        // $this->adjustOverspending();
    }

    private function movePositiveAmounts($category, $oldMonth, $available) {
        $nextMonth = Carbon::createFromFormat("Y-m-d", $oldMonth)->addMonthsWithNoOverflow(1)->format('Y-m-d');
        BudgetMonth::updateOrCreate([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $nextMonth,
            'name' => $nextMonth,
        ], [
            'user_id' => $category->user_id,
            'left_from_last_month' => $available ?? 0,

        ]);
    }

    private function moveReadyToAssign($teamId, $month) {
        $readyToAssignCategory = Category::where([
            "name" => BudgetReservedNames::READY_TO_ASSIGN->value,
            "team_id" => $teamId
        ])->first();
        $results = DB::table('budget_months')
        ->where([
            'team_id' => $teamId,
            'month' => $month,
        ])->whereNot('category_id', $readyToAssignCategory->id)
        ->selectRaw("
            coalesce(sum(budgeted), 0) as budgeted
        ")
        ->first();

        $budgetMonth = BudgetMonth::where([
            'category_id' => $readyToAssignCategory->id,
            'team_id' => $readyToAssignCategory->team_id,
            'month' => $month,
            'name' => $month,
        ])->first();

        $activity = (new BudgetCategoryService($readyToAssignCategory))->getCategoryActivity($readyToAssignCategory, $month);
        $activityPlusLeft = $activity + $budgetMonth->left_from_last_month + $budgetMonth->funded_spending;
        $available = $activityPlusLeft - ($results?->budgeted ?? 0);

        $nextMonth = Carbon::createFromFormat("Y-m-d", $month)->addMonthsWithNoOverflow(1)->format('Y-m-d');

        // close current month
        BudgetMonth::updateOrCreate([
            'category_id' => $readyToAssignCategory->id,
            'team_id' => $readyToAssignCategory->team_id,
            'month' => $month,
            'name' => $month,
        ], [
            'user_id' => $readyToAssignCategory->user_id,
            'budgeted' => $results?->budgeted,
            'activity' => $activity,
            'available' => $activityPlusLeft,
        ]);

        //  set left over to the next month
        BudgetMonth::updateOrCreate([
            'category_id' => $readyToAssignCategory->id,
            'team_id' => $readyToAssignCategory->team_id,
            'month' => $nextMonth,
            'name' => $nextMonth,
        ], [
            'user_id' => $readyToAssignCategory->user_id,
            'left_from_last_month' => $available ?? 0,
        ]);
        // If your category had been overspent in cash (negative red Available), that amount will be deducted from Ready to Assign in the new month.

        // If your category had been overspent in credit (negative yellow Available), the amount you overspent will be represented as an Underfunded alert ↗️ in your Credit Card Payment category. If you can't cover this overspending in the month it happens, you'll need to assign funds directly to the Credit Card Payment category to pay back the debt.


        // Not seeing an Underfunded Alert in your Credit Card Payment category? We're testing this new feature in stages and releasing it to everyone soon.
    }

    private function reduceOverspent() {
        // If your category had been overspent in cash (negative red Available), that amount will be deducted from Ready to Assign in the new month.

        // If your category had been overspent in credit (negative yellow Available), the amount you overspent will be represented as an Underfunded alert ↗️ in your Credit Card Payment category. If you can't cover this overspending in the month it happens, you'll need to assign funds directly to the Credit Card Payment category to pay back the debt.


        // Not seeing an Underfunded Alert in your Credit Card Payment category? We're testing this new feature in stages and releasing it to everyone soon.
    }

    private function setReadyToAssign() {

    }

    // transactions with more than 3 days prior to the las recinciled transaction are not imported
}
