<?php

namespace Tests\Feature\Security;

use App\Domains\AppCore\Models\CoreModule;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Category;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Watchlist\Models\Watchlist;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class CrossTeamActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        (new AccountDetailTypesCreate)->create();

        $this->user = $this->teamedUser();
        $this->otherUser = $this->teamedUser();
    }

    public function test_bulk_updates_ignore_other_teams_rows_and_ownership_fields(): void
    {
        $theirCategory = Category::where('team_id', $this->otherUser->current_team_id)->firstOrFail();
        $myCategory = Category::where('team_id', $this->user->current_team_id)->firstOrFail();
        $theirAccount = $this->accountFor($this->otherUser, 'Their bank');

        $this->actingAs($this->user)->patchJson('/api/categories', ['data' => [
            $theirCategory->id => ['name' => 'pwned'],
            $myCategory->id => ['name' => 'Renamed', 'team_id' => $this->otherUser->current_team_id],
        ]])->assertOk();

        $this->actingAs($this->user)->patchJson('/api/accounts', ['accounts' => [
            $theirAccount->id => ['name' => 'pwned'],
        ]])->assertOk();

        $this->assertNotSame('pwned', $theirCategory->fresh()->name);
        $this->assertSame('Renamed', $myCategory->fresh()->name);
        $this->assertSame($this->user->current_team_id, (int) $myCategory->fresh()->team_id);
        $this->assertSame('Their bank', $theirAccount->fresh()->name);
    }

    public function test_account_pages_and_actions_of_another_team_are_denied(): void
    {
        $theirAccount = $this->accountFor($this->otherUser, 'Their bank');

        $this->actingAs($this->user)->get("/finance/accounts/{$theirAccount->id}")->assertRedirect(route('finance.transactions'));
        $this->actingAs($this->user)
            ->put("/finance/accounts/{$theirAccount->id}/close", ['closed_at' => '2026-09-01', 'archived' => true, 'status' => 'closed'])
            ->assertForbidden();
        $this->actingAs($this->user)->getJson("/api/accounts/{$theirAccount->id}/multi-currency-balances")->assertForbidden();

        $this->assertNull($theirAccount->fresh()->closed_at);
    }

    public function test_reconciliations_of_another_team_are_denied(): void
    {
        $theirAccount = $this->accountFor($this->otherUser, 'Their bank');
        $reconciliation = Reconciliation::create([
            'team_id' => $this->otherUser->current_team_id,
            'user_id' => $this->otherUser->id,
            'account_id' => $theirAccount->id,
            'date' => '2026-09-01',
            'amount' => 100,
            'difference' => 10,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)->get("/finance/reconciliation/accounts/{$theirAccount->id}")->assertForbidden();
        $this->actingAs($this->user)->put("/finance/reconciliation/{$reconciliation->id}/save-adjustment")->assertForbidden();
        $this->actingAs($this->user)->delete("/finance/reconciliation/{$reconciliation->id}")->assertForbidden();

        $this->assertDatabaseHas('reconciliations', ['id' => $reconciliation->id]);
    }

    public function test_mark_as_paid_cannot_touch_another_teams_transaction(): void
    {
        $theirAccount = $this->accountFor($this->otherUser, 'Their bank');
        $theirTransaction = $this->verifiedExpense($this->otherUser, $theirAccount, 'Rent', 500);

        $this->actingAs($this->user)
            ->patchJson("/api/next-payments/planned_{$theirTransaction->id}/mark-as-paid", ['amount' => 1, 'date' => '2026-09-01'])
            ->assertStatus(400);

        $this->assertEquals(500, (float) $theirTransaction->fresh()->total);
        $this->assertSame($this->otherUser->current_team_id, (int) $theirTransaction->fresh()->team_id);
    }

    public function test_occurrence_preview_only_matches_the_own_team(): void
    {
        $theirAccount = $this->accountFor($this->otherUser, 'Their bank');
        $this->verifiedExpense($this->otherUser, $theirAccount, 'JUMBO LA ROMANA', 80);

        $conditions = ['description' => [['operator' => 'contains', 'value' => 'JUMBO']]];
        $mine = $this->occurrenceFor($this->user, $conditions);
        $theirs = $this->occurrenceFor($this->otherUser, $conditions);

        $this->actingAs($this->user)->getJson("/housing/occurrences/{$mine->id}/preview")->assertOk()->assertJsonCount(0);
        $this->actingAs($this->user)->getJson("/housing/occurrences/{$theirs->id}/preview")->assertNotFound();
    }

    public function test_journal_transactions_cannot_reference_or_move_to_another_team(): void
    {
        $myAccount = $this->accountFor($this->user, 'My bank');
        $myCounterAccount = $this->accountFor($this->user, 'My cash');
        $theirAccount = $this->accountFor($this->otherUser, 'Their bank');

        $this->actingAs($this->user)->post('/transactions', [
            'account_id' => $theirAccount->id,
            'date' => '2026-09-01',
            'description' => 'injected',
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => 10,
            'status' => Transaction::STATUS_VERIFIED,
        ])->assertSessionHasErrors('account_id');

        $mine = $this->verifiedExpense($this->user, $myAccount, 'Groceries', 20);
        $this->actingAs($this->user)->put("/transactions/{$mine->id}", [
            'account_id' => $myAccount->id,
            'counter_account_id' => $myCounterAccount->id,
            'date' => '2026-09-02',
            'description' => 'Groceries',
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => 25,
            'status' => Transaction::STATUS_VERIFIED,
            'team_id' => $this->otherUser->current_team_id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->user->current_team_id, (int) $mine->fresh()->team_id);
        $this->assertDatabaseMissing('transactions', ['description' => 'injected']);
    }

    public function test_multi_currency_payment_rejects_another_teams_account(): void
    {
        $theirAccount = $this->accountFor($this->otherUser, 'Their card');

        $this->actingAs($this->user)->postJson('/api/api/multi-currency/payments', [
            'account_id' => $theirAccount->id,
            'total' => 100,
            'exchange_amount' => 1,
            'secondary_currency' => 'USD',
            'payment_date' => '2026-09-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('account_id');
    }

    public function test_profiles_of_another_team_are_not_found(): void
    {
        CoreModule::updateOrCreate(
            ['team_id' => $this->user->current_team_id, 'name' => 'profiles'],
            ['user_id' => $this->user->id, 'enabled' => true],
        );
        $theirProfile = LogerProfile::create([
            'team_id' => $this->otherUser->current_team_id,
            'user_id' => $this->otherUser->id,
            'name' => 'Diana',
        ]);

        $this->actingAs($this->user)->getJson("/loger-profiles/{$theirProfile->id}/transactions")->assertNotFound();
        $this->actingAs($this->user)->postJson("/loger-profiles/{$theirProfile->id}/entities", [])->assertNotFound();
    }

    public function test_api_token_requires_the_second_factor_when_enabled(): void
    {
        $secret = app(Google2FA::class)->generateSecretKey();
        $this->user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-one'])),
        ])->save();

        $credentials = ['email' => $this->user->email, 'password' => 'password', 'device_name' => 'android'];

        $this->postJson('/api/sanctum/token', $credentials)->assertStatus(402);
        $this->postJson('/api/sanctum/token', [...$credentials, 'code' => '000000'])->assertStatus(402);
        $this->postJson('/api/sanctum/token', [...$credentials, 'recovery_code' => 'recovery-one'])->assertOk();
        $this->postJson('/api/sanctum/token', [...$credentials, 'recovery_code' => 'recovery-one'])->assertStatus(402);
        $this->postJson('/api/sanctum/token', [...$credentials, 'code' => app(Google2FA::class)->getCurrentOtp($secret)])->assertOk();
    }

    public function test_watchlists_of_another_team_are_not_found(): void
    {
        $theirCategory = Category::where('team_id', $this->otherUser->current_team_id)->whereNotNull('parent_id')->firstOrFail();
        $theirWatchlist = Watchlist::create([
            'team_id' => $this->otherUser->current_team_id,
            'user_id' => $this->otherUser->id,
            'name' => 'Their groceries',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$theirCategory->id],
        ]);

        $this->actingAs($this->user)->get("/finance/watchlist/{$theirWatchlist->id}")->assertNotFound();
    }

    public function test_plan_boards_and_items_of_another_team_are_not_reachable(): void
    {
        $theirPlan = Plan::where('team_id', $this->otherUser->current_team_id)->firstOrFail();
        $theirItem = PlanItem::create([
            'plan_id' => $theirPlan->id,
            'stage_id' => $theirPlan->stages()->value('id'),
            'team_id' => $this->otherUser->current_team_id,
            'user_id' => $this->otherUser->id,
            'title' => 'Their chore',
        ]);

        $this->actingAs($this->user)->get("/housing/boards/{$theirPlan->id}")->assertRedirect('dashboard');
        $this->actingAs($this->user)
            ->putJson("/housing/plans/{$theirPlan->id}/items/{$theirItem->id}", ['title' => 'pwned'])
            ->assertNotFound();
        $this->actingAs($this->user)
            ->postJson("/housing/plans/{$theirPlan->id}/items", ['title' => 'injected'])
            ->assertNotFound();
        $this->actingAs($this->user)
            ->deleteJson("/housing/plans/{$theirPlan->id}/items/{$theirItem->id}")
            ->assertNotFound();

        $this->assertSame('Their chore', $theirItem->fresh()->title);
        $this->assertDatabaseMissing('plan_items', ['title' => 'injected']);
    }

    public function test_account_ledger_search_stays_inside_the_account(): void
    {
        $myAccount = $this->accountFor($this->user, 'My bank');
        $theirAccount = $this->accountFor($this->otherUser, 'Their bank');
        $theirs = $this->verifiedExpense($this->otherUser, $theirAccount, 'Their secret purchase', 42);

        $response = $this->actingAs($this->user)
            ->get("/finance/accounts/{$myAccount->id}?search=2026-09-05&filter[date]=2026-09-01~2026-09-30")
            ->assertOk();

        $ids = collect($response->viewData('page')['props']['transactions'])->pluck('id')->all();
        $this->assertNotContains($theirs->id, $ids);
    }

    private function teamedUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();
        CoreModule::where('team_id', $user->current_team_id)->where('name', 'Housing')->update(['enabled' => true]);

        return $user;
    }

    private function accountFor(User $owner, string $name): Account
    {
        return Account::create([
            'team_id' => $owner->current_team_id,
            'user_id' => $owner->id,
            'name' => $name,
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', AccountDetailType::BANK)->value('id'),
        ]);
    }

    private function verifiedExpense(User $owner, Account $account, string $description, float $amount): Transaction
    {
        $transaction = new Transaction;
        $transaction->forceFill([
            'team_id' => $owner->current_team_id,
            'user_id' => $owner->id,
            'account_id' => $account->id,
            'date' => '2026-09-05',
            'currency_code' => 'DOP',
            'description' => $description,
            'direction' => Transaction::DIRECTION_CREDIT,
            'total' => $amount,
            'status' => Transaction::STATUS_VERIFIED,
        ])->save();

        (new TransactionLine)->forceFill([
            'team_id' => $owner->current_team_id,
            'user_id' => $owner->id,
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'date' => '2026-09-05',
            'amount' => $amount,
            'concept' => $description,
            'index' => 0,
            'anchor' => 1,
            'type' => -1,
            'category_id' => 0,
        ])->save();

        return $transaction;
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function occurrenceFor(User $owner, array $conditions): Occurrence
    {
        return Occurrence::create([
            'team_id' => $owner->current_team_id,
            'user_id' => $owner->id,
            'name' => 'Supermercado',
            'conditions' => $conditions,
            'is_active' => true,
        ]);
    }
}
