<?php

namespace App\Domains\Budget\Services;

use Exception;
use App\Models\User;
use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMatchAccount;

class BudgetMatchService
{
    public function update(Category $category, BudgetMatchAccount $matchAccount, User $user, $postData) {
        if ($category->id !== $matchAccount->category_id){
            throw new Exception(__("This match account doesn't belongs to this category $category->id and $matchAccount->category_id are different"));
        }

        $matchAccount->update([
            ...$postData,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'category_id' => $matchAccount->category_id,
        ]);
    }

    public function add(User $user, mixed $postData)
    {
        return BudgetMatchAccount::create([
            ...$postData,
            'team_id' => $user->current_team_id,
            "user_id" => $user->id
        ]);
    }

    public function list($teamId) {
        $items = BudgetMatchAccount::where([
            "team_id" => $teamId
        ])->get();

        return $items->map(fn( $item) => [
            ...$item->toArray(),
        ]);
    }
}
