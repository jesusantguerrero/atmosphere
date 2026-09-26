<?php

namespace Tests\Feature\Finance;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\User;
use App\Notifications\CreditCardRenewalAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

class CreditCardRenewalReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
    }

    public function test_notifies_once_when_card_renewal_is_within_thirty_days(): void
    {
        Carbon::setTestNow('2026-09-15 12:00:00');
        Notification::fake();

        try {
            [$user, $card] = $this->createCard(10);

            $this->artisan('app:check-credit-card-renewals')->assertSuccessful();

            Notification::assertSentTo($user, CreditCardRenewalAlert::class, function ($notification) use ($user, $card): bool {
                $payload = $notification->toArray($user);

                return $payload['account_id'] === $card->id
                    && $payload['renewal_year'] === 2026
                    && str_contains($payload['message'], 'annual fee 2,500.00')
                    && str_contains($payload['message'], 'monthly insurance 350.00');
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_does_not_notify_outside_the_renewal_window(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');
        Notification::fake();

        try {
            [$user] = $this->createCard(10);

            $this->artisan('app:check-credit-card-renewals')->assertSuccessful();

            Notification::assertNotSentTo($user, CreditCardRenewalAlert::class);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createCard(int $renewalMonth): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $teamId = $user->ownedTeams()->first()->id;
        $user->forceFill(['current_team_id' => $teamId])->save();

        $card = Account::create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'name' => 'Visa BHD',
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::CREDIT_CARD)->value('id'),
            'credit_closing_day' => 21,
            'credit_payment_days' => 20,
            'credit_limit' => 100000,
            'credit_renewal_month' => $renewalMonth,
            'credit_annual_fee' => 2500,
            'credit_monthly_insurance' => 350,
        ]);

        return [$user, $card];
    }
}
