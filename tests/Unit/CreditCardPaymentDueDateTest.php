<?php

namespace Tests\Unit;

use App\Models\Account;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CreditCardPaymentDueDateTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_payment_due_date_crosses_month_and_year_boundaries(): void
    {
        $card = new Account(['credit_payment_days' => 20]);

        $this->assertSame('2027-01-10', $card->paymentDueDateForCut(Carbon::parse('2026-12-21'))->format('Y-m-d'));
    }

    public function test_missing_payment_days_preserves_existing_cut_date_behavior(): void
    {
        $card = new Account;

        $this->assertSame('2026-05-21', $card->paymentDueDateForCut(Carbon::parse('2026-05-21'))->format('Y-m-d'));
    }
}
