<?php

namespace App\Domains\Transaction\Listeners;

use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\PlannedTransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Events\TransactionDeleted;
use Insane\Journal\Events\TransactionUpdated;

class UpdatePlannedTransactions implements ShouldQueue
{
    public function __construct(private PlannedTransactionService $plannedTransactionService) {}

    public function handle(TransactionCreated|TransactionUpdated|TransactionDeleted $event)
    {
        if ($event instanceof TransactionDeleted) {
            return;
        }

        $transaction = $event->transaction;

        // Only process verified transactions
        if ($transaction->status !== Transaction::STATUS_VERIFIED) {
            return;
        }

        $month = substr($transaction->date, 0, 7).'-01';

        if ($transaction->category_id) {
            $plannedTransaction = $this->plannedTransactionService->find($transaction, $month);
            if ($plannedTransaction) {
                $plannedTransaction->schedule->markAsCompleted($transaction, 'Automatically completed by actual transaction');
            }
        }
    }
}
