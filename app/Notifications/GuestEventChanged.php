<?php

namespace App\Notifications;

/**
 * An event of a granted profile was added, changed, cancelled or removed within the next week.
 */
class GuestEventChanged extends GuestNotification
{
    public const ADDED = 'added';

    public const CHANGED = 'changed';

    public const CANCELLED = 'cancelled';

    public const REMOVED = 'removed';

    private const MESSAGES = [
        self::ADDED => 'New on :profile\'s calendar: :name, :date.',
        self::CHANGED => 'Changed on :profile\'s calendar: :name, :date.',
        self::CANCELLED => 'Cancelled on :profile\'s calendar: :name, :date.',
        self::REMOVED => 'Removed from :profile\'s calendar: :name, :date.',
    ];

    public function __construct(public string $change, public int $plannerId, public string $name, public string $date, public string $profileName) {}

    public function toArray($notifiable): array
    {
        return [
            'message' => __(self::MESSAGES[$this->change], ['profile' => $this->profileName, 'name' => $this->name, 'date' => date('d/m', strtotime($this->date))]),
            'cta' => __('View calendar'),
            'link' => route('calendar', [], false),
            'change' => $this->change,
            'planner_id' => $this->plannerId,
            'date' => $this->date,
        ];
    }
}
