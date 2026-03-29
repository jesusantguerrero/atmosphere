<?php

namespace App\Notifications;

use App\Domains\Integration\Models\Integration;
use App\Domains\Transaction\Models\BillingCycle;
use Illuminate\Bus\Queueable;

class BillingCycleCutAlert extends LogerNotification
{
    use Queueable;

    public function __construct(
        private BillingCycle $billingCycle,
        private string $accountName,
        private int $userId
    ) {}

    public function via($notifiable): array
    {
        return ['database', TelegramChannel::class];
    }

    public function toArray($notifiable): array
    {
        return $this->getFormattedMessage();
    }

    private function getFormattedMessage(): array
    {
        $total = number_format($this->billingCycle->total, 2);

        return [
            'message' => "Credit card {$this->accountName} has cut. Billing cycle total: {$total}. Due date: {$this->billingCycle->due_at}",
            'cta' => 'View billing cycle',
            'link' => "/finance/accounts/{$this->billingCycle->account_id}",
        ];
    }

    public function toTelegram($notifiable): array
    {
        $message = $this->getFormattedMessage();

        $userIntegration = Integration::where([
            'name' => 'telegram',
            'user_id' => $this->userId,
        ])->first();

        if (! $userIntegration) {
            return [];
        }

        return [
            ...$message,
            'chatId' => $userIntegration->config->phone_id,
        ];
    }
}
