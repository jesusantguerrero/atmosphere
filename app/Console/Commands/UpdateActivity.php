<?php

namespace App\Console\Commands;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Services\BudgetCategoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
                (new BudgetCategoryService($category))->updateActivity($category, $month.'-01');
                echo "updated {$category->name} for month {$month}".PHP_EOL;
                echo "{$count} of {$total}".PHP_EOL.PHP_EOL;
            }
        }
    }
}
