<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\CurrencyBalance;

class CurrencyBalanceTest extends TestCase
{

    /** @test */
    public function it_can_calculate_total_balance()
    {
        $currencyBalance = new CurrencyBalance([
            'balance' => 100.50,
            'pending_balance' => 25.25
        ]);
        
        $this->assertEquals(125.75, $currencyBalance->total_balance);
    }

    /** @test */
    public function it_can_update_balance()
    {
        $currencyBalance = new CurrencyBalance([
            'balance' => 100.00,
            'pending_balance' => 0.00
        ]);
        
        $currencyBalance->updateBalance(50.00);
        
        $this->assertEquals(150.00, $currencyBalance->balance);
        $this->assertEquals(0.00, $currencyBalance->pending_balance);
    }

    /** @test */
    public function it_can_update_pending_balance()
    {
        $currencyBalance = new CurrencyBalance([
            'balance' => 100.00,
            'pending_balance' => 0.00
        ]);
        
        $currencyBalance->updateBalance(25.00, true);
        
        $this->assertEquals(100.00, $currencyBalance->balance);
        $this->assertEquals(25.00, $currencyBalance->pending_balance);
    }

    /** @test */
    public function it_can_set_balance()
    {
        $currencyBalance = new CurrencyBalance([
            'balance' => 100.00,
            'pending_balance' => 50.00
        ]);
        
        $currencyBalance->setBalance(200.00);
        
        $this->assertEquals(200.00, $currencyBalance->balance);
        $this->assertEquals(50.00, $currencyBalance->pending_balance);
    }

    /** @test */
    public function it_can_set_pending_balance()
    {
        $currencyBalance = new CurrencyBalance([
            'balance' => 100.00,
            'pending_balance' => 50.00
        ]);
        
        $currencyBalance->setBalance(75.00, true);
        
        $this->assertEquals(100.00, $currencyBalance->balance);
        $this->assertEquals(75.00, $currencyBalance->pending_balance);
    }

    /** @test */
    public function it_can_transfer_pending_to_balance()
    {
        $currencyBalance = new CurrencyBalance([
            'balance' => 100.00,
            'pending_balance' => 50.00
        ]);
        
        $transferred = $currencyBalance->transferPendingToBalance(30.00);
        
        $this->assertEquals(30.00, $transferred);
        $this->assertEquals(130.00, $currencyBalance->balance);
        $this->assertEquals(20.00, $currencyBalance->pending_balance);
    }

    /** @test */
    public function it_can_transfer_all_pending_to_balance()
    {
        $currencyBalance = new CurrencyBalance([
            'balance' => 100.00,
            'pending_balance' => 50.00
        ]);
        
        $transferred = $currencyBalance->transferPendingToBalance();
        
        $this->assertEquals(50.00, $transferred);
        $this->assertEquals(150.00, $currencyBalance->balance);
        $this->assertEquals(0.00, $currencyBalance->pending_balance);
    }

    /** @test */
    public function it_throws_exception_when_transfer_amount_exceeds_pending()
    {
        $currencyBalance = new CurrencyBalance([
            'balance' => 100.00,
            'pending_balance' => 50.00
        ]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transfer amount cannot exceed pending balance');
        
        $currencyBalance->transferPendingToBalance(75.00);
    }
}