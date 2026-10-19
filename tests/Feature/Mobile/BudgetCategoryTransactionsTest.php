<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
