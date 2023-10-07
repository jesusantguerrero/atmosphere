<?php

namespace App\Console\Commands;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Services\BudgetRolloverService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TeamBudgetRollover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:team-budget-rollover {teamId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(BudgetRolloverService $rolloverService)
    {

        $teamId = $this->argument('teamId');

        $categories = Category::where([
            'team_id' => $teamId,
        ])
        ->whereNot('name', BudgetReservedNames::READY_TO_ASSIGN->value)
        ->get();

        $monthsWithTransactions = DB::table('transaction_lines')
            ->selectRaw("date_format(transaction_lines.date, '%Y-%m') AS date")
            ->groupBy(DB::raw("date_format(transaction_lines.date, '%Y-%m')"))
            ->get()
            ->pluck('date');

        $total = count($monthsWithTransactions);
        $count = 0;
        foreach ($monthsWithTransactions as $month) {
            $count++;
            $rolloverService->rollMonth($teamId, $month."-01", $categories);
            echo "updated month {$month}".PHP_EOL;
            echo "{$count} of {$total}".PHP_EOL.PHP_EOL;
        }
    }
}
