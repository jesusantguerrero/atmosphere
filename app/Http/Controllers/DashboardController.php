<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlannedMealResource;
use App\Models\BudgetMonth;
use App\Models\Planner;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Insane\Journal\Helpers\ReportHelper;

class DashboardController {
    public function __invoke() {
        $request = request();
        $startDate = $request->query('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $teamId = $request->user()->current_team_id;

        $budget = BudgetMonth::getMonthAssignments($teamId, $startDate);
        $transactionsTotal = Transaction::getExpensesTotal($teamId, $startDate, $endDate);
        $plannedMeals = Planner::where([
            'team_id' => $teamId,
            'date' => date('Y-m-d')
        ])->with(['dateable', 'dateable.mealType'])->get();

        return Inertia::render('Dashboard', [
            "sectionTitle" => "Dashboard",
            "strings" => __('dashboard'),
            "meals" => PlannedMealResource::collection($plannedMeals),
            "budgetTotal" => $budget->sum('budgeted'),
            "transactionTotal" => $transactionsTotal,
            "revenue" => ReportHelper::generateExpensesByPeriod($teamId),
        ]);
    }
}
