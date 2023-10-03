<?php

namespace App\Console\Commands;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Services\BudgetCategoryService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyRollover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monthly-rollover {teamId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $teamId = $this->argument('teamId');
        // $yearMonth = $this->argument('month') ?? null;
        // $month = $yearMonth.'-01';

        $categories = Category::where([
            'team_id' => $teamId,
        ])->get();

        $monthsWithTransactions = DB::table('transaction_lines')
            ->selectRaw("date_format(transaction_lines.date, '%Y-%m') AS date")
            ->groupBy(DB::raw("date_format(transaction_lines.date, '%Y-%m')"))
            ->get()
            ->pluck('date');

        $total = count($categories) * count($monthsWithTransactions);
        $count = 0;
        foreach ($categories as $category) {
            foreach ($monthsWithTransactions as $month) {
                $count++;
                $this->setNewMonthBudget($category, $month.'-01');
                // $this->reduceOverspent();
                // $this->setReadyToAssign();
                // $this->setNewMonthBudget();
                echo "updated {$category->name} for month {$month}".PHP_EOL;
                echo "{$count} of {$total}".PHP_EOL.PHP_EOL;
            }
        }
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
        $this->movePositiveAmounts($category, $month, $available);
        // $this->movePositiveAmounts();
        // $this->resetAssignedAmounts();
        // $this->adjustOverspending();
    }

    private function movePositiveAmounts($category, $oldMonth, $available) {
        try {
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
        } catch (Exception $e) {
            dd($oldMonth, $e->getMessage());
        }
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
