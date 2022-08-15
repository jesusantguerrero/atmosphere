<?php

namespace App\Listeners;

use App\Events\BudgetAssigned;
use App\Models\BudgetMovement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
