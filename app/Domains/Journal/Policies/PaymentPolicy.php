<?php

namespace App\Domains\Journal\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Insane\Journal\Models\Invoice\Invoice;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
        return true;
    }

    public function pay(User $user, Invoice $invoice)
    {
        return $user->team_id == $invoice->team_id;
    }

    public function delete(User $user, Invoice $invoice)
    {
        return $user->team_id == $invoice->team_id;
    }
}
