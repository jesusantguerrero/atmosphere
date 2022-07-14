<?php

namespace App\Events;

use App\Models\MonthBudget;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BudgetAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

     /**
     * The month budget instance.
     *
     * @var \App\Models\MonthBudget
     */
    public $monthBudget;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(MonthBudget $monthBudget)
    {
        $this->monthBudget = $monthBudget;
    }
}
