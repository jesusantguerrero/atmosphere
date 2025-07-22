<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\CurrencyBalance;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class MultiCurrencyTransactionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->withPersonalTeam()->create();
        Sanctum::actingAs($this->user);
        
        // Create a multi-currency credit card account
        $this->account = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'name' => 'Multi-Currency Credit Card',
            'currency_code' => 'USD',
            'is_multi_currency' => true,
            'secondary_currencies' => ['DOP', 'EUR'],
            'type' => 'credit_card',
        ]);
        
        // Create currency balance records
        CurrencyBalance::create([
            'account_id' => $this->account->id,
            'currency_code' => 'DOP',
            'balance' => 0,
            'pending_balance' => 0,
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_create_multi_currency_transaction()
    {
        $transactionData = [
            'account_id' => $this->account->id,
            'total' => 1500.00,
            'currency_code' => 'DOP',
            'description' => 'Restaurant bill in Dominican Republic',
            'date' => '2024-01-15',
            'direction' => 'debit',
        ];

        $response = $this->postJson('/api/multi-currency/transactions', $transactionData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Transaction created successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'description',
                        'total',
                        'currency_code',
                        'date',
                        'account' => [
                            'id',
                            'name',
                            'is_multi_currency',
                            'primary_currency',
                        ],
                        'multi_currency' => [
                            'is_converted',
                            'is_secondary_currency',
                            'display_currencies',
                        ]
                    ]
                ]);
    }

    public function test_can_process_credit_card_payment()
    {
        // First create a pending transaction in DOP
        $transaction = Transaction::create([
            'account_id' => $this->account->id,
            'total' => 1500.00,
            'currency_code' => 'DOP',
            'description' => 'Restaurant bill',
            'date' => '2024-01-15',
            'direction' => 'debit',
            'status' => 'verified',
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        // Update pending balance
        $currencyBalance = CurrencyBalance::where('account_id', $this->account->id)
            ->where('currency_code', 'DOP')
            ->first();
        $currencyBalance->update(['pending_balance' => 1500.00]);

        $paymentData = [
            'account_id' => $this->account->id,
            'total' => 1500.00, // DOP amount
            'exchange_amount' => 85.00, // USD equivalent
            'secondary_currency' => 'DOP',
            'payment_date' => '2024-01-20',
            'description' => 'Credit card payment',
        ];

        $response = $this->postJson('/api/multi-currency/payments', $paymentData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Payment processed successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'multi_currency' => [
                            'is_converted',
                            'exchange_rate',
                            'exchange_amount',
                        ]
                    ]
                ]);
    }

    public function test_can_get_currency_balances()
    {
        $response = $this->getJson("/api/accounts/{$this->account->id}/currency-balances");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'data' => [
                        'account_id',
                        'account_name',
                        'is_multi_currency',
                        'primary_currency',
                        'secondary_currencies',
                        'balances'
                    ]
                ]);
    }

    public function test_can_get_pending_balances()
    {
        $response = $this->getJson("/api/accounts/{$this->account->id}/pending-balances");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'data' => [
                        'account_id',
                        'account_name',
                        'pending_balances'
                    ]
                ]);
    }

    public function test_can_list_multi_currency_transactions()
    {
        // Create some test transactions
        Transaction::create([
            'account_id' => $this->account->id,
            'total' => 1500.00,
            'currency_code' => 'DOP',
            'description' => 'DOP Transaction',
            'date' => '2024-01-15',
            'direction' => 'debit',
            'status' => 'verified',
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        Transaction::create([
            'account_id' => $this->account->id,
            'total' => 100.00,
            'currency_code' => 'USD',
            'description' => 'USD Transaction',
            'date' => '2024-01-16',
            'direction' => 'debit',
            'status' => 'verified',
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/multi-currency/transactions');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'data' => [
                        'transactions' => [
                            '*' => [
                                'id',
                                'description',
                                'total',
                                'currency_code',
                                'multi_currency' => [
                                    'is_converted',
                                    'is_secondary_currency',
                                    'display_currencies',
                                ]
                            ]
                        ],
                        'pagination'
                    ]
                ]);
    }

    public function test_rejects_invalid_currency_for_account()
    {
        $transactionData = [
            'account_id' => $this->account->id,
            'total' => 1500.00,
            'currency_code' => 'JPY', // Not supported by this account
            'description' => 'Invalid currency transaction',
            'date' => '2024-01-15',
            'direction' => 'debit',
        ];

        $response = $this->postJson('/api/multi-currency/transactions', $transactionData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                ]);
    }

    public function test_rejects_payment_for_non_multi_currency_account()
    {
        // Create a regular account
        $regularAccount = Account::factory()->create([
            'team_id' => $this->user->current_team_id,
            'user_id' => $this->user->id,
            'name' => 'Regular Account',
            'currency_code' => 'USD',
            'is_multi_currency' => false,
        ]);

        $paymentData = [
            'account_id' => $regularAccount->id,
            'total' => 1500.00,
            'exchange_amount' => 85.00,
            'secondary_currency' => 'DOP',
            'payment_date' => '2024-01-20',
        ];

        $response = $this->postJson('/api/multi-currency/payments', $paymentData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Account does not support multi-currency transactions'
                ]);
    }
}