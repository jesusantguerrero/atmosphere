<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insane\Journal\Models\Core\Payee;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetCategoryTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_transactions_for_category_and_month(): void
    {
        [$user, $team, $category, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        $month = now()->format('Y-m');

        // Create transactions in the current month
        Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'date' => now()->format('Y-m-d'),
            'description' => 'Grocery shopping',
            'total' => 1500.50,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);

        Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'date' => now()->format('Y-m-d'),
            'description' => 'Another purchase',
            'total' => 800,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);

        $response = $this->getJson("/api/mobile/budget/categories/{$category->id}/transactions?month={$month}")
            ->assertOk()
            ->assertJsonStructure(['data', 'last_page']);

        $this->assertCount(2, $response->json('data'));
        $this->assertEquals('Grocery shopping', $response->json('data.1.description'));
    }

    public function test_filters_by_month(): void
    {
        [$user, $team, $category, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        // Transaction this month
        Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'date' => now()->format('Y-m-d'),
            'description' => 'This month',
            'total' => 100,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);

        // Transaction last month
        Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'date' => now()->subMonthNoOverflow()->format('Y-m-d'),
            'description' => 'Last month',
            'total' => 200,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);

        $month = now()->format('Y-m');
        $response = $this->getJson("/api/mobile/budget/categories/{$category->id}/transactions?month={$month}")
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('This month', $response->json('data.0.description'));
    }

    public function test_split_purchases_show_up_with_the_amount_of_the_line_in_that_category(): void
    {
        [$user, $team, $groceries, $account] = $this->makeSetup();
        $cleaning = Category::create(['name' => 'Cleaning', 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => $groceries->parent_id, 'depth' => 1, 'index' => 1]);
        Sanctum::actingAs($user);
        $month = now()->format('Y-m');
        $payee = Payee::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Sirena']);
        $base = ['payee_id' => $payee->id, 'account_id' => $account->id, 'currency_code' => 'DOP', 'date' => now()->format('Y-m-d'), 'direction' => 'WITHDRAW'];

        $this->postJson('/api/mobile/transactions', [
            ...$base,
            'total' => 1000,
            'description' => 'Supermarket',
            'items' => [
                ['category_id' => $groceries->id, 'amount' => 700],
                ['category_id' => $cleaning->id, 'amount' => 300],
            ],
        ])->assertStatus(201);
        $this->postJson('/api/mobile/transactions', [...$base, 'total' => 50, 'description' => 'Bread', 'category_id' => $groceries->id])->assertStatus(201);

        $groceriesRows = collect($this->getJson("/api/mobile/budget/categories/{$groceries->id}/transactions?month={$month}")->assertOk()->json('data'))->keyBy('description');
        $this->assertCount(2, $groceriesRows);
        $this->assertTrue($groceriesRows['Supermarket']['has_splits']);
        $this->assertEquals(700, $groceriesRows['Supermarket']['split_amount']);
        $this->assertEquals(1000, $groceriesRows['Supermarket']['total']);
        $this->assertFalse($groceriesRows['Bread']['has_splits']);
        $this->assertNull($groceriesRows['Bread']['split_amount']);

        $cleaningRows = $this->getJson("/api/mobile/budget/categories/{$cleaning->id}/transactions?month={$month}")->assertOk()->json('data');
        $this->assertCount(1, $cleaningRows);
        $this->assertEquals(300, $cleaningRows[0]['split_amount']);
    }

    public function test_requires_month_parameter(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->getJson("/api/mobile/budget/categories/{$category->id}/transactions")
            ->assertStatus(422);
    }

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $group = Category::create([
            'name' => 'Test Group',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 99,
        ]);

        $category = Category::create([
            'name' => 'Groceries',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $account = Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Test Account',
            'currency_code' => 'DOP',
            'balance_type' => 'DEBIT',
            'type' => 1,
        ]);

        return [$user, $team, $category, $account];
    }
}
