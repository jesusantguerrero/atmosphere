<?php

namespace App\Domains\Budget\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'color',
        'amount',
        'name',
        'target_type',
        'frequency',
        'frequency_date',
        'frequency_week_day',
        'frequency_month_date',
        'completed_at'
    ];

    public function getExpensesByPeriod($startDate, $endDate = null)
    {
        return DB::table('transaction_lines')
            ->where([
                'transaction_lines.account_id' => $this->account_id,
            ])
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->where('status', Transaction::STATUS_VERIFIED)
            ->join('transactions', 'transactions.id', '=', 'transaction_id')
            ->selectRaw('sum(amount * type)  as total')
            ->get()[0]->total;
    }

    public static function getNextTargets($teamId)
    {
        return DB::query()
            ->whereIn('budget_targets.target_type', ['spending'])
            ->where([
                'frequency' => 'monthly',
                'budget_targets.team_id' => $teamId,
            ])
            ->whereRaw("concat(date_format(now(), '%Y-%m'), '-', frequency_month_date) >= now()")
            ->from('budget_targets')
            ->get();
    }

    public static function dashboardParser($budget, $startDate, $endDate = null)
    {
        return [
            'title' => $budget->name,
            'subtitle' => '',
            'value' => $budget->amount,
            'status' => '',
            'expenses' => $budget->getExpensesByPeriod($startDate, $endDate),
        ];
    }
}
