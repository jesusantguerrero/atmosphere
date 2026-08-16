<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;

class EntryGenerated extends LogerNotification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $url = '/finance/transactions?filter[status]=draft';

        return [
            'message' => 'New transactions have been imported. Review and approve them.',
            'cta' => 'Approve new transactions',
            'link' => $url,
        ];
    }
}
