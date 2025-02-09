<?php

namespace App\Notifications;

use App\Domains\Integration\Services\WhatsAppService;

class WhatsappChannel
{
    public function __construct(private WhatsAppService $whatsAppService)
    {
        //
    }
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, LogerNotification $notification): void
    {
        $messageData = $notification->toWhatsapp($notifiable);
        $this->whatsAppService->sendMessage($messageData['phoneId'], $messageData['message']);
    }
}
