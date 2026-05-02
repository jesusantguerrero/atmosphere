<?php

namespace App\Domains\Budget\Http\Controllers;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class GoalsController extends Controller
{
    public function index(Request $request): Response
    {
        $teamId = $request->user()->current_team_id;
        $filter = $request->input('filter', 'all');

        $targets = BudgetTarget::query()
            ->where('team_id', $teamId)
            ->whereIn('target_type', [
                BudgetTarget::TYPE_SAVING_BALANCE,
                BudgetTarget::TYPE_SAVING_MONTHLY,
            ])
            ->with(['category:id,name,parent_id', 'category.group:id,name'])
            ->orderByRaw('completed_at IS NULL DESC')
            ->orderByRaw('frequency_date IS NULL')
            ->orderBy('frequency_date')
            ->orderBy('id')
            ->get();

        $latestMonths = $this->latestMonthByCategory(
            $teamId,
            $targets->pluck('category_id')->all()
        );

        $today = Carbon::today();

        $goals = $targets->map(fn (BudgetTarget $target): array => $this->presentGoal($target, $latestMonths, $today));

        $counts = [
            'all' => $goals->count(),
            'monthly' => $goals->where('frequency', 'MONTHLY')->count(),
            'dated' => $goals->where('frequency', 'DATE')->count(),
            'overdue' => $goals->where('is_overdue', true)->count(),
            'completed' => $goals->where('is_completed', true)->count(),
        ];

        $filtered = match ($filter) {
            'monthly' => $goals->where('frequency', 'MONTHLY'),
            'dated' => $goals->where('frequency', 'DATE'),
            'overdue' => $goals->where('is_overdue', true),
            'completed' => $goals->where('is_completed', true),
            default => $goals,
        };

        return Inertia::render('Finance/Goals/Index', [
            'goals' => $filtered->values()->all(),
            'counts' => $counts,
            'filter' => $filter,
            'availableCategories' => $this->availableCategories($teamId),
        ]);
    }

    /**
     * @return array<int, array{id:int, name:string, group_name:string|null}>
     */
    private function availableCategories(int $teamId): array
    {
        $reserved = array_map(fn (BudgetReservedNames $n) => $n->value, BudgetReservedNames::cases());

        return Category::query()
            ->where('categories.team_id', $teamId)
            ->whereNotNull('categories.parent_id')
            ->where('categories.resource_type', 'transactions')
            ->whereNotIn('categories.name', $reserved)
            ->leftJoin('categories as parent', 'parent.id', '=', 'categories.parent_id')
            ->whereNotIn('parent.name', $reserved)
            ->orderBy('parent.index')
            ->orderBy('categories.index')
            ->get(['categories.id', 'categories.name', 'parent.name as group_name'])
            ->map(fn ($c) => [
                'id' => (int) $c->id,
                'name' => $c->name,
                'group_name' => $c->group_name,
            ])
            ->all();
    }

    /**
     * @param  array<int>  $categoryIds
     */
    private function latestMonthByCategory(int $teamId, array $categoryIds): Collection
    {
        if (empty($categoryIds)) {
            return collect();
        }

        return BudgetMonth::query()
            ->where('team_id', $teamId)
            ->whereIn('category_id', $categoryIds)
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy('category_id')
            ->map(fn (Collection $months) => $months->first());
    }

    /**
     * @return array{
     *     id:int, name:string|null, category_id:int, category_name:string|null,
     *     group_name:string|null, target_type:string|null, frequency:string|null,
     *     frequency_date:string|null, frequency_month_date:int|null,
     *     target_amount:float, current_amount:float, remaining_amount:float,
     *     progress:float, monthly_needed:float|null, months_remaining:int|null,
     *     days_remaining:int|null, is_completed:bool, is_overdue:bool,
     *     completed_at:string|null, created_at:string|null, color:string|null
     * }
     */
    private function presentGoal(BudgetTarget $target, Collection $latestMonths, Carbon $today): array
    {
        $month = $latestMonths->get($target->category_id);
        $current = (float) ($month?->available ?? 0);
        $targetAmount = (float) $target->amount;
        $progress = $targetAmount > 0 ? round(min(100, max(0, $current / $targetAmount * 100)), 1) : 0.0;

        $targetDate = $target->frequency_date ? Carbon::parse($target->frequency_date) : null;
        $daysRemaining = $targetDate ? (int) $today->diffInDays($targetDate, false) : null;
        $monthsRemaining = $targetDate ? (int) $today->diffInMonths($targetDate, false) : null;

        $remaining = max(0, $targetAmount - $current);

        $monthlyNeeded = match (true) {
            $target->frequency === 'DATE' && $monthsRemaining !== null && $monthsRemaining > 0 => $remaining / $monthsRemaining,
            $target->frequency === 'DATE' => $remaining,
            $target->frequency === 'MONTHLY' => $targetAmount,
            default => null,
        };

        $isCompleted = (bool) $target->completed_at || ($targetAmount > 0 && $current >= $targetAmount);
        $isOverdue = ! $isCompleted && $targetDate && $today->greaterThan($targetDate);

        return [
            'id' => $target->id,
            'name' => $target->name ?? $target->category?->name,
            'category_id' => $target->category_id,
            'category_name' => $target->category?->name,
            'group_name' => $target->category?->group?->name,
            'target_type' => $target->target_type,
            'frequency' => $target->frequency,
            'frequency_date' => $target->frequency_date,
            'frequency_month_date' => $target->frequency_month_date,
            'target_amount' => $targetAmount,
            'current_amount' => $current,
            'remaining_amount' => $remaining,
            'progress' => $progress,
            'monthly_needed' => $monthlyNeeded,
            'months_remaining' => $monthsRemaining,
            'days_remaining' => $daysRemaining,
            'is_completed' => $isCompleted,
            'is_overdue' => $isOverdue,
            'completed_at' => $target->completed_at,
            'created_at' => $target->created_at?->toIso8601String(),
            'color' => $target->color,
        ];
    }
}
