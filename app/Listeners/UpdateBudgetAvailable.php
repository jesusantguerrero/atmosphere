<?php

namespace App\Listeners;

use App\Jobs\RollBudgetForward;
use Insane\Journal\Models\Core\Transaction;

class UpdateBudgetAvailable
{
    public function handle($event): void
    {
        if ($event->transaction->status == Transaction::STATUS_VERIFIED) {
            RollBudgetForward::dispatch(
                $event->transaction->team_id,
                substr($event->transaction->date, 0, 7)
            );
        }
    }
}
