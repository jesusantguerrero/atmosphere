<?php

namespace App\Http\Controllers\System;

use Inertia\Inertia;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Meal\Services\MealService;
use App\Http\Resources\PlannedMealResource;
use App\Domains\Transaction\Services\ReportService;
use App\Http\Controllers\Traits\HasEnrichedRequest;
use App\Domains\Transaction\Services\TransactionService;
use App\Domains\Transaction\Services\PlannedTransactionService;

class DashboardController
{
    use HasEnrichedRequest;

    public function __construct(private MealService $mealService, private PlannedTransactionService $plannedService)
    {

    }

    public function __invoke()
    {
        $request = request();
        [$startDate, $endDate] = $this->getFilterDates();
        $team = $request->user()->currentTeam;
        $teamId = $request->user()->current_team_id;
        $budget = BudgetMonth::getMonthAssignmentTotal($teamId, $startDate);
        $transactionsTotal = TransactionService::getExpensesTotal($teamId, $startDate, $endDate);
        $plannedMeals = $this->mealService->getMealSchedule($teamId);
        $nextPayments = $this->plannedService->getPlanned($teamId);

        return inertia('Dashboard', [
            'sectionTitle' => 'Dashboard',
            'meals' => PlannedMealResource::collection($plannedMeals),
            'budgetTotal' => $budget,
            'transactionTotal' => $transactionsTotal,
            'expenses' => ReportService::generateCurrentPreviousReport($teamId, 'month', 1),
            'spendingSummary' => ReportService::generateExpensesByPeriod($teamId, $startDate),
            'onboarding' => function () use ($team) {
                $onboarding = $team->onboarding();

                return $onboarding->inProgress() ? [
                    'percentage' => $onboarding->percentageCompleted(),
                    'steps' => $onboarding->steps()->map(function ($step) {
                        return ['title' => $step->title,
                            'cta' => $step->cta,
                            'link' => $step->link,
                            'description' => $step->description,
                            'icon' => $step->icon,
                            'complete' => $step->complete(),
                        ];
                    }),
                ] : [];
            },
            'checks' => Inertia::lazy(fn () => Occurrence::where('team_id', auth()->user()->current_team_id)->limit(4)->get()),
            'nextPayments' => $nextPayments,
        ]);
    }
}
