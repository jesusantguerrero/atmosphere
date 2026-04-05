<?php

namespace App\Domains\Transaction\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected int $teamId,
        protected ?string $startDate = null,
        protected ?string $endDate = null,
        protected ?int $accountId = null,
    ) {}

    public function query()
    {
        $query = DB::table('transactions')
            ->select([
                'transactions.date',
                'payees.name as payee_name',
                'transactions.description',
                'categories.name as category_name',
                'accounts.name as account_name',
                'transactions.direction',
                'transactions.total',
                'transactions.currency_code',
                'transactions.status',
            ])
            ->leftJoin('payees', 'payees.id', 'transactions.payee_id')
            ->leftJoin('categories', 'categories.id', 'transactions.category_id')
            ->leftJoin('accounts', 'accounts.id', 'transactions.account_id')
            ->where('transactions.team_id', $this->teamId)
            ->where('transactions.status', 'verified')
            ->orderBy('transactions.date', 'desc');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('transactions.date', [$this->startDate, $this->endDate]);
        }

        if ($this->accountId) {
            $query->where('transactions.account_id', $this->accountId);
        }

        return $query;
    }

    public function map($transaction): array
    {
        $direction = $transaction->direction === 'DEPOSIT' ? 'Income' : 'Expense';

        return [
            $transaction->date,
            $transaction->payee_name ?? '',
            $transaction->description ?? '',
            $transaction->category_name ?? '',
            $transaction->account_name ?? '',
            $direction,
            $transaction->total,
            $transaction->currency_code ?? '',
            ucfirst($transaction->status ?? ''),
        ];
    }

    public function headings(): array
    {
        return [
            'Date',
            'Payee',
            'Description',
            'Category',
            'Account',
            'Direction',
            'Amount',
            'Currency',
            'Status',
        ];
    }
}
