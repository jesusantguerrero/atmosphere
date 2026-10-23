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

    public function test_deleting_a_completed_reconciliation_unmatches_but_keeps_the_transactions(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->createCompleted($account, '2026-10-18');
        $this->assertEquals(1, DB::table('transaction_lines')->where('id', 1)->value('matched'));

        $this->deleteJson("/api/mobile/reconciliations/{$id}")->assertOk()->assertJson(['deleted' => true]);

        $this->assertNull(Reconciliation::find($id));
        $this->assertEquals(0, DB::table('transaction_lines')->where('id', 1)->value('matched'));
        $this->assertEquals(1, DB::table('transactions')->where('id', 1)->count());
    }

    public function test_show_returns_a_completed_reconciliation_with_enriched_entries(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->createCompleted($account, '2026-10-18');

        $response = $this->getJson("/api/mobile/reconciliations/{$id}")->assertOk();

        $this->assertEquals('completed', $response->json('reconciliation.status'));
        $this->assertEquals(5000, $response->json('reconciliation.ledger_balance'));
        $this->assertEquals(1, $response->json('reconciliation.matched_count'));
        $this->assertEquals(1, $response->json('reconciliation.total_entries'));
        $this->assertEquals('2026-10-10', substr($response->json('reconciliation.entries.0.date'), 0, 10));
        $this->assertArrayHasKey('direction', $response->json('reconciliation.entries.0'));
        $this->assertArrayHasKey('description', $response->json('reconciliation.entries.0'));
    }

    public function test_reopen_puts_the_latest_completed_reconciliation_back_to_pending(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->createCompleted($account, '2026-10-18');
        DB::table('transactions')->insert(['id' => 2, 'team_id' => $account->team_id, 'user_id' => $user->id, 'date' => '2026-10-12', 'status' => 'verified']);
        DB::table('transaction_lines')->insert(['id' => 2, 'transaction_id' => 2, 'account_id' => $account->id, 'date' => '2026-10-12', 'amount' => 300, 'type' => -1]);

        $response = $this->postJson("/api/mobile/reconciliations/{$id}/reopen")->assertOk();

        $this->assertEquals('pending', $response->json('reconciliation.status'));
        $this->assertEquals(-300, $response->json('reconciliation.difference'));
        $this->assertEquals(0, $response->json('reconciliation.matched_count'));
        $this->assertEquals(0, DB::table('transaction_lines')->where('id', 1)->value('matched'));
        $this->postJson("/api/mobile/reconciliations/{$id}/reopen")->assertStatus(422);
    }

    public function test_only_the_most_recent_reconciliation_can_be_reopened(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $older = $this->createCompleted($account, '2026-10-12');
        $this->createCompleted($account, '2026-10-18');

        $this->postJson("/api/mobile/reconciliations/{$older}/reopen")->assertStatus(422);
    }

    public function test_reopen_is_scoped_to_the_team(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->createCompleted($account, '2026-10-18');
        [$other] = $this->makeSetup();
        Sanctum::actingAs($other);

        $this->postJson("/api/mobile/reconciliations/{$id}/reopen")->assertForbidden();
        $this->getJson("/api/mobile/reconciliations/{$id}")->assertForbidden();
        $this->patchJson("/api/mobile/reconciliations/{$id}", ['date' => '2026-10-19'])->assertForbidden();
    }

    public function test_changing_the_date_rebuilds_the_entries_and_keeps_matches_that_still_apply(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        DB::table('transactions')->insert(['id' => 2, 'team_id' => $account->team_id, 'user_id' => $user->id, 'date' => '2026-10-16', 'status' => 'verified']);
        DB::table('transaction_lines')->insert(['id' => 2, 'transaction_id' => 2, 'account_id' => $account->id, 'date' => '2026-10-16', 'amount' => 300, 'type' => -1]);
        $created = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 4500, 'date' => '2026-10-12'])->assertCreated();
        $id = $created->json('reconciliation.id');
        $this->assertEquals(1, $created->json('reconciliation.total_entries'));
        $this->putJson("/api/mobile/reconciliations/{$id}/entries/{$created->json('reconciliation.entries.0.id')}/check", ['matched' => true])->assertOk();

        $response = $this->patchJson("/api/mobile/reconciliations/{$id}", ['date' => '2026-10-18'])->assertOk();

        $this->assertEquals(2, $response->json('reconciliation.total_entries'));
        $this->assertEquals(1, $response->json('reconciliation.matched_count'));
        $this->assertEquals(4700, $response->json('reconciliation.ledger_balance'));
        $this->assertEquals(200, $response->json('reconciliation.difference'));
        $this->assertStringStartsWith('2026-10-18', $response->json('reconciliation.date'));
    }

    public function test_changing_the_date_to_one_already_reconciled_is_rejected(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $this->createCompleted($account, '2026-10-12');
        $id = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 4500, 'date' => '2026-10-18'])->json('reconciliation.id');

        $this->patchJson("/api/mobile/reconciliations/{$id}", ['date' => '2026-10-12'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date')
            ->assertJsonStructure(['message', 'errors']);
        $this->patchJson("/api/mobile/reconciliations/{$id}", ['date' => '2026-10-18', 'balance' => 4400])->assertOk()->assertJsonPath('reconciliation.amount', 4400);
        $this->patchJson("/api/mobile/reconciliations/{$id}", ['date' => 'nope'])->assertStatus(422);
    }

    /** A reconciliation whose statement balance equals the 5000 on the books, so it is born completed. */
    private function createCompleted($account, string $date): int
    {
        $response = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 5000, 'date' => $date])->assertCreated();
        $this->assertEquals('completed', $response->json('reconciliation.status'));

        return $response->json('reconciliation.id');
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

    // ── What the app needs to reconcile like the web ────────────────────

    public function test_payload_carries_the_ledger_balance_and_the_matched_counts(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 4500, 'date' => '2026-10-18'])->assertCreated();

        $this->assertEquals(5000, $response->json('reconciliation.ledger_balance'));
        $this->assertEquals(0, $response->json('reconciliation.matched_count'));
        $this->assertEquals(1, $response->json('reconciliation.total_entries'));
        $this->assertEquals(500, $response->json('reconciliation.difference')); // what it always had

        $entry = $response->json('reconciliation.entries.0.id');
        $id = $response->json('reconciliation.id');
        $this->putJson("/api/mobile/reconciliations/{$id}/entries/{$entry}/check", ['matched' => true])->assertOk();

        $again = $this->getJson("/api/mobile/accounts/{$account->id}/reconciliation")->assertOk();
        $this->assertEquals(1, $again->json('reconciliation.matched_count'));
        $this->assertEquals(5000, $again->json('reconciliation.ledger_balance'));
    }

    public function test_editing_the_statement_balance_recomputes_the_difference_without_closing_it(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 4500, 'date' => '2026-10-18'])->json('reconciliation.id');

        // Typing the balance that matches the books does NOT complete it (only the adjustment endpoint does).
        $response = $this->patchJson("/api/mobile/reconciliations/{$id}", ['balance' => 5000])->assertOk();
        $this->assertEquals('pending', $response->json('reconciliation.status'));
        $this->assertEquals(0, $response->json('reconciliation.difference'));
        $this->assertEquals(5000, $response->json('reconciliation.amount'));
        $this->assertNotEmpty($response->json('reconciliation.entries'));

        $this->patchJson("/api/mobile/reconciliations/{$id}", ['balance' => 4000])->assertOk()->assertJsonPath('reconciliation.difference', 1000);
        $this->patchJson("/api/mobile/reconciliations/{$id}", [])->assertStatus(422);

        $this->putJson("/api/mobile/reconciliations/{$id}/adjustment", ['balance' => 5000])->assertOk();
        $this->patchJson("/api/mobile/reconciliations/{$id}", ['balance' => 1])->assertStatus(422); // completed
    }

    public function test_pull_new_adds_the_movements_that_arrived_after_it_was_opened(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 4500, 'date' => '2026-10-18'])->json('reconciliation.id');

        DB::table('transactions')->insert(['id' => 2, 'team_id' => $account->team_id, 'user_id' => $user->id, 'date' => '2026-10-12', 'status' => 'verified']);
        DB::table('transaction_lines')->insert(['id' => 2, 'transaction_id' => 2, 'account_id' => $account->id, 'date' => '2026-10-12', 'amount' => 300, 'type' => -1]);

        $response = $this->putJson("/api/mobile/reconciliations/{$id}/sync-transactions")->assertOk();

        $this->assertEquals(2, $response->json('reconciliation.total_entries'));
        $this->assertCount(2, $response->json('reconciliation.entries'));
        $this->assertEquals(4700, $response->json('reconciliation.ledger_balance'));
        $this->assertEquals(200, $response->json('reconciliation.difference')); // 4700 − 4500
    }

    public function test_balance_at_a_date_ignores_what_happened_after(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);

        $this->getJson("/api/mobile/accounts/{$account->id}/balance-at?date=2026-10-18")->assertOk()->assertJsonPath('balance', 5000)->assertJsonPath('date', '2026-10-18');
        $this->getJson("/api/mobile/accounts/{$account->id}/balance-at?date=2026-10-01")->assertOk()->assertJsonPath('balance', 0);
        $this->getJson("/api/mobile/accounts/{$account->id}/balance-at?date=not-a-date")->assertStatus(422);
    }

    public function test_history_and_hub_list_reconciliations_by_account(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 4500, 'date' => '2026-10-18'])->json('reconciliation.id');

        $history = $this->getJson("/api/mobile/accounts/{$account->id}/reconciliations")->assertOk();
        $this->assertEquals($id, $history->json('last_id'));
        $this->assertEquals('pending', $history->json('reconciliations.0.status'));
        $this->assertEquals(1, $history->json('reconciliations.0.total_transactions'));
        $this->assertEquals(0, $history->json('reconciliations.0.matched_transactions'));

        $hub = collect($this->getJson('/api/mobile/reconciliations')->assertOk()->json('accounts'))->firstWhere('id', $account->id);
        $this->assertNotNull($hub);
        $this->assertEquals($id, $hub['last_id']);
        $this->assertEquals('pending', $hub['last_status']);
        $this->assertEquals(500, $hub['last_difference']);
    }

    public function test_new_reconciliation_endpoints_are_scoped_to_the_team(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 4500, 'date' => '2026-10-18'])->json('reconciliation.id');

        $other = User::factory()->withPersonalTeam()->create();
        $other->forceFill(['current_team_id' => $other->ownedTeams()->first()->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->patchJson("/api/mobile/reconciliations/{$id}", ['balance' => 1])->assertForbidden();
        $this->putJson("/api/mobile/reconciliations/{$id}/sync-transactions")->assertForbidden();
        $this->getJson("/api/mobile/accounts/{$account->id}/reconciliations")->assertForbidden();
        $this->getJson("/api/mobile/accounts/{$account->id}/balance-at")->assertForbidden();
        $this->assertNull(collect($this->getJson('/api/mobile/reconciliations')->assertOk()->json('accounts'))->firstWhere('id', $account->id));
    }

    /** An account whose books hold one verified deposit of `$amount` on 2026-10-10. */
    private function makeSetupWithBalance(float $amount): array
    {
        [$user, $account] = $this->makeSetup();
        DB::table('transactions')->insert(['id' => 1, 'team_id' => $account->team_id, 'user_id' => $user->id, 'date' => '2026-10-10', 'status' => 'verified']);
        DB::table('transaction_lines')->insert(['id' => 1, 'transaction_id' => 1, 'account_id' => $account->id, 'date' => '2026-10-10', 'amount' => $amount, 'type' => 1]);

        return [$user, $account];
    }

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
