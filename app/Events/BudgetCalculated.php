<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Duijker\LaravelMercureBroadcaster\Broadcasting\Channel;

class BudgetCalculated implements ShouldBroadcastNow
{
    use SerializesModels;
    public User $user;

    public function __construct(string $message = "Hello world")
    {
        $this->user= User::all()->first();
    }
    public function broadcastOn(): array
    {
        return [
            new Channel('https://example.com/main', false),
        ];
    }

            /**
         * Get the data to broadcast.
         *
         * @return array<string, mixed>
         */
        public function broadcastWith(): array
        {
            return [
                'message' => "{$this->user->name} Are you not entertained?"
            ];
        }
}
