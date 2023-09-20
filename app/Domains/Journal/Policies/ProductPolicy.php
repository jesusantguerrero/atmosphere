<?php

namespace App\Domains\Journal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Insane\Journal\Models\Product\Product;

class ProductPolicy
{
    use HandlesAuthorization;


    public function create(User $user)
    {
        return true;
    }


    public function view(User $user, Product $product)
    {
        return $user->team_id == $product->team_id;
    }

    public function update(User $user, Product $product)
    {
        return $user->team_id == $product->team_id;
    }


    public function delete(User $user, Product $product)
    {
        return $user->team_id == $product->team_id;
    }
}
