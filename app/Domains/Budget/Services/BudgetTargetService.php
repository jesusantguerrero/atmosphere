<?php

namespace App\Domains\Budget\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Integration\Concerns\PlannedTransactionDTO;
use App\Domains\Transaction\Services\PlannedTransactionService;

class BudgetTargetService
{
    public function __construct(private PlannedTransactionService $plannedService, private BudgetCategoryService $budgetCategoryService)
    {

    }

    public function createPlannedTransactions(int $teamId)
    {
        $targets = BudgetTarget::where([
            'team_id' => $teamId,
        ])
            ->whereNotNull('frequency_month_date')
            ->get();

        $months = [now()];

        foreach ($months as $month) {
            foreach ($targets as $target) {
                $this->buildPlanned($target, $month->format('Y-m'));
            }
        }
    }

    private function buildPlanned(BudgetTarget $target, $month)
    {
        $date = $month.'-'.$target->frequency_month_date;
        $data = PlannedTransactionDTO::fromTarget($target, $date);
        $this->plannedService->add($data);
    }

    public function complete(BudgetTarget $budgetTarget, Category $category,  array $postData) {
        $budgetTarget->update(array_merge(
            $postData, [
                'completed_at' => $postData['completed_at']
                ?? $this->budgetCategoryService->getLastTransactionMonth($category)?->month,
            ]));

    }
}
