<?php

namespace Tests\Feature\Mcp;

use App\Domains\AppCore\Models\Category;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Mcp\Servers\LogerServer;
use App\Mcp\Tools\CreatePlannedTransactionTool;
use App\Mcp\Tools\GetUpcomingPaymentsTool;
use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use App\Providers\PassportServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Payee;
use Laravel\Passport\AccessToken;
use Tests\TestCase;

/**
 * Scheduling a recurring bill from Claude: «la terapia, RD$12,000 cada 4 semanas desde el 1/12».
 */
class McpCreatePlannedTransactionTest extends TestCase
{
    use RefreshDatabase;

    private const READ_SCOPES = [PassportServiceProvider::READ_SCOPE];

    private const WRITE_SCOPES = [PassportServiceProvider::READ_SCOPE, PassportServiceProvider::WRITE_SCOPE];

    protected function setUp(): void
    {
        parent::setUp();
        app(AccountDetailTypesCreate::class)->create();
        Carbon::setTestNow('2026-11-10 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_schedules_a_payment_every_four_weeks_and_lists_it_as_upcoming(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        $therapy = $this->category($team, $group, 'Terapia');

        $server = $this->server($user);
        $server->tool(CreatePlannedTransactionTool::class, [
            'description' => 'Terapia Sofía',
            'total' => 12000,
            'date' => '2026-12-01',
            'frequency' => 'WEEK',
            'frequency_interval' => 4,
            'account_id' => $account->id,
            'category_id' => $therapy->id,
            'payee_name' => 'Centro Crecer',
        ])
            ->assertOk()
            ->assertSee([
                '"next_dates":["2026-12-01","2026-12-29","2027-01-26"]',
                '"frequency":"WEEK"',
                '"frequency_interval":4',
                '"total":12000',
                '"type":"expense"',
                '"account":"Popular"',
                '"category":"Terapia"',
                '"payee":"Centro Crecer"',
                '"currency_code":"DOP"',
                '"duplicate":false',
            ]);

        $planned = Transaction::query()->where('team_id', $team->id)->where('status', Transaction::STATUS_PLANNED)->sole();
        $this->assertSame('2026-12-01', substr((string) $planned->date, 0, 10));
        $this->assertSame(Transaction::DIRECTION_CREDIT, $planned->direction);
        $this->assertSame('WEEK', $planned->schedule->frequency);
        $this->assertSame(4, $planned->schedule->frequency_interval);
        $this->assertNull($planned->schedule->end_date);

        $server->tool(GetUpcomingPaymentsTool::class, ['days' => 30])
            ->assertOk()
            ->assertSee(["planned_{$planned->id}", '"due_date":"2026-12-01"']);
    }

    public function test_it_stops_the_next_dates_at_the_end_date_and_does_not_duplicate_a_retry(): void
    {
        [$user, $team, $account] = $this->makeHousehold();
        $arguments = [
            'description' => 'Colegio',
            'total' => 8500.5,
            'date' => '2026-12-15',
            'frequency' => 'MONTHLY',
            'frequency_interval' => 2,
            'end_date' => '2027-03-01',
            'account_id' => $account->id,
            'direction' => 'income',
        ];

        $server = $this->server($user);
        $server->tool(CreatePlannedTransactionTool::class, $arguments)
            ->assertOk()
            ->assertSee(['"next_dates":["2026-12-15","2027-02-15"]', '"end_date":"2027-03-01"', '"type":"income"', '"total":8500.5']);
        $server->tool(CreatePlannedTransactionTool::class, $arguments)->assertOk()->assertSee('"duplicate":true');

        $this->assertSame(1, Transaction::query()->where('team_id', $team->id)->where('status', Transaction::STATUS_PLANNED)->count());
    }

    public function test_it_does_not_overwrite_another_plan_of_the_same_category(): void
    {
        [$user, $team, $account, $group] = $this->makeHousehold();
        $health = $this->category($team, $group, 'Salud');
        $base = ['total' => 3000, 'date' => '2026-12-01', 'frequency' => 'MONTHLY', 'account_id' => $account->id, 'category_id' => $health->id];

        $server = $this->server($user);
        $server->tool(CreatePlannedTransactionTool::class, [...$base, 'description' => 'Seguro'])->assertOk();
        $server->tool(CreatePlannedTransactionTool::class, [...$base, 'description' => 'Terapia', 'frequency' => 'WEEK', 'frequency_interval' => 4])->assertOk();

        $this->assertEqualsCanonicalizing(
            ['Seguro', 'Terapia'],
            Transaction::query()->where('team_id', $team->id)->where('status', Transaction::STATUS_PLANNED)->pluck('description')->all(),
        );
    }

    public function test_it_refuses_read_only_tokens(): void
    {
        [$user, $team, $account] = $this->makeHousehold();

        LogerServer::actingAs($this->withScopes($user, self::READ_SCOPES))
            ->tool(CreatePlannedTransactionTool::class, ['description' => 'Terapia', 'total' => 12000, 'date' => '2026-12-01', 'frequency' => 'WEEK', 'account_id' => $account->id])
            ->assertHasErrors(['read-only']);

        $this->assertSame(0, Transaction::query()->where('team_id', $team->id)->count());
    }

    public function test_it_refuses_another_households_account_category_or_payee(): void
    {
        [$user, $team, $account] = $this->makeHousehold();
        [$stranger, $strangerTeam, $strangerAccount, $strangerGroup] = $this->makeHousehold('Ajena');
        $strangerCategory = $this->category($strangerTeam, $strangerGroup, 'Vinos ajenos');
        $strangerPayee = Payee::create(['name' => 'Ajeno', 'team_id' => $strangerTeam->id, 'user_id' => $stranger->id]);
        $base = ['description' => 'Terapia', 'total' => 12000, 'date' => '2026-12-01', 'frequency' => 'WEEK', 'account_id' => $account->id];

        $server = $this->server($user);
        $server->tool(CreatePlannedTransactionTool::class, [...$base, 'account_id' => $strangerAccount->id])->assertHasErrors(['not an open account of this household']);
        $server->tool(CreatePlannedTransactionTool::class, [...$base, 'category_id' => $strangerCategory->id])->assertHasErrors(['not a budget category of this household']);
        $server->tool(CreatePlannedTransactionTool::class, [...$base, 'payee_id' => $strangerPayee->id])->assertHasErrors(['not in this household']);

        $this->assertSame(0, Transaction::query()->whereIn('team_id', [$team->id, $strangerTeam->id])->count());
    }

    public function test_it_refuses_an_interval_outside_one_to_fifty_two_and_unknown_frequencies(): void
    {
        [$user, $team, $account] = $this->makeHousehold();
        $base = ['description' => 'Terapia', 'total' => 12000, 'date' => '2026-12-01', 'frequency' => 'WEEK', 'account_id' => $account->id];

        $server = $this->server($user);
        $server->tool(CreatePlannedTransactionTool::class, [...$base, 'frequency_interval' => 0])->assertHasErrors(['between 1 and 52']);
        $server->tool(CreatePlannedTransactionTool::class, [...$base, 'frequency_interval' => 53])->assertHasErrors(['between 1 and 52']);
        $server->tool(CreatePlannedTransactionTool::class, [...$base, 'frequency' => 'YEARLY'])->assertHasErrors(['DAILY, WEEK_DAY, WEEK or MONTHLY']);
        $server->tool(CreatePlannedTransactionTool::class, [...$base, 'total' => 0])->assertHasErrors();
        $server->tool(CreatePlannedTransactionTool::class, [...$base, 'end_date' => '2026-11-01'])->assertHasErrors();

        $this->assertSame(0, Transaction::query()->where('team_id', $team->id)->count());
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
        $account = Account::forceCreate([
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

    private function category(Team $team, Category $group, string $name): Category
    {
        return Category::create(['name' => $name, 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => 0]);
    }

    /**
     * @param  list<string>  $scopes
     */
    private function withScopes(User $user, array $scopes): User
    {
        return $user->withAccessToken(new AccessToken(['oauth_user_id' => $user->id, 'oauth_scopes' => $scopes]));
    }
}
