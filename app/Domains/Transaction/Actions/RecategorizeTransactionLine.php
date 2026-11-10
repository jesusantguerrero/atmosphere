<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Budget\Services\BudgetCategoryService;
use App\Domains\Transaction\Models\TransactionLine;
use App\Jobs\RollBudgetForward;
use App\Jobs\RunTeamChecks;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Transaction;

/**
 * Moves one categorized transaction line to another category without
 * rebuilding the transaction's lines. A non-split transaction keeps its own
 * category_id in sync so a later edit doesn't revert the change.
 *
 * Both categories' activity and available are recomputed for the line's month
 * before returning, so the budget screen reloaded right after shows them
 * settled; the queued rollover only carries the change into later months.
 */
class RecategorizeTransactionLine
{
    public function __construct(private BudgetCategoryService $budgetCategoryService) {}

    public function handle(TransactionLine $line, Category $category): TransactionLine
    {
        $previousCategory = $line->category_id ? Category::find($line->category_id) : null;

        if ($previousCategory?->id === $category->id) {
            return $line;
        }

        DB::transaction(function () use ($line, $category) {
            $line->update(['category_id' => $category->id]);

            $transaction = $line->transaction;
            if ($transaction && ! $line->is_split && ! $transaction->has_splits) {
                $transaction->update(['category_id' => $category->id]);
            }
        });

        $month = substr($line->date, 0, 7).'-01';
        foreach (array_filter([$previousCategory, $category]) as $affectedCategory) {
            $this->budgetCategoryService->updateActivity($affectedCategory, $month);
            if (! $affectedCategory->account_id) {
                $this->budgetCategoryService->updateMonthBalances($affectedCategory, $month);
            }
        }

        if ($line->transaction?->status === Transaction::STATUS_VERIFIED) {
            RollBudgetForward::dispatch($line->team_id, substr($line->date, 0, 7));
            RunTeamChecks::dispatch($line->team_id);
        }

        return $line;
    }
}
