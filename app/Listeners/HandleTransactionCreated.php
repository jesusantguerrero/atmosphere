<?php

namespace App\Listeners;

use App\Domains\Automation\Services\LogerAutomationService;
use App\Events\AutomationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Insane\Journal\Events\TransactionCreated;

class HandleTransactionCreated implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        AutomationEvent::dispatch(
            $transaction->team_id,
            LogerAutomationService::TRANSACTION_CREATED,
            $transaction->toArray()
        );
    }
}
