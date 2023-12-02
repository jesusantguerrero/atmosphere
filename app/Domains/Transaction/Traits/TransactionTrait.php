<?php

namespace App\Domains\Transaction\Traits;

use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Transaction;
use App\Domains\Budget\Data\BudgetReservedNames;

trait TransactionTrait
{
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeVerified($query)
    {
        return $query->where('transactions.status', 'verified');
    }

    public function scopeByTeam($query, $teamId)
    {
        return $query->where('transactions.team_id', $teamId);
    }

    public function scopePlanned($query, $isAutomatic = false)
    {
        return $query->where('status', 'planned')
            ->whereHas('schedule', function ($query) use ($isAutomatic) {
                $query->where('automatic', $isAutomatic);
            });
    }

    public function scopeInDateFrame($query, $startDate = null, $endDate = null, $orderByDate = true)
    {
        return $query
            ->when($startDate && ! $endDate, function ($query) use ($startDate) {
                $query->where('date', '=', $startDate);
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->where('date', '>=', $startDate);
                $query->where('date', '<=', $endDate);
            })
            ->when($orderByDate, function ($query) {
                $query->orderBy('date', 'desc');
            });
    }

    public function scopeExpenses($query)
    {
        return $query->where([
            'transactions.direction' => Transaction::DIRECTION_CREDIT,
            'transactions.status' => 'verified',
        ])
            ->whereNotNull('category_id');
    }

    public function scopeBalance($query, $uptoDate = null, $accountIds = [])
    {
        $transactionsTotalSum = "ABS(sum(CASE
        WHEN transactions.direction = 'WITHDRAW'
        THEN transactions.total * -1
        ELSE transactions.total * 1 END)) as total";

        return $query->where([
            'transactions.status' => 'verified',
        ])
            ->when($uptoDate, fn($q) => $q->whereRaw('transactions.date <= ?', [$uptoDate]))
            ->when(count($accountIds), fn($q) => $q->whereIn('transactions.account_id', [$uptoDate]))
            ->whereNotNull('category_id')
            ->selectRaw($transactionsTotalSum);
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where(DB::raw("(account_id = $accountId)"));
    }

    public function scopeCategories($query, array $categories)
    {
        return $query->whereIn('category_id', $categories);
    }

    public function scopeExpenseCategories($query, array $categories = null)
    {
        $categories = collect($categories);
        $excluded = $categories->filter( fn ($id) => $id < 0)->map(fn ($id) => abs($id))->all();
        $included = $categories->filter( fn ($id) => $id > 0)->all();


        $query->whereNot('categories.name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where(fn ($q) =>
                $q->when(count($excluded),fn ($q) => $q->whereNotIn('categories.id', $excluded))
                ->when(count($included), fn ($q) =>  $q->whereIn('categories.id', $included))
            );

        return $query;
    }

    public function scopePayees($query, array $payees)
    {
        return $query->whereIn('payee_id', $payees);
    }
}
