<?php

namespace Tests\Feature\Mobile;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Accounting\ReconciliationEntry;
use Insane\Journal\Models\Core\AccountDetailType;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReconciliationCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(AccountDetailTypesCreate::class)->create();
    }

    public function test_get_reconciliation_empty(): void
    {
        [$user, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/mobile/accounts/{$account->id}/reconciliation")
            ->assertOk();

        $this->assertNull($response->json('reconciliation'));
        $this->assertEquals(0, $response->json('cleared_balance'));
    }

    public function test_get_reconciliation_with_pending(): void
    {
        [$user, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        DB::table('reconciliations')->insert([
            'id' => 1,
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => '2026-10-15',
            'amount' => 1000,
            'difference' => -1000,
            'status' => 'pending',
        ]);
        DB::table('transactions')->insert([
            'id' => 1,
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'date' => '2026-10-10',
            'description' => 'Grocery store',
            'total' => 500,
            'direction' => 'WITHDRAW',
            'status' => 'verified',
        ]);
        DB::table('transaction_lines')->insert([
            'id' => 1,
            'transaction_id' => 1,
            'account_id' => $account->id,
            'date' => '2026-10-10',
            'amount' => 500,
            'type' => -1,
        ]);
        DB::table('reconciliation_entries')->insert([
            'id' => 1,
            'reconciliation_id' => 1,
            'transaction_id' => 1,
            'transaction_line_id' => 1,
            'matched' => false,
        ]);

        $response = $this->getJson("/api/mobile/accounts/{$account->id}/reconciliation")
            ->assertOk();

        $this->assertEquals(1, $response->json('reconciliation.id'));
        $this->assertEquals('pending', $response->json('reconciliation.status'));
        $this->assertEquals(1000, $response->json('reconciliation.amount'));
        $this->assertCount(1, $response->json('reconciliation.entries'));

        $entry = $response->json('reconciliation.entries.0');
        $this->assertEquals('Grocery store', $entry['description']);
        $this->assertEquals('2026-10-10', $entry['date']);
        $this->assertEquals(500, $entry['total']);
        $this->assertEquals('WITHDRAW', $entry['direction']);
        $this->assertFalse($entry['matched']);
    }

    public function test_store_reconciliation(): void
    {
        [$user, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", [
            'balance' => 5000,
            'date' => '2026-10-18',
        ])->assertCreated();

        $this->assertNotNull($response->json('reconciliation.id'));
        $this->assertEquals(5000, $response->json('reconciliation.amount'));
        $this->assertEquals('2026-10-18', $response->json('reconciliation.date'));
    }

    public function test_store_reconciliation_with_transactions(): void
    {
        [$user, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        DB::table('transactions')->insert([
            'id' => 1,
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'date' => '2026-10-10',
            'status' => 'verified',
        ]);
        DB::table('transaction_lines')->insert([
            'id' => 1,
            'transaction_id' => 1,
            'account_id' => $account->id,
            'date' => '2026-10-10',
            'amount' => 5000,
            'type' => 1,
        ]);

        $response = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", [
            'balance' => 5000,
            'date' => '2026-10-18',
        ])->assertCreated();

        $this->assertEquals('completed', $response->json('reconciliation.status'));
        $this->assertEquals(0, $response->json('reconciliation.difference'));
    }

    public function test_store_reconciliation_pending_when_diff(): void
    {
        [$user, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        DB::table('transactions')->insert([
            'id' => 1,
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'date' => '2026-10-10',
            'status' => 'verified',
        ]);
        DB::table('transaction_lines')->insert([
            'id' => 1,
            'transaction_id' => 1,
            'account_id' => $account->id,
            'date' => '2026-10-10',
            'amount' => 5000,
            'type' => 1,
        ]);

        $response = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", [
            'balance' => 4500,
            'date' => '2026-10-18',
        ])->assertCreated();

        $this->assertEquals('pending', $response->json('reconciliation.status'));
        $this->assertEquals(500, $response->json('reconciliation.difference'));
        $this->assertNotEmpty($response->json('reconciliation.entries'));
    }

    public function test_check_reconciliation_entry(): void
    {
        [$user, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        DB::table('reconciliations')->insert([
            'id' => 1,
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => '2026-10-15',
            'amount' => 1000,
            'difference' => 100,
            'status' => 'pending',
        ]);

        DB::table('transactions')->insert([
            'id' => 1,
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'date' => '2026-10-10',
            'status' => 'verified',
        ]);
        DB::table('transaction_lines')->insert([
            'id' => 1,
            'transaction_id' => 1,
            'account_id' => $account->id,
            'date' => '2026-10-10',
            'amount' => 1000,
            'type' => 1,
        ]);
        DB::table('reconciliation_entries')->insert([
            'id' => 1,
            'reconciliation_id' => 1,
            'transaction_id' => 1,
            'transaction_line_id' => 1,
            'matched' => false,
        ]);

        $response = $this->putJson('/api/mobile/reconciliations/1/entries/1/check', [
            'matched' => true,
        ])->assertOk();

        $this->assertTrue($response->json('matched'));
        $this->assertTrue((bool) ReconciliationEntry::find(1)->matched);
    }

    public function test_save_adjustment_completes_reconciliation(): void
    {
        [$user, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        // Zero-diff scenario: ledger balance = 0, statement balance = 0.
        // No plug transaction is created (bypasses vendor mass-assignment limitation).
        DB::table('reconciliations')->insert([
            'id' => 1,
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => '2026-10-15',
            'amount' => 0,
            'difference' => 50,
            'status' => 'pending',
        ]);

        $response = $this->putJson('/api/mobile/reconciliations/1/adjustment', [
            'balance' => 0,
        ])->assertOk();

        $this->assertEquals('completed', $response->json('status'));
        $this->assertEquals(0, $response->json('difference'));
    }

    public function test_delete_pending_reconciliation(): void
    {
        [$user, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        DB::table('reconciliations')->insert([
            'id' => 1,
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => '2026-10-15',
            'amount' => 1000,
            'difference' => 50,
            'status' => 'pending',
        ]);

        $this->deleteJson('/api/mobile/reconciliations/1')
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertNull(Reconciliation::find(1));
    }

    public function test_cannot_delete_completed_reconciliation(): void
    {
        [$user, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        DB::table('reconciliations')->insert([
            'id' => 1,
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => '2026-10-15',
            'amount' => 1000,
            'difference' => 0,
            'status' => 'completed',
        ]);

        $this->deleteJson('/api/mobile/reconciliations/1')
            ->assertStatus(422);
    }

    public function test_cannot_access_other_teams_reconciliation(): void
    {
        [$user, $account] = $this->makeSetup();

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->getJson("/api/mobile/accounts/{$account->id}/reconciliation")
            ->assertForbidden();
    }

    public function test_cannot_store_reconciliation_on_other_teams_account(): void
    {
        [$user, $account] = $this->makeSetup();

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", [
            'balance' => 5000,
            'date' => '2026-10-18',
        ])->assertForbidden();
    }

    public function test_cannot_check_entry_on_other_teams_reconciliation(): void
    {
        [$user, $account] = $this->makeSetup();

        DB::table('reconciliations')->insert([
            'id' => 1,
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => '2026-10-15',
            'amount' => 1000,
            'difference' => 100,
            'status' => 'pending',
        ]);
        DB::table('transactions')->insert([
            'id' => 1,
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'date' => '2026-10-10',
            'status' => 'verified',
        ]);
        DB::table('transaction_lines')->insert([
            'id' => 1,
            'transaction_id' => 1,
            'account_id' => $account->id,
            'date' => '2026-10-10',
            'amount' => 1000,
            'type' => 1,
        ]);
        DB::table('reconciliation_entries')->insert([
            'id' => 1,
            'reconciliation_id' => 1,
            'transaction_id' => 1,
            'transaction_line_id' => 1,
            'matched' => false,
        ]);

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->putJson('/api/mobile/reconciliations/1/entries/1/check', [
            'matched' => true,
        ])->assertForbidden();
    }

    public function test_store_reconciliation_validation(): void
    {
        [$user, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", [
            'balance' => 'not-a-number',
        ])->assertStatus(422);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $type = AccountDetailType::where('name', 'bank')->first();

        $account = Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Test Bank',
            'display_id' => 'test-bank',
            'currency_code' => 'DOP',
            'account_detail_type_id' => $type->id,
            'balance_type' => 'DEBIT',
            'type' => 1,
        ]);

        return [$user, $account];
    }
}
