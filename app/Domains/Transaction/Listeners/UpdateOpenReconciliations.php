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
    public function __construct(private ReconciliationService $service) {}

    /**
     * Handle the event.
     */
    public function handle(TransactionCreated $event): void
    {
        if ($event->transaction->account) {
            $this->service->checkOpenReconciliation($event->transaction->account, $event->transaction);
        }
        if ($event->transaction->counterAccount && $event->transaction->counterAccount->id !== $event->transaction->account?->id) {
            $this->service->checkOpenReconciliation($event->transaction->counterAccount, $event->transaction);
        }
    }
}
