<?php

namespace App\Console\Commands;

use App\Domains\Budget\Models\BudgetMonth;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-activity {teamId}';

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
        // $month = $this->argument('month') ?? null;
        $month = null;


        $months = BudgetMonth::where([
            'team_id' => $teamId,
        ])
        ->when($month, fn ($q) => $q->whereRaw("date_format(month, '%Y-%m') = '$month'"))
        ->orderBy("month")
        ->get();

        $total = count($months);
        foreach ($months as  $index => $month) {
            $month->updateActivity();
            echo "updated {$month->category->name} for month {$month->month}" . PHP_EOL;
            echo "{$index} of {$total}" . PHP_EOL . PHP_EOL;
        }
    }
}
