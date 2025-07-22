<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountMultiCurrencyFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_secondary_currencies_field_is_fillable_and_saved()
    {
        $user = User::factory()->withPersonalTeam()->create();
        
        // Test creating account with secondary_currencies
        $accountData = [
            'name' => 'Test Multi-Currency Account',
            'currency_code' => 'USD',
            'is_multi_currency' => true,
            'secondary_currencies' => ['DOP', 'EUR', 'CAD'],
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'type' => 'credit_card',
        ];

        $account = Account::create($accountData);

        // Verify the account was created
        $this->assertNotNull($account->id);
        
        // Verify multi-currency fields are saved
        $this->assertTrue($account->is_multi_currency);
        $this->assertEquals(['DOP', 'EUR', 'CAD'], $account->secondary_currencies);
        
        // Test the helper methods
        $this->assertEquals('USD', $account->getPrimaryCurrency());
        $this->assertEquals(['DOP', 'EUR', 'CAD'], $account->getSecondaryCurrencies());
        $this->assertTrue($account->isMultiCurrency());
        $this->assertEquals(['USD', 'DOP', 'EUR', 'CAD'], $account->getAllSupportedCurrencies());
        
        // Test currency support
        $this->assertTrue($account->supportsCurrency('USD')); // Primary
        $this->assertTrue($account->supportsCurrency('DOP')); // Secondary
        $this->assertTrue($account->supportsCurrency('EUR')); // Secondary
        $this->assertTrue($account->supportsCurrency('CAD')); // Secondary
        $this->assertFalse($account->supportsCurrency('JPY')); // Not supported
    }

    public function test_empty_secondary_currencies_allows_any_currency()
    {
        $user = User::factory()->withPersonalTeam()->create();
        
        // Create account with multi-currency enabled but no specific currencies
        $account = Account::create([
            'name' => 'Dynamic Multi-Currency Account',
            'currency_code' => 'USD',
            'is_multi_currency' => true,
            'secondary_currencies' => [], // Empty array
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'type' => 'credit_card',
        ]);

        // Should support primary currency
        $this->assertTrue($account->supportsCurrency('USD'));
        
        // Should support any other currency (dynamic support)
        $this->assertTrue($account->supportsCurrency('JPY'));
        $this->assertTrue($account->supportsCurrency('GBP'));
        $this->assertTrue($account->supportsCurrency('CHF'));
        
        // Test adding currency dynamically
        $account->addSecondaryCurrency('JPY');
        $account->refresh();
        
        $this->assertEquals(['JPY'], $account->getSecondaryCurrencies());
        $this->assertTrue($account->supportsCurrency('JPY'));
    }

    public function test_null_secondary_currencies_allows_any_currency()
    {
        $user = User::factory()->withPersonalTeam()->create();
        
        // Create account with multi-currency enabled but null secondary_currencies
        $account = Account::create([
            'name' => 'Null Secondary Currencies Account',
            'currency_code' => 'USD',
            'is_multi_currency' => true,
            'secondary_currencies' => null, // Null value
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'type' => 'credit_card',
        ]);

        // getSecondaryCurrencies should return empty array for null
        $this->assertEquals([], $account->getSecondaryCurrencies());
        
        // Should support primary currency
        $this->assertTrue($account->supportsCurrency('USD'));
        
        // Should support any other currency (dynamic support)
        $this->assertTrue($account->supportsCurrency('EUR'));
        $this->assertTrue($account->supportsCurrency('DOP'));
    }

    public function test_non_multi_currency_account_only_supports_primary()
    {
        $user = User::factory()->withPersonalTeam()->create();
        
        // Create regular (non-multi-currency) account
        $account = Account::create([
            'name' => 'Regular Account',
            'currency_code' => 'USD',
            'is_multi_currency' => false,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'type' => 'checking',
        ]);

        // Should support primary currency
        $this->assertTrue($account->supportsCurrency('USD'));
        
        // Should NOT support other currencies
        $this->assertFalse($account->supportsCurrency('EUR'));
        $this->assertFalse($account->supportsCurrency('DOP'));
        $this->assertFalse($account->supportsCurrency('JPY'));
    }

    public function test_enable_multi_currency_method()
    {
        $user = User::factory()->withPersonalTeam()->create();
        
        // Create regular account
        $account = Account::create([
            'name' => 'Regular Account',
            'currency_code' => 'USD',
            'is_multi_currency' => false,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'type' => 'credit_card',
        ]);

        $this->assertFalse($account->isMultiCurrency());
        
        // Enable multi-currency with specific currencies
        $account->enableMultiCurrency(['DOP', 'EUR']);
        $account->refresh();
        
        $this->assertTrue($account->isMultiCurrency());
        $this->assertEquals(['DOP', 'EUR'], $account->getSecondaryCurrencies());
        $this->assertTrue($account->supportsCurrency('DOP'));
        $this->assertTrue($account->supportsCurrency('EUR'));
    }
}