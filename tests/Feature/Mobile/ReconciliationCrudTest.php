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

    public function test_a_completed_reconciliation_can_be_deleted_and_releases_its_movements(): void
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

        $this->deleteJson('/api/mobile/reconciliations/1')->assertOk()->assertJsonPath('deleted', true);
        $this->assertDatabaseMissing('reconciliations', ['id' => 1]);
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

    // ── Completed reconciliations: see them, reopen the latest, delete; move the date of one in progress ──

    public function test_a_completed_reconciliation_can_be_opened_to_see_its_movements(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 5000, 'date' => '2026-10-18'])->json('reconciliation.id');

        $shown = $this->getJson("/api/mobile/reconciliations/{$id}")->assertOk();
        $this->assertEquals('completed', $shown->json('reconciliation.status'));
        $this->assertEquals(1, $shown->json('reconciliation.total_entries'));
        $this->assertEquals(1, $shown->json('reconciliation.matched_count'));
        $this->assertCount(1, $shown->json('reconciliation.entries'));
        $this->assertEquals(5000, $shown->json('reconciliation.ledger_balance'));
    }

    public function test_the_latest_completed_reconciliation_can_be_reopened(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 5000, 'date' => '2026-10-18'])->json('reconciliation.id');
        $this->assertEquals(1, DB::table('transaction_lines')->where('matched', true)->count());

        $reopened = $this->postJson("/api/mobile/reconciliations/{$id}/reopen")->assertOk();

        $this->assertEquals('pending', $reopened->json('reconciliation.status'));
        $this->assertEquals(0, $reopened->json('reconciliation.matched_count'));
        $this->assertEquals(1, $reopened->json('reconciliation.total_entries'));
        $this->assertEquals(0, DB::table('transaction_lines')->where('matched', true)->count());
        $this->assertEquals('pending', DB::table('reconciliations')->find($id)->status);

        // It is in progress now: it cannot be reopened again, and it can be finished.
        $this->postJson("/api/mobile/reconciliations/{$id}/reopen")->assertStatus(422);
        $this->putJson("/api/mobile/reconciliations/{$id}/adjustment", ['balance' => 5000])->assertOk()->assertJsonPath('status', 'completed');
    }

    public function test_only_the_latest_reconciliation_of_an_account_can_be_reopened(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $first = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 5000, 'date' => '2026-10-18'])->json('reconciliation.id');
        $second = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 5000, 'date' => '2026-10-25'])->json('reconciliation.id');

        $this->postJson("/api/mobile/reconciliations/{$first}/reopen")->assertStatus(422);
        $this->assertEquals('completed', DB::table('reconciliations')->find($first)->status);
        $this->postJson("/api/mobile/reconciliations/{$second}/reopen")->assertOk();
    }

    public function test_deleting_a_completed_reconciliation_unmatches_its_movements_but_keeps_the_transactions(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 5000, 'date' => '2026-10-18'])->json('reconciliation.id');
        $this->assertEquals(1, DB::table('transaction_lines')->where('matched', true)->count());

        $this->deleteJson("/api/mobile/reconciliations/{$id}")->assertOk();

        $this->assertSame(0, DB::table('reconciliations')->where('id', $id)->count());
        $this->assertSame(0, DB::table('reconciliation_entries')->where('reconciliation_id', $id)->count());
        $this->assertSame(0, DB::table('transaction_lines')->where('matched', true)->count());
        $this->assertSame(1, DB::table('transactions')->count()); // the movement itself stays
    }

    public function test_moving_the_statement_date_releases_later_movements_and_adds_earlier_ones(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000); // +5000 on 2026-10-10
        DB::table('transactions')->insert(['id' => 2, 'team_id' => $account->team_id, 'user_id' => $user->id, 'date' => '2026-10-15', 'status' => 'verified']);
        DB::table('transaction_lines')->insert(['id' => 2, 'transaction_id' => 2, 'account_id' => $account->id, 'date' => '2026-10-15', 'amount' => 300, 'type' => -1]);
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 4500, 'date' => '2026-10-18'])->json('reconciliation.id');

        // Earlier: the movement of the 15th does not belong to it any more.
        $earlier = $this->patchJson("/api/mobile/reconciliations/{$id}", ['date' => '2026-10-12'])->assertOk();
        $this->assertEquals('2026-10-12', substr($earlier->json('reconciliation.date'), 0, 10));
        $this->assertEquals(1, $earlier->json('reconciliation.total_entries'));
        $this->assertEquals(5000, $earlier->json('reconciliation.ledger_balance'));
        $this->assertEquals(500, $earlier->json('reconciliation.difference')); // 5000 - 4500
        $this->assertSame(0, DB::table('reconciliation_entries')->where(['reconciliation_id' => $id, 'transaction_line_id' => 2])->count());

        // Later: it is back, and the balance can change in the same call.
        $later = $this->patchJson("/api/mobile/reconciliations/{$id}", ['date' => '2026-10-20', 'balance' => 4700])->assertOk();
        $this->assertEquals(2, $later->json('reconciliation.total_entries'));
        $this->assertEquals(4700, $later->json('reconciliation.ledger_balance'));
        $this->assertEquals(0, $later->json('reconciliation.difference'));
        $this->assertEquals('pending', $later->json('reconciliation.status'));
    }

    public function test_the_statement_date_must_be_free_valid_and_of_a_reconciliation_in_progress(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 4500, 'date' => '2026-10-18'])->json('reconciliation.id');
        $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 4500, 'date' => '2026-10-25'])->assertCreated();

        $this->patchJson("/api/mobile/reconciliations/{$id}", ['date' => '2026-10-25'])->assertStatus(422); // taken
        $this->patchJson("/api/mobile/reconciliations/{$id}", ['date' => 'tomorrow'])->assertStatus(422);
        $this->patchJson("/api/mobile/reconciliations/{$id}", [])->assertStatus(422);
        $this->assertEquals('2026-10-18', substr((string) DB::table('reconciliations')->find($id)->date, 0, 10));

        $this->putJson("/api/mobile/reconciliations/{$id}/adjustment", ['balance' => 4500])->assertOk();
        $this->patchJson("/api/mobile/reconciliations/{$id}", ['date' => '2026-10-20'])->assertStatus(422); // completed
    }

    public function test_adjusting_a_real_difference_books_the_plug_transaction_and_closes_it(): void
    {
        [$user, $account] = $this->makeSetupWithBalance(5000);
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/accounts/{$account->id}/reconciliation", ['balance' => 4500, 'date' => '2026-10-18'])->json('reconciliation.id');

        $this->putJson("/api/mobile/reconciliations/{$id}/adjustment", ['balance' => 4500])
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('difference', 0);

        // The books now say what the statement says, through one "Loger adjustment" of the gap.
        $plug = DB::table('transactions')->where('description', 'Loger adjustment')->first();
        $this->assertNotNull($plug);
        $this->assertEquals(500, $plug->total);
        $this->assertEquals('2026-10-18', substr((string) $plug->date, 0, 10));
        $this->assertEquals(4500, DB::table('transaction_lines')->where('account_id', $account->id)->sum(DB::raw('amount * type')));
        $this->assertEquals('completed', DB::table('reconciliations')->find($id)->status);
    }

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
