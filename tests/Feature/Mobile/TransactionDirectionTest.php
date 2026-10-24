<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionDirectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_withdraw_transaction(): void
    {
        [$user, $team, $account, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/transactions', [
            'account_id' => $account->id,
            'total' => 1500,
            'currency_code' => 'DOP',
            'description' => 'Groceries at La Sirena',
            'date' => '2026-10-24',
            'direction' => 'WITHDRAW',
            'category_id' => $category->id,
        ])->assertCreated()->assertJson(['success' => true]);

        $tx = Transaction::find($response->json('data.id'));
        $this->assertEquals('WITHDRAW', $tx->direction);
        $this->assertEquals(1500.0, (float) $tx->total);
        $this->assertTrue($tx->lines()->exists(), 'WITHDRAW must create journal lines');
    }

    public function test_create_deposit_transaction(): void
    {
        [$user, $team, $account, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/transactions', [
            'account_id' => $account->id,
            'total' => 50000,
            'currency_code' => 'DOP',
            'description' => 'Salary payment',
            'date' => '2026-10-15',
            'direction' => 'DEPOSIT',
            'category_id' => $category->id,
        ])->assertCreated()->assertJson(['success' => true]);

        $tx = Transaction::find($response->json('data.id'));
        $this->assertEquals('DEPOSIT', $tx->direction);
        $this->assertEquals(50000.0, (float) $tx->total);
        $this->assertTrue($tx->lines()->exists(), 'DEPOSIT must create journal lines');
    }

    public function test_legacy_credit_direction_still_accepted(): void
    {
        [$user, $team, $account, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/transactions', [
            'account_id' => $account->id,
            'total' => 2000,
            'currency_code' => 'DOP',
            'description' => 'Legacy credit',
            'date' => '2026-10-24',
            'direction' => 'credit',
            'category_id' => $category->id,
        ])->assertCreated();
    }

    public function test_legacy_debit_direction_still_accepted(): void
    {
        [$user, $team, $account, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/transactions', [
            'account_id' => $account->id,
            'total' => 3000,
            'currency_code' => 'DOP',
            'description' => 'Legacy debit',
            'date' => '2026-10-24',
            'direction' => 'debit',
            'category_id' => $category->id,
        ])->assertCreated();
    }

    public function test_invalid_direction_is_rejected(): void
    {
        [$user, $team, $account, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/transactions', [
            'account_id' => $account->id,
            'total' => 1000,
            'currency_code' => 'DOP',
            'description' => 'Bad direction',
            'date' => '2026-10-24',
            'direction' => 'TRANSFER',
            'category_id' => $category->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['direction']);
    }

    public function test_transaction_without_category_creates_uncategorized(): void
    {
        [$user, $team, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/transactions', [
            'account_id' => $account->id,
            'total' => 750,
            'currency_code' => 'DOP',
            'description' => 'Quick expense',
            'date' => '2026-10-24',
            'direction' => 'WITHDRAW',
        ])->assertCreated();

        $tx = Transaction::find($response->json('data.id'));
        $this->assertNull($tx->category_id);
    }

    public function test_transaction_rejects_other_teams_account(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $alien = Account::create([
            'team_id' => $otherTeam->id,
            'user_id' => $other->id,
            'name' => 'Foreign',
            'currency_code' => 'DOP',
            'balance_type' => 'DEBIT',
            'type' => 1,
        ]);

        $this->postJson('/api/mobile/transactions', [
            'account_id' => $alien->id,
            'total' => 500,
            'currency_code' => 'DOP',
            'description' => 'Should fail',
            'date' => '2026-10-24',
            'direction' => 'WITHDRAW',
        ])->assertStatus(422);
    }

    public function test_draft_transaction_is_created_correctly(): void
    {
        [$user, $team, $account, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/transactions', [
            'account_id' => $account->id,
            'total' => 900,
            'currency_code' => 'DOP',
            'description' => 'Pending review',
            'date' => '2026-10-24',
            'direction' => 'WITHDRAW',
            'category_id' => $category->id,
            'status' => 'draft',
        ])->assertCreated();

        $tx = Transaction::find($response->json('data.id'));
        $this->assertEquals('draft', $tx->status);
    }

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $account = Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'BHD Checking',
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

        return [$user, $team, $account, $category];
    }
}
