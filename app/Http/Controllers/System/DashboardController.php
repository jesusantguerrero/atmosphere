<?php

namespace App\Http\Controllers\System;

use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Meal\Services\MealService;
use App\Domains\Transaction\Services\CreditCardReportService;
use App\Domains\Transaction\Services\PlannedTransactionService;
use App\Domains\Transaction\Services\ReportService;
use App\Domains\Transaction\Services\TransactionService;
use App\Http\Controllers\Traits\HasEnrichedRequest;
use App\Http\Resources\PlannedMealResource;
use Inertia\Inertia;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\AccountDetailType;
use Modules\Watchlist\Services\WatchlistService;

class DashboardController
{
    use HasEnrichedRequest;

    public function __construct(
        private MealService $mealService,
        private PlannedTransactionService $plannedService,
        private CreditCardReportService $creditCardReportService,
        private WatchlistService $watchlistService,
    ) {}

    public function __invoke()
    {
        $request = request();
        [$startDate, $endDate] = $this->getFilterDates();

        $team = $request->user()->currentTeam;
        $teamId = $request->user()->current_team_id;
        $budget = BudgetMonth::getMonthAssignmentTotal($teamId, $startDate);
        $transactionsTotal = TransactionService::getExpensesTotal($teamId, $startDate, $endDate);
        $netWorth = collect(TransactionService::getNetWorth($teamId, $startDate, $endDate))->reverse()->values();
        $plannedMeals = $this->mealService->getMealSchedule($teamId);
        $nextPayments = $this->plannedService->getPlanned($teamId);

        $accounts = Account::getByDetailTypes($teamId, AccountDetailType::ALL)->load('detailType');

        $creditCardIds = $accounts
            ->filter(fn ($a) => $a->detailType?->name === AccountDetailType::CREDIT_CARD)
            ->pluck('id')
            ->all();
        $lastPayments = $this->creditCardReportService->getLastPaymentsForAccounts($teamId, $creditCardIds);
        foreach ($accounts as $account) {
            $account->last_payment = $lastPayments[$account->id] ?? null;
        }

        $topWatchlists = $this->getTopWatchlists($teamId, $startDate, $endDate);

        return inertia('Dashboard/Index', [
            'sectionTitle' => 'Dashboard',
            'meals' => PlannedMealResource::collection($plannedMeals),
            'budgetTotal' => $budget,
            'transactionTotal' => $transactionsTotal,
            'netWorth' => $netWorth,
            'expenses' => ReportService::generateCurrentPreviousReport($teamId, 'month', 1),
            'spendingSummary' => ReportService::generateExpensesByPeriod($teamId, $startDate),
            'accounts' => $accounts,
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
            'drafts' => Inertia::lazy(fn () => TransactionService::getDraftCount($teamId)),
            'checks' => Inertia::lazy(fn () => Occurrence::where('team_id', $teamId)->limit(4)->get()),
            'nextPayments' => $nextPayments,
            'topWatchlists' => $topWatchlists,
        ]);
    }

    /**
     * Pick the 3 watchlists most worth surfacing on the dashboard.
     *
     * Ranking: watchlists with a target come first, ordered by % of target consumed
     * this month (highest first → most actionable). Untargeted watchlists are excluded
     * — without a threshold there's no semáforo to render. Returns at most 3 entries.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTopWatchlists(int $teamId, string $startDate, string $endDate): array
    {
        $all = $this->watchlistService->list($teamId, $startDate, $endDate);

        $withTarget = array_values(array_filter($all, function ($item) {
            return (float) ($item['target'] ?? 0) > 0;
        }));

        usort($withTarget, function ($a, $b) {
            $aRatio = ((float) ($a['data']['month']['total'] ?? 0)) / (float) $a['target'];
            $bRatio = ((float) ($b['data']['month']['total'] ?? 0)) / (float) $b['target'];

            return $bRatio <=> $aRatio;
        });

        return array_slice($withTarget, 0, 3);
    }
}
