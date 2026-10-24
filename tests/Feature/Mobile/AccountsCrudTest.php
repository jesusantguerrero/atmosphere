<?php

namespace Tests\Feature\Mobile;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insane\Journal\Models\Core\AccountDetailType;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed account detail types (bank, cash, credit_card, etc.)
        app(AccountDetailTypesCreate::class)->create();
    }

    public function test_create_bank_account(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/accounts', [
            'name' => 'BHD Checking',
            'detail_type' => 'bank',
            'currency_code' => 'DOP',
        ])->assertCreated()->assertJson(['success' => true]);

        $this->assertEquals('BHD Checking', $response->json('account.name'));
        $this->assertEquals('bank', $response->json('account.detail_type'));
        $this->assertEquals('DOP', $response->json('account.currency_code'));
    }

    public function test_create_credit_card_account(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/accounts', [
            'name' => 'Visa Gold',
            'detail_type' => 'credit_card',
            'currency_code' => 'DOP',
            'credit_limit' => 290000,
            'credit_closing_day' => 15,
        ])->assertCreated();

        $this->assertEquals(290000.0, $response->json('account.credit_limit'));
        $this->assertEquals(15, $response->json('account.credit_closing_day'));
    }

    public function test_create_rejects_invalid_detail_type(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/accounts', [
            'name' => 'Bad Account',
            'detail_type' => 'nonexistent',
            'currency_code' => 'DOP',
        ])->assertStatus(422);
    }

    public function test_show_account(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $account = $this->createTestAccount($user, $team);

        $response = $this->getJson("/api/mobile/accounts/{$account->id}")
            ->assertOk();

        $this->assertEquals($account->id, $response->json('id'));
        $this->assertEquals('Test Bank', $response->json('name'));
        $this->assertArrayHasKey('current_balance', $response->json());
        $this->assertArrayHasKey('detail_type', $response->json());
    }

    public function test_update_account_name(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $account = $this->createTestAccount($user, $team);

        $this->patchJson("/api/mobile/accounts/{$account->id}", [
            'name' => 'Renamed Account',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertEquals('Renamed Account', $account->fresh()->name);
    }

    public function test_update_credit_limit(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $account = $this->createTestAccount($user, $team, 'credit_card');

        $this->patchJson("/api/mobile/accounts/{$account->id}", [
            'credit_limit' => 500000,
        ])->assertOk();

        $this->assertEquals(500000, (float) $account->fresh()->credit_limit);
    }

    public function test_close_account(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $account = $this->createTestAccount($user, $team);

        $this->putJson("/api/mobile/accounts/{$account->id}/close", [
            'closed' => true,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertNotNull($account->fresh()->closed_at);
    }

    public function test_reopen_account(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $account = $this->createTestAccount($user, $team);
        $account->forceFill(['closed_at' => now()])->save();

        $this->putJson("/api/mobile/accounts/{$account->id}/close", [
            'closed' => false,
        ])->assertOk();

        $this->assertNull($account->fresh()->closed_at);
    }

    public function test_cannot_view_other_teams_account(): void
    {
        [$user, $team] = $this->makeSetup();
        $account = $this->createTestAccount($user, $team);

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->getJson("/api/mobile/accounts/{$account->id}")
            ->assertForbidden();
    }

    public function test_cannot_update_other_teams_account(): void
    {
        [$user, $team] = $this->makeSetup();
        $account = $this->createTestAccount($user, $team);

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->patchJson("/api/mobile/accounts/{$account->id}", [
            'name' => 'Hacked',
        ])->assertForbidden();
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        return [$user, $team];
    }

    public function test_overview_excludes_closed_accounts_by_default(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $open = $this->createTestAccount($user, $team, 'bank');
        $closed = $this->createTestAccount($user, $team, 'savings');
        $closed->forceFill(['name' => 'Closed Savings', 'closed_at' => now()])->save();

        $response = $this->getJson('/api/mobile/overview')->assertOk();
        $accountIds = collect($response->json('accounts'))->pluck('id')->all();

        $this->assertContains($open->id, $accountIds);
        $this->assertNotContains($closed->id, $accountIds);
    }

    public function test_overview_includes_closed_accounts_when_requested(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $open = $this->createTestAccount($user, $team, 'bank');
        $closed = $this->createTestAccount($user, $team, 'savings');
        $closed->forceFill(['name' => 'Closed Savings', 'closed_at' => now()])->save();

        $response = $this->getJson('/api/mobile/overview?include_closed=1')->assertOk();
        $accounts = collect($response->json('accounts'));

        $this->assertTrue($accounts->contains('id', $open->id));
        $this->assertTrue($accounts->contains('id', $closed->id));

        $closedAccount = $accounts->firstWhere('id', $closed->id);
        $this->assertNotNull($closedAccount['closed_at']);
    }

    public function test_overview_includes_index_field(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $account = $this->createTestAccount($user, $team, 'bank');
        $account->forceFill(['index' => 5])->save();

        $response = $this->getJson('/api/mobile/overview')->assertOk();
        $found = collect($response->json('accounts'))->firstWhere('id', $account->id);

        $this->assertEquals(5, $found['index']);
    }

    public function test_reorder_accounts_via_token(): void
    {
        [$user, $team] = $this->makeSetup();
        Sanctum::actingAs($user);

        $a = $this->createTestAccount($user, $team, 'bank');
        $b = $this->createTestAccount($user, $team, 'savings');
        $b->forceFill(['name' => 'Savings'])->save();

        $this->patchJson('/api/mobile/accounts/reorder', [
            'account_ids' => [$b->id, $a->id],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertEquals(0, $b->fresh()->index);
        $this->assertEquals(1, $a->fresh()->index);
    }

    public function test_reorder_rejects_other_teams_accounts(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $type = AccountDetailType::where('name', 'bank')->first();
        $alien = Account::create([
            'team_id' => $otherTeam->id,
            'user_id' => $other->id,
            'name' => 'Foreign',
            'display_id' => 'foreign',
            'currency_code' => 'USD',
            'account_detail_type_id' => $type->id,
            'balance_type' => 'DEBIT',
            'type' => 1,
        ]);

        $this->patchJson('/api/mobile/accounts/reorder', [
            'account_ids' => [$alien->id],
        ])->assertStatus(422);
    }

    public function test_store_task_via_fab_endpoint(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/tasks', [
            'name' => 'Buy groceries',
            'date' => '2026-10-25',
        ])->assertCreated()->assertJson(['success' => true]);

        $this->assertEquals('task', $response->json('data.kind'));
        $this->assertEquals('Buy groceries', $response->json('data.name'));
    }

    private function createTestAccount(User $user, $team, string $detailTypeName = 'bank'): Account
    {
        $type = AccountDetailType::where('name', $detailTypeName)->first();

        return Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Test Bank',
            'display_id' => 'test-bank',
            'currency_code' => 'DOP',
            'account_detail_type_id' => $type->id,
            'balance_type' => $detailTypeName === 'credit_card' ? 'CREDIT' : 'DEBIT',
            'type' => $detailTypeName === 'credit_card' ? -1 : 1,
            'credit_limit' => $detailTypeName === 'credit_card' ? 200000 : 0,
        ]);
    }
}
