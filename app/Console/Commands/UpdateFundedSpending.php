<?php

namespace App\Console\Commands;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Services\BudgetCategoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;

class UpdateFundedSpending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-funded-spending {teamId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update available money for credit cards';

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

        $categories = Category::where('categories.team_id', $teamId)
            ->whereHas('account', fn ($q) => $q
                ->join('account_detail_types', 'account_detail_types.id', '=', 'accounts.account_detail_type_id')
                ->whereIn('account_detail_types.name', [AccountDetailType::CREDIT_CARD])
            )
            ->get();

        $monthsWithTransactions = DB::table('transaction_lines')
            ->selectRaw("date_format(transaction_lines.date, '%Y-%m') AS date")
            ->groupBy(DB::raw("date_format(transaction_lines.date, '%Y-%m')"))
            ->get()
            ->pluck('date');

        $total = count($categories) * count($monthsWithTransactions);
        foreach ($categories as $categoryIndex => $category) {
            foreach ($monthsWithTransactions as $index => $month) {
                $count = $categoryIndex + $index;
                (new BudgetCategoryService($category))->updateFundedSpending($category, $month.'-01');
                echo "updated {$category->name} for month {$month}".PHP_EOL;
                echo "{$count} of {$total}".PHP_EOL.PHP_EOL;
            }
        }
    }
}
