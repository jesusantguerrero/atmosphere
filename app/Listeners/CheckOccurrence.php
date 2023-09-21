<?php

namespace App\Listeners;

use App\Jobs\RunTeamChecks;
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
        RunTeamChecks::dispatch($transaction->team_id);
    }
}
