<?php

namespace App\Domains\Today\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
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
     * MEAL THIS WEEK: week's planned meals grouped by day (from MealPlan via Planner).
     * Widened from today-only to start-of-week → end-of-week so the user sees
     * "this week's menu" at a glance. Each entry includes the date for UI grouping.
     *
     * @return array<int, array<string, mixed>>
     */
    private function meal(int $teamId): array
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $weekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        return $this->mealService->getMealScheduleInFrame($teamId, $weekStart, $weekEnd)
            ->sortBy(fn ($plan) => $plan->date)
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'meal_id' => $plan->dateable?->meal_id,
                'name' => $plan->dateable?->name,
                'meal_type' => $plan->dateable?->mealType?->name,
                'is_liked' => (bool) ($plan->dateable?->meal?->is_liked ?? false),
                'date' => $plan->date->format('Y-m-d'),
                'day_label' => $plan->date->isToday() ? 'Today' : $plan->date->format('D'),
            ])
            ->values()
            ->all();
    }

    /**
     * MONEY: today's spend + daily remaining budget.
     *
     * `today_spent` — action surface ("did you log it?").
     * `daily_remaining` — (month_budgeted − month_spent) / days_left — gives
     * a "safe to spend per day" number without duplicating Dashboard's full
     * budget donut. Spending-only (excludes savings targets).
     *
     * @return array{today_spent: float, daily_remaining: float, month_remaining: float, days_in_month_left: int, currency_code: ?string}
     */
    public function money(int $teamId, Carbon $today): array
    {
        $todayRow = TransactionService::getExpensesTotal(
            $teamId,
            $today->copy()->startOfDay()->format('Y-m-d'),
            $today->copy()->endOfDay()->format('Y-m-d')
        );

        $monthStart = $today->copy()->startOfMonth()->format('Y-m-d');

        // Total budgeted for spending categories this month (excludes savings)
        $monthBudgeted = (float) BudgetMonth::query()
            ->where('budget_months.team_id', $teamId)
            ->where('month', $monthStart)
            ->join('categories', fn ($q) => $q->on('categories.id', 'budget_months.category_id')
                ->whereNot('categories.name', BudgetReservedNames::READY_TO_ASSIGN->value)
            )
            ->leftJoin('budget_targets', 'budget_targets.category_id', 'budget_months.category_id')
            ->where(fn ($q) => $q
                ->whereNull('budget_targets.target_type')
                ->orWhereNotIn('budget_targets.target_type', ['saving_balance', 'savings_monthly'])
            )
            ->sum('budget_months.budgeted');

        // Total spent this month so far
        $monthSpentRow = TransactionService::getExpensesTotal(
            $teamId,
            $monthStart,
            $today->format('Y-m-d')
        );

        $daysRemaining = max(1, $today->copy()->endOfMonth()->startOfDay()->diffInDays($today->copy()->startOfDay()) + 1); // includes today
        $monthRemaining = $monthBudgeted - abs($monthSpentRow?->total_amount ?? 0);
        $dailyRemaining = $monthRemaining / $daysRemaining;

        return [
            'today_spent' => (float) abs($todayRow?->total_amount ?? 0),
            'daily_remaining' => round($dailyRemaining, 2),
            'month_remaining' => round($monthRemaining, 2),
            'days_in_month_left' => $daysRemaining,
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
                // Free-form `name` wins (school/family TODOs); fall back to the
                // dateable type label for legacy rows that depend on the polymorph.
                'name' => $p->name ?: ($p->dateable_type ? class_basename($p->dateable_type) : null),
                'subtitle' => $p->notes,
                'status' => $p->status,
                'total' => $p->total !== null ? (float) $p->total : null,
                'source' => $p->source,
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
     * UPCOMING: things to plan ahead for.
     *
     * Three sources:
     *   - BillingCycle (credit cards) — `due_at` BETWEEN today and +7d, not paid
     *   - Occurrence with type=utility (HM-1) — recurrence due by `last_date + avg_days_passed` ≤ +7d
     *   - Planner rows with a free-form `name` set (no dateable) — date > today
     *     within the next 30 days, not completed. Wider window than financial items
     *     because school/family TODOs are batched monthly and the user needs to
     *     see the whole month for "anticipation" (their original ask).
     *
     * Each item is tagged with `kind` so the UI can render appropriate icon/copy.
     *
     * @return array<int, array<string, mixed>>
     */
    private function upcoming(int $teamId, Carbon $today): array
    {
        $windowEnd = $today->copy()->addDays(7)->endOfDay();
        $plannerWindowEnd = $today->copy()->addDays(30)->endOfDay();

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

        $plannerUpcoming = Planner::query()
            ->where('team_id', $teamId)
            ->whereNotNull('name')
            ->whereDate('date', '>', $today->format('Y-m-d'))
            ->whereDate('date', '<=', $plannerWindowEnd->format('Y-m-d'))
            ->whereNull('completed_at')
            ->orderBy('date')
            ->get()
            ->map(fn ($p) => [
                'kind' => 'planner',
                'id' => 'planner-up-'.$p->id,
                'name' => $p->name,
                'account_id' => null,
                'total' => $p->total !== null ? (float) $p->total : null,
                'due_at' => $p->date->format('Y-m-d'),
                'days_until' => (int) $today->copy()->startOfDay()->diffInDays($p->date->copy()->startOfDay(), false),
                'source' => $p->source,
                'notes' => $p->notes,
            ]);

        return $cycles
            ->concat($utilities)
            ->concat($plannerUpcoming)
            ->sortBy('days_until')
            ->take(20)
            ->values()
            ->all();
    }
}
