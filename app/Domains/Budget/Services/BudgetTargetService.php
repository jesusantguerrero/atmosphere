<?php

namespace App\Domains\Budget\Services;

use Exception;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Transaction\Models\TransactionLine;
use App\Domains\Integration\Concerns\PlannedTransactionDTO;
use App\Domains\Transaction\Services\PlannedTransactionService;

class BudgetTargetService
{
    public function __construct(private PlannedTransactionService $plannedService, private BudgetCategoryService $budgetCategoryService) {}

    public function createPlannedTransactionsFor(int $teamId)
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

    public function createPlannedTransactions()
    {
        $targets = BudgetTarget::whereNotNull('frequency_month_date')
        ->whereNotNull('frequency')
        ->where([
            "target_type" => 'spending',
            "notify" => 1
        ])
        ->get();

        $months = [now()];

        foreach ($months as $month) {
            $endOfMonth = $month->endOfMonth()->format('Y-m-d');
            foreach ($targets as $target) {
                $this->buildPlanned($target, $month->format('Y-m'), $endOfMonth);
            }
        }
    }

    private function buildPlanned(BudgetTarget $target, $month, $endOfMonth = null)
    {
        $originalDate = $month.'-'. str_pad($target->frequency_month_date, 2, 0, STR_PAD_LEFT);
        $date = ($endOfMonth && $originalDate > $endOfMonth) ? $endOfMonth : $originalDate;
        $data = PlannedTransactionDTO::fromTarget($target, $date);
        if ($target->frequency_date) {
            $targetDate = Carbon::createFromFormat('Y-m-d', $target->frequency_date);
            if ($targetDate->month != now()->month) return;
        }

        $transaction = TransactionLine::where([
            "transaction_lines.team_id" => $target->team_id,
            "category_id" => $target->category_id,
        ])
        ->whereRaw("DATE_FORMAT(transaction_lines.date, '%Y-%m') like ?", [$month])
        ->first();

        if (!$transaction) {
            $this->plannedService->add($data);
        }
    }

    public function complete(BudgetTarget $budgetTarget, Category $category,  array $postData) {
        $budgetTarget->update([
            ...$postData,
            'completed_at' => $postData['completed_at'] ?? $this->budgetCategoryService->getLastTransactionMonth($category)?->month,
        ]);
    }

    public function update(Category $category, BudgetTarget $budgetTarget, User $user, $postData) {
        if ($category->id !== $budgetTarget->category_id){
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
            throw new Exception(__("This category does not belongs to this team"));
        }

        return $category->budget()->create([
            ...$postData,
            'name' => $category->name ?? $category->display_id,
            'team_id' => $user->current_team_id,
            "user_id" => $user->id
        ]);
    }
}
