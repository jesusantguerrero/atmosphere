<?php

namespace App\Domains\AppCore\Models;

use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Transaction\Models\Transaction;
use App\Events\BudgetAssigned;
use App\Models\Team;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category as CoreCategory;
use Insane\Journal\Models\Core\TransactionLine;

class Category extends CoreCategory
{
    use HasFactory;
    const READY_TO_ASSIGN = "Ready to Assign";
    const INFLOW = "Inflow";
    protected $with = ['budget'];

    public function budget() {
        return $this->hasOne(BudgetTarget::class);
    }

    public function team() {
        return $this->belongsTo(Team::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    public function transactionLines()
    {
        return $this->hasMany(TransactionLine::class, 'category_id');
    }

    public function creditLines()
    {
        return $this->hasMany(TransactionLine::class, 'account_id',  'resource_type_id');
    }

    public function group() {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subCategories() {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('index');
    }

    public function budgets() {
        return $this->hasMany(BudgetMonth::class)->orderBy('month', 'desc');
    }

    public function lastMonthBudget() {
        return $this->hasMany(BudgetMonth::class)->orderBy('month', 'desc')->limit(1);
    }

    public function assignBudget(string $month, mixed $postData) {
        $amount = (double) $postData['budgeted'];
        $type = $postData['type'] ?? 'budgeted';
        $shouldAggregate = $this->name === self::READY_TO_ASSIGN || $type === 'movement';

        $month = BudgetMonth::updateOrCreate([
            'category_id' => $this->id,
            'team_id' => $this->team_id,
            'month' => $month,
            'name' => $month,
        ], [
            'user_id' => $this->user_id,
            'budgeted' => $shouldAggregate ? DB::raw("budgeted + $amount") : $amount,
        ]);

        BudgetAssigned::dispatch($month, $postData);
        return $month;
    }

    public function getBudgetInfo(string $month) {
        $yearMonth = substr((string) $month, 0, 7);
        $monthBudget = $this->budgets->where('month', $month)->first();
        $budgeted = $monthBudget ? $monthBudget->budgeted : 0;
        $monthBalance = (double) $this->getMonthBalance($yearMonth)->balance;
        $prevMonthLeftOver = $this->getPrevMonthLeftOver($yearMonth);
        $available = Money::of($budgeted, 'USD')->plus($prevMonthLeftOver)->plus($monthBalance)->getAmount()->toFloat();

        return [
            'budgeted' => $budgeted,
            'activity' => $monthBalance,
            'available' => $available,
            'prevMonthLeftOver' => $prevMonthLeftOver
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
     * @return Object
     */
    public function getMonthBalance($yearMonth)
    {
        if (!$this->resource_type_id) {
            return $this->transactionLines()
            ->whereHas('transaction', fn ($q) => $q->where('status', Transaction::STATUS_VERIFIED))
            ->whereRaw("date_format(transaction_lines.date, '%Y-%m') = '$yearMonth'")
            ->selectRaw("SUM(amount * type) as balance"
            )->first();
        } else {
            return $this->creditLines()
            ->whereRaw("date_format(date, '%Y-%m') = '$yearMonth'")
            ->sum(DB::raw("amount * type"));
        }
    }

    /**
     * Get the current balance.
     *
     * @return string
     */
    public function getPrevMonthLeftOver($yearMonth)
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

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\CategoryFactory::new();
    }
}
