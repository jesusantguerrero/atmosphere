<?php

namespace App\Domains\Journal\Actions;

use App\Http\Requests\CreditCardSettingsRequest;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Insane\Journal\Contracts\AccountUpdates;
use Insane\Journal\Models\Core\Account;

class AccountUpdate implements AccountUpdates
{
    public function update(User $user, Account $account, array $accountData): Account
    {
        $this->validate($user, $account);
        $settings = new CreditCardSettingsRequest;
        $settings->setUserResolver(fn () => $user);
        Validator::make([
            'credit_opened_at' => $account->getRawOriginal('credit_opened_at'),
            'closed_at' => $account->getRawOriginal('closed_at'),
            ...$accountData,
        ], [...self::creditCardRules(), ...$settings->rules()], $settings->messages())->validate();
        $account = \App\Models\Account::query()->findOrFail($account->id);
        unset($accountData['team_id'], $accountData['user_id']);
        $account->update($accountData);

        return $account;
    }

    public function validate(User $user, Account $account)
    {
        Gate::forUser($user)->authorize('update', $account);
    }

    private static function creditCardRules(): array
    {
        return [
            'credit_payment_days' => ['nullable', 'integer', 'between:0,90'],
            'credit_renewal_month' => ['nullable', 'integer', 'between:1,12'],
            'credit_annual_fee' => ['nullable', 'numeric', 'min:0'],
            'credit_monthly_insurance' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
