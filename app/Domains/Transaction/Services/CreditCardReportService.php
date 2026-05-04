<?php

namespace App\Domains\Transaction\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Transaction\Models\BillingCycle;
use App\Models\User;
use App\Notifications\BillingCycleCutAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Transaction;

class CreditCardReportService
{
    const REPORT_CACHE_TTL_SECONDS = 600;

    private function reportCacheKey(string $method, array $parts): string
    {
        return 'cc_report:'.$method.':'.md5(json_encode($parts));
    }

    public function creditCardCycleByAccount($teamId, $date, $monthsBack = 1, $creditCardId = null, $asToday = null)
    {
        $endCycleDate = Carbon::createFromFormat('Y-m-d', $date)->startOfMonth()->subMonth($monthsBack)->format('Y-m-d');
        $startCycleDate = Carbon::createFromFormat('Y-m-d', $date)->startOfMonth()->subMonth($monthsBack + 1)->format('Y-m-d');

        $readyToAssign = Category::where([
            'team_id' => $teamId,
        ])
            ->where('name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->first();

        return DB::table(DB::raw('accounts a'))
            ->where('a.team_id', $teamId)
            ->whereNotNull('a.credit_closing_day')
            ->when($asToday, fn ($q) => $q->whereRaw("now() >= DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0')))", [
                $endCycleDate,
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
                $endCycleDate,
            ])

            ->leftJoin(DB::raw('transaction_lines tl'), fn ($q) => $q->on('a.id', 'tl.account_id')
                ->whereRaw('(tl.type = ? or tl.category_id  = ?)', [-1, $readyToAssign->id])
            )
            ->when($creditCardId, fn ($q) => $q->where('account_id', $creditCardId))
            ->whereRaw("(tl.date >= DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) and tl.type = -1
            AND
                tl.date < DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0')))
            )
            OR tl.date = DATE_FORMAT(?, CONCAT('%Y-%m-', LPAD(a.credit_closing_day, 2, '0'))) AND tl.type = 1", [
                $startCycleDate,
                $endCycleDate,
                $endCycleDate,
            ])
            ->groupBy('a.id')
            ->get();
    }

    public function getTopCategoriesByAccount($teamId, $startDate, $endDate, $creditCardId = null)
    {
        return DB::table(DB::raw('accounts a'))
            ->where('a.team_id', $teamId)
            ->whereNotNull('a.credit_closing_day')
            ->selectRaw('
                ABS(COALESCE(SUM(CASE WHEN tl.type = -1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS subtotal,
                ABS(COALESCE(SUM(tl.amount * tl.type), 0)) AS total,
                ABS(COALESCE(SUM(CASE WHEN tl.type = 1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS discount,
                a.name,
                a.id,
                c.name as cat_name
            ')
            ->leftJoin(DB::raw('transaction_lines tl'), fn ($q) => $q->on('a.id', 'tl.account_id')
                ->whereNotNull('tl.category_id')
            )
            ->join(DB::raw('categories c'), 'tl.category_id', 'c.id')
            ->whereBetween('tl.date', [$startDate, $endDate])
            ->groupBy(DB::raw('a.id, c.name'))
            ->orderBy('total', 'desc')
            ->get();
    }

    public function getTopCategoriesByCreditCard($teamId, $startDate, $endDate, $creditCardId = null)
    {
        $cacheKey = $this->reportCacheKey('topCategoriesByCreditCard', [
            $teamId,
            (string) $startDate,
            (string) $endDate,
            $creditCardId,
        ]);

        return Cache::remember($cacheKey, self::REPORT_CACHE_TTL_SECONDS, function () use ($teamId, $startDate, $endDate) {
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
                'readyToAssign' => $readyToAssign->id,
            ]);
        });
    }

    public function getTopPayeesByAccount($teamId, $startDate, $endDate, $asToday = null)
    {
        $cacheKey = $this->reportCacheKey('topPayeesByAccount', [
            $teamId,
            (string) $startDate,
            (string) $endDate,
        ]);

        return Cache::remember($cacheKey, self::REPORT_CACHE_TTL_SECONDS, function () use ($teamId, $startDate, $endDate) {
            $readyToAssign = Category::where([
                'team_id' => $teamId,
            ])
                ->where('name', BudgetReservedNames::READY_TO_ASSIGN->value)
                ->first();

            return DB::select("
        SELECT
            ABS(COALESCE(SUM(CASE WHEN tl.type = -1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS subtotal,
            ABS(COALESCE(SUM(tl.amount * tl.type), 0)) AS total,
            ABS(COALESCE(SUM(CASE WHEN tl.type = 1 THEN tl.amount * tl.type ELSE 0 END), 0)) AS discount,
            a.name,
            a.id,
            p.name as cat_name,
            ROW_NUMBER() OVER (PARTITION BY tl.account_id ORDER BY SUM(tl.amount * tl.type)) AS rank
        FROM transaction_lines tl
        INNER JOIN transactions t on tl.transaction_id = t.id AND t.status = 'verified' AND tl.category_id <> :readyToAssign
        INNER JOIN accounts a on tl.account_id = a.id AND a.credit_closing_day IS NOT NULL
        INNER JOIN payees p on p.id = tl.payee_id
        WHERE tl.date >= :startDate AND tl.date <= :endDate
        AND tl.team_id = :teamId
        GROUP BY tl.payee_id
        ORDER BY ABS(COALESCE(SUM(tl.amount * tl.type), 0)) desc
        ", [
                'teamId' => $teamId,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'readyToAssign' => $readyToAssign->id,
            ]);
        });
    }

    public function getBillingCyclesByCardInPeriod($teamId, $startDate, $endDate, $creditCardId = null)
    {
        $cacheKey = $this->reportCacheKey('billingCyclesByCardInPeriod', [
            $teamId,
            (string) $startDate,
            (string) $endDate,
            $creditCardId,
        ]);

        return Cache::remember($cacheKey, self::REPORT_CACHE_TTL_SECONDS, function () use ($teamId, $startDate, $endDate, $creditCardId) {
            $billingData = DB::table(DB::raw('accounts a'))
                ->where('a.team_id', $teamId)
                ->whereNotNull('a.credit_closing_day')
                ->selectRaw('
                ABS(COALESCE(SUM(bc.subtotal), 0)) AS subtotal,
                ABS(COALESCE(SUM(bc.total), 0)) AS total,
                ABS(COALESCE(SUM(bc.discounts), 0)) AS discounts,
                a.name,
                a.id
            ')
                ->when($creditCardId, fn ($q) => $q->where('a.id', $creditCardId))
                ->leftJoin(DB::raw('billing_cycles bc'), 'a.id', 'bc.account_id')
                ->whereBetween('bc.end_at', [$startDate, $endDate])
                ->groupBy('a.id')
                ->get();

            return [
                'data' => $billingData,
                'discountTotal' => $billingData->sum('discounts'),
                'total' => $billingData->sum('total'),
                'subtotal' => $billingData->sum('subtotal'),
            ];
        });
    }

    public function getBillingCyclesInPeriod($teamId, $startDate, $endDate, $creditCardId = null, $statuses = null)
    {
        return DB::table(DB::raw('accounts a'))
            ->where('a.team_id', $teamId)
            ->whereNotNull('a.credit_closing_day')
            ->selectRaw('
                bc.*,
                a.name
            ')
            ->when($creditCardId, fn ($q) => $q->where('a.id', $creditCardId))
            ->leftJoin(DB::raw('billing_cycles bc'), 'a.id', 'bc.account_id')
            ->when($statuses, fn ($q) => $q->whereIn('bc.status', $statuses))
            ->when($startDate && $endDate, fn ($q) => $q->whereBetween('bc.end_at', [$startDate, $endDate]))
            ->when(! $startDate && $endDate, fn ($q) => $q->where('bc.end_at', '<=', $endDate))
            ->when($startDate && ! $endDate, fn ($q) => $q->where('bc.end_at', '>=', $startDate))
            ->orderByDesc('bc.end_at')
            ->get();
    }

    public function creditCards($teamId, $date, ?string $startDate = null, ?array $accountIds = null)
    {
        $hasCreditCards = Account::query()
            ->where('team_id', $teamId)
            ->whereNotNull('credit_closing_day')
            ->when(! empty($accountIds), fn ($q) => $q->whereIn('id', $accountIds))
            ->exists();

        $lastCycleBalances = $this->creditCardCycleByAccount($teamId, $date);
        $previousCycleBalances = $this->creditCardCycleByAccount($teamId, $date, 2);

        if (! empty($accountIds)) {
            $lastCycleBalances = $lastCycleBalances->whereIn('id', $accountIds)->values();
            $previousCycleBalances = $previousCycleBalances->whereIn('id', $accountIds)->values();
        }

        $creditTotal = (float) $lastCycleBalances->sum('total');
        $creditCapacity = (float) $lastCycleBalances->sum('credit_limit');
        $creditTotalPrevious = (float) $previousCycleBalances->sum('total');
        $creditTotalDelta = $creditTotal - $creditTotalPrevious;

        $startPeriodDate = $startDate
            ? Carbon::createFromFormat('Y-m-d', $startDate)
            : Carbon::createFromFormat('Y-m-d', $date)->startOfMonth()->subMonths(12);

        return [
            'hasCreditCards' => $hasCreditCards,
            'lastCycleBalances' => $lastCycleBalances,
            'creditTotal' => $creditTotal,
            'creditTotalPrevious' => $creditTotalPrevious,
            'creditTotalDelta' => $creditTotalDelta,
            'creditCapacity' => $creditCapacity,
            'creditLineUsage' => $creditCapacity > 0 ? round($creditTotal / $creditCapacity * 100, 2) : 0.0,
            'topCategoriesByCard' => $this->getTopCategoriesByCreditCard($teamId, $startPeriodDate, $date),
            'billingCyclesByCard' => $this->getBillingCyclesByCardInPeriod($teamId, $startPeriodDate, $date),
            'topPayeesByCard' => $this->getTopPayeesByAccount($teamId, is_string($startPeriodDate) ? $startPeriodDate : $startPeriodDate->format('Y-m-d'), $date),
        ];
    }

    public function generateBillingCycles($teamId, $yearMonth)
    {
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
            $lastCycleBalances = $this->creditCardCycleByAccount($teamId, $month.'-01', 0, null, true)->sortBy('from');
            $count++;
            foreach ($lastCycleBalances as $creditCardAccount) {
                echo "{$creditCardAccount->name} month {$creditCardAccount->from} {$creditCardAccount->until} generated".PHP_EOL;
                $billingCycle = BillingCycle::updateOrCreate([
                    'account_id' => $creditCardAccount->id,
                    'team_id' => $teamId,
                    'user_id' => 0,
                    'start_at' => $creditCardAccount->from,
                    'end_at' => $creditCardAccount->until,
                ], [
                    'minimum_payment' => $creditCardAccount->total,
                    'debt' => $creditCardAccount->total,
                    'subtotal' => $creditCardAccount->subtotal,
                    'discounts' => $creditCardAccount->discount,
                    'total' => $creditCardAccount->total,
                    'due_at' => $creditCardAccount->until,
                ]);

                // Notify via Telegram when a new billing cycle is created and the cut date has passed
                if ($billingCycle->wasRecentlyCreated && Carbon::parse($creditCardAccount->until)->lte(now())) {
                    $teamUser = User::where('current_team_id', $teamId)->first();
                    if ($teamUser) {
                        $teamUser->notify(new BillingCycleCutAlert(
                            $billingCycle,
                            $creditCardAccount->name,
                            $teamUser->id
                        ));
                    }
                }
            }
            echo "updated month {$month}".PHP_EOL;
            echo "{$count} of {$total}".PHP_EOL.PHP_EOL;
        }
    }

    /**
     * Last payment made against a credit card account, regardless of whether it
     * was linked to a billing cycle yet. A "payment" specifically means a transfer
     * from another cash/bank-type account — cashback, refunds, and manual adjustments
     * also reduce debt but are not payments in the verb sense.
     *
     * Filter: type = 1 line on the card AND the transaction's source `account_id` is
     * a Loger account whose detail type is one of `AccountDetailType::ALL_CASH`
     * (bank, cash, cash_on_hand, savings). Cashback typically has the card itself
     * as `account_id` (income posted directly to the card), so the join+filter
     * excludes it automatically.
     *
     * Returns null when the account has no payments yet — caller renders an empty state.
     *
     * @return array{date: string, amount: float, source_account: ?string, is_linked: bool}|null
     */
    public function getLastPayment(int $teamId, int $accountId): ?array
    {
        $line = DB::table('transaction_lines as tl')
            ->join('transactions as t', 'tl.transaction_id', '=', 't.id')
            ->join('accounts as src', 'src.id', '=', 't.account_id')
            ->join('account_detail_types as srcType', 'srcType.id', '=', 'src.account_detail_type_id')
            ->where('tl.team_id', $teamId)
            ->where('tl.account_id', $accountId)
            ->where('tl.type', 1)
            ->where('t.status', 'verified')
            ->where('t.account_id', '!=', $accountId)
            ->whereIn('srcType.name', AccountDetailType::ALL_CASH)
            ->orderByDesc('tl.date')
            ->orderByDesc('tl.id')
            ->select([
                'tl.date',
                'tl.amount',
                'src.name as source_account_name',
                't.transactionable_type',
            ])
            ->first();

        if (! $line) {
            return null;
        }

        return [
            'date' => $line->date,
            'amount' => (float) $line->amount,
            'source_account' => $line->source_account_name,
            'is_linked' => ! empty($line->transactionable_type),
        ];
    }

    public function getUnlinkedPayments($teamId, Account $account)
    {
        $readyToAssign = Category::where([
            'team_id' => $teamId,
        ])
            ->where('name', BudgetReservedNames::READY_TO_ASSIGN->value)
            ->first();

        return DB::table(DB::raw('transaction_lines tl'))
            ->where([
                'a.team_id' => $teamId,
                'a.id' => $account->id,
                'tl.payee_id' => 0,
            ])
            ->selectRaw('
                tl.*,
                t.transactionable_type,
                t.transactionable_id,
                a.name
            ')
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

    public function addPayment(BillingCycle $billingCycle, $postData)
    {
        $payment = $billingCycle->createPayment($postData);
        $billingCycle->save();

        return $payment;
    }
}
