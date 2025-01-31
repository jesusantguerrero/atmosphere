<?php

namespace Tests\Feature\Finance;

use Tests\Feature\Helpers\CreditCardBase;
use App\Domains\Transaction\Models\BillingCycle;

class CreditCardCronsTest extends CreditCardBase {
  public function testItGeneratesLateFees() {
    $this->createCreditCard([
       "name" => "Visa Gold",
       "category_id" => null,
       "account_detail_type_id" => null,
       "description" => 0,
       "opening_balance" => 0,
       "credit_closing_day" => 15,
       "credit_limit" => 10000,
    ]);

    $this->artisan('background:generate-billing-cycles')->assertSuccessful();
    $this->assertGreaterThan(0, BillingCycle::all());
  }
}
