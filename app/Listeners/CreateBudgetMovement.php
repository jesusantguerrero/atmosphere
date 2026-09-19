<?php

namespace App\Listeners;

use App\Events\BudgetAssigned;
use App\Jobs\RollBudgetForward;

class CreateBudgetMovement
{
    public function handle(BudgetAssigned $event): void
    {
        RollBudgetForward::dispatch(
            $event->budgetMonth->team_id,
            substr($event->budgetMonth->date, 0, 7)
        );
    }
}
