<?php

namespace App\Domains\Budget\Models\Traits;

use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Transaction;
use App\Domains\Budget\Data\BudgetReservedNames;

trait BudgetMonthTrait
{
    public function scopeByTeam($query, $teamId)
    {
        return $query->where('budget_months.team_id', $teamId);
    }


    public function scopeInDateFrame($query, $startDate = null, $endDate = null, $orderByDate = true)
    {
        return $query
            ->when($startDate && ! $endDate, function ($query) use ($startDate) {
                $query->where('budget_months.month', '=', $startDate);
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->where('budget_months.month', '>=', $startDate);
                $query->where('budget_months.month', '<=', $endDate);
            })
            ->when($orderByDate, function ($query) {
                $query->orderBy('budget_months.month', 'desc');
            });
    }

    public function scopeExpenses($query)
    {
        return $query->where([
            'transactions.direction' => Transaction::DIRECTION_CREDIT,
            'transactions.status' => 'verified',
        ])
            ->whereNotNull('budget_months.category_id');
    }

    public function scopeBalance($query)
    {
        $transactionsTotalSum = 'sum(budget_months.budgeted) as total_amount, group_concat(distinct(categories.name)) as cat_name';

        return $query
            ->whereNotNull('budget_months.category_id')
            ->where('budget_months.budgeted', '<>', 0)
            ->selectRaw($transactionsTotalSum);
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where(DB::raw("(account_id = $accountId)"));
    }

    public function scopeCategories($query, array $categories)
    {
        return $query->whereIn('budget_months.category_id', $categories)
            ->join('categories', 'budget_months.category_id', '=', 'categories.id')
            ->when($categories, fn ($query) => $query->whereIn('budget_months.category_id', $categories));
    }

    public function scopeExpenseCategories($query, array $categories = null)
    {
        $query->whereNot('categories.name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->join('categories', 'budget_months.category_id', '=', 'categories.id');

        $categories = collect($categories);
        $excluded = $categories->filter( fn ($id) => $id < 0)->all();
        $included = $categories->filter( fn ($id) => $id > 0)->all();

        if (count($excluded)) {
            $query->whereNotIn('budget_months.category_id', $excluded);
        }
        if (count($included)) {
            $query->whereIn('budget_months.category_id', $included);
        }

        return $query;
    }

    public function scopeAllCategories($query, array $categories = null)
    {
        $query
            ->whereNot('categories.name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->join('categories', 'budget_months.category_id', '=', 'categories.id');

        $categories = collect($categories);
        $excluded = $categories->filter( fn ($id) => $id < 0)->all();
        $included = $categories->filter( fn ($id) => $id > 0)->all();

        if (count($excluded)) {
            $query->whereNotIn('budget_months.category_id', $excluded);
        }
        if (count($included)) {
            $query->whereIn('budget_months.category_id', $included);
        }

        return $query;
    }
}
