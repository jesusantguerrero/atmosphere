<?php

namespace Tests\Feature\Finance;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

/**
 * Regression: NextPaymentsService used to suppress a credit card from "next payments"
 * if any type=1 line existed since the previous cut. That false-positives on cashback,
 * refunds, and adjustments — hiding cards the user still owes.
 *
 * Suppression must require a real payment (transfer from a cash/bank-type Loger account).
 */
class NextPaymentsCashbackSuppressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
    }

    private function setupCardWithDebt(int $closingDay = 3, float $debt = 7000): array
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
            'credit_closing_day' => $closingDay,
            'credit_limit' => 50000,
        ]);

        $bank = Account::create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'name' => 'Bank Checking',
            'currency_code' => 'DOP',
            'account_detail_type_id' => $bankType,
        ]);

        // Account::balance is computed via SUM(amount * type) over verified transaction
        // lines. Seed a purchase BEFORE the previous closing date so it's "debt carried
        // forward" — outside the suppression check window but still part of the balance.
        $purchaseDate = now()->subMonths(2)->format('Y-m-d');
        $this->seedPurchase($teamId, $user->id, $card, $purchaseDate, $debt);

        return [$user, $card, $bank];
    }

    private function seedPurchase(int $teamId, int $userId, Account $card, string $date, float $amount): void
    {
        $transactionId = DB::table('transactions')->insertGetId([
            'team_id' => $teamId,
            'user_id' => $userId,
            'account_id' => $card->id,
            'counter_account_id' => null,
            'date' => $date,
            'description' => 'Purchase on '.$card->name,
            'direction' => 'WITHDRAW',
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
            'type' => -1,
            'amount' => $amount,
            'anchor' => 1,
            'index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

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

    public function test_cashback_does_not_suppress_card_with_debt(): void
    {
        [$user, $card] = $this->setupCardWithDebt();
        $teamId = $user->current_team_id;

        // Cashback in the current statement period — used to falsely suppress.
        $this->recordCashback($teamId, $user->id, $card, now()->subDay()->format('Y-m-d'), 50.00);

        $payments = (new NextPaymentsService)->getNextPayments($teamId);

        $this->assertTrue(
            $payments->contains(fn ($p) => $p['type'] === 'credit_card_payment' && $p['account_id'] === $card->id),
            'Card with $7000 debt and a $50 cashback should still appear in next payments.'
        );
    }

    public function test_real_payment_suppresses_card(): void
    {
        [$user, $card, $bank] = $this->setupCardWithDebt();
        $teamId = $user->current_team_id;

        // Pay the card in the current period — should suppress.
        // The balance still reflects partial debt because the payment ($6,900) is less
        // than total debt ($7,000). This exercises both gates: debt > 0 AND suppression.
        $this->recordPayment($teamId, $user->id, $bank, $card, now()->subDay()->format('Y-m-d'), 6900.00);

        $payments = (new NextPaymentsService)->getNextPayments($teamId);

        $this->assertFalse(
            $payments->contains(fn ($p) => $p['type'] === 'credit_card_payment' && $p['account_id'] === $card->id),
            'A real payment in the current period should suppress the card from next payments.'
        );
    }
}
