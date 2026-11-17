<?php

namespace App\Notifications;

use App\Notifications\Channels\OneSignalChannel;

/**
 * What a guest hears about goes to her inbox and, if she allows it, her phone.
 * Never by email: these are small, frequent nudges.
 */
abstract class GuestNotification extends LogerNotification
{
    /**
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return $this->notifiableWants($notifiable, 'push') ? ['database', OneSignalChannel::class] : ['database'];
    }
}
