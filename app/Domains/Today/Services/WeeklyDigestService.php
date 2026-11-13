<?php

namespace App\Domains\Today\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Transaction\Services\NextPaymentsService;
use Illuminate\Support\Carbon;

/**
 * What the coming week holds for a household: Planner events (school
 * activities, things to bring) and the payments that fall due.
 */
class WeeklyDigestService
{
    public function __construct(private NextPaymentsService $nextPayments) {}

    /**
     * @return array{from: string, until: string, events: list<array{date: string, name: string, total: float|null, notes: string|null}>, payments: list<array{date: string, title: string, total: float}>, total: float}|null
     *                                                                                                                                                                                                                         null when the week is empty
     */
    public function forTeam(int $teamId, Carbon $today): ?array
    {
        $from = $today->copy()->addDay()->format('Y-m-d');
        $until = $today->copy()->addDays(7)->format('Y-m-d');

        $events = Planner::where('team_id', $teamId)
            ->whereBetween('date', [$from, $until])
            ->whereNotIn('status', [Planner::STATUS_COMPLETED, Planner::STATUS_CANCELLED])
            ->orderBy('date')
            ->get()
            ->map(fn (Planner $planner) => [
                'date' => $planner->date->format('Y-m-d'),
                'name' => $planner->name,
                'total' => $planner->total === null ? null : round((float) $planner->total, 2),
                'notes' => $planner->notes,
            ])
            ->values()
            ->all();

        $payments = $this->nextPayments->getNextPayments($teamId, $today->format('Y-m-d'))
            ->filter(fn (array $payment) => ($payment['due_date'] ?? null) && $payment['due_date'] <= $until)
            ->map(fn (array $payment) => [
                'date' => $payment['due_date'],
                'title' => $payment['title'],
                'total' => round((float) (string) $payment['total'], 2),
            ])
            ->values()
            ->all();

        if (! $events && ! $payments) {
            return null;
        }

        return [
            'from' => $from,
            'until' => $until,
            'events' => $events,
            'payments' => $payments,
            'total' => round(collect($payments)->sum('total') + collect($events)->sum('total'), 2),
        ];
    }
}
