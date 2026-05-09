<?php

namespace App\Listeners;

use App\Events\ShoppingListItemUpdated;
use App\Services\OneSignalService;

/**
 * Send a OneSignal web-push when a shared shopping list gets a new item or
 * gets reset. Skips pure cycle-state changes — those happen multiple times
 * per minute while shopping, and Mercure already keeps every viewer's screen
 * in sync. Pushing for them would be spam.
 *
 * Pushes target tag `shopping_list = {share_token}` so only people who
 * opened the shared link (and tagged themselves on the frontend) get hit.
 */
class PushShoppingListUpdate
{
    public function __construct(private OneSignalService $oneSignal) {}

    public function handle(ShoppingListItemUpdated $event): void
    {
        if (! $event->plan->share_token) {
            return;
        }

        if (! in_array($event->action, ['created', 'reset'], true)) {
            return;
        }

        [$heading, $message] = $this->copyFor($event);

        $this->oneSignal->sendToTag(
            key: 'shopping_list',
            value: $event->plan->share_token,
            heading: $heading,
            message: $message,
            url: ['web' => url("/shared/list/{$event->plan->share_token}")],
            excludedSubscriptionIds: $event->actorSubscriptionId
                ? [$event->actorSubscriptionId]
                : [],
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function copyFor(ShoppingListItemUpdated $event): array
    {
        $listName = $event->plan->name ?: 'Shopping list';

        if ($event->action === 'reset') {
            return [$listName, 'Trip reset — every item is pending again.'];
        }

        $title = $event->item?->title ?? 'something';

        return [$listName, "Added: {$title}"];
    }
}
