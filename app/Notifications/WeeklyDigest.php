<?php

namespace App\Notifications;

/**
 * Sunday evening summary of the coming week: Planner events (school
 * activities, what to bring) and payments due.
 */
class WeeklyDigest extends LogerNotification
{
    /**
     * @param  array{from: string, until: string, events: list<array<string, mixed>>, payments: list<array<string, mixed>>, total: float}  $digest
     */
    public function __construct(public array $digest) {}

    public function toArray($notifiable): array
    {
        $lines = [];
        foreach ($this->digest['events'] as $event) {
            $lines[] = '• '.date('d/m', strtotime($event['date'])).' '.$event['name']
                .($event['total'] ? ' (RD$'.number_format($event['total'], 2).')' : '')
                .($event['notes'] ? ' — '.$event['notes'] : '');
        }
        foreach ($this->digest['payments'] as $payment) {
            $lines[] = '• '.date('d/m', strtotime($payment['date'])).' Pago: '.$payment['title'].' (RD$'.number_format($payment['total'], 2).')';
        }

        return [
            'message' => 'Esta semana: '.count($this->digest['events']).' evento(s) y '.count($this->digest['payments'])
                .' pago(s), RD$'.number_format($this->digest['total'], 2).".\n".implode("\n", $lines),
            'cta' => 'Ver calendario',
            'link' => route('calendar', [], false),
            'from' => $this->digest['from'],
            'until' => $this->digest['until'],
            'events' => $this->digest['events'],
            'payments' => $this->digest['payments'],
            'total' => $this->digest['total'],
        ];
    }
}
