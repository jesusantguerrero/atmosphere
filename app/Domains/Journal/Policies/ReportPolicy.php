<?php

namespace App\Domains\Journal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Insane\Journal\Models\Core\Account;

class ReportPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Account $account)
    {
        return $user->team_id == $account->team_id;
    }
}
