<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PlannedAlert extends Notification
{
    use Queueable;


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private $planned)
    {

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
        $name = $this->planned->description;

        $diff =  Carbon::createFromFormat("Y-m-d", $this->planned->date)->diffInDays(now(), false);
        $diffAbs = abs($diff);

        $messages = match (true) {
            $diff < 0 => ["is close to", "{$this->planned->date} in $diffAbs days"],
            $diff == 0 => ["is", "due to today"],
            default => ["has passed", "{$this->planned->date} by $diffAbs days"]
        };

        return [
            'message' => "The $name bill {$messages[0]} {$messages[1]}",
            'cta' => "Check $name",
            'link' => '/dashboard',
        ];
    }
}
