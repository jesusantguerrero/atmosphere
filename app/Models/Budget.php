<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Account;
use Insane\Journal\Transaction;

class Budget extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id','account_id', 'amount'];

    public function account() {
        return $this->belongsTo(Account::class);
    }

    public function getExpensesByPeriod($startDate, $endDate = null) {
        return DB::table('transaction_lines')
        ->where([
            'account_id' => $this->account_id
        ])
        ->whereRaw("date = ?", [$startDate])
        ->join('transactions', 'transactions.id', '=', 'transaction_id')
        ->selectRaw('sum(amount * type)  as total')
        ->get()[0]->total;
    }

    public static function dashboardParser($budget, $startDate, $endDate = null) {
        return [
            "title" => $budget->account->name,
            "subtitle" => '',
            "value" => $budget->amount,
            "status" => '',
            "expenses" => $budget->getExpensesByPeriod($startDate, $endDate)
        ];
    }
}
