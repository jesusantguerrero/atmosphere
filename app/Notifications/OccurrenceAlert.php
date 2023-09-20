<?php

namespace App\Notifications;

use App\Domains\Housing\Models\OccurrenceCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OccurrenceAlert extends Notification
{
    use Queueable;

    private OccurrenceCheck $occurrence;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(OccurrenceCheck $occurrence)
    {
        $this->occurrence = $occurrence;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $name = $this->occurrence->name;
        $days = $this->occurrence->previous_days_count;

        return [
            'message' => "Hey the $name occurrence is close to $days days in 3 days",
            'cta' => "Check $name",
            'link' => '/housing/occurrence',
        ];
    }
}
