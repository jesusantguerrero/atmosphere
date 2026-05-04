<?php

namespace App\Domains\Budget\Models;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Watchlist\Models\Watchlist;

class BudgetTarget extends Model
{
    use HasFactory;

    const TYPE_SPENDING = 'spending';

    const TYPE_SAVING_BALANCE = 'saving_balance';

    const TYPE_SAVING_MONTHLY = 'savings_monthly';

    // WL-7: "Spend less than X in this watchlist for N consecutive months"
    const TYPE_CHALLENGE_UNDER_AMOUNT = 'challenge_under_amount';

    protected $fillable = [
        'team_id',
        'user_id',
        'category_id',
        'color',
        'amount',
        'name',
        'target_type',
        'watchlist_id',
        'frequency',
        'frequency_date',
        'frequency_week_day',
        'frequency_month_date',
        'frequency_interval',
        'frequency_interval_unit',
        'notify',
        'completed_at',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function watchlist()
    {
        return $this->belongsTo(Watchlist::class);
    }

    /**
     * For a `challenge_under_amount` target tied to a watchlist, count how many
     * consecutive months ending at $endDate (default: now) the watchlist's monthly
     * total stayed strictly below `amount`. Returns 0 when current month already broke,
     * positive integer for active streak.
     */
    public function streakInMonths(?Carbon $endDate = null, int $maxLookback = 24): int
    {
        if ($this->target_type !== self::TYPE_CHALLENGE_UNDER_AMOUNT || ! $this->watchlist_id) {
            return 0;
        }
        $watchlist = $this->watchlist;
        if (! $watchlist) {
            return 0;
        }
        $endCarbon = ($endDate ?? Carbon::now())->copy()->endOfMonth();
        $series = Watchlist::monthlySeries(
            $maxLookback,
            (int) $this->team_id,
            $watchlist,
            $endCarbon->format('Y-m-d')
        );

        $threshold = (float) $this->amount;
        $streak = 0;
        // Walk from most recent backwards while still under threshold.
        foreach (array_reverse($series) as $point) {
            if ((float) $point['total'] < $threshold) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

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

    public static function getNextTargets($teamId, $targetTypes = ['spending'])
    {
        return DB::query()
            ->whereIn('budget_targets.target_type', $targetTypes)
            ->where([
                'frequency' => 'monthly',
                'budget_targets.team_id' => $teamId,
            ])
            ->whereRaw("concat(date_format(now(), '%Y-%m'), '-', LPAD(frequency_month_date, 2, '0')) >= date_format(now(), '%Y-%m-%d')")
            ->addSelect(DB::raw("budget_targets.*, concat(date_format(now(), '%Y-%m'), '-', LPAD(frequency_month_date, 2, '0')) as due_date"))
            ->from('budget_targets')
            ->orderByRaw('due_date')
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
