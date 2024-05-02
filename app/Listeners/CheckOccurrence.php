<?php

namespace App\Listeners;

use App\Jobs\RunTeamChecks;
use Insane\Journal\Models\Core\Transaction;
use Insane\Journal\Events\TransactionCreated;

class CheckOccurrence
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(TransactionCreated $event)
    {
        $transaction = $event->transaction;
        if ($transaction->status == Transaction::STATUS_VERIFIED) {
            RunTeamChecks::dispatch($transaction->team_id);
        }
    }
}
