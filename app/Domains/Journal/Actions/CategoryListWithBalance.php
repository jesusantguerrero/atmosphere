<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Contracts\CategoryListClientBalances;

class CategoryListWithBalance implements CategoryListClientBalances
{
    public function list(User $user, string $uniqueField, int $clientId)
    {
        $this->validate($user);
        $category = Category::byUniqueField($uniqueField, $user->current_team_id);

        return $category->transactionBalance($clientId);
    }

    public function validate(mixed $user)
    {
        Gate::forUser($user)->authorize('update', Category::class);
    }
}
