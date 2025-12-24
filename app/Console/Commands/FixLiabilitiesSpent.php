<?php

namespace App\Console\Commands;

use DateTime;
use App\Models\Team;
use Illuminate\Console\Command;

use App\Domains\Transaction\Services\TransactionService;

class FixLiabilitiesSpent extends Command
{

    protected $signature = 'app:fix-liabilities-spent {teamId}';


    protected $description = 'change counter account for liability transactions';

    public function handle(TransactionService $transactionService)
    {

       $transactions = $transactionService->getCreditCardSpentTransactions($this->argument('teamId'));
       foreach ($transactions as $transaction) {
        $transactions->id;
       }
    }
}
