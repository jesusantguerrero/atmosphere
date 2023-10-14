<?php

namespace App\Http\Controllers\Finance;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Freesgen\Atmosphere\Http\Querify;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Transaction\Services\TransactionService;

class FinanceTrendController extends Controller
{
    use Querify;

    const DateFormat = 'Y-m-d';

    const sections = [
        'groups' => 'group',
        'categories' => 'category',
        'payees' => 'payee',
        'net-worth' => 'NetWorth',
        'income-expenses' => 'IncomeExpenses',
        'income-expenses-graph' => 'IncomeExpensesGraph',
        'year-summary' => 'yearSummary',
    ];

    public function __construct(private ReportService $reportService)
    {

    }

    public function index(Request $request, $sectionName = 'groups')
    {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        $section = self::sections[$sectionName];
        $data = $this->$section($request);

        return inertia('Finance/Trends',
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

        $teamId = $request->user()->current_team_id;

        return [
            'data' => TransactionService::getCategoryExpensesGroup($teamId, $startDate, $endDate),
            'metaData' => [
                'title' => 'Category Group Trends',
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
            ],
        ];
    }

    public function payee(Request $request)
    {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        $teamId = $request->user()->current_team_id;

        $data = TransactionService::getIncomeByPayeeInPeriod($teamId, $startDate, $endDate);

        return [
            'data' => $data,
            'metaData' => [
                'title' => 'Payee Trends',
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

        return [
            'data' => ReportService::generateExpensesByPeriod($teamId, 'month', 12),
            'metaData' => [
                'name' => 'incomeExpensesGraph',
                'title' => 'Income vs Expenses',
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

    private function getFilterDates($filters)
    {
        $dates = isset($filters['date']) ? explode('~', $filters['date']) : [
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d'),
        ];

        return $dates;
    }
}
