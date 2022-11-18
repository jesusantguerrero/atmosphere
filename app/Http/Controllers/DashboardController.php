<?php

namespace App\Http\Controllers;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Transaction\Services\TransactionService;
use App\Http\Resources\PlannedMealResource;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController {
    public function __invoke() {
        $request = request();
        $startDate = $request->query('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $team = $request->user()->currentTeam;
        $teamId = $request->user()->current_team_id;
        $budget = BudgetMonth::getMonthAssignments($teamId, $startDate);
        $transactionsTotal = TransactionService::getExpensesTotal($teamId, $startDate, $endDate);
        $plannedMeals = Planner::where([
            'team_id' => $teamId,
            'date' => date('Y-m-d')
        ])->with(['dateable', 'dateable.mealType'])->get();

        $nextPayments = BudgetCategoryService::getNextBudgetItems($teamId);

        return Inertia::render('Dashboard', [
            "sectionTitle" => "Dashboard",
            "meals" => PlannedMealResource::collection($plannedMeals),
            "budgetTotal" => $budget->sum('budgeted'),
            "transactionTotal" => $transactionsTotal,
            "revenue" => ReportService::generateCurrentPreviousReport($teamId, 'month', 1),
            'onboarding' => function () use ($team) {
                $onboarding =  $team->onboarding();
                return $onboarding->inProgress() ? [
                    "percentage" => $onboarding->percentageCompleted(),
                    "steps" => $onboarding->steps()->map(function($step) {
                        return ["title" => $step->title,
                        "cta" => $step->cta,
                        "link" => $step->link,
                        "description" => $step->description,
                        "icon" => $step->icon,
                        "complete" => $step->complete(),
                    ];
                    })
                ] : [];
            },
            "nextPayments" => $nextPayments
        ]);
    }
}
