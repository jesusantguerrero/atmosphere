<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Services\BudgetRolloverService;

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

        $monthsWithTransactions = DB::table('transaction_lines')
            ->where([
                "team_id" => $teamId
            ])
            ->selectRaw("date_format(transaction_lines.date, '%Y-%m') AS date")
            ->groupBy(DB::raw("date_format(transaction_lines.date, '%Y-%m')"))
            ->orderBy('date')
            ->first();

            $rolloverService->startFrom($teamId, $monthsWithTransactions->date);
    }
}
