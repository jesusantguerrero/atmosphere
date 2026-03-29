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
        if (empty($messageData) || empty($messageData['chatId'])) {
            return;
        }
        $this->telegramService->sendMessage($messageData['chatId'], $messageData['message']);
    }
}
