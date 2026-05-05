<?php

namespace App\Domains\Journal\Actions;

use App\Domains\Transaction\Services\MultiCurrencyTransactionService;
use App\Models\Account;
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

    /**
     * Strip form fields that don't map to any Transaction column. Without this,
     * LM-8's Model::preventSilentlyDiscardingAttributes throws on the downstream
     * mass-assign. These are all UI helpers from the transaction form (payee
     * autocomplete, tag picker, multi-currency toggle, etc.).
     */
    private function stripUiOnlyFields(array $data): array
    {
        unset(
            $data['name'],
            $data['payee_label'],
            $data['label_id'],
            $data['label_name'],
            $data['is_multi_currency'],
            $data['resource_type_id'],
        );

        return $data;
    }

    public function create(User $user, array $transactionData)
    {
        $transactionData = $this->stripUiOnlyFields([
            ...$transactionData,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
        ]);

        if (isset($transactionData['account_id'], $transactionData['currency_code'])) {
            $account = Account::find($transactionData['account_id']);

            if ($account && $account->isMultiCurrency()
                && $transactionData['currency_code'] !== $account->getPrimaryCurrency()) {
                // Multi-currency path: MultiCurrencyTransactionService uses Loger's
                // Transaction extension which has exchange_rate/exchange_amount fillable.
                return $this->multiCurrencyService->createCreditCardTransaction($transactionData);
            }
        }

        // Standard path goes through the journal package's static helper, which
        // doesn't know about Loger's exchange columns. Drop them here so we don't
        // throw — they don't apply to single-currency transactions anyway.
        unset($transactionData['exchange_rate'], $transactionData['exchange_amount']);

        return Transaction::createTransaction($transactionData);
    }
}
