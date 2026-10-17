<?php

namespace App\Notifications;

use App\Models\Account;

class AccountOverdraftAlert extends LogerNotification
{
    public function __construct(
        private Account $account,
        private float $balance,
        private string $currencyCode,
    ) {}

    /**
     * Keep immediate ledger alerts in the inbox without coupling transaction saves to external delivery.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{message: string, cta: string, link: string, team_id: int, account_id: int, currency_code: string, balance: float}
     */
    public function toArray($notifiable): array
    {
        $amount = number_format(abs($this->balance), 2);

        return [
            'message' => "Tu cuenta {$this->account->name} tiene saldo negativo: -{$this->currencyCode} {$amount}.",
            'cta' => 'Revisar cuenta',
            'link' => route('finance.accounts.show', $this->account->id, false),
            'team_id' => $this->account->team_id,
            'account_id' => $this->account->id,
            'currency_code' => $this->currencyCode,
            'balance' => $this->balance,
        ];
    }
}
