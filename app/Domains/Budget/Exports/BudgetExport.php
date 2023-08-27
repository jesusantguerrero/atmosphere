<?php

namespace App\Domains\Budget\Exports;

use App\Domains\Budget\Models\BudgetMonth;
use App\Models\Setting;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Ramsey\Uuid\Type\Decimal;

class BudgetExport implements FromQuery, WithMapping, WithHeadings
{

    public function __construct(protected int $teamId)
    {
        
    }


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
        ->where("budget_months.team_id", $this->teamId)
        ->orderBy('month')
        ->orderBy('g.index')
        ->orderBy('c.index');
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
            $this->formatMoney($budgetMonth,'available')
       ];
    }

    private function formatMoney($budget, $property) {
        $sign = $budget->$property < 0 ? '-' : '';
        return $sign . $budget->currency_code . '$' . abs($budget->$property);
    }

    public function headings(): array {
        return [
            "Month",
            "Category Group/Category",
            "Category Group",
            "Category",
            "Budgeted",
            "Activity",
            "Available",
        ];
    }
}
