<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category as CoreCategory;

class Category extends CoreCategory
{
    const READY_TO_ASSIGN = "Ready to Assign";
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
            'team_id' => $this->team_id,
            'user_id' => $this->user_id,
            'month' => $month,
            'name' => $month,
        ], [
            'budgeted' => (double) $postData['budgeted']
        ]);
    }

    public function getBudgetInfo(string $month) {
        $yearMonth = substr((string) $month, 0, 7);
        $monthBudget = $this->budgets->where('month', $month)->first();
        $monthBalance = $this->getMonthBalance($yearMonth);
        return [
            'budgeted' => $monthBudget ? $monthBudget->budgeted : 0,
            'spent' => $monthBalance,
            'available' => $monthBudget ? $monthBudget->available : 0,
        ];
    }

    public static function getBudgetSubcategories($teamId) {
        return self::where([
            'categories.team_id' => $teamId,
            'categories.resource_type' => 'transactions',
        ])
        ->whereNull('parent_id')
            ->orderBy('index')
            ->with('subCategories')
            ->get();
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
