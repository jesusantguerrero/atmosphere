<?php

namespace App\Domains\Transaction\Models;

use App\Domains\AppCore\Models\Planner;
use Insane\Journal\Models\Core\Category;
use App\Domains\Transaction\Traits\TransactionTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Watchlist extends Model
{
    public static function getData($teamId, $listData, $startDate = null, $endDate = null) {
        $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');

        $prevStartDate = Carbon::now()->subMonth(1)->startOfMonth()->format('Y-m-d');
        $prevEndDate = Carbon::now()->subMonth(1)->endOfMonth()->format('Y-m-d');


        return [
            'month' => self::expensesInRange($teamId, $startDate, $endDate, $listData),
            'prevMonth' => self::expensesInRange($teamId, $prevStartDate, $prevEndDate, $listData)
        ];
    }

    public static function expensesInRange($teamId, $startDate, $endDate, $listData) {
        return Transaction::byTeam($teamId)
        ->verified()
        ->expenses()
        ->inDateFrame($startDate, $endDate)
        ->select(DB::raw('SUM(total) as total, currency_code'))
        ->categories($listData['input'])
        ->first();
    }
}
