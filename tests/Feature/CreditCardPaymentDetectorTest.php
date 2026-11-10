<?php

namespace Tests\Feature;

use App\Domains\Transaction\Services\CreditCardPaymentDetector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Transaction;
use Tests\TestCase;

/**
 * «Pago TC Qik 3861» from a bank account was saved as an uncategorized
 * expense, so the Qik card kept asking to be paid.
 */
class CreditCardPaymentDetectorTest extends TestCase
{
    use RefreshDatabase;

    private int $teamId;

    private Account $bank;

    private Account $qik;

    private Account $sirena;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $user->ownedTeams()->first()->id;
        $user->forceFill(['current_team_id' => $this->teamId])->save();

        $this->bank = $this->account($user, 'Personal: No Debit Card', null);
        $this->qik = $this->account($user, 'Qik Visa Clasica', '3861');
        $this->sirena = $this->account($user, 'APAP Sirena', '0112');
    }

    public function test_a_card_payment_is_matched_by_the_last_four_digits(): void
    {
        $this->assertSame($this->qik->id, CreditCardPaymentDetector::cardFor($this->teamId, 'Pago TC 5412****3861', $this->bank->id)?->id);
        $this->assertSame($this->sirena->id, CreditCardPaymentDetector::cardFor($this->teamId, 'PAGO DE TC 4641...0112', $this->bank->id)?->id);
    }

    public function test_a_card_payment_is_matched_by_the_card_name(): void
    {
        $this->assertSame($this->qik->id, CreditCardPaymentDetector::cardFor($this->teamId, 'Pago TC Qik', $this->bank->id)?->id);
        $this->assertSame($this->sirena->id, CreditCardPaymentDetector::cardFor($this->teamId, 'Pago tarjeta Sirena', $this->bank->id)?->id);
    }

    public function test_purchases_ambiguous_names_and_card_to_card_movements_are_left_alone(): void
    {
        $this->assertNull(CreditCardPaymentDetector::cardFor($this->teamId, 'Compra Qik 3861', $this->bank->id));
        $this->assertNull(CreditCardPaymentDetector::cardFor($this->teamId, 'Pago TC', $this->bank->id));
        $this->assertNull(CreditCardPaymentDetector::cardFor($this->teamId, 'Pago TC Qik 3861', $this->sirena->id));
    }

    public function test_the_payment_becomes_a_transfer_to_the_card(): void
    {
        $data = CreditCardPaymentDetector::apply([
            'team_id' => $this->teamId,
            'account_id' => $this->bank->id,
            'counter_account_id' => null,
            'payee_id' => 15,
            'category_id' => 20,
            'description' => 'Pago TC Qik 3861',
            'direction' => Transaction::DIRECTION_CREDIT,
        ]);

        $this->assertSame($this->qik->id, $data['counter_account_id']);
        $this->assertTrue($data['is_transfer']);
        $this->assertNull($data['payee_id']);
        $this->assertNull($data['category_id']);

        $deposit = ['team_id' => $this->teamId, 'account_id' => $this->bank->id, 'description' => 'Pago TC Qik 3861', 'direction' => Transaction::DIRECTION_DEBIT];
        $this->assertSame($deposit, CreditCardPaymentDetector::apply($deposit));
    }

    public function test_the_command_links_payments_already_saved_as_expenses(): void
    {
        $payment = Transaction::createTransaction([
            'team_id' => $this->teamId,
            'user_id' => $this->bank->user_id,
            'account_id' => $this->bank->id,
            'counter_account_id' => null,
            'payee_id' => null,
            'date' => now()->subDays(5)->format('Y-m-d'),
            'description' => 'Pago TC Qik 3861',
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => 22331.79,
            'currency_code' => 'DOP',
            'status' => Transaction::STATUS_VERIFIED,
        ]);

        $this->artisan('app:link-card-payments')->expectsOutputToContain('→ Qik Visa Clasica')->assertSuccessful();
        $this->assertNull($payment->fresh()->counter_account_id);

        $this->artisan('app:link-card-payments', ['--apply' => true])->assertSuccessful();
        $this->assertSame($this->qik->id, (int) $payment->fresh()->counter_account_id);
        $this->assertTrue($payment->fresh()->lines()->where('account_id', $this->qik->id)->exists());
    }

    private function account(User $user, string $name, ?string $number): Account
    {
        return Account::forceCreate([
            'team_id' => $this->teamId,
            'user_id' => $user->id,
            'name' => $name,
            'number' => $number,
            'currency_code' => 'DOP',
            'display_id' => str($name)->slug()->toString(),
            'account_detail_type_id' => 1,
            'credit_closing_day' => $number ? 5 : null,
        ]);
    }
}
