<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;


class LogerNotification extends Notification
{
    public function toTelegram($notifiable)
    {


        return [];
    }


    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toWhatsapp($notifiable)
    {
        return [];
    }
}
