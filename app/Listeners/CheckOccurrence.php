<?php

namespace App\Listeners;

use App\Jobs\RunTeamChecks;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Events\TransactionUpdated;
use Insane\Journal\Models\Core\Transaction;

class CheckOccurrence
{
    public function handle(TransactionCreated|TransactionUpdated $event): void
    {
        if ($event->transaction->status === Transaction::STATUS_VERIFIED) {
            RunTeamChecks::dispatch($event->transaction->team_id);
        }
    }
}
