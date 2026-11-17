<?php

namespace App\Notifications;

/**
 * Sunday summary of a guest's granted profiles: the coming week and the rest of the month. No amounts.
 */
class GuestWeeklyDigest extends GuestNotification
{
    /**
     * @param  array{profiles: string, week: list<array{date: string, name: string, profile: string|null}>, later: list<array{date: string, name: string, profile: string|null}>}  $digest
     */
    public function __construct(public array $digest) {}

    public function toArray($notifiable): array
    {
        $lines = [__(':count event(s) this week for :profiles.', ['count' => count($this->digest['week']), 'profiles' => $this->digest['profiles']])];
        array_push($lines, ...array_map(fn (array $event) => $this->line($event), $this->digest['week']));
        if ($this->digest['later']) {
            $lines[] = __('Later this month:');
            array_push($lines, ...array_map(fn (array $event) => $this->line($event), $this->digest['later']));
        }

        return [
            'message' => implode("\n", $lines),
            'cta' => __('View calendar'),
            'link' => route('calendar', [], false),
            'week' => $this->digest['week'],
            'later' => $this->digest['later'],
        ];
    }

    /**
     * @param  array{date: string, name: string, profile: string|null}  $event
     */
    private function line(array $event): string
    {
        return '• '.date('d/m', strtotime($event['date'])).' '.$event['name'].($event['profile'] ? ' ('.$event['profile'].')' : '');
    }
}
