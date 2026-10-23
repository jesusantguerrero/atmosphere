<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Insane\Journal\Events\TransactionCreated;
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

    public function test_store_transaction_fires_created_event(): void
    {
        [$user, $team, $account, $category, $payee] = $this->makeSetup();
        Sanctum::actingAs($user);

        Event::fake([TransactionCreated::class]);

        $this->postJson('/api/mobile/transactions', [
            'account_id' => $account->id,
            'total' => 450.00,
            'currency_code' => 'DOP',
            'description' => 'Supermercado',
            'date' => '2026-10-20',
            'direction' => 'WITHDRAW',
            'category_id' => $category->id,
            'payee_id' => $payee->id,
        ])->assertCreated();

        Event::assertDispatched(TransactionCreated::class);
    }

    public function test_transfer_fires_created_event(): void
    {
        [$user, $team, $account] = $this->makeSetup();
        Sanctum::actingAs($user);

        $toAccount = Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Savings',
            'currency_code' => 'DOP',
            'balance_type' => 'DEBIT',
            'type' => 1,
        ]);

        Event::fake([TransactionCreated::class]);

        $this->postJson('/api/mobile/transfers', [
            'from_account_id' => $account->id,
            'to_account_id' => $toAccount->id,
            'total' => 1000,
            'date' => '2026-10-20',
        ])->assertCreated();

        Event::assertDispatched(TransactionCreated::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    // ── Lines: what balances, the budget and reconciliations are summed from ──

    public function test_creating_a_transaction_posts_its_lines(): void
    {
        [$user, $team, $account, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/mobile/transactions', $this->payload($account, $category, 250))->assertStatus(201)->json('data.id');

        $lines = DB::table('transaction_lines')->where('transaction_id', $id)->get();
        $this->assertCount(2, $lines); // the category side and the account side
        $this->assertEquals(250, $lines->sum('amount') / 2);
        $this->assertEquals($category->id, $lines->firstWhere('anchor', 1)->category_id);
        $this->assertEquals(-250, DB::table('transaction_lines')->where('account_id', $account->id)->sum(DB::raw('amount * type'))); // the money left the account
    }

    public function test_two_identical_purchases_of_the_same_day_are_both_kept(): void
    {
        [$user, $team, $account, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $first = $this->postJson('/api/mobile/transactions', $this->payload($account, $category, 100))->assertStatus(201)->json('data.id');
        $second = $this->postJson('/api/mobile/transactions', $this->payload($account, $category, 100))->assertStatus(201)->json('data.id');

        $this->assertNotEquals($first, $second);
        $this->assertEquals(-200, DB::table('transaction_lines')->where('account_id', $account->id)->sum(DB::raw('amount * type')));
    }

    // ── Splits: one purchase, several categories ───────────────────────

    public function test_a_purchase_can_be_split_across_categories(): void
    {
        [$user, $team, $account, $groceries, $payee] = $this->makeSetup();
        $cleaning = $this->makeCategory($team, 'Cleaning');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/transactions', [
            ...$this->payload($account, null, 1000),
            'payee_id' => $payee->id,
            'items' => [
                ['category_id' => $groceries->id, 'amount' => 700],
                ['category_id' => $cleaning->id, 'amount' => 300, 'concept' => 'Detergent'],
            ],
        ])->assertStatus(201);
        $id = $response->json('data.id');

        $this->assertTrue($response->json('data.has_splits'));
        $transaction = DB::table('transactions')->find($id);
        $this->assertNull($transaction->category_id);
        $this->assertEquals(1000, $transaction->total);

        // Each split is a category line plus its counter line; the account moved by the whole amount.
        $splits = DB::table('transaction_lines')->where(['transaction_id' => $id, 'is_split' => 1])->orderBy('id')->get();
        $this->assertSame([$groceries->id, $cleaning->id], $splits->pluck('category_id')->map(fn ($c) => (int) $c)->all());
        $this->assertEquals([700.0, 300.0], $splits->pluck('amount')->map(fn ($a) => (float) $a)->all());
        $this->assertCount(4, DB::table('transaction_lines')->where('transaction_id', $id)->get());
        $this->assertEquals(-1000, DB::table('transaction_lines')->where('account_id', $account->id)->sum(DB::raw('amount * type')));

        // The app reads them back to edit.
        $show = $this->getJson("/api/mobile/transactions/{$id}")->assertOk();
        $this->assertTrue($show->json('has_splits'));
        $this->assertCount(2, $show->json('splits'));
        $this->assertEquals(['Groceries', 'Cleaning'], array_column($show->json('splits'), 'category_name'));
        $this->assertEquals(300, $show->json('splits.1.amount'));
        $this->assertEquals('Detergent', $show->json('splits.1.concept'));
    }

    public function test_a_split_needs_two_lines_that_add_up_and_belong_to_the_team(): void
    {
        [$user, $team, $account, $groceries] = $this->makeSetup();
        $cleaning = $this->makeCategory($team, 'Cleaning');
        Sanctum::actingAs($user);

        $base = $this->payload($account, null, 1000);
        $this->postJson('/api/mobile/transactions', [...$base, 'items' => [['category_id' => $groceries->id, 'amount' => 700], ['category_id' => $cleaning->id, 'amount' => 200]]])
            ->assertStatus(422)->assertJsonValidationErrors('items');
        $this->postJson('/api/mobile/transactions', [...$base, 'items' => [['category_id' => $groceries->id, 'amount' => 1000]]])
            ->assertStatus(422)->assertJsonValidationErrors('items');

        $other = User::factory()->withPersonalTeam()->create();
        $foreign = $this->makeCategory($other->ownedTeams()->first(), 'Not mine');
        $this->postJson('/api/mobile/transactions', [...$base, 'items' => [['category_id' => $groceries->id, 'amount' => 500], ['category_id' => $foreign->id, 'amount' => 500]]])
            ->assertStatus(422)->assertJsonValidationErrors('items.1.category_id');

        $this->assertSame(0, DB::table('transactions')->count());
    }

    public function test_editing_a_split_purchase_does_not_collapse_it(): void
    {
        [$user, $team, $account, $groceries, $payee] = $this->makeSetup();
        $cleaning = $this->makeCategory($team, 'Cleaning');
        Sanctum::actingAs($user);
        $id = $this->postJson('/api/mobile/transactions', [
            ...$this->payload($account, null, 1000),
            'payee_id' => $payee->id,
            'items' => [['category_id' => $groceries->id, 'amount' => 700], ['category_id' => $cleaning->id, 'amount' => 300]],
        ])->assertStatus(201)->json('data.id');

        // Only the description changes: the two lines stay.
        $this->patchJson("/api/mobile/transactions/{$id}", ['description' => 'Sirena weekly'])->assertOk();
        $this->assertCount(2, DB::table('transaction_lines')->where(['transaction_id' => $id, 'is_split' => 1])->get());

        // The total changes without new amounts: refused, the lines would no longer add up.
        $this->patchJson("/api/mobile/transactions/{$id}", ['total' => 1200])->assertStatus(422)->assertJsonValidationErrors('items');

        // New amounts and total together.
        $this->patchJson("/api/mobile/transactions/{$id}", ['total' => 1200, 'items' => [['category_id' => $groceries->id, 'amount' => 900], ['category_id' => $cleaning->id, 'amount' => 300]]])->assertOk();
        $this->assertEquals([900.0, 300.0], DB::table('transaction_lines')->where(['transaction_id' => $id, 'is_split' => 1])->orderBy('id')->pluck('amount')->map(fn ($a) => (float) $a)->all());
        $this->assertEquals(-1200, DB::table('transaction_lines')->where('account_id', $account->id)->sum(DB::raw('amount * type')));

        // Picking one category turns it back into a plain purchase.
        $this->patchJson("/api/mobile/transactions/{$id}", ['category_id' => $groceries->id])->assertOk();
        $this->assertFalse((bool) DB::table('transactions')->find($id)->has_splits);
        $this->assertSame(0, DB::table('transaction_lines')->where(['transaction_id' => $id, 'is_split' => 1])->count());
        $this->assertEquals($groceries->id, DB::table('transaction_lines')->where(['transaction_id' => $id, 'anchor' => 1])->value('category_id'));
    }

    public function test_transactions_can_be_searched_by_description_or_payee(): void
    {
        [$user, $team, $account, $category, $payee] = $this->makeSetup();
        Sanctum::actingAs($user);
        $this->postJson('/api/mobile/transactions', [...$this->payload($account, $category, 100), 'description' => 'Sirena weekly'])->assertStatus(201);
        $this->postJson('/api/mobile/transactions', [...$this->payload($account, $category, 200), 'description' => 'Gasolina', 'payee_id' => $payee->id])->assertStatus(201);

        $found = fn (string $search) => collect($this->getJson('/api/mobile/transactions?search='.urlencode($search))->assertOk()->json('data.transactions'))->pluck('description')->all();

        $this->assertSame(['Sirena weekly'], $found('siren'));
        $this->assertSame(['Gasolina'], $found('Test Payee')); // the payee's name
        $this->assertSame([], $found('%')); // wildcards are literal
        $this->assertCount(2, $this->getJson('/api/mobile/transactions')->json('data.transactions'));
    }

    public function test_transactions_can_be_filtered_by_status(): void
    {
        [$user, $team, $account] = $this->makeSetup();
        Sanctum::actingAs($user);
        $this->makeTransaction($user, $account, 'verified', '2026-10-10', 'Paid');
        $this->makeTransaction($user, $account, 'draft', '2026-10-10', 'Imported');
        $this->makeTransaction($user, $account, 'planned', '2026-10-20', 'Rent');

        $found = fn (string $query) => collect($this->getJson('/api/mobile/transactions'.$query)->assertOk()->json('data.transactions'))->pluck('description')->all();

        $this->assertSame(['Paid'], $found('?status=verified'));
        $this->assertSame(['Imported'], $found('?status=draft'));
        $this->assertSame(['Rent'], $found('?status=scheduled'));
        $this->assertCount(3, $found(''));
        $this->getJson('/api/mobile/transactions?status=planned')->assertStatus(422)->assertJsonValidationErrors('status');
    }

    public function test_approve_all_drafts_only_touches_the_teams_drafts_in_range(): void
    {
        [$user, $team, $account] = $this->makeSetup();
        Sanctum::actingAs($user);
        $inRange = $this->makeTransaction($user, $account, 'draft', '2026-10-10');
        $outOfRange = $this->makeTransaction($user, $account, 'draft', '2026-09-10');
        $verified = $this->makeTransaction($user, $account, 'verified', '2026-10-11');
        [$other, , $otherAccount] = $this->makeSetup();
        $foreign = $this->makeTransaction($other, $otherAccount, 'draft', '2026-10-10');

        $this->postJson('/api/mobile/transactions/approve-all-drafts', ['start' => '2026-10-01', 'end' => '2026-10-31'])
            ->assertOk()
            ->assertJson(['success' => true, 'approved' => 1]);

        $this->assertSame('verified', $inRange->fresh()->status);
        $this->assertSame('draft', $outOfRange->fresh()->status);
        $this->assertSame('verified', $verified->fresh()->status);
        $this->assertSame('draft', $foreign->fresh()->status);

        $this->postJson('/api/mobile/transactions/approve-all-drafts')->assertOk()->assertJson(['approved' => 1]);
        $this->assertSame('verified', $outOfRange->fresh()->status);
        $this->assertSame('draft', $foreign->fresh()->status);
    }

    public function test_remove_all_drafts_deletes_only_the_teams_drafts_in_range(): void
    {
        [$user, $team, $account] = $this->makeSetup();
        Sanctum::actingAs($user);
        $inRange = $this->makeTransaction($user, $account, 'draft', '2026-10-10');
        $outOfRange = $this->makeTransaction($user, $account, 'draft', '2026-09-10');
        $verified = $this->makeTransaction($user, $account, 'verified', '2026-10-11');
        [$other, , $otherAccount] = $this->makeSetup();
        $foreign = $this->makeTransaction($other, $otherAccount, 'draft', '2026-10-10');

        $this->postJson('/api/mobile/transactions/remove-all-drafts', ['start' => '2026-10-01', 'end' => '2026-10-31'])
            ->assertOk()
            ->assertJson(['success' => true, 'deleted' => 1]);

        $this->assertNull(Transaction::find($inRange->id));
        $this->assertNotNull(Transaction::find($outOfRange->id));
        $this->assertNotNull(Transaction::find($verified->id));
        $this->assertNotNull(Transaction::find($foreign->id));

        $this->postJson('/api/mobile/transactions/remove-all-drafts')->assertOk()->assertJson(['deleted' => 1]);
        $this->assertNull(Transaction::find($outOfRange->id));
        $this->assertNotNull(Transaction::find($foreign->id));
    }

    public function test_bulk_draft_actions_validate_the_range(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/transactions/approve-all-drafts', ['start' => '2026-10-31', 'end' => '2026-10-01'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('end');
        $this->postJson('/api/mobile/transactions/remove-all-drafts', ['start' => 'soon'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    private function makeTransaction(User $user, Account $account, string $status, string $date, string $description = 'Sirena'): Transaction
    {
        return Transaction::create([
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => $date,
            'description' => $description,
            'total' => 100,
            'direction' => 'WITHDRAW',
            'currency_code' => 'DOP',
            'status' => $status,
        ]);
    }

    private function payload($account, $category, float $total): array
    {
        return array_filter([
            'account_id' => $account->id,
            'total' => $total,
            'currency_code' => 'DOP',
            'description' => 'Sirena',
            'date' => '2026-10-10',
            'direction' => 'WITHDRAW',
            'category_id' => $category?->id,
        ], fn ($value) => $value !== null);
    }

    private function makeCategory($team, string $name): Category
    {
        $group = Category::firstOrCreate(['name' => 'Expenses', 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => null], ['depth' => 0, 'index' => 99]);

        return Category::create(['name' => $name, 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => 1]);
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
