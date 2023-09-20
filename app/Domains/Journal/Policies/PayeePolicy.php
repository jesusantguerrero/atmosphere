<?php

namespace App\Domains\Journal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Insane\Journal\Models\Core\Payee;

class PayeePolicy
{
    use HandlesAuthorization;


    public function create(User $user)
    {
        return true;
    }

    public function view(User $user, Payee $payee)
    {
        return $user->team_id == $payee->team_id;
    }

    public function update(User $user, Payee $payee)
    {
        return $user->team_id == $payee->team_id;
    }


    public function delete(User $user, Payee $payee)
    {
        return $user->team_id == $payee->team_id;
    }
}
