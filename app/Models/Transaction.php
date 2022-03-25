<?php

namespace App\Models;

use App\Models\Planner;
use Insane\Journal\Models\Core\Transaction as CoreTransaction;

class Transaction extends CoreTransaction
{
    const STATUS_PLANNED = 'planned';
    protected $with = ['schedule'];


    public function schedule() {
        return $this->morphOne(Planner::class, 'dateable', 'dateable_type', 'dateable_id', );
    }

    public function copy($data = []) {
        $transactionNew = Self::create(array_merge($this->toArray(), $data));
        foreach ($this->lines as $line) {
            $transactionNew->lines()->create($line->toArray());
        }
        return $transactionNew;
    }

    public static function parser($transaction) {
        return [
            'id' => $transaction->id,
            'date' => $transaction->date,
            'number' => $transaction->number,
            'description' => $transaction->description,
            'direction' => $transaction->direction,
            'account' => $transaction->mainLine ? $transaction->mainLine->account: [],
            'category' => $transaction->category ? $transaction->category->account : [],
            'currency_code' => $transaction->currency_code,
            'total' => $transaction->total,
            'lines' => $transaction->lines,
            'mainLine' => $transaction->mainLine,
            'schedule' => $transaction->schedule,
        ];
    }
}
