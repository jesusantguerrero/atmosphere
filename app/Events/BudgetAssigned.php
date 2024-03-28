<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use App\Domains\Budget\Data\BudgetAssignData;
use Illuminate\Foundation\Events\Dispatchable;
use App\Domains\Budget\Data\BudgetMovementData;
use Illuminate\Broadcasting\InteractsWithSockets;

class BudgetAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public BudgetAssignData|BudgetMovementData $budgetMonth;

    public mixed $postData;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(BudgetAssignData|BudgetMovementData $budgetMonth, mixed $postData = [])
    {
        $this->budgetMonth = $budgetMonth;
        $this->postData = $postData;
    }
}
