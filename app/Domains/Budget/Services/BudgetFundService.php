<?php

namespace App\Domains\Budget\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetFund;
use App\Domains\Budget\Models\BudgetTarget;
use App\Models\User;
use Exception;

class BudgetFundService
{
    public function update(Category $category, BudgetTarget $budgetTarget, User $user, $postData)
    {
        if ($category->id !== $budgetTarget->category_id) {
            throw new Exception(__('This target doent belongs to this category'));
        }

        $budgetTarget->update([
            ...$postData,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => $category->name,
            'category_id' => $budgetTarget->category_id,
        ]);
    }

    public function add(User $user, mixed $postData): BudgetFund
    {
        $category = Category::findOrFail($postData['category_id']);
        $userBelongsToTeam = $user->allTeams()->pluck('id')->contains($category->team_id);

        if (! $userBelongsToTeam) {
            abort(403, __('You do not have access to the selected category.'));
        }

        return BudgetFund::create([
            ...$postData,
            'name' => 'Emergency fund',
            'team_id' => $category->team_id,
            'user_id' => $user->id,
        ]);
    }

    public function list($teamId)
    {
        $items = BudgetFund::where([
            'team_id' => $teamId,
        ])->get();

        return $items->map(fn ($item) => [
            ...$item->toArray(),
            ...$this->getData($item),
        ]);
    }

    public function getData(BudgetFund $budgetFund)
    {
        $available = $budgetFund->category->budgets[1]?->available ?? 0;
        // watchlist is optional — a fund can be created without one, in which
        // case there is no monthly-expense reference to compare against.
        $expenses = $budgetFund->watchlist?->fullData();
        $expenseTotal = $expenses['month']?->total ?? 0;

        return [
            'balance' => $available,
            'monthlyExpense' => $expenseTotal,
            'total' => $budgetFund->monthly_splits > 0 ? $available / $budgetFund->monthly_splits : 0,
        ];
    }
}
