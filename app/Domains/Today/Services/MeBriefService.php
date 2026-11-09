<?php

namespace App\Domains\Today\Services;

use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\TransactionService;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Decision summary for the mobile «Tú» screen: this month against the previous
 * one and the 3-month baseline, with the categories, fixed charges, budget lines
 * and occurrences that deviate. Honors the Insights exclusions.
 */
class MeBriefService
{
    public const MIN_DELTA = 1500;

    public const MIN_PCT = 15;

    private const CATEGORY_LIMIT = 10;

    private const SAVINGS_TARGET_TYPES = ['saving_balance', 'savings_monthly'];

    /**
     * @param  array{min_delta?: float|int|string|null, min_pct?: float|int|string|null}  $options
     * @return array<string, mixed>
     */
    public function build(int $teamId, array $options = []): array
    {
        $context = $this->context($teamId);
        $categoryRows = $this->categoryMonthly($context);
        $closedMonths = $context['baseline_months'];
        $monthsWithData = collect($closedMonths)->filter(fn (string $month) => $categoryRows->contains('ym', $month))->count();
        $hasHistory = $monthsWithData > 0;
        $prevMonth = end($closedMonths);
        $currentMonth = $context['month'];

        $monthTotals = $categoryRows->groupBy('ym')->map(fn (Collection $rows) => $rows->sum('total'));
        $spentToDate = round((float) ($monthTotals[$currentMonth] ?? 0), 2);
        $hasPrevMonth = $monthTotals->has($prevMonth);
        $daily = $this->dailyCumulative($context, $hasPrevMonth);
        $income = $this->incomeMonthly($context);

        return [
            'period' => [
                'month' => $currentMonth,
                'day' => $context['today']->day,
                'days_elapsed' => $context['today']->day,
                'days_left' => $context['today']->daysInMonth - $context['today']->day,
                'baseline_months' => $closedMonths,
                'months_with_data' => $monthsWithData,
                'currency_code' => $context['currency_code'],
            ],
            'month' => [
                'spent_to_date' => $spentToDate,
                'spent_same_day_prev_month' => $hasPrevMonth ? $daily['same_day_previous'] : null,
                'prev_month_total' => $hasPrevMonth ? round((float) $monthTotals[$prevMonth], 2) : null,
                'avg_3m_total' => $hasHistory ? round(collect($closedMonths)->sum(fn (string $month) => (float) ($monthTotals[$month] ?? 0)) / $monthsWithData, 2) : null,
                'projected_total' => round($spentToDate / $context['today']->day * $context['today']->daysInMonth, 2),
                'income_to_date' => round((float) ($income[$currentMonth] ?? 0), 2),
                'budget_available_spending' => $this->availableSpending($context),
                'daily_cumulative' => $daily['series'],
            ],
            'categories' => $hasHistory ? $this->categories($categoryRows, $context, $monthsWithData, $options) : [],
            'recurring' => $hasHistory ? $this->recurring($context) : [],
            'savings' => $this->savings($context, $monthTotals, $income),
            'budget_strain' => $hasHistory ? $this->budgetStrain($context, $categoryRows, $monthsWithData) : [],
            'occurrences_speeding' => $this->occurrencesSpeeding($teamId),
        ];
    }

    /**
     * @return array{team_id: int, today: Carbon, month: string, baseline_months: list<string>, start: string, end: string, currency_code: string, excluded_category_ids: list<int>, excluded_group_ids: list<int>}
     */
    private function context(int $teamId): array
    {
        $settings = Setting::getByTeam($teamId);
        $today = Carbon::now($settings['team_timezone'] ?? 'America/Santo_Domingo')->startOfDay();
        $firstOfMonth = $today->copy()->startOfMonth();

        return [
            'team_id' => $teamId,
            'today' => $today,
            'month' => $firstOfMonth->format('Y-m'),
            'baseline_months' => collect([3, 2, 1])->map(fn (int $back) => $firstOfMonth->copy()->subMonthsNoOverflow($back)->format('Y-m'))->all(),
            'start' => $firstOfMonth->copy()->subMonthsNoOverflow(3)->format('Y-m-d'),
            'end' => $today->format('Y-m-d'),
            'currency_code' => strtoupper(trim((string) ($settings['team_primary_currency_code'] ?? ''))) ?: 'DOP',
            'excluded_category_ids' => array_map('intval', json_decode($settings['insights_excluded_category_ids'] ?? '[]', true) ?: []),
            'excluded_group_ids' => array_map('intval', json_decode($settings['insights_excluded_group_ids'] ?? '[]', true) ?: []),
        ];
    }

    /**
     * Verified spending joined to its category and group, minus inflow, Ready to
     * Assign and the Insights exclusions. Same criteria as Insights.
     */
    private function expenseQuery(array $context, string $start, string $end): Builder
    {
        return DB::table('transactions')
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->join(DB::raw('categories pc'), 'pc.id', '=', 'categories.parent_id')
            ->where('transactions.team_id', $context['team_id'])
            ->where('categories.resource_type', 'transactions')
            ->where('transactions.direction', Transaction::DIRECTION_CREDIT)
            ->where('transactions.status', 'verified')
            ->whereNull('transactions.deleted_at')
            ->where('pc.display_id', '!=', 'inflow')
            ->where('categories.name', '!=', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->whereBetween('transactions.date', [$start, $end])
            ->when($context['excluded_category_ids'], fn (Builder $query, array $ids) => $query->whereNotIn('categories.id', $ids))
            ->when($context['excluded_group_ids'], fn (Builder $query, array $ids) => $query->whereNotIn('categories.parent_id', $ids));
    }

    /**
     * @return Collection<int, object{id: int, name: string, group_name: string, ym: string, total: float}>
     */
    private function categoryMonthly(array $context): Collection
    {
        return $this->expenseQuery($context, $context['start'], $context['end'])
            ->selectRaw('categories.id, categories.name, pc.name as group_name, DATE_FORMAT(transactions.date, "%Y-%m") as ym, SUM(COALESCE(transactions.total, 0)) as total')
            ->groupByRaw('categories.id, categories.name, pc.name, DATE_FORMAT(transactions.date, "%Y-%m")')
            ->get()
            ->map(function (object $row) {
                $row->id = (int) $row->id;
                $row->total = (float) (string) $row->total;

                return $row;
            });
    }

    /**
     * @return array{series: list<array{day: int, current: float|null, previous: float|null}>, same_day_previous: float}
     */
    private function dailyCumulative(array $context, bool $hasPrevMonth): array
    {
        $today = $context['today'];
        $prevStart = $today->copy()->startOfMonth()->subMonthNoOverflow();
        $daysInPrev = $prevStart->daysInMonth;

        $byDate = $this->expenseQuery($context, $prevStart->format('Y-m-d'), $context['end'])
            ->selectRaw('DATE(transactions.date) as day_date, SUM(COALESCE(transactions.total, 0)) as total')
            ->groupByRaw('DATE(transactions.date)')
            ->pluck('total', 'day_date')
            ->map(fn ($total) => (float) (string) $total);

        $current = 0.0;
        $previous = 0.0;
        $sameDayPrevious = 0.0;
        $series = [];
        foreach (range(1, max($today->daysInMonth, $daysInPrev)) as $day) {
            $previous += $day <= $daysInPrev ? (float) ($byDate[$prevStart->copy()->day($day)->format('Y-m-d')] ?? 0) : 0;
            if ($day <= $today->day) {
                $current += (float) ($byDate[$today->copy()->day($day)->format('Y-m-d')] ?? 0);
            }
            if ($day === min($today->day, $daysInPrev)) {
                $sameDayPrevious = $previous;
            }
            if ($day > $today->daysInMonth) {
                continue;
            }
            $series[] = [
                'day' => $day,
                'current' => $day <= $today->day ? round($current, 2) : null,
                'previous' => $hasPrevMonth && $day <= $daysInPrev ? round($previous, 2) : null,
            ];
        }

        return ['series' => $series, 'same_day_previous' => round($sameDayPrevious, 2)];
    }

    /**
     * Deposits with a payee per month, the same income source as /insights.
     *
     * @return Collection<string, float>
     */
    private function incomeMonthly(array $context): Collection
    {
        return collect(TransactionService::getTransactionsByPayeeInPeriod($context['team_id'], $context['start'], $context['end'], Transaction::DIRECTION_DEBIT))
            ->groupBy(fn (object $row) => substr((string) $row->date, 0, 7))
            ->map(fn (Collection $rows) => $rows->sum(fn (object $row) => (float) (string) $row->total));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categories(Collection $rows, array $context, int $monthsWithData, array $options): array
    {
        $minDelta = (float) ($options['min_delta'] ?? self::MIN_DELTA);
        $minPct = (float) ($options['min_pct'] ?? self::MIN_PCT);
        $prevMonth = end($context['baseline_months']);

        return $rows->groupBy('id')
            ->map(function (Collection $months) use ($context, $monthsWithData, $prevMonth) {
                $byMonth = $months->pluck('total', 'ym');
                $thisMonth = (float) ($byMonth[$context['month']] ?? 0);
                $avg = collect($context['baseline_months'])->sum(fn (string $month) => (float) ($byMonth[$month] ?? 0)) / $monthsWithData;
                $delta = $thisMonth - $avg;

                return [
                    'id' => $months->first()->id,
                    'name' => $months->first()->name,
                    'group_name' => $months->first()->group_name,
                    'this_month' => round($thisMonth, 2),
                    'prev_month' => round((float) ($byMonth[$prevMonth] ?? 0), 2),
                    'avg_3m' => round($avg, 2),
                    'delta_vs_avg' => round($delta, 2),
                    'delta_pct' => $avg > 0 ? round($delta / $avg * 100, 1) : null,
                ];
            })
            ->filter(fn (array $category) => abs($category['delta_vs_avg']) >= $minDelta
                && ($category['delta_pct'] === null || abs($category['delta_pct']) >= $minPct))
            ->sortByDesc(fn (array $category) => abs($category['delta_vs_avg']))
            ->take(self::CATEGORY_LIMIT)
            ->values()
            ->all();
    }

    /**
     * Payees charged in at least 3 of the last 4 months (closed months plus this one).
     *
     * @return list<array<string, mixed>>
     */
    private function recurring(array $context): array
    {
        $windowMonths = [...array_slice($context['baseline_months'], -3), $context['month']];

        return $this->expenseQuery($context, $context['start'], $context['end'])
            ->join('payees', 'payees.id', '=', 'transactions.payee_id')
            ->selectRaw('payees.id as payee_id, payees.name as payee_name, DATE_FORMAT(transactions.date, "%Y-%m") as ym, SUM(COALESCE(transactions.total, 0)) as total, MAX(transactions.date) as last_date, SUBSTRING_INDEX(GROUP_CONCAT(transactions.category_id ORDER BY transactions.date DESC), ",", 1) as category_id')
            ->groupByRaw('payees.id, payees.name, DATE_FORMAT(transactions.date, "%Y-%m")')
            ->get()
            ->groupBy('payee_id')
            ->map(function (Collection $months) use ($context, $windowMonths) {
                $seen = $months->whereIn('ym', $windowMonths);
                if ($seen->count() < 3) {
                    return null;
                }
                $latest = $months->sortByDesc('last_date')->first();
                $closedTotals = $seen->where('ym', '!=', $context['month'])->map(fn (object $row) => (float) (string) $row->total);
                $median = $this->median($closedTotals->isNotEmpty() ? $closedTotals : $seen->map(fn (object $row) => (float) (string) $row->total));
                $thisMonth = (float) (string) ($seen->firstWhere('ym', $context['month'])?->total ?? 0);

                return [
                    'payee_id' => (int) $latest->payee_id,
                    'payee_name' => $latest->payee_name,
                    'category_id' => $latest->category_id !== null ? (int) $latest->category_id : null,
                    'months_seen' => $seen->count(),
                    'median_amount' => round($median, 2),
                    'this_month_amount' => round($thisMonth, 2),
                    'delta_pct' => $median > 0 && $thisMonth > 0 ? round(($thisMonth - $median) / $median * 100, 1) : null,
                    'last_date' => substr((string) $latest->last_date, 0, 10),
                ];
            })
            ->filter()
            ->sortByDesc('median_amount')
            ->values()
            ->all();
    }

    /**
     * @return list<array{month: string, income: float, expense: float, savings_rate: float|null}>
     */
    private function savings(array $context, Collection $monthTotals, Collection $income): array
    {
        return collect([...$context['baseline_months'], $context['month']])
            ->map(function (string $month) use ($monthTotals, $income) {
                $monthIncome = round((float) ($income[$month] ?? 0), 2);
                $expense = round((float) ($monthTotals[$month] ?? 0), 2);

                return [
                    'month' => $month,
                    'income' => $monthIncome,
                    'expense' => $expense,
                    'savings_rate' => $monthIncome > 0 ? round(($monthIncome - $expense) / $monthIncome * 100, 1) : null,
                ];
            })
            ->filter(fn (array $row) => $row['income'] > 0 || $row['expense'] > 0)
            ->values()
            ->all();
    }

    private function budgetQuery(array $context): EloquentBuilder
    {
        return BudgetMonth::query()
            ->where('budget_months.team_id', $context['team_id'])
            ->join('categories', 'categories.id', '=', 'budget_months.category_id')
            ->where('categories.name', '!=', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->when($context['excluded_category_ids'], fn ($query, array $ids) => $query->whereNotIn('categories.id', $ids))
            ->when($context['excluded_group_ids'], fn ($query, array $ids) => $query->whereNotIn('categories.parent_id', $ids));
    }

    private function availableSpending(array $context): float
    {
        $total = $this->budgetQuery($context)
            ->where('budget_months.month', $context['month'].'-01')
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')
                ->from('budget_targets')
                ->whereColumn('budget_targets.category_id', 'budget_months.category_id')
                ->whereIn('budget_targets.target_type', self::SAVINGS_TARGET_TYPES))
            ->sum('budget_months.available');

        return round((float) (string) $total, 2);
    }

    /**
     * Categories overspent (available < 0) in at least 2 of the 3 closed months.
     *
     * @return list<array<string, mixed>>
     */
    private function budgetStrain(array $context, Collection $categoryRows, int $monthsWithData): array
    {
        $closedMonths = collect($context['baseline_months'])->map(fn (string $month) => $month.'-01');
        $spentByCategory = $categoryRows->whereIn('ym', $context['baseline_months'])->groupBy('id')->map(fn (Collection $rows) => $rows->sum('total'));

        return $this->budgetQuery($context)
            ->whereIn('budget_months.month', $closedMonths)
            ->get(['budget_months.category_id', 'categories.name', 'budget_months.month', 'budget_months.budgeted', 'budget_months.available'])
            ->groupBy('category_id')
            ->map(function (Collection $months) use ($spentByCategory, $monthsWithData) {
                $overspent = $months->filter(fn ($row) => (float) (string) $row->available < 0)->count();
                if ($overspent < 2) {
                    return null;
                }
                $spentAvg = (float) ($spentByCategory[$months->first()->category_id] ?? 0) / $monthsWithData;

                return [
                    'category_id' => (int) $months->first()->category_id,
                    'name' => $months->first()->name,
                    'budgeted_avg_3m' => round($months->sum(fn ($row) => (float) (string) $row->budgeted) / 3, 2),
                    'spent_avg_3m' => round($spentAvg, 2),
                    'months_overspent' => $overspent,
                    'suggested_budget' => (float) (ceil($spentAvg / 500) * 500),
                ];
            })
            ->filter()
            ->sortByDesc('months_overspent')
            ->values()
            ->all();
    }

    /**
     * Occurrences whose last 3 intervals average 20% or more below their usual pace.
     *
     * @return list<array{id: int, name: string, avg_days_passed: float, recent_avg_days: float, change_pct: float}>
     */
    private function occurrencesSpeeding(int $teamId): array
    {
        return Occurrence::query()
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->get(['id', 'name', 'avg_days_passed', 'log'])
            ->map(function (Occurrence $occurrence) {
                $dates = collect((array) $occurrence->log)->filter()->map(fn ($date) => Carbon::parse($date)->startOfDay())->sort()->values();
                $average = (float) $occurrence->avg_days_passed;
                if ($dates->count() < 4 || $average <= 0) {
                    return null;
                }
                $intervals = $dates->slice(-4)->values()->sliding(2)->map(fn (Collection $pair) => $pair->first()->diffInDays($pair->last(), true));
                $recent = $intervals->avg();
                $change = ($recent - $average) / $average * 100;
                if ($change > -20) {
                    return null;
                }

                return [
                    'id' => $occurrence->id,
                    'name' => $occurrence->name,
                    'avg_days_passed' => round($average, 1),
                    'recent_avg_days' => round($recent, 1),
                    'change_pct' => round($change, 1),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, float>  $values
     */
    private function median(Collection $values): float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();
        if ($count === 0) {
            return 0.0;
        }
        $middle = intdiv($count, 2);

        return $count % 2 ? $sorted[$middle] : ($sorted[$middle - 1] + $sorted[$middle]) / 2;
    }
}
