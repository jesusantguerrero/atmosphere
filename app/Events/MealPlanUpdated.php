<?php

namespace App\Events;

use App\Domains\Meal\Models\MealPlan;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MealPlanUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public MealPlan $mealPlan;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(MealPlan $mealPlan)
    {
        $this->mealPlan = $mealPlan;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
