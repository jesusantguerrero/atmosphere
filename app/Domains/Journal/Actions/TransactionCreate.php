<?php

namespace App\Domains\Journal\Actions;

use App\Models\Account;
use App\Domains\Transaction\Services\MultiCurrencyTransactionService;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Insane\Journal\Contracts\TransactionCreates;
use Insane\Journal\Models\Core\Transaction;

class TransactionCreate implements TransactionCreates
{
    public function __construct(
        private MultiCurrencyTransactionService $multiCurrencyService
    ) {}

    public function validate(User $user)
    {
        Gate::forUser($user)->authorize('create', Transaction::class);
    }

    public function create(User $user, array $transactionData)
    {
        $transactionData = [
            ...$transactionData,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
        ];

        // Check if this is a multi-currency transaction
        if (isset($transactionData['account_id']) && isset($transactionData['currency_code'])) {
            $account = Account::find($transactionData['account_id']);
            
            if ($account && $account->isMultiCurrency() && 
                $transactionData['currency_code'] !== $account->getPrimaryCurrency()) {
                // Use multi-currency service for secondary currency transactions
                return $this->multiCurrencyService->createCreditCardTransaction($transactionData);
            }
        }

        // Use standard transaction creation for primary currency or non-multi-currency accounts
        return Transaction::createTransaction($transactionData);
    }
}
