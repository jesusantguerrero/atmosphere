<?php

namespace App\Domains\Budget\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetAssignData;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * "Cubrir lo que falta": fund every category whose monthly target is not met yet, with what is
 * still in Ready to Assign. Categories due first get money first when it does not reach for all.
 */
class BudgetQuickAssignService
{
    /**
     * Targets that ask for an amount every month. A saving balance goal is a total, not a month.
     */
    const MONTHLY_TARGET_TYPES = ['spending', 'debt_monthly_payment', 'loan', 'savings_monthly'];

    public function __construct(private BudgetMovementService $movementService) {}

    /**
     * @return array{month: string, ready_to_assign: float, needed: float, assigned: float, missing: float, items: list<array{category_id: int, name: string, group_name: ?string, due_day: ?int, target: float, funded: float, needed: float, amount: float}>}
     */
    public function plan(int $teamId, string $month): array
    {
        $month = Carbon::parse($month)->startOfMonth();
        $months = BudgetMonth::query()
            ->where('team_id', $teamId)
            ->where('month', $month->format('Y-m-d'))
            ->get(['category_id', 'budgeted', 'activity', 'left_from_last_month'])
            ->keyBy('category_id');

        $readyToAssign = $this->readyToAssign($teamId, $month, $months);
        $left = max(0, $readyToAssign);

        $items = $this->underfunded($teamId, $month, $months)
            ->map(function (array $item) use (&$left) {
                $item['amount'] = round(min($item['needed'], $left), 2);
                $left = round($left - $item['amount'], 2);

                return $item;
            })
            ->values();

        $needed = round($items->sum('needed'), 2);
        $assigned = round($items->sum('amount'), 2);

        return [
            'month' => $month->format('Y-m'),
            'ready_to_assign' => round($readyToAssign, 2),
            'needed' => $needed,
            'assigned' => $assigned,
            'missing' => round($needed - $assigned, 2),
            'items' => $items->all(),
        ];
    }

    /**
     * Assign the plan amounts on top of what each category already has. Recomputed here, so a stale
     * preview can never assign more than Ready to Assign holds now.
     *
     * @param  list<int>|null  $categoryIds  Only these categories, when the user unticked some.
     * @return array{funded: int, assigned: float}
     */
    public function apply(int $teamId, int $userId, string $month, ?array $categoryIds = null): array
    {
        $month = Carbon::parse($month)->startOfMonth()->format('Y-m-d');
        $plan = $this->plan($teamId, $month);
        $months = BudgetMonth::query()
            ->where('team_id', $teamId)
            ->where('month', $month)
            ->pluck('budgeted', 'category_id');

        $left = max(0, $plan['ready_to_assign']);
        $funded = 0;
        $assigned = 0.0;

        foreach ($plan['items'] as $item) {
            if ($categoryIds !== null && ! in_array($item['category_id'], $categoryIds, true)) {
                continue;
            }
            $amount = round(min($item['needed'], $left), 2);
            if ($amount <= 0) {
                continue;
            }

            $this->movementService->registerAssignment(new BudgetAssignData(
                $teamId,
                $userId,
                $month,
                $item['category_id'],
                round((float) ($months[$item['category_id']] ?? 0) + $amount, 2),
            ));
            $left = round($left - $amount, 2);
            $assigned += $amount;
            $funded++;
        }

        return ['funded' => $funded, 'assigned' => round($assigned, 2)];
    }

    /**
     * Same as the Budget screen: inflow this month plus its carry-over, minus everything assigned.
     *
     * @param  Collection<int, BudgetMonth>  $months
     */
    private function readyToAssign(int $teamId, Carbon $month, Collection $months): float
    {
        $groups = Category::query()
            ->without(['budget', 'matchAccount'])
            ->where('team_id', $teamId)
            ->where('resource_type', 'transactions')
            ->whereNull('parent_id')
            ->whereNot('name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->with(['subCategories' => fn ($query) => $query->without(['budget', 'matchAccount'])
                ->where(fn ($query) => $query->whereNull('account_id')
                    ->orWhereDoesntHave('account', fn ($query) => $query->whereNotNull('closed_at')->where('closed_at', '<=', $month->format('Y-m-d'))))])
            ->get(['id', 'name']);

        [$inflow, $outflow] = $groups->partition(fn (Category $group) => $group->name === BudgetReservedNames::INFLOW->value);
        $inflowMonth = $months->get($inflow->first()?->subCategories->first()?->id);
        $assigned = $outflow->flatMap->subCategories->sum(fn (Category $category) => (float) $months->get($category->id)?->budgeted);

        return (float) $inflowMonth?->activity + (float) $inflowMonth?->left_from_last_month - $assigned;
    }

    /**
     * @param  Collection<int, BudgetMonth>  $months
     * @return Collection<int, array{category_id: int, name: string, group_name: ?string, due_day: ?int, target: float, funded: float, needed: float}>
     */
    private function underfunded(int $teamId, Carbon $month, Collection $months): Collection
    {
        $targets = BudgetTarget::query()
            ->where('team_id', $teamId)
            ->whereIn('target_type', self::MONTHLY_TARGET_TYPES)
            ->whereNull('completed_at')
            ->where(fn ($query) => $query->whereNull('snoozed_month')->orWhere('snoozed_month', '!=', $month->format('Y-m')))
            ->with(['category' => fn ($query) => $query->without(['budget', 'matchAccount'])])
            ->get()
            ->filter(fn (BudgetTarget $target) => $target->category?->parent_id && (float) $target->amount > 0);
        $groupNames = Category::query()->without(['budget', 'matchAccount'])
            ->whereIn('id', $targets->pluck('category.parent_id')->unique())
            ->pluck('name', 'id');

        return $targets
            ->map(function (BudgetTarget $target) use ($month, $months, $groupNames) {
                $row = $months->get($target->category_id);
                $monthly = round((float) $target->amount * $this->timesInMonth($target, $month), 2);
                $funded = round((float) $row?->budgeted + (float) $row?->left_from_last_month, 2);

                return [
                    'category_id' => $target->category_id,
                    'name' => $target->category->name,
                    'group_name' => $groupNames->get($target->category->parent_id),
                    'due_day' => $target->frequency_month_date ? (int) $target->frequency_month_date : null,
                    'target' => $monthly,
                    'funded' => $funded,
                    'needed' => round(max(0, $monthly - $funded), 2),
                ];
            })
            ->filter(fn (array $item) => $item['needed'] > 0)
            ->unique('category_id')
            ->sortBy([fn (array $a, array $b) => ($a['due_day'] ?? 32) <=> ($b['due_day'] ?? 32), ['name', 'asc']])
            ->values();
    }

    /**
     * A weekly target counts once per matching weekday in the month, like getBudgetTarget on the web.
     */
    private function timesInMonth(BudgetTarget $target, Carbon $month): int
    {
        if (strtoupper((string) $target->frequency) !== 'WEEKLY' || ! $target->frequency_week_day) {
            return 1;
        }

        $weekDay = strtolower(substr((string) $target->frequency_week_day, 0, 2));

        return collect(range(0, $month->daysInMonth - 1))
            ->filter(fn (int $offset) => strtolower(substr($month->copy()->addDays($offset)->englishDayOfWeek, 0, 2)) === $weekDay)
            ->count();
    }
}
