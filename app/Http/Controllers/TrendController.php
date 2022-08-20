<?php

namespace App\Http\Controllers;

use App\Helpers\BudgetHelper;
use App\Imports\BudgetImport;
use App\Imports\TransactionsImport;
use App\Models\BudgetMonth;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;
use Insane\Journal\Helpers\CategoryHelper;
use Freesgen\Atmosphere\Http\InertiaController;
use Freesgen\Atmosphere\Http\Querify;
use Insane\Journal\Models\Core\Account;
use Maatwebsite\Excel\Facades\Excel;

class TrendController extends Controller {
    use Querify;
    const DateFormat = 'Y-m-d';
    const sections  = [
        'groups' => 'groupTrends',
        'categories' => 'categories',
        'payees' => 'payees',
        'net-worth' => 'NetWorth',
        'income-expenses' => 'IncomeExpenses',
    ];

    public function index(Request $request, $sectionName = 'groups') {
        $section = self::sections[$sectionName];
        $data = $this->$section($request);

        return Jetstream::inertia()->render($request, 'Finance/Trends', [
            "data" => $data,
        ]);
    }

    public function groupTrends(Request $request) {
        $queryParams = $request->query();
        $filters = isset($queryParams['filter']) ? $queryParams['filter'] : [];
        [$startDate, $endDate] = $this->getFilterDates($filters);

        $teamId = $request->user()->current_team_id;

        return Transaction::getCategoryExpensesGroup($teamId, $startDate, $endDate);
    }

    private Function getFilterDates($filters) {
        $dates = isset($filters['date']) ? explode("~", $filters['date']) : [
            Carbon::now()->startOfMonth()->format('Y-m-d'),
            Carbon::now()->endOfMonth()->format('Y-m-d')
        ];
        return $dates;
    }
}
