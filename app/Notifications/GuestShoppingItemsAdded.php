<?php

namespace App\Notifications;

/**
 * What others put on a guest's shopping list, grouped so a run of additions is a single alert.
 */
class GuestShoppingItemsAdded extends GuestNotification
{
    private const SHOWN_ITEMS = 3;

    /**
     * @param  array<int, string>  $titles
     */
    public function __construct(public int $planId, public string $listName, public array $titles) {}

    public function toArray($notifiable): array
    {
        $items = implode(', ', array_slice($this->titles, 0, self::SHOWN_ITEMS));
        $rest = count($this->titles) - self::SHOWN_ITEMS;

        return [
            'message' => __(':count new on «:list»: :items', ['count' => count($this->titles), 'list' => $this->listName, 'items' => $items])
                .($rest > 0 ? ' '.__('and :count more', ['count' => $rest]) : '').'.',
            'cta' => __('View shopping list'),
            'link' => route('shopping.index', ['plan' => $this->planId], false),
            'plan_id' => $this->planId,
            'items' => $this->titles,
        ];
    }
}
