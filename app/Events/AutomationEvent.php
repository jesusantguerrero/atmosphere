<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AutomationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $teamId;

    public $eventData;

    public $eventName;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($teamId, $eventName, $eventData = [])
    {
        $this->teamId = $teamId;
        $this->eventName = $eventName;
        $this->eventData = $eventData;
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
