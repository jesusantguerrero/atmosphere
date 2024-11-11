<?php

namespace App\Domains\Transaction\Imports;

use App\Domains\AppCore\Imports\ImportConcern;
use App\Domains\Transaction\Actions\MapYnabToLoger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Insane\Journal\Models\Core\Transaction;

class TransactionsImport extends ImportConcern implements ShouldQueue
{
    /**
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
