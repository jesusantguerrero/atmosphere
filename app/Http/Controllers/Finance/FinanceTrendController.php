<?php

namespace App\Http\Controllers\Finance;

use App\Domains\Transaction\Services\CreditCardReportService;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Transaction\Services\TransactionService;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Freesgen\Atmosphere\Http\Querify;
use Illuminate\Http\Request;
use Insane\Journal\Models\Core\Transaction;
use Modules\Watchlist\Models\Watchlist;

class FinanceTrendController extends Controller
{
    use Querify;

    const DateFormat = 'Y-m-d';

    const sections = [
        'insights' => [
            'template' => 'Trends/Insights',
            'handler' => 'insights',
        ],
        'groups' => [
            'handler' => 'group',
        ],
        'categories' => [
            'handler' => 'category',
        ],
        'payees' => [
            'handler' => 'payee',
        ],
        'net-worth' => [
            'template' => 'Trends/NetWorth',
            'handler' => 'NetWorth',
        ],
        'income-expenses' => [
            'handler' => 'IncomeExpenses',
        ],
        'spending-year' => [
            'handler' => 'spendingYear',
        ],
        'assigned-year' => [
            'handler' => 'assignedInYear',
        ],
        'income-expenses-graph' => [
            'handler' => 'IncomeExpensesGraph',
        ],
        'year-summary' => [
            'handler' => 'yearSummary',
        ],
        'credit-cards' => [
            'template' => 'Trends/CreditCards',
            'handler' => 'creditCards',
        ],
        'relationships' => [
            'template' => 'Trends/Relationships',
            'handler' => 'relationships',
        ],
    ];

    public function __construct(private ReportService $reportService, private CreditCardReportService $creditCardService) {}

    public function index(Request $request, $sectionName = 'insights')
    {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        $section = self::sections[$sectionName];
        $sectionHandler = $section['handler'];
        $sectionTemplate = $section['template'] ?? 'Trends/Overview';
        $data = $this->$sectionHandler($request);

        $teamId = $request->user()->current_team_id;

        return inertia($sectionTemplate,
            array_merge([
                'serverSearchOptions' => $filters,
                'section' => $sectionName,
                'activeWatchlist' => $this->resolveActiveWatchlist($request, $teamId),
                'watchlists' => $this->teamWatchlists($teamId),
            ],
                $data
            ));
    }

    private function resolveActiveWatchlist(Request $request, int $teamId): ?array
    {
        $id = $request->query('watchlist');
        if (! $id) {
            return null;
        }

        $watchlist = Watchlist::query()
            ->where('team_id', $teamId)
            ->find($id);

        return $watchlist?->only(['id', 'name', 'type', 'input', 'direction']);
    }

    /**
     * @return array<int, array{id:int,name:string,type:string,input:array,direction:?string}>
     */
    private function teamWatchlists(int $teamId): array
    {
        return Watchlist::query()
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'input', 'direction'])
            ->toArray();
    }

    public function group(Request $request)
    {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);
        $excludedCategories = null;

        if (isset($filters['category'])) {
            $excludedCategories = collect(explode(',', $filters['category']))->map(fn ($id) => "-$id")->all();
        }

        $teamId = $request->user()->current_team_id;

        return [
            'data' => TransactionService::getCategoryExpensesGroup($teamId, $startDate, $endDate, null, $excludedCategories),
            'metaData' => [
                'title' => 'Category Group Trends',
                'name' => 'group',
            ],
        ];
    }

    /**
     * Consolidated Insights view — reuses the existing per-section aggregations
     * (spending by group, by payee both directions, and income vs expenses) in
     * a single payload so one page can render the whole picture.
     */
    public function insights(Request $request)
    {
        $filters = $request->query('filter', []);
        [$startDate, $endDate] = $this->getFilterDates($filters);
        $teamId = $request->user()->current_team_id;

        $groups = TransactionService::getCategoryExpensesGroup($teamId, $startDate, $endDate, null, null);

        // History length driven by the range toolbar (1M / 3M / 6M / YTD / 1Y).
        // YTD sends a dynamic count (Jan..current month, 1..12), so accept any
        // 1..12 span instead of the old fixed [3, 6, 12] whitelist that silently
        // coerced 1M and YTD back to 6.
        $months = (int) $request->query('months', 6);
        $months = ($months >= 1 && $months <= 12) ? $months : 6;

        // Money-in vs money-out over the SAME window the spending chart uses, so
        // the "Money out" / "Net cashflow" headline reconciles with the monthly
        // bars and the period average instead of being a fixed 3-month total.
        // getIncomeVsExpenses is now-anchored and spans now-($months-1)..now, so
        // for YTD this is a true calendar Jan..current-month range.
        $incomeExpenses = TransactionService::getIncomeVsExpenses($teamId, max(0, $months - 1));
        // Same two charts the Dashboard's "Financial glance" widget shows, so
        // Insights reuses them behind a Previous / Spending toggle. Anchored to
        // the most recent month that actually has data so they stay populated
        // even when demo data lags the system clock; in production the latest
        // active month IS the current month.
        $latestExpenseDate = ReportService::getLatestExpenseDate($teamId);
        $anchor = $latestExpenseDate
            ? Carbon::createFromFormat('Y-m-d', $latestExpenseDate)
            : Carbon::now();

        // Payee breakdowns (money in / money out) for the latest active month,
        // aggregated per payee. Anchored so they are not empty when data lags.
        $breakStart = $anchor->copy()->startOfMonth()->format('Y-m-d');
        $breakEnd = $anchor->copy()->endOfMonth()->format('Y-m-d');
        $payeesOut = ReportService::getExpensesByPayeeInPeriod($teamId, $breakStart, $breakEnd)
            ->groupBy('name')
            ->map(fn ($rows, $name) => ['name' => $name, 'total' => (float) $rows->sum('total_amount')])
            ->values()->sortByDesc('total')->values();
        // Income by payee — same source the category (income) view uses so the
        // Category / Payee totals line up on the money-in widget.
        $payeesIn = TransactionService::getTransactionsByPayeeInPeriod($teamId, $breakStart, $breakEnd, Transaction::DIRECTION_DEBIT)
            ->groupBy('name')
            ->map(fn ($rows, $name) => ['name' => $name, 'total' => (float) $rows->sum('total')])
            ->values()->sortByDesc('total')->values();
        $expensesReport = ReportService::generateCurrentPreviousReport($teamId, 'month', 1, 'expenses', $latestExpenseDate);
        // Now-anchored (not latest-expense-date anchored): YTD must be a real
        // calendar Jan..current-month span, and the current month stays the end
        // of the range even before it has any transactions.
        $spendingSummary = ReportService::generateExpensesByPeriodInDate(
            $teamId,
            Carbon::now()->subMonths($months - 1)->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d'),
        );
        // Assets vs debts, cumulative by month, for the Patrimonio tab
        // (reuses the ChartNetWorth widget from /trends/net-worth). Now-anchored
        // so its YTD span matches the other tabs exactly — the range toolbar is
        // shared, so all four tabs must resolve YTD to the same Jan..current
        // window instead of slipping to the latest-transaction month.
        $netWorth = collect(TransactionService::getNetWorth(
            $teamId,
            Carbon::now()->subMonths($months - 1)->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d'),
        ))->values();
        // Money in/out per month for the Income tab's monthly chart.
        $monthlyFlow = ReportService::getMonthlyFlow($teamId, $months);
        // Credit card summary for the Cards tab (reuses the credit-card report
        // service). A current-balance snapshot over the last card cycle, not a
        // range series — anchored to now so the whole page reads "as of" the
        // current month like the other tabs.
        $creditCards = $this->creditCardService->creditCards(
            $teamId,
            Carbon::now()->endOfMonth()->format('Y-m-d'),
            Carbon::now()->subMonths(2)->startOfMonth()->format('Y-m-d'),
            null,
        );

        return [
            'data' => [
                'groups' => $groups,
                'payeesOut' => $payeesOut,
                'payeesIn' => $payeesIn,
                'incomeExpenses' => $incomeExpenses,
                'expensesReport' => $expensesReport,
                'spendingSummary' => $spendingSummary,
                'netWorth' => $netWorth,
                'monthlyFlow' => $monthlyFlow,
                'creditCards' => $creditCards,
            ],
            'metaData' => [
                'name' => 'insights',
                'title' => 'Insights',
                'months' => $months,
            ],
        ];
    }

    public function category(Request $request)
    {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);
        $teamId = $request->user()->current_team_id;
        $parentId = $filters['parent_id'] ?? null;

        $data = TransactionService::getCategoryExpenses($teamId, $startDate, $endDate, null, $parentId);
        $hasData = isset($data[0]);
        $parentName = $hasData && $parentId ? $data[0]?->parent_name.' - ' : null;

        return [
            'data' => $data,
            'metaData' => [
                'title' => $parentName.'Category Trends',
                'parent_id' => $hasData ? $data[0]?->parent_id : null,
                'parent_name' => $hasData ? $data[0]?->parent_name : null,
                'name' => 'categories',
            ],
        ];
    }

    public function payee(Request $request)
    {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);
        $direction = isset($filters['expenses']) ? Transaction::DIRECTION_CREDIT : Transaction::DIRECTION_DEBIT;

        $teamId = $request->user()->current_team_id;

        $data = TransactionService::getTransactionsByPayeeInPeriod($teamId, $startDate, $endDate, $direction);

        return [
            'data' => $data->sortByDesc('total')->values(),
            'metaData' => [
                'title' => 'Payee Trends',
                'name' => 'payee',
            ],
        ];
    }

    public function netWorth(Request $request)
    {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        $teamId = $request->user()->current_team_id;

        return [
            'data' => collect(TransactionService::getNetWorth($teamId, $startDate, $endDate))->reverse()->values(),
            'metaData' => [
                'name' => 'netWorth',
                'title' => 'Net Worth',
            ],
        ];
    }

    public function incomeExpenses()
    {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        // [$startDate, $endDate] = $this->getFilterDates($filters);
        $teamId = request()->user()->current_team_id;

        return [
            'data' => TransactionService::getIncomeVsExpenses($teamId, 3),
            'metaData' => [
                'name' => 'incomeExpenses',
                'title' => 'Income vs Expenses',
            ],
        ];
    }

    public function incomeExpensesGraph()
    {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);
        $teamId = request()->user()->current_team_id;

        $span = [
            'month' => 12,
            'year' => 2,
        ];

        return [
            'data' => ReportService::getIncomeVsExpenses($teamId, 2, $startDate, 'year'),
            'metaData' => [
                'name' => 'incomeExpensesGraph',
                'title' => 'Income vs Expenses',
                'props' => [
                    'headerTemplate' => 'grid',
                    'assetsLabel' => 'income',
                    'debtsLabel' => 'expense',
                ],
            ],
        ];
    }

    public function spendingYear()
    {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate] = $this->getFilterDates($filters);
        $teamId = request()->user()->current_team_id;
        $excludedAccounts = null;
        if (isset($filters['category'])) {
            $excludedAccounts = collect(explode(',', $filters['category']))->map(fn ($id) => "-$id")->all();
        }

        $endDate = Carbon::createFromFormat('Y-m-d', $startDate)->endOfYear()->format('Y-m-d');
        $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->startOfYear()->format('Y-m-d');

        $monthlyExpensesInYear = ReportService::generateExpensesByPeriodInDate($teamId, $startDate, $endDate, $excludedAccounts);

        return [
            'data' => $monthlyExpensesInYear,
            'metaData' => [
                'name' => 'spendingYear',
                'title' => 'Expenses this year',
                'props' => [
                    'headerTemplate' => 'grid',
                    'total' => $monthlyExpensesInYear->sum('total'),
                ],
            ],
        ];
    }

    public function assignedInYear()
    {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate] = $this->getFilterDates($filters);
        $teamId = request()->user()->current_team_id;
        $excludedAccounts = null;
        if (isset($filters['category'])) {
            $excludedAccounts = collect(explode(',', $filters['category']))->map(fn ($id) => "-$id")->all();
        }

        $endDate = Carbon::createFromFormat('Y-m-d', $startDate)->endOfYear()->format('Y-m-d');
        $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->startOfYear()->format('Y-m-d');

        $assignedInYear = ReportService::getAssignedByPeriod($teamId, $startDate, $endDate, $excludedAccounts);

        return [
            'data' => $assignedInYear,
            'metaData' => [
                'name' => 'assignedYear',
                'title' => 'Assigned in year',
                'props' => [
                    'headerTemplate' => 'grid',
                    'total' => $assignedInYear->sum('total'),
                ],
            ],
        ];
    }

    public function yearSummary()
    {
        // $queryParams = request()->query();
        // $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        // [$startDate, $endDate] = $this->getFilterDates($filters);
        $teamId = request()->user()->current_team_id;

        return [
            'data' => $this->reportService->yearSummary($teamId, now()->subYear(1)->format('Y')),
            'metaData' => [
                'name' => 'yearSummary',
                'title' => 'Results of the year',
            ],
        ];
    }

    public function creditCards()
    {
        $teamId = request()->user()->current_team_id;
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        $accountIds = isset($filters['account']) && ! empty($filters['account'])
            ? array_map('intval', is_array($filters['account']) ? $filters['account'] : explode(',', $filters['account']))
            : null;

        return [
            'data' => $this->creditCardService->creditCards($teamId, $endDate, $startDate, $accountIds),
            'metaData' => [
                'name' => 'creditCards',
                'title' => 'Credit Card Report',
            ],
        ];
    }

    public function relationships(Request $request)
    {
        return [
            'data' => [],
            'metaData' => [
                'name' => 'relationships',
                'title' => 'Relationships',
            ],
        ];
    }

    private function getFilterDates($filters)
    {
        $dates = isset($filters['date']) ? explode('~', $filters['date']) : [
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d'),
        ];

        return $dates;
    }
}
