<?php

namespace App\Console\Commands;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Services\BudgetRolloverService;
use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;

class MonthlyRollover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monthly-rollover {teamId} {month}';

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
        $month = $this->argument('month');

        $rolloverService->rollMonth($teamId, $month."-01");

    }
}
