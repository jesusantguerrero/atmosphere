<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;
use Freesgen\Atmosphere\Http\Querify;

class FinanceTrendController extends Controller {
    use Querify;
    const DateFormat = 'Y-m-d';
    const sections  = [
        'groups' => 'groupTrends',
        'categories' => 'categoryTrends',
        'payees' => 'payees',
        'net-worth' => 'NetWorth',
        'income-expenses' => 'IncomeExpenses',
    ];

    public function index(Request $request, $sectionName = 'groups') {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        $section = self::sections[$sectionName];
        $data = $this->$section($request);

        return Jetstream::inertia()->render($request, 'Finance/Trends', array_merge([
            "serverSearchOptions" => $filters
        ], $data));
    }

    public function groupTrends(Request $request) {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        $teamId = $request->user()->current_team_id;

        return [
            'data' => Transaction::getCategoryExpensesGroup($teamId, $startDate, $endDate),
            'metaData' => [
                'title' => 'Category Group Trends',
            ]
        ];
    }

    public function categoryTrends(Request $request) {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        $teamId = $request->user()->current_team_id;

        $data  = Transaction::getCategoryExpenses($teamId, $startDate, $endDate, null, $filters['parent_id'] ?? null);
        $parentName = $data[0]?->parent_name ? $data[0]?->parent_name . " - " : null;
        return [
            'data' => $data,
            'metaData' => [
                'title' => $parentName . 'Category Trends',
                'parent_id' => $data[0]?->parent_id ?? null,
                'parent_name' => $data[0]?->parent_name ?? null,
            ]
        ];
    }

    public function netWorth(Request $request) {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        $teamId = $request->user()->current_team_id;

        return [
            'data' => Transaction::getNetWorth($teamId, $startDate, $endDate),
            'metaData' => [
                'name' => 'netWorth',
                'title' => 'Net Worth',
            ]
        ];
    }

    private Function getFilterDates($filters) {
        $dates = isset($filters['date']) ? explode("~", $filters['date']) : [
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d')
        ];
        return $dates;
    }
}
