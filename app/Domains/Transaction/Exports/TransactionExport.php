<?php

namespace App\Domains\Transaction\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class TransactionExport implements FromArray
{
    protected $transactions;

    public function __construct(array $transactions)
    {
        $this->transactions= $transactions;
    }

    public function array(): array
    {
        return $this->transactions;
    }
}
