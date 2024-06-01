<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Insane\Journal\Events\TransactionEvent;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Events\TransactionUpdated;
use App\Domains\Budget\Services\BudgetCategoryService;

class CreateBudgetTransactionMovement implements ShouldQueue
{
    public function __construct(private BudgetCategoryService $budgetCategoryService)
    {
    }

    public function handle(TransactionCreated|TransactionUpdated $event)
    {
        $transaction = $event->transaction;
        $account = $transaction->account;
        $month = substr($transaction->date, 0, 7).'-01';

        if ($categoryAccount = $this->budgetCategoryService->findByAccount($account)) {
            $this->budgetCategoryService->updateFundedSpending($categoryAccount, $month);
            $this->budgetCategoryService->updateActivity($categoryAccount, $month);
        }

        if ($transaction->category_id) {
            $this->budgetCategoryService->updateActivity($transaction->category, $month);
        }

        // if ($transaction->category) {
        //     $this->budgetCategoryService->updateMonthBalances($transaction->category, $month);
        // }

    }
}
