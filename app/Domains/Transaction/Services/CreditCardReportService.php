<?php

namespace App\Domains\Transaction\Services;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Payment;
use App\Domains\AppCore\Models\Category;
use Insane\Journal\Models\Core\Transaction;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Transaction\Models\BillingCycle;

class CreditCardReportService
{
    public function creditCardCycleByAccount($teamId, $date, $monthsBack = 1, $creditCardId = null, $asToday = null) {
        $endCycleDate =  Carbon::createFromFormat('Y-m-d', $date)->startOfMonth()->subMonth($monthsBack)->format('Y-m-d');
        $startCycleDate =  Carbon::createFromFormat('Y-m-d', $date)->startOfMonth()->subMonth($monthsBack+1)->format('Y-m-d');

        $readyToAssign = Category::where([
            'team_id' => $teamId,
        ])
        ->where('name', BudgetReservedNames::READY_TO_ASSIGN->value)
        ->first();

        return DB::table(DB::raw('accounts a'))
            ->where("a.team_id", $teamId)
            ->whereNotNull('a.credit_closing_day')
            ->when($asToday, fn ($q) => $q->whereRaw("now() >= DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0')))", [
                    $endCycleDate
            ]))
            ->selectRaw("
                ABS(COALESCE(SUM(CASE WHEN tl.type = -1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS subtotal,
                ABS(COALESCE(SUM(tl.amount * tl.type), 0)) AS total,
                ABS(COALESCE(SUM(CASE WHEN tl.type = 1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS discount,
                a.name,
                a.id,
                a.credit_limit,
                DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) AS `from`,
                DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) AS `until`", [
                    $startCycleDate,
                    $endCycleDate
            ])

            ->leftJoin(DB::raw('transaction_lines tl'), fn ($q) => $q->on('a.id', 'tl.account_id')
                ->whereRaw('(tl.type = ? or tl.category_id  = ?)', [-1, $readyToAssign->id])
            )
            ->when($creditCardId, fn($q) => $q->where('account_id', $creditCardId))
            ->whereRaw("(tl.date >= DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) and tl.type = -1
            AND
                tl.date < DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0')))
            )
            OR tl.date = DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) AND tl.type = 1", [
                $startCycleDate,
                $endCycleDate,
                $endCycleDate
            ])
            ->groupBy('a.id')
            ->get();
    }

    public function getTopCategoriesByAccount($teamId, $startDate = 1, $endDate, $creditCardId = null) {
        return DB::table(DB::raw('accounts a'))
            ->where("a.team_id", $teamId)
            ->whereNotNull('a.credit_closing_day')
            ->selectRaw("
                ABS(COALESCE(SUM(CASE WHEN tl.type = -1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS subtotal,
                ABS(COALESCE(SUM(tl.amount * tl.type), 0)) AS total,
                ABS(COALESCE(SUM(CASE WHEN tl.type = 1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS discount,
                a.name,
                a.id,
                c.name as cat_name
            ")
            ->leftJoin(DB::raw('transaction_lines tl'), fn ($q) => $q->on('a.id', 'tl.account_id')
                ->whereNotNull('tl.category_id')
            )
            ->join(DB::raw('categories c'),'tl.category_id', 'c.id')
            ->whereBetween("tl.date", [$startDate, $endDate])
            ->groupBy(DB::raw('a.id, c.name'))
            ->orderBy('total', 'desc')
            ->get();
    }

    public function getTopCategoriesByCreditCard($teamId, $startDate = 1, $endDate, $creditCardId = null) {
        $readyToAssign = Category::where([
            'team_id' => $teamId,
        ])
        ->where('name', BudgetReservedNames::READY_TO_ASSIGN->value)
        ->first();

        return DB::select("
         WITH TopCategories as (
            SELECT
                ABS(COALESCE(SUM(CASE WHEN tl.type = -1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS subtotal,
                ABS(COALESCE(SUM(tl.amount * tl.type), 0)) AS total,
                ABS(COALESCE(SUM(CASE WHEN tl.type = 1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS discount,
                a.name,
                a.id,
                c.name as cat_name,
                ROW_NUMBER() OVER (PARTITION BY tl.account_id ORDER BY SUM(tl.amount * tl.type)) AS rank
            FROM transaction_lines tl
            INNER JOIN transactions t on tl.transaction_id = t.id AND t.status = 'verified' AND tl.category_id <> :readyToAssign
            INNER JOIN accounts a on tl.account_id = a.id AND a.credit_closing_day IS NOT NULL
            INNER JOIN categories c on c.id = tl.category_id
            WHERE tl.date >= :startDate AND tl.date <= :endDate
            AND tl.team_id = :teamId
            GROUP BY tl.account_id, tl.category_id
            ORDER BY a.id, ABS(COALESCE(SUM(tl.amount * tl.type), 0))
        )
            SELECT
                tc.name,
                tc.cat_name,
                tc.total
            FROM TopCategories tc
            WHERE tc.rank <= 4
            GROUP BY tc.id, tc.cat_name
            ORDER BY tc.id, tc.rank", [
                'teamId' => $teamId,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'readyToAssign' => $readyToAssign->id
            ]);
    }

    public function getTopPayeesByAccount($teamId, $date, $monthsBack = 1, $creditCardId = null, $asToday = null) {
        $endCycleDate =  Carbon::createFromFormat('Y-m-d', $date)->startOfMonth()->subMonth($monthsBack)->format('Y-m-d');
        $startCycleDate =  Carbon::createFromFormat('Y-m-d', $date)->startOfMonth()->subMonth($monthsBack+1)->format('Y-m-d');

        $readyToAssign = Category::where([
            'team_id' => $teamId,
        ])
        ->where('name', BudgetReservedNames::READY_TO_ASSIGN->value)
        ->first();

        return DB::table(DB::raw('accounts a'))
            ->where("a.team_id", $teamId)
            ->whereNotNull('a.credit_closing_day')
            ->when($asToday, fn ($q) => $q->whereRaw("now() >= DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0')))", [
                    $endCycleDate
            ]))
            ->selectRaw("
                ABS(COALESCE(SUM(CASE WHEN tl.type = -1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS subtotal,
                ABS(COALESCE(SUM(tl.amount * tl.type), 0)) AS total,
                ABS(COALESCE(SUM(CASE WHEN tl.type = 1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS discount,
                a.name,
                a.id,
                a.credit_limit,
                DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) AS `from`,
                DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) AS `until`", [
                    $startCycleDate,
                    $endCycleDate
            ])

            ->leftJoin(DB::raw('transaction_lines tl'), fn ($q) => $q->on('a.id', 'tl.account_id')
                ->whereRaw('(tl.type = ? or tl.category_id  = ?)', [-1, $readyToAssign->id])
            )
            // ->where('account_id', $accountId)
            ->whereRaw("(tl.date >= DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) and tl.type = -1
            AND
                tl.date < DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0')))
            )
            OR tl.date = DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) AND tl.type = 1", [
                $startCycleDate,
                $endCycleDate,
                $endCycleDate
            ])
            ->groupBy('a.id')
            ->get();
    }

    public function getBillingCyclesByCardInPeriod($teamId, $startDate = 1, $endDate, $creditCardId = null) {
        $billingData = DB::table(DB::raw('accounts a'))
            ->where("a.team_id", $teamId)
            ->whereNotNull('a.credit_closing_day')
            ->selectRaw("
                ABS(COALESCE(SUM(bc.subtotal), 0)) AS subtotal,
                ABS(COALESCE(SUM(bc.total), 0)) AS total,
                ABS(COALESCE(SUM(bc.discounts), 0)) AS discounts,
                a.name,
                a.id
            ")
            ->when($creditCardId, fn ($q) => $q->where('a.id', $creditCardId))
            ->leftJoin(DB::raw('billing_cycles bc'), 'a.id', 'bc.account_id')
            ->whereBetween("bc.end_at", [$startDate, $endDate])
            ->groupBy('a.id')
            ->get();

        return [
            "data" => $billingData,
            "discountTotal" => $billingData->sum('discount'),
            "total" => $billingData->sum('total'),
            "subtotal" => $billingData->sum('subtotal')
        ];
    }

    public function getBillingCyclesInPeriod($teamId, $startDate, $endDate, $creditCardId = null, $statuses= null) {
        return DB::table(DB::raw('accounts a'))
            ->where("a.team_id", $teamId)
            ->whereNotNull('a.credit_closing_day')
            ->selectRaw("
                bc.*,
                a.name
            ")
            ->when($creditCardId, fn ($q) => $q->where('a.id', $creditCardId))
            ->leftJoin(DB::raw('billing_cycles bc'), 'a.id', 'bc.account_id')
            ->when($statuses, fn($q) => $q->whereIn("bc.status", $statuses))
            ->when($startDate && $endDate, fn($q) => $q->whereBetween("bc.end_at", [$startDate, $endDate]))
            ->when(!$startDate && $endDate, fn($q) => $q->where("bc.end_at", '<=', $endDate))
            ->when($startDate && !$endDate, fn($q) => $q->where("bc.end_at", '>=', $startDate))
            ->orderByDesc('bc.end_at')
            ->get();
    }

    public function creditCards($teamId, $date)
    {
        $lastCycleBalances = $this->creditCardCycleByAccount($teamId, $date);
        $creditTotal = $lastCycleBalances->sum('total');

        $startPeriodDate = Carbon::createFromFormat('Y-m-d', $date)->startOfMonth()->subMonths(12);

        // dd($this->getTopCategoriesByCreditCard($teamId, $startPeriodDate, $date));

        $data = [
            'lastCycleBalances' => $lastCycleBalances,
            "creditTotal" =>  $creditTotal,
            "creditLineUsage" =>  round($creditTotal / $lastCycleBalances->sum('credit_limit') * 100, 2),
            'topCategoriesByCard' => $this->getTopCategoriesByCreditCard($teamId, $startPeriodDate, $date),
            'billingCyclesByCard' => $this->getBillingCyclesByCardInPeriod($teamId, $startPeriodDate, $date),
            'topPayeesByCard' => [],
            'topTransaction' => [],
            'billingCyclePayments' => [],
        ];

        return $data;
    }

    public function generateBillingCycles($teamId, $yearMonth) {
        $monthsWithTransactions = DB::table('transaction_lines')
        ->selectRaw("date_format(transaction_lines.date, '%Y-%m') AS date")
        ->groupBy(DB::raw("date_format(transaction_lines.date, '%Y-%m')"))
        ->whereRaw("date_format(transaction_lines.date, '%Y-%m') >= ?", [$yearMonth])
        ->get()
        ->pluck('date');

        $monthsWithTransactions = [
            ...$monthsWithTransactions,
        ];

        $total = count($monthsWithTransactions);
        $count = 0;

        foreach ($monthsWithTransactions as $month) {
            $lastCycleBalances = $this->creditCardCycleByAccount($teamId, $month."-01", 0, null, true)->sortBy('from');
            $count++;
            foreach ($lastCycleBalances as $creditCardAccount) {
                    BillingCycle::updateOrCreate([
                        "account_id" => $creditCardAccount->id,
                        "team_id" => $teamId,
                        "user_id" => 0,
                        "start_at" => $creditCardAccount->from,
                        "end_at" => $creditCardAccount->until,
                    ], [
                        "minimum_payment" => $creditCardAccount->total,
                        "debt" => $creditCardAccount->total,
                        "subtotal" => $creditCardAccount->subtotal,
                        "discounts" =>$creditCardAccount->discount,
                        "total" => $creditCardAccount->total,
                        "due_at" => $creditCardAccount->until
                    ]);
            }
            echo "updated month {$month}".PHP_EOL;
            echo "{$count} of {$total}".PHP_EOL.PHP_EOL;
        }
    }

    public function getUnlinkedPayments($teamId, Account $account) {
        $readyToAssign = Category::where([
            'team_id' => $teamId,
        ])
        ->where('name', BudgetReservedNames::READY_TO_ASSIGN->value)
        ->first();

        return DB::table(DB::raw('transaction_lines tl'))
            ->where([
                "a.team_id" => $teamId,
                "a.id" => $account->id,
                "tl.payee_id" => 0
            ])
            ->selectRaw("
                tl.*,
                t.transactionable_type,
                t.transactionable_id,
                a.name
            ")
            ->whereRaw('(tl.type = ? or tl.category_id  = ?)', [1, $readyToAssign->id])
            ->whereNull('t.transactionable_id')
            ->join(DB::raw('accounts a'), fn ($q) => $q->on('a.id', 'tl.account_id'))
            ->join(DB::raw('transactions t'), 'tl.transaction_id', 't.id')
            ->orderByDesc('tl.date')
            ->get();
    }


    public function linkCreditCardPayment(BillingCycle $billingCycle, Transaction $transaction, $postData)
    {
        $billingCycle->linkPayment($transaction, $postData);
        $billingCycle->save();
    }
}
