<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;

class Budget extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id', 'target_amount', 'name', 'target_type', 'frequency', 'frequency_date', 'frequency_week_day', 'frequency_month_date'];
    protected $with = ['children'];

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function children() {
        return $this->hasMany(Budget::class, 'parent_id');
    }

    public function getExpensesByPeriod($startDate, $endDate = null) {
        return DB::table('transaction_lines')
        ->where([
            'transaction_lines.account_id' => $this->account_id
        ])
        ->where("date",  ">=", $startDate)
        ->where("date", "<=", $endDate)
        ->where('status', Transaction::STATUS_VERIFIED)
        ->join('transactions', 'transactions.id', '=', 'transaction_id')
        ->selectRaw('sum(amount * type)  as total')
        ->get()[0]->total;
    }

    public static function dashboardParser($budget, $startDate, $endDate = null) {
        return [
            "title" => $budget->name,
            "subtitle" => '',
            "value" => $budget->amount,
            "status" => '',
            "expenses" => $budget->getExpensesByPeriod($startDate, $endDate)
        ];
    }
}
