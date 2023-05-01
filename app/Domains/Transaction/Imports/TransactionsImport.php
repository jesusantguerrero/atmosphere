<?php

namespace App\Domains\Transaction\Imports;

use App\Domains\Imports\ImportConcern;
use App\Domains\Transaction\Actions\MapYnabToLoger;
use Insane\Journal\Models\Core\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;

class TransactionsImport extends ImportConcern implements ShouldQueue
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return Transaction::createTransaction(MapYnabToLoger::parse($row, $this->session));
    }

    public function map($row): array
    {
        return MapYnabToLoger::parse($row, $this->session);
    }
}
