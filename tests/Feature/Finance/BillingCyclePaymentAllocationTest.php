<?php

namespace Tests\Feature\Finance;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\BillingCycle;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\AccountDetailType;
use Tests\TestCase;

/**
 * A credit-card payment is made in arrears: it settles the statement that has
 * already closed, never the cycle that is still accumulating. These tests pin
 * BillingCycle::checkPayments so an unlinked payment is attributed to the
 * oldest cycle whose cut has passed — and the open cycle stays unpaid.
 *
 * Regression: a single unlinked DOP 18,815.16 payment dated mid-cycle was
 * marking the still-open DOP 4,230.80 cycle as PAID (debt went negative).
 */
class BillingCyclePaymentAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new AccountDetailTypesCreate)->create();
    }

    private function newCardAndBank(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $teamId = $user->ownedTeams()->first()->id;
        $user->forceFill(['current_team_id' => $teamId])->save();

        $creditCardType = AccountDetailType::where('name', AccountDetailType::CREDIT_CARD)->value('id');
        $bankType = AccountDetailType::where('name', AccountDetailType::BANK)->value('id');

        $card = Account::create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'name' => 'Visa Mi Pais',
            'currency_code' => 'DOP',
            'account_detail_type_id' => $creditCardType,
            'credit_closing_day' => 21,
            'credit_limit' => 68000,
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

    private function recordPayment(Account $bank, Account $card, string $date, float $amount): void
    {
        DB::table('transactions')->insert([
            'team_id' => $bank->team_id,
            'user_id' => $bank->user_id,
            'account_id' => $bank->id,
            'counter_account_id' => $card->id,
            'date' => $date,
            'description' => 'Pago TC',
            'direction' => 'WITHDRAW',
            'total' => $amount,
            'currency_code' => 'DOP',
            'status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeCycle(Account $card, string $start, string $end, float $total): BillingCycle
    {
        return BillingCycle::create([
            'team_id' => $card->team_id,
            'user_id' => $card->user_id,
            'account_id' => $card->id,
            'start_at' => $start,
            'end_at' => $end,
            'due_at' => $end,
            'subtotal' => $total,
            'discounts' => 0,
            'total' => $total,
            'status' => BillingCycle::STATUS_PENDING,
        ]);
    }

    /** Re-save oldest first so every cycle reflects the global allocation. */
    private function recompute(array $cycles): void
    {
        foreach ($cycles as $cycle) {
            $cycle->save();
        }
    }

    public function test_arrears_payment_does_not_settle_the_still_open_cycle(): void
    {
        [, $card, $bank] = $this->newCardAndBank();

        // Single unlinked payment, dated inside the open cycle's window but
        // really paying the statement that closed on 04-21.
        $this->recordPayment($bank, $card, '2026-05-07', 18815.16);

        $cycleA = $this->makeCycle($card, '2026-02-21', '2026-03-21', 23731.64); // oldest open
        $cycleB = $this->makeCycle($card, '2026-03-21', '2026-04-21', 16343.15);
        $cycleC = $this->makeCycle($card, '2026-04-21', '2026-05-21', 4230.80);  // still accumulating
        $this->recompute([$cycleA, $cycleB, $cycleC]);

        $cycleA->refresh();
        $cycleB->refresh();
        $cycleC->refresh();

        // The open cycle must stay unpaid — the payment's cut (05-21) hasn't passed.
        $this->assertSame(BillingCycle::STATUS_PENDING, $cycleC->status);
        $this->assertEqualsWithDelta(0.0, (float) $cycleC->paid, 0.001);
        $this->assertEqualsWithDelta(4230.80, (float) $cycleC->debt, 0.001);

        // Oldest open statement absorbs the payment.
        $this->assertSame(BillingCycle::STATUS_PARTIALLY_PAID, $cycleA->status);
        $this->assertEqualsWithDelta(18815.16, (float) $cycleA->paid, 0.001);

        // Nothing left over for the middle cycle.
        $this->assertSame(BillingCycle::STATUS_PENDING, $cycleB->status);
        $this->assertEqualsWithDelta(0.0, (float) $cycleB->paid, 0.001);
    }

    public function test_payment_after_cut_settles_that_cycle(): void
    {
        [, $card, $bank] = $this->newCardAndBank();

        $cycle = $this->makeCycle($card, '2026-04-21', '2026-05-21', 4230.80);
        // Payment dated after the 05-21 cut covers the full statement.
        $this->recordPayment($bank, $card, '2026-05-25', 4230.80);
        $cycle->save();
        $cycle->refresh();

        $this->assertSame(BillingCycle::STATUS_PAID, $cycle->status);
        $this->assertEqualsWithDelta(4230.80, (float) $cycle->paid, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $cycle->debt, 0.001);
    }

    public function test_unlinked_payment_never_overpays_a_cycle(): void
    {
        [, $card, $bank] = $this->newCardAndBank();

        $cycle = $this->makeCycle($card, '2026-04-21', '2026-05-21', 4230.80);
        // A payment far larger than the statement, dated after the cut.
        $this->recordPayment($bank, $card, '2026-05-25', 18815.16);
        $cycle->save();
        $cycle->refresh();

        // Capped at the cycle's own total — debt never goes negative.
        $this->assertEqualsWithDelta(4230.80, (float) $cycle->paid, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $cycle->debt, 0.001);
        $this->assertSame(BillingCycle::STATUS_PAID, $cycle->status);
    }
}
