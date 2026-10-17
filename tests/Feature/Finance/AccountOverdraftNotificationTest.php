<?php

namespace Tests\Feature\Finance;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\User;
use App\Notifications\AccountOverdraftAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Insane\Journal\Events\TransactionCreated;
use Insane\Journal\Events\TransactionUpdated;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Transaction;
use Tests\TestCase;

class AccountOverdraftNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        (new AccountDetailTypesCreate)->create();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->user->forceFill(['current_team_id' => $this->user->ownedTeams()->first()->id])->save();
        $this->account = Account::create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'name' => 'Test efectivo',
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::CASH)->value('id'),
        ]);
    }

    private function record(float $amount, string $direction = 'WITHDRAW', string $status = 'verified', string $currency = 'DOP'): Transaction
    {
        return Transaction::createTransaction([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'counter_account_id' => null,
            'payee_id' => null,
            'category_id' => null,
            'date' => now()->toDateString(),
            'description' => fake()->unique()->sentence(),
            'currency_code' => $currency,
            'direction' => $direction,
            'total' => $amount,
            'status' => $status,
        ]);
    }

    public function test_verified_overdraft_creates_an_inbox_alert_with_a_valid_account_link(): void
    {
        $this->record(1);
        $alert = $this->user->unreadNotifications()->sole();

        $this->assertSame(AccountOverdraftAlert::class, $alert->type);
        $this->assertEquals(-1, $alert->data['balance']);
        $this->assertSame($this->account->id, $alert->data['account_id']);
        $this->assertSame('DOP', $alert->data['currency_code']);
        $this->assertSame('/finance/accounts/'.$this->account->id, $alert->data['link']);

        $this->actingAs($this->user)->get('/notifications')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('unreadCount', 1)
                ->where('notifications.data.0.type', AccountOverdraftAlert::class));
    }

    public function test_repeated_saves_and_more_spending_do_not_duplicate_an_unread_alert(): void
    {
        $transaction = $this->record(1);
        event(new TransactionCreated($transaction));
        event(new TransactionUpdated($transaction));
        $this->record(2);

        $this->assertSame(1, $this->user->unreadNotifications()->count());
    }

    public function test_drafts_planned_movements_and_nonnegative_balances_do_not_alert(): void
    {
        $this->record(100, status: 'draft');
        $this->record(100, status: 'planned');
        $this->record(10, direction: 'DEPOSIT');
        $this->record(10);

        $this->assertSame(0, $this->user->notifications()->count());
    }

    public function test_approving_a_draft_triggers_the_alert(): void
    {
        $transaction = $this->record(1, status: 'draft');
        $transaction->updateTransaction([...$transaction->only($transaction->getFillable()), 'status' => 'verified']);

        $this->assertSame(1, $this->user->unreadNotifications()->count());
    }

    public function test_recovery_resolves_the_alert_and_a_later_overdraft_can_alert_again(): void
    {
        $this->record(1);
        $this->record(1, direction: 'DEPOSIT');
        $this->assertSame(0, $this->user->unreadNotifications()->count());
        $this->assertNotNull($this->user->notifications()->sole()->read_at);

        $this->record(2);
        $this->assertSame(1, $this->user->unreadNotifications()->count());
        $this->assertSame(2, $this->user->notifications()->count());
    }

    public function test_normal_credit_card_debt_is_not_a_cash_overdraft(): void
    {
        $this->account->update([
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::CREDIT_CARD)->value('id'),
        ]);
        $this->record(100);

        $this->assertSame(0, $this->user->notifications()->count());
    }

    public function test_currency_balances_are_not_combined_or_deduplicated_across_currencies(): void
    {
        $this->record(100, direction: 'DEPOSIT');
        $this->record(1, currency: 'USD');
        $this->record(101);

        $alerts = $this->user->unreadNotifications()->get();
        $this->assertCount(2, $alerts);
        $this->assertEqualsCanonicalizing(['DOP', 'USD'], $alerts->pluck('data.currency_code')->all());
    }

    public function test_foreign_team_accounts_and_nonmembers_never_receive_alerts(): void
    {
        $outsider = User::factory()->withPersonalTeam()->create();
        $transaction = $this->record(1, status: 'draft');
        $transaction->forceFill(['status' => 'verified', 'team_id' => $outsider->ownedTeams()->first()->id])->save();
        event(new TransactionUpdated($transaction));
        $transaction->forceFill(['user_id' => $outsider->id, 'team_id' => $this->account->team_id])->save();
        event(new TransactionUpdated($transaction));

        $this->assertSame(0, $this->user->notifications()->count());
        $this->assertSame(0, $outsider->notifications()->count());
    }

    public function test_transaction_rollback_does_not_leave_an_alert(): void
    {
        $connection = $this->account->getConnection();
        $connection->beginTransaction();
        $this->record(1);
        $this->assertSame(0, $this->user->notifications()->count());
        $connection->rollBack();

        $this->assertSame(0, $this->user->notifications()->count());
    }
}
