<?php

namespace App\Domains\Today\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Meal\Services\MealService;
use App\Domains\Transaction\Models\BillingCycle;
use App\Domains\Transaction\Services\TransactionService;
use App\Notifications\WatchlistThresholdAlert;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;

/**
 * TODAY-1 v0.1 — aggregates the four widgets on /today using only primitives that
 * already exist in v2.0. Widgets enrich automatically as feeders mature
 * (HM-1 utilities, FM-1 reminders, FD-1 favorites). See tasks.md TODAY-1.
 */
class TodayService
{
    public function __construct(
        private MealService $mealService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(int $teamId, int $userId): array
    {
        $today = Carbon::now();

        return [
            'money' => $this->money($teamId, $today),
            'attention' => $this->attention($userId, $today),
            'today' => $this->today($teamId, $today),
            'upcoming' => $this->upcoming($teamId, $today),
            'meal' => $this->meal($teamId),
        ];
    }

    /**
     * MEAL HOY: today's planned meals (from MealPlan via Planner). FD-1 v0.2.
     * Empty array when no meals scheduled — widget renders empty state with link
     * to /meal-planner. Each entry exposes the meal type label so the UI can
     * group "Breakfast / Lunch / Dinner" in order.
     *
     * @return array<int, array<string, mixed>>
     */
    private function meal(int $teamId): array
    {
        return $this->mealService->getMealSchedule($teamId)
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'meal_id' => $plan->dateable?->meal_id,
                'name' => $plan->dateable?->name,
                'meal_type' => $plan->dateable?->mealType?->name,
                'is_liked' => (bool) ($plan->dateable?->meal?->is_liked ?? false),
            ])
            ->all();
    }

    /**
     * MONEY (today-only): a single number — what was spent today.
     *
     * Intentionally narrow: Dashboard owns the comprehensive "how am I doing
     * this month" view (budget donut, % spent, totals, charts). Today is the
     * action surface — "log what you spent" is the action this card invites.
     * Showing the same month aggregates here was design overlap.
     *
     * @return array{today_spent: float, currency_code: ?string}
     */
    private function money(int $teamId, Carbon $today): array
    {
        $todayRow = TransactionService::getExpensesTotal(
            $teamId,
            $today->copy()->startOfDay()->format('Y-m-d'),
            $today->copy()->endOfDay()->format('Y-m-d')
        );

        return [
            'today_spent' => (float) abs($todayRow?->total_amount ?? 0),
            'currency_code' => null,
        ];
    }

    /**
     * ATTENTION: unread watchlist threshold alerts triggered this month.
     * The WL-1 engine writes these to `notifications`; we surface them here too
     * so the user doesn't have to open the bell to see them.
     *
     * @return array<int, array<string, mixed>>
     */
    private function attention(int $userId, Carbon $today): array
    {
        return DatabaseNotification::query()
            ->where('notifiable_id', $userId)
            ->where('type', WatchlistThresholdAlert::class)
            ->whereNull('read_at')
            ->where('data->month', $today->format('Y-m'))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? '',
                'cta' => $n->data['cta'] ?? null,
                'link' => $n->data['link'] ?? null,
            ])
            ->all();
    }

    /**
     * TODAY: two sources tagged with `kind`:
     *   - `planner`: Planner records dated today (planned transactions etc.) not completed
     *   - `relationship`: FM-1 — Occurrence type=relationship that is overdue or due today
     *
     * @return array<int, array<string, mixed>>
     */
    private function today(int $teamId, Carbon $today): array
    {
        $plannerItems = Planner::query()
            ->where('team_id', $teamId)
            ->whereDate('date', $today->format('Y-m-d'))
            ->whereNull('completed_at')
            ->orderBy('date')
            ->get()
            ->map(fn ($p) => [
                'kind' => 'planner',
                'id' => 'planner-'.$p->id,
                'name' => $p->dateable_type ? class_basename($p->dateable_type) : null,
                'subtitle' => null,
                'status' => $p->status,
            ]);

        $relationships = Occurrence::query()
            ->byTeam($teamId)
            ->ofType(Occurrence::TYPE_RELATIONSHIP)
            ->where('is_active', true)
            ->whereNotNull('last_date')
            ->where('avg_days_passed', '>', 0)
            ->get()
            ->filter(function ($occurrence) {
                $days = $occurrence->daysUntilNext();

                // Overdue (≤0) — surface so the user knows it's been longer than usual.
                return $days !== null && $days <= 0;
            })
            ->map(function ($occurrence) {
                $days = $occurrence->daysUntilNext() ?? 0;

                return [
                    'kind' => 'relationship',
                    'id' => 'relationship-'.$occurrence->id,
                    'name' => $occurrence->name,
                    'subtitle' => $days < 0 ? abs($days).' days overdue' : 'due today',
                    'status' => null,
                ];
            });

        return $plannerItems
            ->concat($relationships)
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * UPCOMING: bills + utilities due in the next 7 days.
     *
     * Two sources:
     *   - BillingCycle (credit cards) — `due_at` BETWEEN today and +7d, not paid
     *   - Occurrence with type=utility (HM-1) — recurrence due by `last_date + avg_days_passed` ≤ +7d
     *
     * Each item is tagged with `kind` so the UI can render appropriate icon/copy.
     *
     * @return array<int, array<string, mixed>>
     */
    private function upcoming(int $teamId, Carbon $today): array
    {
        $windowEnd = $today->copy()->addDays(7)->endOfDay();

        $cycles = BillingCycle::query()
            ->where('team_id', $teamId)
            ->whereBetween('due_at', [
                $today->copy()->startOfDay()->format('Y-m-d'),
                $windowEnd->format('Y-m-d'),
            ])
            ->where('status', '!=', BillingCycle::STATUS_PAID)
            ->with('account:id,name')
            ->orderBy('due_at')
            ->get()
            ->map(fn ($cycle) => [
                'kind' => 'billing_cycle',
                'id' => 'cycle-'.$cycle->id,
                'name' => $cycle->account?->name,
                'account_id' => $cycle->account_id,
                'total' => (float) $cycle->total,
                'due_at' => optional($cycle->due_at)->format('Y-m-d'),
                'days_until' => $today->copy()->startOfDay()->diffInDays($cycle->due_at, false),
            ]);

        $utilities = Occurrence::query()
            ->byTeam($teamId)
            ->ofType(Occurrence::TYPE_UTILITY)
            ->where('is_active', true)
            ->whereNotNull('last_date')
            ->where('avg_days_passed', '>', 0)
            ->get()
            ->filter(function ($occurrence) {
                $days = $occurrence->daysUntilNext();

                // Include overdue (≤0) AND within next 7 days. Overdue stays surfaced
                // until the user logs the next occurrence so it doesn't silently fall off.
                return $days !== null && $days <= 7;
            })
            ->map(function ($occurrence) {
                $nextDate = $occurrence->last_date->copy()->addDays((int) $occurrence->avg_days_passed);

                return [
                    'kind' => 'utility',
                    'id' => 'utility-'.$occurrence->id,
                    'name' => $occurrence->name,
                    'account_id' => null,
                    'total' => null,
                    'due_at' => $nextDate->format('Y-m-d'),
                    'days_until' => $occurrence->daysUntilNext(),
                ];
            });

        return $cycles
            ->concat($utilities)
            ->sortBy('days_until')
            ->take(10)
            ->values()
            ->all();
    }
}
