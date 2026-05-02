<?php

namespace App\Domains\Budget\Exports;

use App\Domains\Budget\Models\BudgetMonth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BudgetExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected int $teamId,
        protected ?string $month = null,
    ) {}

    public function query()
    {
        $query = BudgetMonth::query()
            ->select([
                'budget_months.*',
                'c.name as categoryName',
                'c.display_id as categoryDisplayId',
                'g.name as groupName',
                'c.index as categoryIndex',
                'g.index as groupIndex',
            ])
            ->join(DB::raw('categories c'), 'c.id', 'category_id')
            ->join(DB::raw('categories g'), 'g.id', 'c.parent_id')
            ->where('budget_months.team_id', $this->teamId)
            ->orderBy('month')
            ->orderBy('g.index')
            ->orderBy('c.index');

        if ($this->month) {
            $query->where('budget_months.month', $this->month);
        }

        return $query;
    }

    public function map($budgetMonth): array
    {
        return [
            Carbon::createFromFormat('Y-m-d', $budgetMonth->month)->format('M Y'),
            "{$budgetMonth->groupName}: {$budgetMonth->categoryName}",
            "{$budgetMonth->groupName}",
            "{$budgetMonth->categoryName}",
            $this->formatMoney($budgetMonth, 'budgeted'),
            $this->formatMoney($budgetMonth, 'activity'),
            $this->formatMoneyValue($budgetMonth, $this->resolveAvailable($budgetMonth)),
        ];
    }

    private function resolveAvailable($budgetMonth): float
    {
        if ($budgetMonth->categoryDisplayId === 'ready_to_assign') {
            return (float) ($budgetMonth->activity ?? 0)
                + (float) ($budgetMonth->left_from_last_month ?? 0)
                - (float) ($budgetMonth->budgeted ?? 0);
        }

        return (float) $budgetMonth->available;
    }

    private function formatMoney($budget, $property)
    {
        return $this->formatMoneyValue($budget, (float) $budget->$property);
    }

    private function formatMoneyValue($budget, float $value): string
    {
        $sign = $value < 0 ? '-' : '';

        return $sign.$budget->currency_code.'$'.abs($value);
    }

    public function headings(): array
    {
        return [
            'Month',
            'Category Group/Category',
            'Category Group',
            'Category',
            'Budgeted',
            'Activity',
            'Available',
        ];
    }
}
