<?php

namespace App\Domains\Budget\Services;

use Exception;
use App\Models\User;
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

    public function getNextBudgetItems($teamId)
    {
        return BudgetTarget::getNextTargets($teamId);
    }

    private function buildPlanned(BudgetTarget $target, $month)
    {
        $date = $month.'-'.$target->frequency_month_date;
        $data = PlannedTransactionDTO::fromTarget($target, $date);
        $this->plannedService->add($data);
    }

    public function complete(BudgetTarget $budgetTarget, Category $category,  array $postData) {
        $budgetTarget->update([
            ...$postData,
            'completed_at' => $postData['completed_at'] ?? $this->budgetCategoryService->getLastTransactionMonth($category)?->month,
        ]);
    }

    public function update(Category $category, BudgetTarget $budgetTarget, User $user, $postData) {
        if ($category->id !== $budgetTarget->category->id){
            throw new Exception(__("This target doent belongs to this category"));
        }

        $budgetTarget->update([
            ...$postData,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => $category->name,
            'category_id' => $budgetTarget->category_id,
        ]);
    }

    public function add(Category $category,User $user, mixed $postData)
    {
        if ($category->team_id !== $user->current_team_id){
            throw new Exception(__("This category doent belongs to this team"));
        }

        return $category->budget()->create([
            ...$postData,
            'name' => $category->name ?? $category->display_id,
            'team_id' => $user->current_team_id,
            "user_id" => $user->id
        ]);
    }
}
