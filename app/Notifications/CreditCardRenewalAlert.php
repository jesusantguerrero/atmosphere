<?php

namespace App\Notifications;

use App\Models\Account;
use Illuminate\Bus\Queueable;

class CreditCardRenewalAlert extends LogerNotification
{
    use Queueable;

    public function __construct(private Account $account, private int $renewalYear) {}

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message(),
            'account_id' => $this->account->id,
            'renewal_year' => $this->renewalYear,
            'cta' => 'View card',
            'link' => "/finance/accounts/{$this->account->id}",
        ];
    }

    private function message(): string
    {
        $costs = [];
        if ((float) $this->account->credit_annual_fee > 0) {
            $costs[] = 'annual fee '.number_format((float) $this->account->credit_annual_fee, 2);
        }
        if ((float) $this->account->credit_monthly_insurance > 0) {
            $costs[] = 'monthly insurance '.number_format((float) $this->account->credit_monthly_insurance, 2);
        }

        $costSummary = $costs ? ' Estimated '.implode(' and ', $costs).'.' : '';

        return "{$this->account->name} renews soon. Contact the bank to negotiate the annual fee and insurance.{$costSummary}";
    }
}
