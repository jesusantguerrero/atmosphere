<?php

namespace App\Domains\Today\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Transaction\Models\BillingCycle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    /**
     * Aggregate all time-based items across domains for a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEvents(int $teamId, string $start, string $end): array
    {
        $plannerEvents = $this->plannerEvents($teamId, $start, $end);
        $billingEvents = $this->billingEvents($teamId, $start, $end);
        $occurrenceEvents = $this->occurrenceEvents($teamId, $start, $end);

        return $plannerEvents
            ->concat($billingEvents)
            ->concat($occurrenceEvents)
            ->sortBy('start')
            ->values()
            ->all();
    }

    private function plannerEvents(int $teamId, string $start, string $end): Collection
    {
        return Planner::query()
            ->where('team_id', $teamId)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get()
            ->map(fn (Planner $p) => [
                'id' => 'planner-'.$p->id,
                'planner_id' => $p->id,
                'title' => $p->name ?: ($p->dateable_type ? class_basename($p->dateable_type) : 'Task'),
                'start' => $p->date->format('Y-m-d'),
                'end' => $p->end_date?->format('Y-m-d'),
                'kind' => $p->dateable_type ? strtolower(class_basename($p->dateable_type)) : 'task',
                'status' => $p->status,
                'total' => $p->total !== null ? (float) $p->total : null,
                'source' => $p->source,
                'notes' => $p->notes,
                'completed_at' => $p->completed_at?->toDateTimeString(),
            ]);
    }

    private function billingEvents(int $teamId, string $start, string $end): Collection
    {
        return BillingCycle::query()
            ->where('team_id', $teamId)
            ->whereBetween('due_at', [$start, $end])
            ->with('account:id,name')
            ->orderBy('due_at')
            ->get()
            ->map(fn (BillingCycle $c) => [
                'id' => 'billing-'.$c->id,
                'planner_id' => null,
                'title' => $c->account?->name ?? 'Payment',
                'start' => Carbon::parse($c->due_at)->format('Y-m-d'),
                'end' => null,
                'kind' => 'billing',
                'status' => $c->status,
                'total' => (float) $c->total,
                'source' => null,
                'notes' => null,
                'completed_at' => null,
            ]);
    }

    private function occurrenceEvents(int $teamId, string $start, string $end): Collection
    {
        return Occurrence::query()
            ->byTeam($teamId)
            ->where('is_active', true)
            ->whereNotNull('last_date')
            ->where('avg_days_passed', '>', 0)
            ->get()
            ->map(function (Occurrence $o) {
                $nextDate = $o->last_date->copy()->addDays((int) $o->avg_days_passed);

                return [
                    'id' => 'occurrence-'.$o->id,
                    'planner_id' => null,
                    'title' => $o->name,
                    'start' => $nextDate->format('Y-m-d'),
                    'end' => null,
                    'kind' => $o->type ?? 'occurrence',
                    'status' => null,
                    'total' => null,
                    'source' => null,
                    'notes' => null,
                    'completed_at' => null,
                ];
            })
            ->filter(fn ($event) => $event['start'] >= $start && $event['start'] <= $end);
    }
}
