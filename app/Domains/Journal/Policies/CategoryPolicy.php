<?php

namespace App\Domains\Journal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }


    public function view(User $user, Category $category)
    {
        return $user->team_id == $category->team_id;
    }


    public function create(User $user)
    {
        return true;
    }


    public function update(User $user, Category $category)
    {
        return $user->team_id == $category->team_id;
    }


    public function delete(User $user, Category $category)
    {
        return $user->team_id == $category->team_id;
    }
}
