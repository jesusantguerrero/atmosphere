<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Account;

class MultiCurrencyAccountTest extends TestCase
{
    /** @test */
    public function it_can_get_primary_currency()
    {
        $account = new Account(['currency_code' => 'USD']);
        
        $this->assertEquals('USD', $account->getPrimaryCurrency());
    }

    /** @test */
    public function it_returns_default_currency_when_none_set()
    {
        $account = new Account();
        
        $this->assertEquals('USD', $account->getPrimaryCurrency());
    }

    /** @test */
    public function it_can_get_secondary_currencies()
    {
        $account = new Account(['secondary_currencies' => ['EUR', 'GBP']]);
        
        $this->assertEquals(['EUR', 'GBP'], $account->getSecondaryCurrencies());
    }

    /** @test */
    public function it_returns_empty_array_when_no_secondary_currencies()
    {
        $account = new Account();
        
        $this->assertEquals([], $account->getSecondaryCurrencies());
    }

    /** @test */
    public function it_can_check_if_multi_currency()
    {
        $account = new Account(['is_multi_currency' => true]);
        
        $this->assertTrue($account->isMultiCurrency());
    }

    /** @test */
    public function it_returns_false_for_multi_currency_when_not_set()
    {
        $account = new Account();
        
        $this->assertFalse($account->isMultiCurrency());
    }

    /** @test */
    public function it_can_get_all_supported_currencies()
    {
        $account = new Account([
            'currency_code' => 'USD',
            'secondary_currencies' => ['EUR', 'GBP']
        ]);
        
        $expected = ['USD', 'EUR', 'GBP'];
        $this->assertEquals($expected, $account->getAllSupportedCurrencies());
    }

    /** @test */
    public function it_can_check_if_currency_is_supported()
    {
        $account = new Account([
            'currency_code' => 'USD',
            'secondary_currencies' => ['EUR', 'GBP']
        ]);
        
        $this->assertTrue($account->supportsCurrency('USD'));
        $this->assertTrue($account->supportsCurrency('EUR'));
        $this->assertTrue($account->supportsCurrency('GBP'));
        $this->assertFalse($account->supportsCurrency('JPY'));
    }
}