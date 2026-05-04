<?php

namespace Tests\Feature\Finance;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Services\CreditCardReportService;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

/**
 * Coverage for q-1: surface the last payment made on a credit card.
 *
 * The "last payment" is the most recent transaction line on the credit card
 * account where the line direction is positive (type = 1), meaning money
 * arrived at the card to reduce its debt.
 */
class CreditCardLastPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
    }

    private function newUserWithAccounts(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $teamId = $user->ownedTeams()->first()->id;
        $user->forceFill(['current_team_id' => $teamId])->save();

        $creditCardType = AccountDetailType::where('name', AccountDetailType::CREDIT_CARD)->value('id');
        $bankType = AccountDetailType::where('name', AccountDetailType::BANK)->value('id');

        $card = Account::create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'name' => 'Visa Gold',
            'currency_code' => 'DOP',
            'account_detail_type_id' => $creditCardType,
            'credit_closing_day' => 15,
            'credit_limit' => 50000,
        ]);

        $bank = Account::create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'name' => 'Bank Checking',
            'currency_code' => 'DOP',
            'account_detail_type_id' => $bankType,
        ]);

        return [$user, $card, $bank];
    }

    /**
     * Seed a cashback / income credit posted directly to the card (no real source account).
     * Reduces debt (type=1 on card) but is NOT a payment in the verb sense.
     */
    private function recordCashback(int $teamId, int $userId, Account $card, string $date, float $amount): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $teamId,
            'user_id' => $userId,
            'account_id' => $card->id,
            'counter_account_id' => null,
            'date' => $date,
            'description' => 'Cashback reward',
            'direction' => 'DEPOSIT',
            'total' => $amount,
            'currency_code' => 'DOP',
            'status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transaction_lines')->insert([
            'transaction_id' => $transactionId,
            'team_id' => $teamId,
            'user_id' => $userId,
            'account_id' => $card->id,
            'date' => $date,
            'type' => 1,
            'amount' => $amount,
            'anchor' => 1,
            'index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Seed a payment as direct rows. Bypasses the journal mass-assignment paths so the
     * test doesn't depend on a sibling fix in the insane/journal package shipping first.
     */
    private function recordPayment(int $teamId, int $userId, Account $bank, Account $card, string $date, float $amount): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $teamId,
            'user_id' => $userId,
            'account_id' => $bank->id,
            'counter_account_id' => $card->id,
            'date' => $date,
            'description' => 'Payment to '.$card->name,
            'direction' => 'WITHDRAW',
            'total' => $amount,
            'currency_code' => 'DOP',
            'status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Two lines: -1 on bank (withdrawal), +1 on card (debt reduction).
        DB::table('transaction_lines')->insert([
            [
                'transaction_id' => $transactionId,
                'team_id' => $teamId,
                'user_id' => $userId,
                'account_id' => $bank->id,
                'date' => $date,
                'type' => -1,
                'amount' => $amount,
                'anchor' => 1,
                'index' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'transaction_id' => $transactionId,
                'team_id' => $teamId,
                'user_id' => $userId,
                'account_id' => $card->id,
                'date' => $date,
                'type' => 1,
                'amount' => $amount,
                'anchor' => 0,
                'index' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_returns_null_when_card_has_no_payments(): void
    {
        [$user, $card] = $this->newUserWithAccounts();

        $service = new CreditCardReportService;

        $this->assertNull($service->getLastPayment($user->current_team_id, $card->id));
    }

    public function test_returns_most_recent_payment(): void
    {
        [$user, $card, $bank] = $this->newUserWithAccounts();
        $teamId = $user->current_team_id;

        $this->recordPayment($teamId, $user->id, $bank, $card, '2026-04-10', 1000.00);
        $this->recordPayment($teamId, $user->id, $bank, $card, '2026-04-25', 2500.00);
        $this->recordPayment($teamId, $user->id, $bank, $card, '2026-04-15', 500.00);

        $service = new CreditCardReportService;
        $last = $service->getLastPayment($teamId, $card->id);

        $this->assertNotNull($last);
        $this->assertSame('2026-04-25', $last['date']);
        $this->assertSame(2500.00, $last['amount']);
        $this->assertSame('Bank Checking', $last['source_account']);
    }

    public function test_scoped_to_team(): void
    {
        [, $cardA] = $this->newUserWithAccounts();
        [, $cardB, $bankB] = $this->newUserWithAccounts();

        $this->recordPayment($cardB->team_id, $cardB->user_id, $bankB, $cardB, '2026-04-25', 9999.00);

        $service = new CreditCardReportService;
        $this->assertNull($service->getLastPayment($cardA->team_id, $cardA->id));
    }

    public function test_cashback_is_not_treated_as_a_payment(): void
    {
        [$user, $card, $bank] = $this->newUserWithAccounts();
        $teamId = $user->current_team_id;

        // Real payment 5 days ago.
        $this->recordPayment($teamId, $user->id, $bank, $card, '2026-04-20', 1500.00);
        // Newer cashback that also reduces debt — must NOT win as "last payment".
        $this->recordCashback($teamId, $user->id, $card, '2026-04-28', 75.00);

        $service = new CreditCardReportService;
        $last = $service->getLastPayment($teamId, $card->id);

        $this->assertNotNull($last);
        $this->assertSame('2026-04-20', $last['date']);
        $this->assertSame(1500.00, $last['amount']);
        $this->assertSame('Bank Checking', $last['source_account']);
    }

    public function test_returns_null_when_only_cashback_exists(): void
    {
        [$user, $card] = $this->newUserWithAccounts();

        $this->recordCashback($user->current_team_id, $user->id, $card, '2026-04-28', 200.00);

        $service = new CreditCardReportService;
        $this->assertNull($service->getLastPayment($user->current_team_id, $card->id));
    }
}
