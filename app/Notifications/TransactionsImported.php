<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;

class TransactionsImported extends LogerNotification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(protected string $url) {}

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'message' => 'New transactions have been imported. Review and approve them.',
            'cta' => 'Approve new transactions',
            'link' => $this->url,
        ];
    }
}
