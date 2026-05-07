<?php

namespace App\Events;

use Duijker\LaravelMercureBroadcaster\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;

/**
 * Broadcast when an item on a shared shopping list changes state, gets
 * created, or gets deleted. Subscribers (the spouse's phone, the partner's
 * tablet) listen on the share-token-keyed Mercure channel and update their UI
 * in real time without polling.
 *
 * The channel is keyed by share_token so anyone holding the public link is
 * authorised to subscribe — same trust model as the shared HTTP endpoints.
 */
class ShoppingListItemUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Plan $plan,
        public ?PlanItem $item,
        public string $action,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        if (! $this->plan->share_token) {
            return [];
        }

        $url = url("/shared/list/{$this->plan->share_token}");

        return [new Channel($url, false)];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'plan_id' => $this->plan->id,
            'item' => $this->item ? [
                'id' => $this->item->id,
                'title' => $this->item->title,
                'state' => $this->item->state ?? PlanItem::STATE_PENDING,
                'is_done' => (bool) $this->item->is_done,
                'order' => (int) ($this->item->order ?? 0),
            ] : null,
        ];
    }
}
