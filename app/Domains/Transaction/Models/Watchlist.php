<?php

namespace App\Domains\Transaction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Watchlist extends Model
{

    private $id;
    private $team_id;
    private $user_id;
    private $input = [];
    private $type = '';
    private $target = '';

    const TYPE_PAYEE = 'payees';
    const TYPE_CATEGORY = 'categories';
    const TYPE_CATEGORY_GROUP = 'groups';
    const TYPE_LABELS = 'labels';

    protected $fillable = ['team_id', 'user_id', 'name', 'input', 'type', 'target'];

     /**
     * The attributes that should be cast to native types.
     *
     * @var array
    */
    protected $casts = [
        'input' => 'array',
    ];

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
        $filterType = $listData['type'];

        return Transaction::byTeam($teamId)
        ->verified()
        ->expenses()
        ->inDateFrame($startDate, $endDate)
        ->select(DB::raw('SUM(total) as total, currency_code, count(id) as transactionsCount, max(date) as lastTransactionDate'))
        ->$filterType($listData['input'])
        ->first();
    }

    public function transactions() {

    }

    public function projected() {

    }
}
