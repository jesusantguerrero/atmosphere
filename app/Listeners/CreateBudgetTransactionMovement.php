<?php

namespace App\Listeners;

use App\Domains\Budget\Services\BudgetCategoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Insane\Journal\Events\TransactionCreated;

class CreateBudgetTransactionMovement implements ShouldQueue
{
    public function __construct(private BudgetCategoryService $budgetCategoryService)
    {
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TransactionCreated $event)
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
    }
}
