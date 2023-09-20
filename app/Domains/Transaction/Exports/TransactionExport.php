<?php

namespace App\Domains\Transaction\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransactionExport implements FromArray, WithHeadings
{
    protected $transactions;

    public function __construct(array $transactions)
    {
        $this->transactions = $transactions;
    }

    public function array(): array
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Account',
            'Flag',
            'Date',
            'Payee',
            'Category Group/Category',
            'Category Group',
            'Category',
            'Memo',
            'Outflow',
            'Inflow',
            'Cleared',
        ];
    }
}
