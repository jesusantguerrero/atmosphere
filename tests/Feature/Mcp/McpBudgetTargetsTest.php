<?php

namespace Tests\Feature\Mcp;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Mcp\Servers\LogerServer;
use App\Mcp\Tools\ListBudgetTargetsTool;
use App\Mcp\Tools\UpdateBudgetTargetTool;
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
 * Fixing a budget reminder from Claude when the bill changes price, without
 * recording a second payment.
 */
class McpBudgetTargetsTest extends TestCase
{
    use RefreshDatabase;

    private const WRITE_SCOPES = [PassportServiceProvider::READ_SCOPE, PassportServiceProvider::WRITE_SCOPE];

    private User $user;

    private Team $team;

    private Category $group;

    protected function setUp(): void
    {
        parent::setUp();
        app(AccountDetailTypesCreate::class)->create();
        Carbon::setTestNow('2026-11-18 12:00:00');
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->ownedTeams()->first();
        $this->user->forceFill(['current_team_id' => $this->team->id])->save();
        $this->user = $this->user->fresh();
        $this->group = Category::create(['name' => 'Casa', 'team_id' => $this->team->id, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_lists_the_targets_with_the_payment_id_of_their_reminder(): void
    {
        $target = $this->target('Seguro de salud 15th', 5241.60, 15);

        $this->server()->tool(ListBudgetTargetsTool::class)->assertOk()
            ->assertSee(['"target_id":'.$target->id, '"payment_id":"budget_'.$target->id.'"', '"category_name":"Seguro de salud 15th"', '"amount":5241.6', '"frequency_month_date":15', '"notify":true']);
    }

    public function test_raising_the_amount_to_the_paid_bill_settles_this_month(): void
    {
        $target = $this->target('Seguro de salud 15th', 5241.60, 15);
        $this->pay($target->category, 6342.34, '2026-11-13');

        $this->server()->tool(UpdateBudgetTargetTool::class, ['payment_id' => "budget_{$target->id}", 'amount' => 6342.34])->assertOk()
            ->assertSee(['"amount":6342.34', '"this_month":"paid"']);
        $this->assertSame('6342.34', $target->fresh()->amount);

        $this->server()->tool(UpdateBudgetTargetTool::class, ['target_id' => $target->id, 'amount' => 9000, 'frequency_month_date' => 20])->assertOk()
            ->assertSee(['"amount":9000', '"frequency_month_date":20', '"this_month":"pending"']);

        $this->server()->tool(UpdateBudgetTargetTool::class, ['target_id' => $target->id, 'notify' => false])->assertOk()
            ->assertSee('"this_month":"not_reminded"');
    }

    public function test_it_refuses_bad_input_other_households_loans_and_read_only_connections(): void
    {
        $target = $this->target('Internet', 3000, 17);
        $loan = $this->target('Préstamo', 12000, 27, ['target_type' => BudgetTarget::TYPE_LOAN]);
        $other = User::factory()->withPersonalTeam()->create();
        $otherGroup = Category::create(['name' => 'Otra', 'team_id' => $other->ownedTeams()->first()->id, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
        $foreign = BudgetTarget::create(['team_id' => $otherGroup->team_id, 'user_id' => $other->id, 'category_id' => Category::create(['name' => 'Ajena', 'team_id' => $otherGroup->team_id, 'resource_type' => 'transactions', 'parent_id' => $otherGroup->id, 'depth' => 1, 'index' => 0])->id, 'target_type' => BudgetTarget::TYPE_SPENDING, 'amount' => 100, 'frequency' => 'monthly', 'frequency_month_date' => 1, 'notify' => true]);

        $server = $this->server();
        $server->tool(UpdateBudgetTargetTool::class, ['target_id' => $target->id, 'amount' => 0])->assertHasErrors();
        $server->tool(UpdateBudgetTargetTool::class, ['target_id' => $target->id, 'frequency_month_date' => 32])->assertHasErrors();
        $server->tool(UpdateBudgetTargetTool::class, ['target_id' => $target->id])->assertHasErrors(['Nothing to change']);
        $server->tool(UpdateBudgetTargetTool::class, ['payment_id' => 'planned_4', 'amount' => 10])->assertHasErrors();
        $server->tool(UpdateBudgetTargetTool::class, ['target_id' => $foreign->id, 'amount' => 10])->assertHasErrors(['not in this household']);
        $server->tool(UpdateBudgetTargetTool::class, ['target_id' => $loan->id, 'amount' => 10])->assertHasErrors(['loan']);
        LogerServer::actingAs($this->user->withAccessToken(new AccessToken(['oauth_user_id' => $this->user->id, 'oauth_scopes' => [PassportServiceProvider::READ_SCOPE]])))
            ->tool(UpdateBudgetTargetTool::class, ['target_id' => $target->id, 'amount' => 10])->assertHasErrors(['read-only']);

        $this->assertSame('3000.00', $target->fresh()->amount);
        $this->assertSame('100.00', $foreign->fresh()->amount);
        $this->assertSame('12000.00', $loan->fresh()->amount);
    }

    private function server(): mixed
    {
        return LogerServer::actingAs($this->user->withAccessToken(new AccessToken(['oauth_user_id' => $this->user->id, 'oauth_scopes' => self::WRITE_SCOPES])));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function target(string $name, float $amount, int $day, array $attributes = []): BudgetTarget
    {
        $category = Category::create(['name' => $name, 'team_id' => $this->team->id, 'resource_type' => 'transactions', 'parent_id' => $this->group->id, 'depth' => 1, 'index' => 0]);

        return BudgetTarget::create(['team_id' => $this->team->id, 'user_id' => $this->user->id, 'category_id' => $category->id, 'target_type' => BudgetTarget::TYPE_SPENDING, 'amount' => $amount, 'frequency' => 'monthly', 'frequency_month_date' => $day, 'notify' => true, ...$attributes]);
    }

    private function pay(Category $category, float $total, string $date): void
    {
        $account = Account::forceCreate(['team_id' => $this->team->id, 'user_id' => $this->user->id, 'name' => 'Popular', 'display_id' => 'popular', 'currency_code' => 'DOP', 'account_detail_type_id' => AccountDetailType::where('name', 'bank')->value('id')]);
        Transaction::create(['team_id' => $this->team->id, 'user_id' => $this->user->id, 'account_id' => $account->id, 'category_id' => $category->id, 'date' => $date, 'description' => 'Pago', 'total' => $total, 'direction' => Transaction::DIRECTION_CREDIT, 'currency_code' => 'DOP', 'status' => Transaction::STATUS_VERIFIED]);
    }
}
