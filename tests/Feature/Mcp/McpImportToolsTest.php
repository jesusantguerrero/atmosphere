<?php

namespace Tests\Feature\Mcp;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Mcp\Servers\LogerServer;
use App\Mcp\Tools\AddPlannerItemsTool;
use App\Mcp\Tools\ImportBankStatementTool;
use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use App\Providers\PassportServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\AccountDetailType;
use Laravel\Passport\AccessToken;
use Tests\TestCase;

/**
 * The two "read the document and send me the rows" tools: a school calendar
 * into the Planner, a BHD statement into the Bandeja.
 */
class McpImportToolsTest extends TestCase
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

    public function test_add_planner_items_creates_one_row_per_event(): void
    {
        [$user, $team] = $this->makeHousehold();

        LogerServer::actingAs($this->withScopes($user, self::WRITE_SCOPES))
            ->tool(AddPlannerItemsTool::class, [
                'source' => 'school',
                'items' => [
                    ['date' => '2026-11-12', 'name' => 'Disfraz de planeta'],
                    ['date' => '2026-11-14', 'name' => 'Combo de pizza', 'total' => 250, 'notes' => 'pro fondo banda'],
                    ['date' => '2026-11-18', 'end_date' => '2026-11-20', 'name' => 'Semana de la ciencia'],
                ],
            ])
            ->assertOk()
            ->assertSee(['"created":3', 'Combo de pizza', '"end_date":"2026-11-20"']);

        $planners = Planner::query()->where('team_id', $team->id)->get();
        $this->assertCount(3, $planners);
        $this->assertSame(['school'], $planners->pluck('source')->unique()->values()->all());
        $pizza = $planners->firstWhere('name', 'Combo de pizza');
        $this->assertSame('250.00', (string) $pizza->total);
        $this->assertSame('2026-11-14', $pizza->end_date->format('Y-m-d'));
        $this->assertSame('2026-11-20', $planners->firstWhere('name', 'Semana de la ciencia')->end_date->format('Y-m-d'));
    }

    public function test_add_planner_items_rejects_read_only_tokens_and_bad_rows(): void
    {
        [$user] = $this->makeHousehold();
        $valid = ['items' => [['date' => '2026-11-12', 'name' => 'Disfraz de planeta']]];

        LogerServer::actingAs($this->withScopes($user, self::READ_SCOPES))
            ->tool(AddPlannerItemsTool::class, $valid)
            ->assertHasErrors(['read-only']);

        $server = LogerServer::actingAs($this->withScopes($user, self::WRITE_SCOPES));
        $server->tool(AddPlannerItemsTool::class, ['items' => []])->assertHasErrors();
        $server->tool(AddPlannerItemsTool::class, ['items' => [['date' => '2026-11-12', 'name' => '']]])->assertHasErrors(['Each row needs a name.']);
        $server->tool(AddPlannerItemsTool::class, ['items' => [['name' => 'Sin fecha']]])->assertHasErrors(['Each row needs a date.']);
        $server->tool(AddPlannerItemsTool::class, ['items' => [['date' => '12/11/2026', 'name' => 'Fecha local']]])->assertHasErrors();
        $server->tool(AddPlannerItemsTool::class, ['items' => [['date' => '2026-11-12', 'end_date' => '2026-11-10', 'name' => 'Al revés']]])->assertHasErrors();
        $server->tool(AddPlannerItemsTool::class, ['items' => [['date' => '2026-11-12', 'name' => 'Negativo', 'total' => -5]]])->assertHasErrors();
        $server->tool(AddPlannerItemsTool::class, ['items' => array_fill(0, 101, ['date' => '2026-11-12', 'name' => 'Muchos'])])->assertHasErrors();

        $this->assertSame(0, Planner::query()->count());
    }

    public function test_import_bank_statement_creates_drafts_and_skips_them_the_second_time(): void
    {
        [$user, , $account] = $this->makeHousehold();
        $arguments = ['account_id' => $account->id, 'rows' => [
            ['date' => '2026-11-02', 'reference' => '100200', 'description' => 'SUPERMERCADO NACIONAL', 'debit' => 1500, 'credit' => 0],
            ['date' => '2026-11-03', 'reference' => '100201', 'description' => 'PAGO NOMINA', 'debit' => 0, 'credit' => 30000],
            ['date' => '2026-11-04', 'description' => 'Compra Amazon', 'debit' => 25.5, 'currency' => 'usd'],
        ]];

        $server = LogerServer::actingAs($this->withScopes($user, self::WRITE_SCOPES));
        $server->tool(ImportBankStatementTool::class, $arguments)
            ->assertOk()
            ->assertSee(['"found":3', '"imported":3', '"skipped":0', '"status":"draft"']);

        $drafts = Transaction::query()->where('account_id', $account->id)->where('status', Transaction::STATUS_DRAFT)->get();
        $this->assertCount(3, $drafts);
        $this->assertSame(Transaction::DIRECTION_CREDIT, $drafts->firstWhere('reference', '100200')->direction);
        $this->assertSame(Transaction::DIRECTION_DEBIT, $drafts->firstWhere('reference', '100201')->direction);
        $this->assertSame('USD', $drafts->firstWhere('description', 'Compra Amazon')->currency_code);

        $server->tool(ImportBankStatementTool::class, $arguments)
            ->assertOk()
            ->assertSee(['"found":3', '"imported":0', '"skipped":3']);
        $this->assertSame(3, Transaction::query()->where('account_id', $account->id)->count());
    }

    public function test_a_card_payment_row_becomes_a_transfer_to_the_card(): void
    {
        [$user, $team, $account] = $this->makeHousehold();
        $card = $this->account($team, $user, 'Qik Visa Clasica', ['number' => '3861', 'credit_closing_day' => 5]);

        LogerServer::actingAs($this->withScopes($user, self::WRITE_SCOPES))
            ->tool(ImportBankStatementTool::class, ['account_id' => $account->id, 'rows' => [
                ['date' => '2026-11-05', 'reference' => '100300', 'description' => 'Pago TC 5412****3861', 'debit' => 12000, 'credit' => 0],
            ]])
            ->assertOk()
            ->assertSee('"imported":1');

        $payment = Transaction::query()->where('account_id', $account->id)->sole();
        $this->assertSame($card->id, (int) $payment->counter_account_id);
        $this->assertNull($payment->category_id);
    }

    public function test_import_bank_statement_rejects_read_only_tokens_foreign_accounts_and_bad_rows(): void
    {
        [$user, , $account] = $this->makeHousehold();
        [, , $strangerAccount] = $this->makeHousehold('Ajena');
        $row = ['date' => '2026-11-02', 'reference' => '1', 'description' => 'Compra', 'debit' => 100, 'credit' => 0];

        LogerServer::actingAs($this->withScopes($user, self::READ_SCOPES))
            ->tool(ImportBankStatementTool::class, ['account_id' => $account->id, 'rows' => [$row]])
            ->assertHasErrors(['read-only']);

        $server = LogerServer::actingAs($this->withScopes($user, self::WRITE_SCOPES));
        $server->tool(ImportBankStatementTool::class, ['account_id' => $strangerAccount->id, 'rows' => [$row]])->assertHasErrors(['not an open account of this household']);
        $server->tool(ImportBankStatementTool::class, ['account_id' => $account->id, 'rows' => []])->assertHasErrors();
        $server->tool(ImportBankStatementTool::class, ['account_id' => $account->id, 'rows' => [[...$row, 'date' => '02/11/2026']]])->assertHasErrors();
        $server->tool(ImportBankStatementTool::class, ['account_id' => $account->id, 'rows' => [[...$row, 'debit' => 0]]])->assertHasErrors(['either a debit']);
        $server->tool(ImportBankStatementTool::class, ['account_id' => $account->id, 'rows' => [[...$row, 'credit' => 100]]])->assertHasErrors(['either a debit']);
        $server->tool(ImportBankStatementTool::class, ['account_id' => $account->id, 'rows' => array_fill(0, 501, $row)])->assertHasErrors(['at most 500']);

        $this->assertSame(0, Transaction::query()->count());
    }

    /**
     * @return array{User, Team, Account}
     */
    private function makeHousehold(string $accountName = 'Personal'): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user->fresh(), $team, $this->account($team, $user, $accountName)];
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

    /**
     * @param  list<string>  $scopes
     */
    private function withScopes(User $user, array $scopes): User
    {
        return $user->withAccessToken(new AccessToken(['oauth_user_id' => $user->id, 'oauth_scopes' => $scopes]));
    }
}
