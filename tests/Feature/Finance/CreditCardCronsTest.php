<?php

namespace Tests\Feature\Finance;

use App\Domains\Transaction\Models\BillingCycle;
use Tests\Feature\Helpers\CreditCardBase;

class CreditCardCronsTest extends CreditCardBase
{
    public function test_it_generates_late_fees()
    {
        $this->markTestIncomplete(
            'No late-fee logic exists in the codebase yet (no occurrence of /lateFee/ in app/). '
            .'`bg:generate-billing-cycles` only emits cycles for months that already have transactions, '
            .'so this test would also need to seed transactions on the credit card first. '
            .'Re-enable when the late-fee feature ships and seed transactions in setup.'
        );

        $this->createCreditCard([
            'name' => 'Visa Gold',
            'category_id' => null,
            'account_detail_type_id' => null,
            'description' => 0,
            'opening_balance' => 0,
            'credit_closing_day' => 15,
            'credit_limit' => 10000,
        ]);

        $this->artisan('bg:generate-billing-cycles')->assertSuccessful();
        $this->assertGreaterThan(0, BillingCycle::count());
    }
}
