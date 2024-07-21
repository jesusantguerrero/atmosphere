<?php

namespace App\Http\Controllers\Finance;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Freesgen\Atmosphere\Http\Querify;
use Insane\Journal\Models\Core\Transaction;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Transaction\Services\TransactionService;
use App\Domains\Transaction\Services\CreditCardReportService;

class FinanceTrendController extends Controller
{
    use Querify;

    const DateFormat = 'Y-m-d';

    const sections = [
        'groups' => [
            "handler" => 'group'
        ],
        'categories' => [
            "handler" => 'category'
        ],
        'payees' => [
            "handler" => 'payee',
        ],
        'net-worth' => [
            "template" => "Trends/NetWorth",
            "handler" =>'NetWorth',
        ],
        'income-expenses' => [
            "handler" => 'IncomeExpenses',
        ],
        'spending-year' => [
            "handler" => 'spendingYear'
        ],
        'assigned-year' => [
            "handler" => 'assignedInYear'
        ],
        'income-expenses-graph' => [
            "handler" => 'IncomeExpensesGraph'
        ],
        'year-summary' => [
            "handler" => 'yearSummary',
        ],
        'credit-cards' => [
            "template" => "Trends/CreditCards",
            "handler" => 'creditCards',
        ]
    ];

    public function __construct(private ReportService $reportService, private CreditCardReportService $creditCardService)
    {

    }

    public function index(Request $request, $sectionName = 'groups')
    {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        $section = self::sections[$sectionName];
        $sectionHandler = $section["handler"];
        $sectionTemplate = $section["template"] ?? 'Trends/Overview';
        $data = $this->$sectionHandler($request);

        return inertia($sectionTemplate,
            array_merge([
                'serverSearchOptions' => $filters,
                'section' => $sectionName,
            ],
                $data
        ));
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
                'name' => 'group'
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
                'name' => 'categories'
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

        $data = TransactionService::getIncomeByPayeeInPeriod($teamId, $startDate, $endDate, $direction);

        return [
            'data' => $data,
            'metaData' => [
                'title' => 'Payee Trends',
                'name' => 'payee'
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
            "month" => 12,
            "year" => 2
        ];

        return [
            'data' => ReportService::getIncomeVsExpenses($teamId, 2, $startDate, 'year'),
            'metaData' => [
                'name' => 'incomeExpensesGraph',
                'title' => 'Income vs Expenses',
                'props' => [
                    'headerTemplate' => 'grid',
                    "assetsLabel" => "income",
                    "debtsLabel" => "expense"
                ],
            ],
        ];
    }

    public function spendingYear()
    {
        $queryParams = request()->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [ $startDate ] = $this->getFilterDates($filters);
        $teamId = request()->user()->current_team_id;
        $excludedAccounts = null;
        if (isset($filters['category'])) {
            $excludedAccounts = collect(explode(',', $filters['category']))->map(fn ($id) => "-$id")->all();
        }

        return [
            'data' => ReportService::generateExpensesByPeriod($teamId, $startDate, 12, 'month', $excludedAccounts),
            'metaData' => [
                'name' => 'spendingYear',
                'title' => 'Expenses',
                'props' => [
                    'headerTemplate' => 'grid',
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

        return [
            'data' => ReportService::getAssignedByPeriod($teamId, $startDate, 12, 'month', $excludedAccounts),
            'metaData' => [
                'name' => 'assignedYear',
                'title' => 'Assigned in year',
                'props' => [
                    'headerTemplate' => 'grid',
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

        return [
            'data' => $this->creditCardService->creditCards($teamId, $endDate),
            'metaData' => [
                'name' => 'creditCards',
                'title' => 'Credit Card Report',
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
