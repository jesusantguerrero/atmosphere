<?php

namespace App\Listeners;

use App\Events\BudgetAssigned;

class CreateBudgetMovement
{
    protected $formData;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(BudgetAssigned $event)
    {
        // BudgetMovement::registerMovement($event->monthBudget, $this->formData);
    }
}
