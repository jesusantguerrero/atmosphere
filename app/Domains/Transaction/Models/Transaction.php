<?php

namespace App\Domains\Transaction\Models;

use App\Domains\AppCore\Models\Planner;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Transaction as CoreTransaction;
use App\Domains\Transaction\Traits\TransactionTrait;

class Transaction extends CoreTransaction
{
    const STATUS_PLANNED = 'planned';
    protected $with = ['schedule'];
    use TransactionTrait;

    public function schedule() {
        return $this->morphOne(Planner::class, 'dateable', 'dateable_type', 'dateable_id', );
    }

    public function categoryGroup() {
        return $this->hasOneThrough(Category::class, Category::class, 'parent_id', 'parent_id');
    }

    public function linked() {
        return $this->hasMany(LinkedTransaction::class);
    }

    public function copy($data = []) {
        $transactionNew = Self::create(array_merge($this->toArray(), $data));
        foreach ($this->lines as $line) {
            $transactionNew->lines()->create($line->toArray());
        }
        return $transactionNew;
    }
}
