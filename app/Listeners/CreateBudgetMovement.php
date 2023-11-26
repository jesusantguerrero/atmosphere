<?php

namespace App\Listeners;

use App\Events\BudgetAssigned;

class CreateBudgetMovement
{
    protected $formData;


    public function __construct()
    {

    }

    public function handle(BudgetAssigned $event)
    {
        // BudgetMovement::registerMovement($event->monthBudget, $this->formData);
    }
}
