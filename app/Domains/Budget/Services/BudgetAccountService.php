<?php

namespace App\Domains\Budget\Services;

use Illuminate\Support\Facades\DB;
use App\Domains\Transaction\Models\Transaction;
use Insane\Journal\Models\Core\AccountDetailType;

class BudgetAccountService
{
    public function getBalanceAs($teamId, $endDate)
    {
        return DB::table('transaction_lines')
            ->selectRaw('COALESCE(SUM(amount * transaction_lines.type), 0) as balance')
            ->join('transactions', fn ($q) => $q->on('transactions.id', 'transaction_lines.transaction_id')
                ->where('status', Transaction::STATUS_VERIFIED)
            )
            ->join('accounts', 'accounts.id', 'transaction_lines.account_id')
            ->join('account_detail_types', fn ($q) => $q->on('account_detail_types.id', '=', 'accounts.account_detail_type_id')
                ->whereIn('account_detail_types.name', AccountDetailType::ALL_CASH)
            )
            ->where('transactions.team_id', $teamId)
            ->orderBy('accounts.index')
            ->where('transaction_lines.date', '<=', $endDate)
            ->first()?->balance;
    }

    public function getTotalBalanceAs($teamId, $endDate)
    {
        return DB::table('transaction_lines')
            ->selectRaw('COALESCE(SUM(amount * transaction_lines.type), 0) as balance, accounts.id AS account_id, accounts.name AS account_name')
            ->join('transactions', fn ($q) => $q->on('transactions.id', 'transaction_lines.transaction_id')
                ->where('status', Transaction::STATUS_VERIFIED)
            )
            ->join('accounts', 'accounts.id', 'transaction_lines.account_id')
            ->join('account_detail_types', fn ($q) => $q->on('account_detail_types.id', '=', 'accounts.account_detail_type_id')
                ->whereIn('account_detail_types.name', AccountDetailType::ALL)
            )
            ->where('transactions.team_id', $teamId)
            ->orderBy('accounts.index')
            ->where('transaction_lines.date', '<=', $endDate)
            ->groupBy('accounts.id')
            ->get();
    }
}
