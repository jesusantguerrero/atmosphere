<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Budget\Services\BudgetTargetService;

class BGPlannedFromBudget extends Command
{
    protected $signature = 'app:planned-from-budget';

    protected $description = 'Create planned transactions from budget.';

    public function handle(BudgetTargetService $service): mixed
    {
        $service->createPlannedTransactions();
        return 0;
    }
}
