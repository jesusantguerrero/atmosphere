<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Domains\Transaction\Services\MultiCurrencyTransactionService;
use InvalidArgumentException;

class MultiCurrencyTransactionServiceTest extends TestCase
{
    protected MultiCurrencyTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MultiCurrencyTransactionService();
    }



    /** @test */
    public function it_can_calculate_exchange_rate()
    {
        $rate = $this->service->calculateExchangeRate(1000.00, 18.50);
        $this->assertEquals(0.0185, $rate);

        $rate = $this->service->calculateExchangeRate(500.00, 27.75);
        $this->assertEquals(0.0555, $rate);
    }

    /** @test */
    public function it_throws_exception_for_zero_total_in_exchange_rate_calculation()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total amount cannot be zero');

        $this->service->calculateExchangeRate(0, 100);
    }

    /** @test */
    public function it_validates_exchange_rate_calculation_with_different_values()
    {
        // Test various exchange rate calculations
        $rate1 = $this->service->calculateExchangeRate(100.00, 5.50);
        $this->assertEquals(0.055, $rate1);

        $rate2 = $this->service->calculateExchangeRate(2500.00, 45.25);
        $this->assertEquals(0.0181, $rate2);

        $rate3 = $this->service->calculateExchangeRate(50.00, 2750.00);
        $this->assertEquals(55.0, $rate3);
    }
}