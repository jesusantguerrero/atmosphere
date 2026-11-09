<?php

namespace Tests\Feature\Mcp;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Mcp\Servers\LogerServer;
use App\Mcp\Tools\CreateTransactionTool;
use App\Mcp\Tools\GetMonthBriefTool;
use App\Mcp\Tools\GetSpendingByCategoryTool;
use App\Mcp\Tools\GetUpcomingPaymentsTool;
use App\Mcp\Tools\ListAccountsAndCategoriesTool;
use App\Mcp\Tools\ListTransactionsTool;
use App\Mcp\Tools\MarkPaymentPaidTool;
use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use App\Providers\PassportServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\AccountDetailType;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LogerMcpServerTest extends TestCase
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

    public function test_http_endpoint_needs_an_oauth_token_not_a_mobile_one(): void
    {
        [$user] = $this->makeHousehold();

        $this->postJson('/mcp', $this->rpc('initialize'))
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate');
        $this->withToken($user->createToken('Mobile')->plainTextToken)->postJson('/mcp', $this->rpc('initialize'))->assertUnauthorized();
        $this->app['auth']->forgetGuards();

        Passport::actingAs($user, self::READ_SCOPES);
        $this->postJson('/mcp', $this->rpc('initialize'))
            ->assertOk()
            ->assertJsonPath('result.serverInfo.name', 'Loger');
        $this->postJson('/mcp', $this->rpc('tools/list'))
            ->assertOk()
            ->assertJsonCount(7, 'result.tools');
    }

    public function test_read_tools_answer_with_this_households_data(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        $food = $this->category($team, $group, 'Supermercado');
        $this->expense($user, $account, $food, '2026-11-03', 4200, 'Sirena');
        $this->budgetTarget($team, $user, $food, 15);

        $server = LogerServer::actingAs($user);
        $server->tool(GetSpendingByCategoryTool::class)->assertOk()->assertSee(['Supermercado', '4200']);
        $server->tool(GetMonthBriefTool::class)->assertOk()->assertSee('"spent_to_date":4200');
        $server->tool(ListTransactionsTool::class, ['search' => 'Sire'])->assertOk()->assertSee(['Sirena', '"total":1']);
        $server->tool(ListAccountsAndCategoriesTool::class)->assertOk()->assertSee(['Popular', 'Supermercado']);
        $server->tool(GetUpcomingPaymentsTool::class, ['days' => 10])->assertOk()->assertSee('budget_'); // 4,200 spent of a 25,000 bill: still pending
        $server->tool(GetSpendingByCategoryTool::class, ['month' => 'noviembre'])->assertHasErrors();
    }

    public function test_upcoming_payments_lists_pending_bills_within_the_window(): void
    {
        [$user, $team, , $group] = $this->makeHousehold();
        $rent = $this->budgetTarget($team, $user, $this->category($team, $group, 'Alquiler'), 12);
        $later = $this->budgetTarget($team, $user, $this->category($team, $group, 'Colegio'), 28);

        LogerServer::actingAs($user)->tool(GetUpcomingPaymentsTool::class)
            ->assertOk()
            ->assertSee("budget_{$rent->id}")
            ->assertDontSee("budget_{$later->id}");
    }

    public function test_read_tools_never_show_another_household(): void
    {
        [$user] = $this->makeHousehold();
        [$stranger, $strangerTeam, $strangerAccount, $strangerGroup] = $this->makeHousehold('Banreservas ajeno');
        $this->expense($stranger, $strangerAccount, $this->category($strangerTeam, $strangerGroup, 'Vinos ajenos'), '2026-11-03', 9900, 'Tienda ajena');

        $server = LogerServer::actingAs($user);
        $server->tool(GetSpendingByCategoryTool::class)->assertOk()->assertDontSee('Vinos ajenos');
        $server->tool(ListTransactionsTool::class)->assertOk()->assertDontSee('Tienda ajena');
        $server->tool(ListAccountsAndCategoriesTool::class)->assertOk()->assertDontSee(['Banreservas ajeno', 'Vinos ajenos']);
    }

    public function test_create_transaction_records_an_expense_once(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        $phone = $this->category($team, $group, 'Teléfono');
        $arguments = ['type' => 'expense', 'amount' => 2400, 'description' => 'Claro internet', 'account_id' => $account->id, 'category_id' => $phone->id, 'payee_name' => 'Claro'];

        $server = LogerServer::actingAs($this->withScopes($user, self::WRITE_SCOPES));
        $server->tool(CreateTransactionTool::class, $arguments)->assertOk()->assertSee(['"duplicate":false', 'Claro internet']);
        $server->tool(CreateTransactionTool::class, $arguments)->assertOk()->assertSee('"duplicate":true');

        $transaction = Transaction::query()->where('team_id', $team->id)->sole();
        $this->assertSame(Transaction::DIRECTION_CREDIT, $transaction->direction);
        $this->assertSame('2026-11-09', substr((string) $transaction->date, 0, 10));
        $this->assertSame($phone->id, (int) $transaction->category_id);
        $this->assertSame('Claro', $transaction->payee?->name);
    }

    public function test_create_transaction_rejects_read_only_tokens_bad_input_and_foreign_ids(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        [, $strangerTeam, $strangerAccount, $strangerGroup] = $this->makeHousehold('Ajena');
        $phone = $this->category($team, $group, 'Teléfono');
        $strangerCategory = $this->category($strangerTeam, $strangerGroup, 'Ajena');
        $valid = ['type' => 'expense', 'amount' => 2400, 'description' => 'Claro', 'account_id' => $account->id, 'category_id' => $phone->id];

        LogerServer::actingAs($this->withScopes($user, self::READ_SCOPES))
            ->tool(CreateTransactionTool::class, $valid)
            ->assertHasErrors(['read-only']);

        $server = LogerServer::actingAs($this->withScopes($user, self::WRITE_SCOPES));
        $server->tool(CreateTransactionTool::class, [...$valid, 'amount' => 0])->assertHasErrors();
        $server->tool(CreateTransactionTool::class, [...$valid, 'category_id' => null])->assertHasErrors(['category_id']);
        $server->tool(CreateTransactionTool::class, [...$valid, 'account_id' => $strangerAccount->id])->assertHasErrors();
        $server->tool(CreateTransactionTool::class, [...$valid, 'category_id' => $strangerCategory->id])->assertHasErrors();

        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_mark_payment_paid_records_the_payment_and_refuses_a_second_time(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        [, $strangerTeam, , $strangerGroup] = $this->makeHousehold('Ajena');
        $rent = $this->budgetTarget($team, $user, $this->category($team, $group, 'Alquiler'), 12);
        $strangerRent = $this->budgetTarget($strangerTeam, $user, $this->category($strangerTeam, $strangerGroup, 'Alquiler'), 12);

        LogerServer::actingAs($this->withScopes($user, self::READ_SCOPES))
            ->tool(MarkPaymentPaidTool::class, ['payment_id' => "budget_{$rent->id}"])
            ->assertHasErrors(['read-only']);

        $server = LogerServer::actingAs($this->withScopes($user, self::WRITE_SCOPES));
        $server->tool(MarkPaymentPaidTool::class, ['payment_id' => "budget_{$strangerRent->id}"])->assertHasErrors(['not pending']);
        $server->tool(MarkPaymentPaidTool::class, ['payment_id' => "budget_{$rent->id}", 'account_id' => $account->id])
            ->assertOk()
            ->assertSee('"paid":true');
        $server->tool(MarkPaymentPaidTool::class, ['payment_id' => "budget_{$rent->id}", 'account_id' => $account->id])->assertHasErrors(['not pending']);

        $this->assertSame(1, Transaction::query()->where('team_id', $team->id)->where('category_id', $rent->category_id)->count());
        $this->assertSame(0, Transaction::query()->where('team_id', $strangerTeam->id)->count());
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
        $account = Account::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => $accountName,
            'display_id' => str($accountName)->slug('_')->value(),
            'currency_code' => 'DOP',
            'account_detail_type_id' => AccountDetailType::where('name', 'bank')->value('id'),
            'balance_type' => 'DEBIT',
            'type' => 1,
        ]);

        return [$user->fresh(), $team, $account, $group];
    }

    /**
     * @param  list<string>  $scopes
     */
    private function withScopes(User $user, array $scopes): User
    {
        return $user->withAccessToken(new AccessToken(['oauth_user_id' => $user->id, 'oauth_scopes' => $scopes]));
    }

    private function category(Team $team, Category $group, string $name): Category
    {
        return Category::create(['name' => $name, 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => 0]);
    }

    private function expense(User $user, Account $account, Category $category, string $date, float $total, string $description): Transaction
    {
        return Transaction::create([
            'team_id' => $account->team_id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'date' => $date,
            'description' => $description,
            'total' => $total,
            'direction' => Transaction::DIRECTION_CREDIT,
            'currency_code' => 'DOP',
            'status' => 'verified',
        ]);
    }

    private function budgetTarget(Team $team, User $user, Category $category, int $dueDay): BudgetTarget
    {
        return BudgetTarget::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'target_type' => BudgetTarget::TYPE_SPENDING,
            'amount' => 25000,
            'frequency' => 'monthly',
            'frequency_month_date' => $dueDay,
            'notify' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rpc(string $method): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $method === 'initialize'
                ? ['protocolVersion' => '2025-06-18', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'tests', 'version' => '1.0']]
                : (object) [],
        ];
    }
}
