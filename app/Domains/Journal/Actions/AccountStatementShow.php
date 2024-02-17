<?php

namespace App\Domains\Journal\Actions;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Helpers\ReportHelper;
use Insane\Journal\Contracts\AccountStatementShows;

class AccountStatementShow implements AccountStatementShows
{
    public function show(User $user, string $reportName, ?int $accountId): array
    {
        $this->validate($user);
        $filters= [
            'account_id' => $accountId
        ];

        return ReportHelper::getGeneralLedger($user->current_team_id, $reportName, $filters);
    }

    public function validate(mixed $user)
    {
        Gate::forUser($user)->authorize('show', Account::class);
    }
}
