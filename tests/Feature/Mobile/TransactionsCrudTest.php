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

class TransactionsCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_transaction(): void
    {
        [$user, $team, $account, $category, $payee] = $this->makeSetup();
        Sanctum::actingAs($user);

        $transaction = Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payee_id' => $payee->id,
            'date' => '2026-10-15',
            'description' => 'Almuerzo',
            'total' => 850.00,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);

        $response = $this->getJson("/api/mobile/transactions/{$transaction->id}")
            ->assertOk()
            ->assertJsonFragment(['description' => 'Almuerzo']);

        $this->assertEquals(850.0, $response->json('total'));
        $this->assertNotNull($response->json('account'));
        $this->assertEquals($account->id, $response->json('account.id'));
        $this->assertEquals($category->id, $response->json('category.id'));
        $this->assertEquals($payee->id, $response->json('payee.id'));
    }

    public function test_update_transaction_description(): void
    {
        [$user, $team, $account, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $transaction = Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'date' => '2026-10-15',
            'description' => 'Old description',
            'total' => 500,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);

        $this->patchJson("/api/mobile/transactions/{$transaction->id}", [
            'description' => 'New description',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertEquals('New description', $transaction->fresh()->description);
    }

    public function test_update_transaction_category(): void
    {
        [$user, $team, $account, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $newCategory = Category::create([
            'name' => 'Transport',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $category->parent_id,
            'depth' => 1,
            'index' => 1,
        ]);

        $transaction = Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'date' => '2026-10-15',
            'description' => 'Uber ride',
            'total' => 300,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);

        $response = $this->patchJson("/api/mobile/transactions/{$transaction->id}", [
            'category_id' => $newCategory->id,
        ])->assertOk();

        $this->assertEquals($newCategory->id, $response->json('transaction.category.id'));
    }

    public function test_update_rejects_transfer(): void
    {
        [$user, $team, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        $transaction = Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => '2026-10-15',
            'description' => 'Transfer',
            'total' => 1000,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'verified',
            'is_transfer' => true,
        ]);

        $this->patchJson("/api/mobile/transactions/{$transaction->id}", [
            'description' => 'Hacked',
        ])->assertStatus(422);
    }

    public function test_delete_any_transaction(): void
    {
        [$user, $team, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        $transaction = Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => '2026-10-15',
            'description' => 'To delete',
            'total' => 200,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);

        $this->deleteJson("/api/mobile/transactions/{$transaction->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(Transaction::find($transaction->id));
    }

    public function test_cannot_view_other_teams_transaction(): void
    {
        [$user, $team, $account] = $this->makeSetup();

        $transaction = Transaction::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => '2026-10-15',
            'description' => 'Secret',
            'total' => 100,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->getJson("/api/mobile/transactions/{$transaction->id}")
            ->assertForbidden();
    }

    public function test_payees_list(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        Payee::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Supermercado Nacional']);
        Payee::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Bravo']);
        Payee::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Super Pola']);

        $response = $this->getJson('/api/mobile/payees')
            ->assertOk();

        // 3 created here + 1 from makeSetup ("Test Payee")
        $this->assertCount(4, $response->json());
    }

    public function test_payees_search(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        Payee::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Supermercado Nacional']);
        Payee::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Bravo']);
        Payee::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Super Pola']);

        $response = $this->getJson('/api/mobile/payees?search=Super')
            ->assertOk();

        $this->assertCount(2, $response->json());
    }

    public function test_create_payee(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/payees', ['name' => 'La Sirena'])
            ->assertCreated();

        $this->assertEquals('La Sirena', $response->json('name'));
        $this->assertNotNull($response->json('id'));
    }

    public function test_create_payee_returns_existing(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $existing = Payee::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'La Sirena']);

        $response = $this->postJson('/api/mobile/payees', ['name' => 'La Sirena'])
            ->assertCreated();

        $this->assertEquals($existing->id, $response->json('id'));
    }

    public function test_categories_flat_list(): void
    {
        [$user, $team, , $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/categories')
            ->assertOk();

        $names = collect($response->json())->pluck('name')->toArray();
        $this->assertContains('Groceries', $names);
        $this->assertNotNull($response->json('0.group'));
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $account = Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Test Account',
            'currency_code' => 'DOP',
            'balance_type' => 'DEBIT',
            'type' => 1,
        ]);

        $group = Category::create([
            'name' => 'Expenses',
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

        $payee = Payee::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Test Payee',
        ]);

        return [$user, $team, $account, $category, $payee];
    }
}
