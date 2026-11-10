<?php

namespace Tests\Feature\Mcp;

use App\Domains\AppCore\Models\Category;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Mcp\Servers\LogerServer;
use App\Mcp\Tools\UpdateTransactionTool;
use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use App\Providers\PassportServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Payee;
use Insane\Journal\Models\Core\TransactionLine;
use Laravel\Passport\AccessToken;
use Tests\TestCase;

/**
 * Fixing a transaction from Claude: recategorize, approve a Bandeja draft,
 * or point a card payment at the card.
 */
class McpUpdateTransactionTest extends TestCase
{
    use RefreshDatabase;

    private const READ_SCOPES = [PassportServiceProvider::READ_SCOPE];

    private const WRITE_SCOPES = [PassportServiceProvider::READ_SCOPE, PassportServiceProvider::WRITE_SCOPE];

    protected function setUp(): void
    {
        parent::setUp();
        app(AccountDetailTypesCreate::class)->create();
        Carbon::setTestNow('2026-11-09 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_changes_the_category_and_keeps_the_other_fields(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        $food = $this->category($team, $group, 'Supermercado');
        $groceries = $this->category($team, $group, 'Groceries');
        $payee = Payee::create(['name' => 'Sirena', 'team_id' => $team->id, 'user_id' => $user->id]);
        $transaction = $this->transaction($user, $account, ['category_id' => $food->id, 'payee_id' => $payee->id, 'notes' => 'quincena']);

        $this->server($user)->tool(UpdateTransactionTool::class, ['transaction_id' => $transaction->id, 'category_id' => $groceries->id])
            ->assertOk()
            ->assertSee(['"category":"Groceries"', '"payee":"Sirena"', '"account":"Popular"', '"total":4200', '"is_transfer":false']);

        $transaction->refresh();
        $this->assertSame($groceries->id, (int) $transaction->category_id);
        $this->assertSame($payee->id, (int) $transaction->payee_id);
        $this->assertSame('2026-11-03', substr((string) $transaction->date, 0, 10));
        $this->assertSame('Compra Sirena', $transaction->description);
        $this->assertSame(4200.0, (float) $transaction->total);
        $this->assertSame($account->id, (int) $transaction->account_id);
        $this->assertSame(Transaction::DIRECTION_CREDIT, $transaction->direction);
        $this->assertSame(Transaction::STATUS_VERIFIED, $transaction->status);
        $this->assertSame('quincena', $transaction->notes);
        $this->assertSame($groceries->id, (int) $this->anchorLine($transaction)->category_id);
    }

    public function test_it_approves_a_draft_with_a_category_and_a_new_payee(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        $groceries = $this->category($team, $group, 'Groceries');
        $draft = $this->transaction($user, $account, ['status' => Transaction::STATUS_DRAFT, 'description' => 'JUMBO LUPERON']);

        $this->server($user)->tool(UpdateTransactionTool::class, [
            'transaction_id' => $draft->id,
            'status' => 'verified',
            'category_id' => $groceries->id,
            'payee_name' => 'Jumbo',
            'total' => 4350.5,
        ])
            ->assertOk()
            ->assertSee(['"status":"verified"', '"category":"Groceries"', '"payee":"Jumbo"', '"total":4350.5']);

        $draft->refresh();
        $this->assertSame(Transaction::STATUS_VERIFIED, $draft->status);
        $this->assertSame($groceries->id, (int) $draft->category_id);
        $this->assertSame('Jumbo', $draft->payee?->name);
        $this->assertSame(1, Payee::query()->where('team_id', $team->id)->where('name', 'Jumbo')->count());
        $this->assertSame(4350.5, (float) $draft->total);
    }

    public function test_pointing_a_payment_at_a_card_turns_it_into_a_transfer(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        $card = $this->account($team, $user, 'Qik Visa Clasica', ['credit_closing_day' => 5]);
        $payee = Payee::create(['name' => 'Qik', 'team_id' => $team->id, 'user_id' => $user->id]);
        $payment = $this->transaction($user, $account, [
            'description' => 'Pago TC Qik 3861',
            'total' => 12000,
            'payee_id' => $payee->id,
            'category_id' => $this->category($team, $group, 'Otros')->id,
        ]);

        $this->server($user)->tool(UpdateTransactionTool::class, ['transaction_id' => $payment->id, 'counter_account_id' => $card->id])
            ->assertOk()
            ->assertSee(['"is_transfer":true', '"counter_account":"Qik Visa Clasica"', '"category":null', '"payee":null']);

        $payment->refresh();
        $this->assertTrue((bool) $payment->is_transfer);
        $this->assertSame($card->id, (int) $payment->counter_account_id);
        $this->assertNull($payment->category_id);
        $this->assertEmpty($payment->payee_id);
        $this->assertEqualsCanonicalizing(
            [$account->id, $card->id],
            TransactionLine::query()->where('transaction_id', $payment->id)->pluck('account_id')->map(fn ($id) => (int) $id)->all(),
        );
        $this->assertSame(1, TransactionLine::query()->where('transaction_id', $payment->id)->where('account_id', $card->id)->where('type', 1)->count());
    }

    public function test_a_transfer_takes_no_category_until_it_is_turned_back_into_an_expense(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        $card = $this->account($team, $user, 'Qik Visa Clasica', ['credit_closing_day' => 5]);
        $other = $this->category($team, $group, 'Otros');
        $transfer = $this->transaction($user, $account, ['is_transfer' => true, 'counter_account_id' => $card->id]);

        $server = $this->server($user);
        $server->tool(UpdateTransactionTool::class, ['transaction_id' => $transfer->id, 'category_id' => $other->id])->assertHasErrors(['A transfer has no category']);
        $server->tool(UpdateTransactionTool::class, ['transaction_id' => $transfer->id, 'counter_account_id' => $account->id])->assertHasErrors(['different account']);

        $server->tool(UpdateTransactionTool::class, ['transaction_id' => $transfer->id, 'counter_account_id' => null, 'category_id' => $other->id])
            ->assertOk()
            ->assertSee(['"is_transfer":false', '"category":"Otros"']);

        $transfer->refresh();
        $this->assertFalse((bool) $transfer->is_transfer);
        $this->assertNull($transfer->counter_account_id);
        $this->assertSame($other->id, (int) $transfer->category_id);
    }

    public function test_it_refuses_another_households_transaction_account_or_category(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        [$stranger, $strangerTeam, $strangerAccount, $strangerGroup] = $this->makeHousehold('Ajena');
        $own = $this->transaction($user, $account, ['category_id' => $this->category($team, $group, 'Supermercado')->id]);
        $foreign = $this->transaction($stranger, $strangerAccount);
        $strangerCategory = $this->category($strangerTeam, $strangerGroup, 'Vinos ajenos');
        $strangerPayee = Payee::create(['name' => 'Ajeno', 'team_id' => $strangerTeam->id, 'user_id' => $stranger->id]);

        $server = $this->server($user);
        $server->tool(UpdateTransactionTool::class, ['transaction_id' => $foreign->id, 'description' => 'Mía'])->assertHasErrors(['not in this household']);
        $server->tool(UpdateTransactionTool::class, ['transaction_id' => $own->id, 'account_id' => $strangerAccount->id])->assertHasErrors(['not an open account of this household']);
        $server->tool(UpdateTransactionTool::class, ['transaction_id' => $own->id, 'counter_account_id' => $strangerAccount->id])->assertHasErrors(['not an open account of this household']);
        $server->tool(UpdateTransactionTool::class, ['transaction_id' => $own->id, 'category_id' => $strangerCategory->id])->assertHasErrors(['not a budget category of this household']);
        $server->tool(UpdateTransactionTool::class, ['transaction_id' => $own->id, 'payee_id' => $strangerPayee->id])->assertHasErrors(['not in this household']);
        $server->tool(UpdateTransactionTool::class, ['transaction_id' => $own->id])->assertHasErrors(['Nothing to change']);
        $server->tool(UpdateTransactionTool::class, ['transaction_id' => $own->id, 'status' => 'planned'])->assertHasErrors();
        $server->tool(UpdateTransactionTool::class, ['transaction_id' => $own->id, 'total' => 0])->assertHasErrors();

        $this->assertSame('Tienda', $foreign->refresh()->description);
        $this->assertSame($account->id, (int) $own->refresh()->account_id);
        $this->assertSame('Compra Sirena', $own->description);
    }

    public function test_it_refuses_read_only_tokens_and_split_transactions(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        $transaction = $this->transaction($user, $account);
        $split = $this->transaction($user, $account, ['has_splits' => true]);

        LogerServer::actingAs($this->withScopes($user, self::READ_SCOPES))
            ->tool(UpdateTransactionTool::class, ['transaction_id' => $transaction->id, 'description' => 'Otra'])
            ->assertHasErrors(['read-only']);

        $this->server($user)
            ->tool(UpdateTransactionTool::class, ['transaction_id' => $split->id, 'category_id' => $this->category($team, $group, 'Otros')->id])
            ->assertHasErrors(['split across categories']);

        $this->assertSame('Compra Sirena', $transaction->refresh()->description);
        $this->assertNull($split->refresh()->category_id);
    }

    private function server(User $user): mixed
    {
        return LogerServer::actingAs($this->withScopes($user, self::WRITE_SCOPES));
    }

    /**
     * @return array{User, Team, Account, Category}
     */
    private function makeHousehold(string $accountName = 'Popular'): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $group = Category::create(['name' => 'Casa', 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);

        return [$user->fresh(), $team, $this->account($team, $user, $accountName), $group];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function account(Team $team, User $user, string $name, array $attributes = []): Account
    {
        return Account::forceCreate([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => $name,
            'display_id' => str($name)->slug('_')->value(),
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', 'bank')->value('id'),
            'balance_type' => 'DEBIT',
            'type' => 1,
            ...$attributes,
        ]);
    }

    private function category(Team $team, Category $group, string $name): Category
    {
        return Category::create(['name' => $name, 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => 0]);
    }

    /**
     * An expense with its journal lines, like the web saves it.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function transaction(User $user, Account $account, array $attributes = []): Transaction
    {
        $transaction = Transaction::create([
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => '2026-11-03',
            'description' => $account->name === 'Popular' ? 'Compra Sirena' : 'Tienda',
            'total' => 4200,
            'direction' => Transaction::DIRECTION_CREDIT,
            'currency_code' => 'DOP',
            'status' => Transaction::STATUS_VERIFIED,
            ...$attributes,
        ]);
        $transaction->createLines([], $transaction->toArray());

        return $transaction;
    }

    private function anchorLine(Transaction $transaction): TransactionLine
    {
        return TransactionLine::query()->where('transaction_id', $transaction->id)->where('anchor', 1)->sole();
    }

    /**
     * @param  list<string>  $scopes
     */
    private function withScopes(User $user, array $scopes): User
    {
        return $user->withAccessToken(new AccessToken(['oauth_user_id' => $user->id, 'oauth_scopes' => $scopes]));
    }
}
