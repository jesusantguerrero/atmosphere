<?php

namespace App\Notifications;

use App\Domains\Integration\Services\TelegramService;

class TelegramChannel
{
    public function __construct(private TelegramService $telegramService)
    {
        //
    }
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, LogerNotification $notification): void
    {
            $messageData = $notification->toTelegram($notifiable);
            $this->telegramService->sendMessage($messageData["chatId"], $messageData["message"]);
    }
}
