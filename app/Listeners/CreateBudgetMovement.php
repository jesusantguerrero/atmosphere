<?php

namespace App\Listeners;

use App\Events\BudgetAssigned;
use App\Models\BudgetMovement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateBudgetMovement
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(BudgetAssigned $event)
    {
        $this->formData = [
            'team_id' => $event->monthBudget->team_id,
            'user_id' => $event->monthBudget->user_id,
            'source_category_id' => $event->formData['source_category_id'] ?? "",
            'destination_category_id' => $event->monthBudget->category_id
        ];
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle()
    {
        BudgetMovement::registerMovement($this->formData);
    }
}
