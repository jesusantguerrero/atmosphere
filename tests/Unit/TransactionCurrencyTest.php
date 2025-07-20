<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Domains\Transaction\Models\Transaction;

class TransactionCurrencyTest extends TestCase
{
    /** @test */
    public function it_can_calculate_exchange_rate()
    {
        $transaction = new Transaction();
        
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
        $transaction = new Transaction();
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total amount cannot be zero for exchange rate calculation');
        
        $transaction->calculateExchangeRate(0.0, 85.0);
    }

    /** @test */
    public function it_has_currency_conversion_methods()
    {
        $transaction = new Transaction();
        
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
        $transaction = new Transaction();
        $fillable = $transaction->getFillable();
        
        $this->assertContains('exchange_rate', $fillable);
        $this->assertContains('exchange_amount', $fillable);
    }
}