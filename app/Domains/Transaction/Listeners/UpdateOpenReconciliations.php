<?php

namespace App\Domains\Transaction\Listeners;

use App\Domains\Transaction\Services\ReconciliationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Insane\Journal\Events\TransactionCreated;

class UpdateOpenReconciliations implements ShouldQueue
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
    public function handle(TransactionCreated $event): void
    {
        $this->service->checkOpenReconciliation($event->transaction->account, $event->transaction);
        $this->service->checkOpenReconciliation($event->transaction->account, $event->transaction);
    }
}
