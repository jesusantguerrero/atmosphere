<?php

namespace Tests\Feature\Finance;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Models\Account;
use App\Models\User;
use Carbon\Carbon;
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
        // Today is May 6; relevantCutDate (closingDay=3) → May 3.
        // Payment May 4 is strictly after the cut → suppression applies.
        Carbon::setTestNow(Carbon::create(2026, 5, 6, 10, 0, 0));

        try {
            [$user, $card, $bank] = $this->setupCardWithDebt();
            $teamId = $user->current_team_id;

            // Partial payment leaves $100 debt — exercises both gates: debt > 0 AND suppression.
            $this->recordPayment($teamId, $user->id, $bank, $card, '2026-05-04', 6900.00);

            $payments = (new NextPaymentsService)->getNextPayments($teamId);

            $this->assertFalse(
                $payments->contains(fn ($p) => $p['type'] === 'credit_card_payment' && $p['account_id'] === $card->id),
                'A real payment after the most recent cut should suppress the card from next payments.'
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Real-world case from prod: card with closingDay=3, today=May 4. User paid the
     * April-3 statement on April 15. That payment satisfied the previous cycle, NOT
     * the cycle that just closed May 3. The card must still appear in next payments.
     */
    public function test_payment_for_previous_cycle_does_not_suppress_current_cycle(): void
    {
        // Force "today" to a date past the current month's closing.
        $today = Carbon::create(2026, 5, 4, 10, 0, 0);
        Carbon::setTestNow($today);

        try {
            [$user, $card, $bank] = $this->setupCardWithDebt(closingDay: 3);
            $teamId = $user->current_team_id;

            // Paid the previous cycle on April 15 — between previousClosing (Apr 3) and
            // currentClosing (May 3). Old code suppressed; new code must not.
            $this->recordPayment($teamId, $user->id, $bank, $card, '2026-04-15', 5000.00);

            $payments = (new NextPaymentsService)->getNextPayments($teamId);

            $this->assertTrue(
                $payments->contains(fn ($p) => $p['type'] === 'credit_card_payment' && $p['account_id'] === $card->id),
                'A payment between previous and current closing satisfies the previous statement, not the just-closed one. Card must still appear.'
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Counterpart: when the cut for this month is in the future, a payment between
     * the previous cut and today legitimately satisfies the just-passed statement.
     */
    public function test_payment_after_previous_cut_suppresses_when_current_cut_is_future(): void
    {
        $today = Carbon::create(2026, 5, 4, 10, 0, 0);
        Carbon::setTestNow($today);

        try {
            // closingDay 20 → current month's closing is May 20 (future).
            // previousClosingDate = April 20.
            [$user, $card, $bank] = $this->setupCardWithDebt(closingDay: 20);
            $teamId = $user->current_team_id;

            $this->recordPayment($teamId, $user->id, $bank, $card, '2026-04-25', 6900.00);

            $payments = (new NextPaymentsService)->getNextPayments($teamId);

            $this->assertFalse(
                $payments->contains(fn ($p) => $p['type'] === 'credit_card_payment' && $p['account_id'] === $card->id),
                'When the next cut is still in the future, a payment after the previous cut correctly suppresses.'
            );
        } finally {
            Carbon::setTestNow();
        }
    }
}
