<?php

namespace App\Domains\Budget\Services;

use Exception;
use App\Models\Team;
use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Data\BudgetReservedNames;
use Insane\Journal\Models\Core\AccountDetailType;

class BudgetRolloverService {
    private Team|null $team = null;
    private mixed $accounts = [];

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
        $overspendingCategories = [];

        foreach ($categories as $category) {
            if($category->account_id) {
                $fundedFromBudgets += $this->budgetCategoryService->updateFundedSpending($category, $month);
            }
            $available = $this->getAvailableInMonth($category, $month);
            $this->setMonthBudget($category, $month, $available);
            if ($available < 0) {
                $overspending += abs($available);
                $overspendingCategories[] = $category->display_id;
            }
        }

        $this->moveReadyToAssign($teamId, $month, $overspending, $fundedFromBudgets);
    }

    private function getAvailableInMonth($category, $month) {
        $activity = (new BudgetCategoryService($category))->getCategoryActivity($category, $month);

        $budgetMonth = BudgetMonth::where([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $month,
            'name' => $month,
        ])->first();

        if (!$budgetMonth) {
            return;
        }


        if ($budgetMonth->category->account_id) {
            $available = Money::of($budgetMonth->left_from_last_month, $category->account->currency_code,null,  RoundingMode::HALF_UP)
                ->plus($budgetMonth->budgeted, RoundingMode::HALF_UP)
                ->plus($budgetMonth->funded_spending, RoundingMode::HALF_UP)
                ->minus(($budgetMonth->payments), RoundingMode::HALF_UP)
                ->getAmount()
                ->toFloat();

            $activity = Money::of($budgetMonth->funded_spending, $category->account->currency_code, null, RoundingMode::HALF_UP)
            ->minus($budgetMonth->payments)
            ->getAmount()
            ->toFloat();
        } else {
            $available = Money::Of($budgetMonth?->budgeted ?? 0, 'USD', null, RoundingMode::HALF_UP)
            ->plus(($budgetMonth->left_from_last_month ?? 0), RoundingMode::HALF_UP)
            ->minus(abs($activity), RoundingMode::HALF_UP)
            ->getAmount()
            ->toFloat();
        }

        if ($category->id == 754) {
            echo "The available amount for $category->name is $available";
        }

        // close current month
        $budgetMonth->update([
            'activity' => $activity,
            'available' => $available,
        ]);

        return $available;
    }

    private function setMonthBudget($category, $month, $available = 0) {
        $nextMonth = Carbon::createFromFormat("Y-m-d", $month)->addMonthsWithNoOverflow(1)->format('Y-m-d');
        BudgetMonth::updateOrCreate([
            'category_id' => $category->id,
            'team_id' => $category->team_id,
            'month' => $nextMonth,
            'name' => $nextMonth,
        ], [
            'user_id' => $category->user_id,
            'left_from_last_month' => $available > 0 ? $available : 0,
        ]);
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
        ->whereNotNull('categories.parent_id')
        ->join('categories', 'categories.id', 'budget_months.category_id')
        ->join(DB::raw('categories g'), 'g.id', 'categories.parent_id')
        ->selectRaw("
            coalesce(sum(budgeted), 0) as budgeted,
            coalesce(sum(activity), 0) as budgetsActivity,
            coalesce(sum(payments), 0) as payments,
            sum(coalesce(available, 0)) as available,
            sum(CASE WHEN available < 0 THEN available ELSE 0 END) as overspendingInMonth,
            group_concat(g.index, '.', categories.index, ':', categories.name, ':', available) as description,
            coalesce(sum(funded_spending), 0) as funded_spending
        ")
        ->orderBy(DB::raw('concat(g.index, ".", categories.index)'))
        ->groupBy('month')
        ->first();

        $budgetMonth = BudgetMonth::where([
            'category_id' => $readyToAssignCategory->id,
            'team_id' => $readyToAssignCategory->team_id,
            'month' => $month,
            'name' => $month,
        ])->first();


        $inflow = (new BudgetCategoryService($readyToAssignCategory))->getCategoryInflow($readyToAssignCategory, $month);
        $TBB = $budgetMonth->left_from_last_month + $inflow;

        $nextMonth = Carbon::createFromFormat("Y-m-d", $month)->addMonthsWithNoOverflow(1)->format('Y-m-d');
        $overspending = abs($results?->overspendingInMonth);
        $leftover = $TBB - $results?->budgeted;

        $available = Money::of($budgetMonth->left_from_last_month, "DOP", null, RoundingMode::HALF_UP)
        ->plus($results->budgeted, RoundingMode::HALF_UP)
        ->plus($results->funded_spending, RoundingMode::HALF_UP)
        ->minus(($results->payments), RoundingMode::HALF_UP)
        ->getAmount()
        ->toFloat();


        echo "TBB: " . $TBB . " budgeted: " . $results?->budgeted . " Available: " . $available . " Leftover: ". $leftover . " overspending: " . $overspending . PHP_EOL;

        if ($overspending > 0 && $leftover > 0) {
            $overspendingCopy = $overspending;
            $overspending =  $overspending > $leftover ? $overspending - $leftover : 0;
            $leftover = $overspendingCopy >= $leftover ? 0 : $leftover - $overspendingCopy;
            // 300 = 100 >= 300 ? 0 : 300 - 100 = 200
        }

        if ($leftover <= 0) {
            $leftover = $leftover - $overspending;
        }

        echo "TBB: " . $TBB . " budgeted: " . $results?->budgeted . " Available: " . $available . " Leftover: ". $leftover . " overspending: " . $overspending . PHP_EOL;

        // Close current month

        $details = $this->team->balanceDetail(Carbon::createFromFormat("Y-m-d", $month)->endOfMonth()->format('Y-m-d'), $this->accounts);

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
            "accounts_balance" => collect($details)->sum('balance'),
            "meta_data" => $details
        ]);

        //  Set left over to the next month
        BudgetMonth::updateOrCreate([
            'category_id' => $readyToAssignCategory->id,
            'team_id' => $readyToAssignCategory->team_id,
            'month' => $nextMonth,
            'name' => $nextMonth,
        ], [
            'user_id' => $readyToAssignCategory->user_id,
            'left_from_last_month' => $leftover,
            'moved_from_last_month' => $results?->available + $leftover,
            'overspending_previous_month' => $overspending,
        ]);
    }

    public function startFrom($teamId, $yearMonth, $limit = null) {
        $this->team = Team::find($teamId);
        $this->accounts = Account::getByDetailTypes($teamId, AccountDetailType::ALL_CASH)->pluck('id');

        $categories = Category::where([
            'team_id' => $teamId,
            ])
            ->whereNot('name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->get();


        $monthsWithTransactions = DB::table('transaction_lines')
        ->selectRaw("date_format(transaction_lines.date, '%Y-%m') AS date")
        ->groupBy(DB::raw("date_format(transaction_lines.date, '%Y-%m')"))
        ->whereRaw("date_format(transaction_lines.date, '%Y-%m') >= ?", [$yearMonth])
        ->when($limit, fn($q) => $q->limit($limit))
        ->get()
        ->pluck('date');

        $monthsWithTransactions = [
            ...$monthsWithTransactions,
            now()->format('Y-m'),
        ];

        $total = count($monthsWithTransactions);
        $count = 0;
        foreach ($monthsWithTransactions as $month) {
            try {
                $count++;
                $this->rollMonth($teamId, $month."-01", $categories);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            echo "updated month {$month}".PHP_EOL;
            echo "{$count} of {$total}".PHP_EOL.PHP_EOL;
        }
    }

    // transactions with more than 3 days prior to the las reconciled transaction are not imported
}
