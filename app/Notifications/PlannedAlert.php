<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;

class PlannedAlert extends LogerNotification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private $planned) {}

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $name = $this->planned->description;

        $diff = Carbon::createFromFormat('Y-m-d', $this->planned->date)->diffInDays(now(), false);
        $diffAbs = abs($diff);

        $messages = match (true) {
            $diff < 0 => ['is close to', "{$this->planned->date} in $diffAbs days"],
            $diff == 0 => ['is', 'due to today'],
            default => ['has passed', "{$this->planned->date} by $diffAbs days"]
        };

        // planner_id lets the notifications UI offer "Mark as paid" / "Stop
        // reminders" actions inline. If the relationship is missing for any
        // reason, the UI degrades gracefully — buttons just don't render.
        return [
            'message' => "The $name bill {$messages[0]} {$messages[1]}",
            'cta' => "Check $name",
            'link' => '/dashboard',
            'planner_id' => $this->planned->schedule?->id,
            'planner_name' => $name,
        ];
    }
}
