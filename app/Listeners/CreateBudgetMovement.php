<?php

namespace App\Listeners;

use App\Events\BudgetAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Budget\Services\BudgetRolloverService;

class CreateBudgetMovement implements ShouldQueue
{
    protected $formData;


    public function __construct()
    {

    }

    public function handle(BudgetAssigned $event)
    {
        // BudgetMovement::registerMovement($event->monthBudget, $this->formData);
        (new BudgetRolloverService(new BudgetCategoryService()))->startFrom($event->budgetMonth->team_id, substr($event->budgetMonth->date, 0, 7));
    }
}
