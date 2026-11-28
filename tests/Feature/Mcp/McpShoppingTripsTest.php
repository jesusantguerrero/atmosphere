<?php

namespace Tests\Feature\Mcp;

use App\Domains\Journal\Actions\AccountDetailTypesCreate;
use App\Domains\Transaction\Models\Transaction;
use App\Events\ShoppingListItemUpdated;
use App\Mcp\Servers\LogerServer;
use App\Mcp\Tools\ListShoppingTripsTool;
use App\Models\Account;
use App\Models\User;
use App\Providers\PassportServiceProvider;
use App\Services\ShoppingListReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Insane\Journal\Models\Core\AccountDetailType;
use Insane\Journal\Models\Core\Payee;
use Laravel\Passport\AccessToken;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

/**
 * Telling from Claude which supermarket charges were a shopping list trip and which were bought for someone else.
 */
class McpShoppingTripsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([ShoppingListItemUpdated::class]);
        app(AccountDetailTypesCreate::class)->create();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->user->forceFill(['current_team_id' => $this->user->ownedTeams()->first()->id])->save();
        $this->user = $this->user->fresh();
        $this->plan = app(PlanService::class)->createPlanBoard($this->user->currentTeam, PlanTypes::SHOPPING_LIST, 'Compra');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_lists_the_month_trips_with_store_items_and_the_linked_charge(): void
    {
        $store = Payee::create(['team_id' => $this->plan->team_id, 'user_id' => $this->user->id, 'name' => 'La Sirena']);
        Carbon::setTestNow('2026-10-30 15:00:00');
        $this->trip(['Avena'], '900');
        Carbon::setTestNow('2026-11-26 15:00:00');
        $visit = $this->trip(['Leche', 'Pan'], '2098.01', $store);
        $charge = $this->charge(2098.01, '2026-11-27');
        app(ShoppingListReviewService::class)->linkVisit($this->plan, $visit['id'], $charge->id);
        Carbon::setTestNow('2026-11-27 12:00:00');

        $this->server()->tool(ListShoppingTripsTool::class, ['month' => '2026-11'])->assertOk()
            ->assertSee(['"date":"2026-11-26"', '"list":"Compra"', '"store":"La Sirena"', '"total":2098.01', '"items":["Leche","Pan"]', '"transaction_id":'.$charge->id, '"transaction_date":"2026-11-27"'])
            ->assertDontSee('Avena');
    }

    public function test_a_date_range_reaches_back_and_other_households_stay_out(): void
    {
        Carbon::setTestNow('2026-10-30 15:00:00');
        $this->trip(['Arroz'], '450');
        [$me, $myList] = [$this->user, $this->plan];
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->plan = app(PlanService::class)->createPlanBoard($this->user->currentTeam, PlanTypes::SHOPPING_LIST, 'Ajena');
        $this->trip(['Avena'], '900');
        [$this->user, $this->plan] = [$me, $myList];
        Carbon::setTestNow('2026-11-27 12:00:00');

        $this->server()->tool(ListShoppingTripsTool::class, ['start_date' => '2026-10-01', 'end_date' => '2026-10-31'])->assertOk()
            ->assertSee(['"list":"Compra"', '"items":["Arroz"]', '"total":450'])
            ->assertDontSee('Avena');
        $this->server()->tool(ListShoppingTripsTool::class)->assertOk()->assertSee('"trips":[]');
    }

    private function server(): mixed
    {
        return LogerServer::actingAs($this->user->withAccessToken(new AccessToken(['oauth_user_id' => $this->user->id, 'oauth_scopes' => [PassportServiceProvider::READ_SCOPE]])));
    }

    /**
     * @param  array<int, string>  $titles
     * @return array<string, mixed>
     */
    private function trip(array $titles, string $total, ?Payee $store = null): array
    {
        $stage = $this->plan->stages()->first();
        $items = array_map(fn (string $title) => PlanItem::create(['plan_id' => $this->plan->id, 'team_id' => $this->plan->team_id, 'user_id' => $this->user->id, 'stage_id' => $stage->id, 'title' => $title, 'state' => 'pending', 'order' => 1]), $titles);
        $base = '/shopping/'.$this->plan->id;
        $session = $this->actingAs($this->user)->postJson($base.'/sessions/start')->assertOk()->json('plan.session');
        foreach ($items as $item) {
            $this->postJson($base.'/items/'.$item->id.'/cycle', ['state' => 'buy'])->assertOk();
        }

        return $this->postJson($base.'/sessions/finish', ['session_id' => $session['id'], 'total' => $total, 'currency' => 'DOP', ...($store ? ['payee_id' => $store->id] : [])])->assertOk()->json('visit');
    }

    private function charge(float $total, string $date): Transaction
    {
        $account = Account::forceCreate(['team_id' => $this->plan->team_id, 'user_id' => $this->user->id, 'name' => 'APAP Sirena', 'display_id' => 'apap-sirena', 'currency_code' => 'DOP', 'account_detail_type_id' => AccountDetailType::where('name', 'bank')->value('id')]);

        return Transaction::create(['team_id' => $this->plan->team_id, 'user_id' => $this->user->id, 'account_id' => $account->id, 'date' => $date, 'description' => 'Visa Gold Sirena APAP', 'total' => $total, 'direction' => Transaction::DIRECTION_CREDIT, 'currency_code' => 'DOP', 'status' => Transaction::STATUS_VERIFIED]);
    }
}
