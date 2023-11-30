<?php

namespace App\Domains\Budget\Services;

use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Data\BudgetReservedNames;

class BudgetRolloverService {
    public function __construct(private BudgetCategoryService $budgetCategoryService) {}

    public function rollMonth($teamId, $month, $categories = null) {
        if (!$categories) {
            $categories = Category::where([
                'team_id' => $teamId,
            ])
            ->whereNot('name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->get();
        }
        $overspending = 0;
        $fundedFromBudgets = 0;
        foreach ($categories as $category) {
            if($category->account_id) {
                $fundedFromBudgets += $this->budgetCategoryService->updateFundedSpending($category, $month);
            }
            $overspendingInCat = $this->setMonthBudget($category, $month);
            if ($overspendingInCat < 0) {
                $overspending += abs($overspendingInCat);
            }
        }
        $this->moveReadyToAssign($teamId, $month, $overspending, $fundedFromBudgets);

    }

    private function setMonthBudget($category, $month) {
        $activity = (new BudgetCategoryService($category))->getCategoryActivity($category, $month);

        $budgetMonth = BudgetMonth::where([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $month,
            'name' => $month,
        ])->first();

        if ($budgetMonth->category->account_id) {
            $available = (- $budgetMonth->payments);
            $available = Money::of($budgetMonth->funded_spending, $category->account->currency_code)
                ->plus($budgetMonth->left_from_last_month)
                ->minus(($budgetMonth->payments))
                ->getAmount()
                ->toFloat();
        } else {
            $available = ($budgetMonth?->budgeted ?? 0) + ($budgetMonth->left_from_last_month ?? 0) -  abs($activity);
        }
         // close current month
        $budgetMonth->update([
            'activity' => $activity,
            'available' => $available,
        ]);

        $nextMonth = Carbon::createFromFormat("Y-m-d", $month)->addMonthsWithNoOverflow(1)->format('Y-m-d');
        BudgetMonth::updateOrCreate([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $nextMonth,
            'name' => $nextMonth,
        ], [
            'user_id' => $category->user_id,
            'left_from_last_month' => $available ?? 0,
        ]);
        return $available;

    }

    private function moveReadyToAssign($teamId, $month, $overspending = 0, $fundedFromBudgets = 0) {
        $readyToAssignCategory = Category::where([
            "name" => BudgetReservedNames::READY_TO_ASSIGN->value,
            "team_id" => $teamId
        ])->first();

        $results = DB::table('budget_months')
        ->where([
            'budget_months.team_id' => $teamId,
            'month' => $month,
        ])->whereNot('category_id', $readyToAssignCategory->id)
        ->join('categories', 'categories.id', 'budget_months.category_id')
        ->selectRaw("
            coalesce(sum(budgeted), 0) as budgeted,
            coalesce(sum(activity), 0) as budgetsActivity,
            coalesce(sum(payments), 0) as payments,
            coalesce(sum(funded_spending), 0) as funded_spending
        ")->groupBy('month')
        ->first();

        $budgetMonth = BudgetMonth::where([
            'category_id' => $readyToAssignCategory->id,
            'team_id' => $readyToAssignCategory->team_id,
            'month' => $month,
            'name' => $month,
        ])->first();

        $inflow = (new BudgetCategoryService($readyToAssignCategory))->getCategoryActivity($readyToAssignCategory, $month);
        $positiveAmount = $budgetMonth->left_from_last_month +  $inflow + $results?->funded_spending;
        $available = $positiveAmount -  $results?->budgeted ;

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
            'activity' => $inflow,
            'available' => $available,
            'funded_spending' => $results?->funded_spending ?? 0,
            'payments' => $results?->payments ?? 0,
        ]);

        //  set left over to the next month
        BudgetMonth::updateOrCreate([
            'category_id' => $readyToAssignCategory->id,
            'team_id' => $readyToAssignCategory->team_id,
            'month' => $nextMonth,
            'name' => $nextMonth,
        ], [
            'user_id' => $readyToAssignCategory->user_id,
            'left_from_last_month' => $inflow - $results?->budgeted,
            'overspending_previous_month' => $available < 0 ? $available : $available,
        ]);
    }

    private function fixBudgetMovements($budgetMonth) {
        (new BudgetMovementService(new BudgetCategoryService()))->registerAssignment(new BudgetAssignData(
                $budgetMonth->team_id,
                $budgetMonth->user_id,
                $budgetMonth->month,
                $budgetMonth->category_id,
                $budgetMonth->budgeted,
        ), true);
    }

    private function reduceOverspent() {
        // If your category had been overspent in cash (negative red Available), that amount will be deducted from Ready to Assign in the new month.

        // If your category had been overspent in credit (negative yellow Available), the amount you overspent will be represented as an Underfunded alert ↗️ in your Credit Card Payment category. If you can't cover this overspending in the month it happens, you'll need to assign funds directly to the Credit Card Payment category to pay back the debt.


        // Not seeing an Underfunded Alert in your Credit Card Payment category? We're testing this new feature in stages and releasing it to everyone soon.
    }

    public function startFrom($teamId, $date) {
        $categories = Category::where([
            'team_id' => $teamId,
            ])
            ->whereNot('name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->get();


        $monthsWithTransactions = DB::table('transaction_lines')
        ->selectRaw("date_format(transaction_lines.date, '%Y-%m') AS date")
        ->groupBy(DB::raw("date_format(transaction_lines.date, '%Y-%m')"))
        ->whereRaw("date_format(transaction_lines.date, '%Y-%m') >= ?", [$date])
        ->get()
        ->pluck('date');

        $total = count($monthsWithTransactions);
        $count = 0;
        foreach ($monthsWithTransactions as $month) {
            $count++;
            $this->rollMonth($teamId, $month."-01", $categories);
            echo "updated month {$month}".PHP_EOL;
            echo "{$count} of {$total}".PHP_EOL.PHP_EOL;
        }
    }

    // transactions with more than 3 days prior to the las recinciled transaction are not imported
}
