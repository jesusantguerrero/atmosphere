<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Integration\Models\Integration;
use Illuminate\Notifications\Messages\MailMessage;
use App\Domains\Housing\Contracts\OccurrenceNotifyTypes;

class OccurrenceAlert extends LogerNotification
{
    use Queueable;


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private Occurrence $occurrence, private OccurrenceNotifyTypes $type)
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
        return ['database', TelegramChannel::class, WhatsappChannel::class];
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
        $types = [
            'avg' => "its average of {$this->occurrence->avg_days_passed}",
            'last' => "its last duration of {$this->occurrence->previous_days_count}",
        ];

        $currentCount = $this->occurrence->currentCount();
        $referenceCount = $this->type->value == OccurrenceNotifyTypes::AVG->value ? $this->occurrence->avg_days_passed : $this->occurrence->previous_days_count;
        $diff = $currentCount - $referenceCount;
        $diffAbs = abs($diff);

        $messages = match (true) {
            $diff < 0 => ["is close to", "days in $diffAbs days"],
            $diff == 0 => ["is", ""],
            default => ["has passed", "days by $diffAbs days"]
        };



        return [
            'message' => "The $name occurrence ({$currentCount}) {$messages[0]} {$types[$this->type->value]} {$messages[1]}",
            'cta' => "Check $name",
            'link' => '/housing/occurrence',
        ];
    }


    private function getFormattedMessage() {
        $name = $this->occurrence->name;
        $types = [
            'avg' => "its average of {$this->occurrence->avg_days_passed}",
            'last' => "its last duration of {$this->occurrence->previous_days_count}",
        ];

        $currentCount = $this->occurrence->currentCount();
        $referenceCount = $this->type->value == OccurrenceNotifyTypes::AVG->value ? $this->occurrence->avg_days_passed : $this->occurrence->previous_days_count;
        $diff = $currentCount - $referenceCount;
        $diffAbs = abs($diff);

        $messages = match (true) {
            $diff < 0 => ["is close to", "days in $diffAbs days"],
            $diff == 0 => ["is", ""],
            default => ["has passed", "days by $diffAbs days"]
        };


        return [
            'message' => "The $name occurrence ({$currentCount}) {$messages[0]} {$types[$this->type->value]} {$messages[1]}",
            'cta' => "Check $name",
            'link' => '/housing/occurrence',
        ];
    }


    public function toTelegram($notifiable)
    {
        $message = $this->getFormattedMessage();

        $userIntegration = Integration::where([
            "name" => "telegram",
            "user_id" => $this->occurrence->user_id
        ])->first();

        return [
            ...$message,
            'phone' => $userIntegration->phone
        ];
    }


    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toWhatsapp($notifiable)
    {
        $message = $this->getFormattedMessage();

        $userIntegration = Integration::where([
            "name" => "WhatsApp",
            "user_id" => $this->occurrence->user_id
        ])->first();


        return [
            ...$message,
            'phoneId' => $userIntegration->phoneId
        ];
    }
}
