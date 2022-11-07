<?php

namespace App\Listeners;

use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Transaction\Actions\SearchTransactions;
use App\Events\OccurrenceCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateOccurrenceAutomation
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(OccurrenceCreated $event)
    {
        $occurrence = $event->occurrence;
        if ($occurrence->condition) {
            $occurrence = (new RegisterOccurrence())->load($occurrence);
        }
    }
}
