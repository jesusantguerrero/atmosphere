<?php

namespace App\Events;

use App\Domains\Budget\Models\BudgetMonth;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BudgetAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public BudgetMonth $monthBudget;

    public mixed $postData;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(BudgetMonth $monthBudget, mixed $postData = [])
    {
        $this->monthBudget = $monthBudget;
        $this->postData = $postData;
    }
}
