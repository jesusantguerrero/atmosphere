<?php

namespace App\Listeners;

use App\Domains\Transaction\Actions\FindLinkedTransactions as ActionsFindLinkedTransactions;
use App\Domains\Transaction\Models\LinkedTransaction;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class FindLinkedTransactions
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
    public function handle($event)
    {
        foreach ($event->transactionMetadata as $transactionData) {
            (new ActionsFindLinkedTransactions(
                $event->teamId,
                $event->userId,
                $transactionData
            ))->handle();
        }
    }
}
