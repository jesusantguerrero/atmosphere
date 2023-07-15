<?php

namespace App\Listeners;

use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Housing\Models\OccurrenceCheck;
use App\Events\OccurrenceCreated;
use App\Jobs\RunTeamChecks;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
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
