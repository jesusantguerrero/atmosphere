<?php

namespace App\Domains\Journal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Insane\Journal\Models\Core\Category;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function list(User $user)
    {
        return true;
    }

    public function view(User $user, Category $category)
    {
        return (int) $user->current_team_id === (int) $category->team_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Category $category)
    {
        return (int) $user->current_team_id === (int) $category->team_id;
    }

    public function delete(User $user, Category $category)
    {
        return (int) $user->current_team_id === (int) $category->team_id;
    }
}
