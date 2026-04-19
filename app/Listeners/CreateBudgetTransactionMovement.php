<?php

namespace App\Listeners;

use App\Domains\Budget\Services\BudgetCategoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Events\TransactionDeleted;
use Insane\Journal\Events\TransactionUpdated;

class CreateBudgetTransactionMovement implements ShouldQueue
{
    public function __construct(private BudgetCategoryService $budgetCategoryService) {}

    public function handle(TransactionCreated|TransactionUpdated|TransactionDeleted $event)
    {
        $transaction = $event->transaction;
        $account = $transaction->account;
        $month = substr($transaction->date, 0, 7).'-01';

        if ($account && $categoryAccount = $this->budgetCategoryService->findByAccount($account)) {
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
