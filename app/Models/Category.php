<?php

namespace App\Models;

use App\Events\BudgetAssigned;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category as CoreCategory;

class Category extends CoreCategory
{
    const READY_TO_ASSIGN = "Ready to Assign";
    const INFLOW = "Inflow";
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
        return $this->hasMany(BudgetMonth::class)->orderBy('month', 'desc');
    }

    public function assignBudget(string $month, mixed $postData) {
        $amount = (double) $postData['budgeted'];
        $month = BudgetMonth::updateOrCreate([
            'category_id' => $this->id,
            'team_id' => $this->team_id,
            'user_id' => $this->user_id,
            'month' => $month,
            'name' => $month,
        ], [
            'budgeted' => $this->name === self::READY_TO_ASSIGN ? DB::raw("budgeted + $amount") : $amount,
        ]);

        BudgetAssigned::dispatch($month, $postData);
        return $month;
    }

    public function getBudgetInfo(string $month) {
        $yearMonth = substr((string) $month, 0, 7);
        $monthBudget = $this->budgets->where('month', $month)->first();
        $budgeted = $monthBudget ? $monthBudget->budgeted : 0;
        $monthBalance = $this->getMonthBalance($yearMonth);
        $oldAvailable = $this->getOldAvailable($yearMonth);
        $available = $budgeted + $oldAvailable + $monthBalance;

        return [
            'budgeted' => $budgeted,
            'activity' => $monthBalance,
            'available' => $available,
            'oldAvailable' => $oldAvailable
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
        ])
        ->whereRaw(DB::raw("date_format(transactions.date, '%Y-%m') = '$yearMonth'"))
        ->sum(DB::raw("CASE
            WHEN transactions.direction = 'WITHDRAW'
            THEN total * -1
            ELSE total * 1 END"
        ));
    }


    /**
     * Get the current balance.
     *
     * @return string
     */
    public function getOldAvailable($yearMonth)
    {
       return DB::query()
       ->select('*')
       ->where([
           'category_id' => $this->id,
        ])
        ->whereRaw("date_format(month, '%Y-%m') < '$yearMonth'")
        ->from('budget_months')
        ->sum(DB::raw("budgeted + activity"));
    }

    //    /**
    //  * Get the current balance.
    //  *
    //  * @return string
    //  */
    // public function getOldAvailable($yearMonth)
    // {
    //    return $this->budgets()
    //    ->where('transactions.category_id', '=', 24)
    //     ->whereRaw(DB::raw("date_format(budget_months.month, '%Y%m') < '$yearMonth'"))
    //     ->selectRaw(DB::raw("sum(COALESCE(budgeted, 0)) + sum(CASE
    //      WHEN transactions.direction = 'WITHDRAW'
    //      THEN total * -1
    //      ELSE total * 1 END) as budgeted")
    //     )
    //     ->join('transactions', 'transactions.transaction_category_id', '=', 'budget_months.category_id')
    //     ->dump();
    // }
}
