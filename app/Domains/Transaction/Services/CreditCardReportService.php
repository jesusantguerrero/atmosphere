<?php

namespace App\Domains\Transaction\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreditCardReportService
{
    public function creditCardCycleByAccount($teamId, $date, $creditCardId = null) {
        $endCycleDate =  Carbon::createFromFormat('Y-m-d', $date)->startOfMonth()->subMonth(1)->format('Y-m-d');
        $startCycleDate =  Carbon::createFromFormat('Y-m-d', $date)->startOfMonth()->subMonth(2)->format('Y-m-d');

        return DB::table(DB::raw('accounts a'))
            ->where("a.team_id", $teamId)
            ->whereNotNull('a.credit_closing_day')
            ->selectRaw("
                ABS(COALESCE(SUM(tl.amount * tl.type), 0)) AS total,
                a.name,
                a.credit_limit,
                DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) AS `from`,
                DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) AS `until`", [
                    $startCycleDate,
                    $endCycleDate
            ])

            ->leftJoin(DB::raw('transaction_lines tl'), fn ($q) => $q->on('a.id', 'tl.account_id')->where('tl.type', -1))
            // ->where('account_id', $accountId)
            ->whereRaw("tl.date >= DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0')))
            AND tl.date < DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0')))", [
                $startCycleDate,
                $endCycleDate
            ])
            ->groupBy('a.id')
            ->get();
    }

    public function creditCards($teamId, $date)
    {
        $lastCycleBalances = $this->creditCardCycleByAccount($teamId, $date);
        $creditTotal = $lastCycleBalances->sum('total');

        $data = [
            'lastCycleBalances' => $lastCycleBalances,
            "creditTotal" =>  $creditTotal,
            "creditLineUsage" =>  round($creditTotal / $lastCycleBalances->sum('credit_limit') * 100, 2),
            'topCategoriesByCard' => [],
            'rewardsByCardInPeriod' => [],
            'topPayeesByCard' => [],
            'topTransaction' => [],
            'billingCyclePayments' => [],
        ];

        return $data;
    }


}
