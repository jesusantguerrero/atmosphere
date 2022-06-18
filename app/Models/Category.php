<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category as CoreCategory;

class Category extends CoreCategory
{
    protected $with = ['budget'];

    public function budget() {
        return $this->hasOne(Budget::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'transaction_category_id');
    }

    public function subCategories() {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('index');
    }

    public function budgets() {
        return $this->hasMany(MonthBudget::class)->orderBy('month', 'desc');
    }

    public function assignBudget(string $month, mixed $postData) {
        return MonthBudget::updateOrCreate([
            'category_id' => $this->id,
            'month' => $month,
            'name' => $month,
        ], [
            'budgeted' => (double) $postData['budgeted']
        ]);
    }

    public function getBudgetInfo(string $month) {
        $yearMonth = substr((string) $month, 0, 7);
        return [
            'budgeted' => $this->budgets->where('month', $month)->first()->budgeted ?? 0,
            'spent' => $this->getMonthBalance($yearMonth),
            'remaining' => 0,
        ];
    }

        /**
     * Get the current balance.
     *
     * @return string
     */
    public function getMonthBalance($yearMonth)
    {
       return $this->transactions()
        ->where([
            'status' => 'verified'
        ])->whereRaw(DB::raw("date_format(transactions.date, '%Y-%m') = '$yearMonth'"))->sum(DB::raw("CASE
        WHEN transactions.direction = 'WITHDRAW'
        THEN total * -1
        ELSE total * 1 END"));
    }
}
