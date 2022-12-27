<?php

namespace App\Domains\Budget\Exports;

use App\Domains\Budget\Models\BudgetMonth;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;

class BudgetExport implements FromQuery, WithMapping
{
    public function query() {
        return BudgetMonth::query()
        ->select([
            'budget_months.*',
            'c.name as categoryName',
            'g.name as groupName',
            'c.index as categoryIndex',
            'g.index as groupIndex'
        ])
        ->join(DB::raw('categories c'), 'c.id', 'category_id')
        ->join(DB::raw('categories g'), 'g.id', 'c.parent_id')
        ->orderBy('month')->orderBy('g.index')->orderBy('c.index');
    }

    public function map($budgetMonth): array
    {
       return [
            Carbon::createFromFormat('Y-m-d', $budgetMonth->month)->format('M-Y'),
            "{$budgetMonth->groupName}: {$budgetMonth->categoryName}",
            "{$budgetMonth->categoryName}",
            $this->formatMoney($budgetMonth, 'budgeted'),
            $this->formatMoney($budgetMonth, 'activity'),
            $this->formatMoney($budgetMonth,'available')
       ];
    }

    private function formatMoney($budget, $property) {
        return Money::of($budget->$property, $budget->currency_code, null);
    }
}
