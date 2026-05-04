<?php

namespace Tests\Unit;

use App\Domains\Transaction\Models\Transaction;
use PHPUnit\Framework\TestCase;

class TransactionCurrencyTest extends TestCase
{
    /** @test */
    public function it_can_calculate_exchange_rate()
    {
        $transaction = new Transaction;

        // Test normal calculation
        $rate = $transaction->calculateExchangeRate(100.0, 85.0);
        $this->assertEquals(0.85, $rate);

        // Test with different values
        $rate = $transaction->calculateExchangeRate(50.0, 42.5);
        $this->assertEquals(0.85, $rate);

        // Test precision rounding
        $rate = $transaction->calculateExchangeRate(3.0, 1.0);
        $this->assertEquals(0.333333, $rate);
    }

    /** @test */
    public function it_throws_exception_for_zero_total_in_exchange_rate_calculation()
    {
        $transaction = new Transaction;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total amount cannot be zero for exchange rate calculation');

        $transaction->calculateExchangeRate(0.0, 85.0);
    }

    /** @test */
    public function it_has_currency_conversion_methods()
    {
        $transaction = new Transaction;

        // Test without conversion data
        $this->assertFalse($transaction->hasCurrencyConversion());
        $this->assertNull($transaction->getConvertedAmount());
        $this->assertNull($transaction->getExchangeRate());

        // Set conversion data
        $transaction->exchange_rate = 0.85;
        $transaction->exchange_amount = 85.0;

        // Test with conversion data
        $this->assertTrue($transaction->hasCurrencyConversion());
        $this->assertEquals(85.0, $transaction->getConvertedAmount());
        $this->assertEquals(0.85, $transaction->getExchangeRate());
    }

    /** @test */
    public function it_has_fillable_exchange_fields()
    {
        $transaction = new Transaction;
        $fillable = $transaction->getFillable();

        $this->assertContains('exchange_rate', $fillable);
        $this->assertContains('exchange_amount', $fillable);
    }

    /**
     * Regression for LM-8: child $fillable used to shadow parent's,
     * silently dropping team_id/user_id/category_id/etc. during mass assignment.
     *
     * @test
     */
    public function fillable_includes_parent_fields_to_prevent_silent_drop(): void
    {
        $fillable = (new Transaction)->getFillable();

        $parentFields = [
            'team_id', 'user_id', 'payee_id', 'category_id',
            'counter_account_id', 'account_id', 'date', 'description',
            'direction', 'total', 'currency_code', 'status',
        ];

        foreach ($parentFields as $field) {
            $this->assertContains(
                $field,
                $fillable,
                "Parent field '{$field}' must remain fillable; otherwise Transaction::create([...]) silently drops it."
            );
        }

        $this->assertContains('exchange_rate', $fillable);
        $this->assertContains('exchange_amount', $fillable);
    }

    /** @test */
    public function mass_assignment_persists_parent_and_child_fields(): void
    {
        $transaction = (new Transaction)->fill([
            'team_id' => 99,
            'user_id' => 88,
            'category_id' => 77,
            'account_id' => 66,
            'payee_id' => 55,
            'total' => 123.45,
            'status' => 'verified',
            'exchange_rate' => 1.5,
            'exchange_amount' => 185.18,
        ]);

        $this->assertEquals(99, $transaction->team_id);
        $this->assertEquals(88, $transaction->user_id);
        $this->assertEquals(77, $transaction->category_id);
        $this->assertEquals(66, $transaction->account_id);
        $this->assertEquals(55, $transaction->payee_id);
        $this->assertEquals(123.45, $transaction->total);
        $this->assertEquals('verified', $transaction->status);
        $this->assertEquals(1.5, $transaction->exchange_rate);
        $this->assertEquals(185.18, $transaction->exchange_amount);
    }
}
