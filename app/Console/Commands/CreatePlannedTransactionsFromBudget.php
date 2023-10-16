<?php

namespace App\Console\Commands;

use App\Domains\Budget\Services\BudgetTargetService;
use App\Models\Team;
use Illuminate\Console\Command;

class CreatePlannedTransactionsFromBudget extends Command
{
    protected $signature = 'app:create-planned-transactions-from-budget {teamId}';

    protected $description = 'Create planned transactions from budget.';

    public function handle(BudgetTargetService $service): mixed
    {
        $team = Team::find($this->argument('teamId'));
        $service->createPlannedTransactions($team->id);

        return 0;
    }
}
