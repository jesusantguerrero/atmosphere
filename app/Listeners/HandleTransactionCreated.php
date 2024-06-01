<?php

namespace App\Listeners;

use App\Events\AutomationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Insane\Journal\Events\TransactionEvent;
use App\Domains\Automation\Services\LogerAutomationService;

class HandleTransactionCreated implements ShouldQueue
{
    public function handle(TransactionEvent $event)
    {
        $transaction = $event->transaction;

        AutomationEvent::dispatch(
            $transaction->team_id,
            LogerAutomationService::TRANSACTION_CREATED,
            $transaction->toArray()
        );
    }
}
