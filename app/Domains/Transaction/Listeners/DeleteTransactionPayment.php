<?php

namespace App\Domains\Transaction\Listeners;

use Insane\Journal\Models\Core\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Insane\Journal\Events\TransactionDeleted;
use App\Domains\Transaction\Services\ReconciliationService;

class DeleteTransactionPayment implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(private ReconciliationService $service)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TransactionDeleted $event): void
    {
        echo $event->transaction->transactionable_type;
        if ($event->transaction->transactionable_type == Payment::class) {
            $payment = $event->transaction->transactionable;
            $billingCycle = $payment->payable;
            $payment->delete();
            $billingCycle->update();
        }
    }
}
