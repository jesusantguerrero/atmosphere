<?php

namespace App\Domains\Housing\Services;

use App\Domains\Housing\Contracts\ChoreArea;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;

/**
 * Tody-style freshness for household chores: how long ago each chore was
 * done, when it is due again, and how the areas of the house are doing.
 */
class ChoreAreaService
{
    public const STATUS_FRESH = 'fresh';

    public const STATUS_DUE_SOON = 'due_soon';

    public const STATUS_DUE_TODAY = 'due_today';

    public const STATUS_OVERDUE = 'overdue';

    /**
     * Overdue chores at or below this many days late are orange; above, red.
     */
    public const MILDLY_OVERDUE_DAYS = 3;

    private const STATUS_RANK = [
        self::STATUS_FRESH => 0,
        self::STATUS_DUE_SOON => 1,
        self::STATUS_DUE_TODAY => 2,
        self::STATUS_OVERDUE => 3,
    ];

    /**
     * Areas (all defaults, even empty ones), the team's chores with their
     * freshness, and the single most overdue chore.
     *
     * @return array{areas: list<array<string, mixed>>, chores: list<array<string, mixed>>, most_overdue: array<string, mixed>|null}
     */
    public function overview(int $teamId, ?CarbonInterface $today = null): array
    {
        $chores = $this->choresForTeam($teamId, $today);

        return [
            'areas' => $this->groupByArea($chores),
            'chores' => $chores->values()->all(),
            'most_overdue' => $this->pickMostOverdue($chores),
        ];
    }

    /**
     * The chore that is the most days late, or null when nothing is overdue.
     *
     * @return array<string, mixed>|null
     */
    public function mostOverdue(int $teamId, ?CarbonInterface $today = null): ?array
    {
        return $this->pickMostOverdue($this->choresForTeam($teamId, $today));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function choresForTeam(int $teamId, ?CarbonInterface $today = null): Collection
    {
        return PlanItem::query()
            ->without(['fields', 'checklist'])
            ->where('team_id', $teamId)
            ->whereHas('stage.board', fn ($query) => $query->where('plan_type_name', PlanTypes::CHORES))
            ->get()
            ->reject(fn (PlanItem $item) => $item->is_done && $this->intervalDays($item->rrule) === null)
            ->map(fn (PlanItem $item) => $this->describe($item, $today))
            ->sortBy([['days_left', 'asc'], ['title', 'asc']])
            ->values();
    }

    /**
     * Days between occurrences of a recurrence rule, or null when the chore
     * does not recur (or the rule cannot be read).
     */
    public function intervalDays(?string $rrule): ?int
    {
        if (! $rrule || ! preg_match('/FREQ=([A-Z]+)/i', $rrule, $frequencyMatch)) {
            return null;
        }

        $interval = preg_match('/INTERVAL=(\d+)/i', $rrule, $intervalMatch) ? max(1, (int) $intervalMatch[1]) : 1;

        $baseDays = match (strtoupper($frequencyMatch[1])) {
            'DAILY' => 1,
            'WEEKLY' => $this->weeklyBaseDays($rrule),
            'MONTHLY' => 30,
            'YEARLY' => 365,
            default => null,
        };

        return $baseDays === null ? null : $baseDays * $interval;
    }

    /**
     * Freshness of a single chore.
     *
     * @return array{id: int, title: string, area: string, is_done: bool, interval_days: int|null, last_done_at: string|null, due_date: string, days_left: int, status: string, color: string, freshness: float}
     */
    public function describe(PlanItem $item, ?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::parse($today ?? Carbon::now())->startOfDay();
        $intervalDays = $this->intervalDays($item->rrule);
        $lastDoneAt = $this->lastDoneAt($item);
        $dueDate = $this->dueDate($item, $intervalDays, $lastDoneAt, $today);
        $daysLeft = (int) $today->diffInDays($dueDate, false);
        $status = $this->status($daysLeft, $intervalDays);

        return [
            'id' => (int) $item->id,
            'title' => (string) $item->title,
            'area' => ChoreArea::fromStored($item->area)->value,
            'is_done' => (bool) $item->is_done,
            'interval_days' => $intervalDays,
            'last_done_at' => $lastDoneAt?->toIso8601String(),
            'due_date' => $dueDate->toDateString(),
            'days_left' => $daysLeft,
            'status' => $status,
            'color' => $this->color($status, $daysLeft),
            'freshness' => $this->freshness($daysLeft, $intervalDays),
        ];
    }

    /**
     * Mark the moment a chore flips to done. Runs on every PlanItem save, so
     * it covers the web board, the mobile toggle and any other path.
     */
    public function stampCompletion(PlanItem $item): void
    {
        $becomesDone = $item->isDirty('state')
            ? $item->state === PlanItem::STATE_BUY
            : (bool) $item->is_done;
        $wasDone = $item->exists && (bool) $item->getOriginal('is_done');

        if ($becomesDone && ! $wasDone) {
            $item->last_done_at = Carbon::now();
        }
    }

    public function status(int $daysLeft, ?int $intervalDays): string
    {
        if ($daysLeft < 0) {
            return self::STATUS_OVERDUE;
        }

        if ($daysLeft === 0) {
            return self::STATUS_DUE_TODAY;
        }

        $soonThreshold = $intervalDays ? max(1, (int) ceil($intervalDays * 0.2)) : 1;

        return $daysLeft <= $soonThreshold ? self::STATUS_DUE_SOON : self::STATUS_FRESH;
    }

    /**
     * Dot color: green fresh, yellow due soon/today, orange slightly overdue,
     * red more than MILDLY_OVERDUE_DAYS late.
     */
    public function color(string $status, int $daysLeft): string
    {
        return match ($status) {
            self::STATUS_FRESH => 'green',
            self::STATUS_DUE_SOON, self::STATUS_DUE_TODAY => 'yellow',
            default => -$daysLeft <= self::MILDLY_OVERDUE_DAYS ? 'orange' : 'red',
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $chores
     * @return list<array{key: string, label: string, total: int, counts: array<string, int>, worst_status: string|null, color: string|null, max_overdue_days: int}>
     */
    public function groupByArea(Collection $chores): array
    {
        return collect(ChoreArea::cases())
            ->map(function (ChoreArea $area) use ($chores) {
                $areaChores = $chores->where('area', $area->value);
                $worst = $areaChores
                    ->sortBy([
                        fn ($a, $b) => self::STATUS_RANK[$b['status']] <=> self::STATUS_RANK[$a['status']],
                        fn ($a, $b) => $a['days_left'] <=> $b['days_left'],
                    ])
                    ->first();

                return [
                    'key' => $area->value,
                    'label' => $area->label(),
                    'total' => $areaChores->count(),
                    'counts' => collect(array_keys(self::STATUS_RANK))
                        ->mapWithKeys(fn (string $status) => [$status => $areaChores->where('status', $status)->count()])
                        ->all(),
                    'worst_status' => $worst['status'] ?? null,
                    'color' => $worst['color'] ?? null,
                    'max_overdue_days' => (int) max(0, -($worst['days_left'] ?? 0)),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $chores
     * @return array<string, mixed>|null
     */
    private function pickMostOverdue(Collection $chores): ?array
    {
        $chore = $chores
            ->where('status', self::STATUS_OVERDUE)
            ->sortBy('days_left')
            ->first();

        if (! $chore) {
            return null;
        }

        return $chore + ['area_label' => ChoreArea::from($chore['area'])->label()];
    }

    private function weeklyBaseDays(string $rrule): int
    {
        if (! preg_match('/BYDAY=([A-Z0-9,+-]+)/i', $rrule, $byDayMatch)) {
            return 7;
        }

        $daysPerWeek = count(array_filter(explode(',', $byDayMatch[1])));

        return $daysPerWeek > 1 ? max(1, intdiv(7, $daysPerWeek)) : 7;
    }

    /**
     * last_done_at, or, for chores done before the column existed, the
     * completion day the module stamps into commit_date.
     */
    private function lastDoneAt(PlanItem $item): ?CarbonImmutable
    {
        if ($item->last_done_at) {
            return CarbonImmutable::parse($item->last_done_at);
        }

        if ($item->is_done && $item->commit_date) {
            return CarbonImmutable::parse($item->commit_date);
        }

        return null;
    }

    private function dueDate(PlanItem $item, ?int $intervalDays, ?CarbonImmutable $lastDoneAt, CarbonImmutable $today): CarbonImmutable
    {
        if ($intervalDays && $lastDoneAt) {
            return $lastDoneAt->startOfDay()->addDays($intervalDays);
        }

        if ($item->commit_date) {
            return CarbonImmutable::parse($item->commit_date)->startOfDay();
        }

        return $today;
    }

    private function freshness(int $daysLeft, ?int $intervalDays): float
    {
        $span = $intervalDays ?: 7;

        return round(min(1, max(0, $daysLeft / $span)), 2);
    }
}
