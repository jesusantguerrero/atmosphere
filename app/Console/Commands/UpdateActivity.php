<?php

namespace App\Console\Commands;

use App\Domains\Budget\Models\BudgetMonth;
use Illuminate\Console\Command;

class UpdateActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-activity {teamId} {month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update activity for a given month';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $teamId = $this->argument('teamId');
        $month = $this->argument('month');
        $months = BudgetMonth::where([
            'team_id' => $teamId,
        ])
        ->whereRaw("date_format(month, '%Y-%m') = '$month'")
        ->get();

        foreach ($months as $month) {
            $month->updateActivity();
        }
    }
}
