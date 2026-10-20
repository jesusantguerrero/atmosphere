<?php

namespace App\Notifications;

use App\Notifications\Channels\OneSignalChannel;

class ShoppingListReady extends LogerNotification
{
    public function __construct(public int $planId, public string $listName, public string $reviewId) {}

    public function via($notifiable): array
    {
        $channels = ['database'];
        if (app()->isProduction() && $this->notifiableWants($notifiable, 'push')) {
            $channels[] = OneSignalChannel::class;
        }

        return $channels;
    }

    public function toArray($notifiable): array
    {
        return ['message' => 'La lista «'.$this->listName.'» está revisada y lista para comprar.', 'cta' => 'Ver lista de compra', 'link' => route('shopping.index', ['plan' => $this->planId], false), 'plan_id' => $this->planId, 'review_id' => $this->reviewId];
    }
}
