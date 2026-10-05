<?php

namespace Tests\Unit;

use App\Models\Account;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MultiCurrencyAccountTest extends TestCase
{
    #[Test]
    public function it_can_get_primary_currency()
    {
        $account = new Account(['currency_code' => 'USD']);

        $this->assertEquals('USD', $account->getPrimaryCurrency());
    }

    #[Test]
    public function it_returns_default_currency_when_none_set()
    {
        $account = new Account;

        $this->assertEquals('USD', $account->getPrimaryCurrency());
    }

    #[Test]
    public function it_can_get_secondary_currencies()
    {
        $account = new Account(['secondary_currencies' => ['EUR', 'GBP']]);

        $this->assertEquals(['EUR', 'GBP'], $account->getSecondaryCurrencies());
    }

    #[Test]
    public function it_returns_empty_array_when_no_secondary_currencies()
    {
        $account = new Account;

        $this->assertEquals([], $account->getSecondaryCurrencies());
    }

    #[Test]
    public function it_can_check_if_multi_currency()
    {
        $account = new Account(['is_multi_currency' => true]);

        $this->assertTrue($account->isMultiCurrency());
    }

    #[Test]
    public function it_returns_false_for_multi_currency_when_not_set()
    {
        $account = new Account;

        $this->assertFalse($account->isMultiCurrency());
    }

    #[Test]
    public function appends_includes_all_currency_balances()
    {
        $account = new Account;

        $this->assertContains('all_currency_balances', $account->getAppends());
    }

    /**
     * Regression: declaring `protected $appends = ['all_currency_balances']`
     * at the class level overrode the vendor's `$appends = ['balance']`,
     * blanking the balance shown in the account header. The constructor must
     * MERGE with the parent's appends, not replace them.
     */
    #[Test]
    public function appends_preserves_vendor_balance_entry()
    {
        $account = new Account;

        $this->assertContains('balance', $account->getAppends());
    }

    #[Test]
    public function all_currency_balances_accessor_returns_null_when_single_currency()
    {
        $account = new Account(['currency_code' => 'USD', 'is_multi_currency' => false]);

        $this->assertNull($account->getAllCurrencyBalancesAttribute());
    }

    #[Test]
    public function all_currency_balances_appears_in_serialized_array_when_single_currency()
    {
        $account = new Account(['currency_code' => 'USD', 'is_multi_currency' => false]);

        $arr = $account->toArray();

        $this->assertArrayHasKey('all_currency_balances', $arr);
        $this->assertNull($arr['all_currency_balances']);
    }

    /**
     * If the user enables multi-currency via the AccountUpdate raw update path
     * (i.e., without enableMultiCurrency() seeding rows in `currency_balances`),
     * the accessor should still surface a row per declared secondary currency
     * with zero balances. Otherwise the panel only shows the primary column.
     */
    #[Test]
    public function get_all_currency_balances_backfills_secondaries_with_zero_when_no_balance_row()
    {
        $account = new Account([
            'currency_code' => 'DOP',
            'is_multi_currency' => true,
            'secondary_currencies' => ['USD', 'EUR'],
        ]);
        $account->setRelation('currencyBalances', collect());

        $balances = $account->getAllCurrencyBalances();

        $this->assertArrayHasKey('DOP', $balances);
        $this->assertArrayHasKey('USD', $balances);
        $this->assertArrayHasKey('EUR', $balances);
        $this->assertSame(0.0, $balances['USD']['balance']);
        $this->assertSame(0.0, $balances['USD']['total_balance']);
        $this->assertFalse($balances['USD']['is_primary']);
        $this->assertTrue($balances['DOP']['is_primary']);
    }

    #[Test]
    public function it_can_get_all_supported_currencies()
    {
        $account = new Account([
            'currency_code' => 'USD',
            'secondary_currencies' => ['EUR', 'GBP'],
        ]);

        $expected = ['USD', 'EUR', 'GBP'];
        $this->assertEquals($expected, $account->getAllSupportedCurrencies());
    }

    #[Test]
    public function it_can_check_if_currency_is_supported()
    {
        $account = new Account([
            'currency_code' => 'USD',
            'secondary_currencies' => ['EUR', 'GBP'],
        ]);

        $this->assertTrue($account->supportsCurrency('USD'));
        $this->assertTrue($account->supportsCurrency('EUR'));
        $this->assertTrue($account->supportsCurrency('GBP'));
        $this->assertFalse($account->supportsCurrency('JPY'));
    }
}
