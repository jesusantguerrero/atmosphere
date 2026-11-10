<?php

namespace Tests\Feature;

use App\Domains\AppCore\Models\Category;
use App\Events\ShoppingListItemUpdated;
use App\Models\Account;
use App\Models\Setting;
use App\Models\User;
use App\Services\ShoppingListReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Insane\Journal\Models\Core\Payee;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

class ShoppingSplitTripTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([ShoppingListItemUpdated::class]);
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->plan = app(PlanService::class)->createPlanBoard($this->user->currentTeam, PlanTypes::SHOPPING_LIST, 'Compra');
    }

    public function test_continuing_at_another_store_keeps_the_list_and_opens_a_session_with_what_is_left(): void
    {
        [$milk, $bread, $rice] = $this->items('Leche', 'Pan', 'Arroz');
        $reviews = app(ShoppingListReviewService::class);
        $reviews->markReviewed($this->plan, $bread);
        $reviews->saveItemDetails($this->plan, $bread, '2', 'integral');
        $store = $this->store('Bravo');
        $first = $this->start();
        $this->buy($milk);

        $response = $this->postJson($this->base().'/sessions/finish', ['session_id' => $first['id'], 'total' => '300', 'currency' => 'DOP', 'payee_id' => $store->id, 'continue' => true])
            ->assertOk()
            ->assertJsonPath('visit.id', $first['id'])
            ->assertJsonPath('visit.trip_id', $first['id'])
            ->assertJsonPath('visit.total', '300.00')
            ->assertJsonPath('visit.payee.name', 'Bravo')
            ->assertJsonPath('visit.purchased_items.0.id', $milk->id)
            ->assertJsonCount(2, 'visit.remaining_items')
            ->assertJsonPath('plan.session.trip_id', $first['id'])
            ->assertJsonPath('plan.session.continues_visit_id', $first['id'])
            ->assertJsonPath('plan.session.purchased_ids', [])
            ->assertJsonCount(1, 'plan.visits');

        $second = $response->json('plan.session');
        $this->assertNotSame($first['id'], $second['id']);
        $this->assertEqualsCanonicalizing([$bread->id, $rice->id], array_column($second['items'], 'id'));
        $this->assertDatabaseHas('plan_items', ['id' => $milk->id, 'state' => 'buy']);
        $this->assertSame([$bread->id], $reviews->status($this->plan)['reviewed_ids']);
        $this->assertSame(['quantity' => '2', 'note' => 'integral'], $reviews->status($this->plan)['item_details'][$bread->id]);
        $this->postJson($this->base().'/sessions/start')->assertOk()->assertJsonPath('plan.session.id', $second['id']);

        $this->buy($bread);
        $this->postJson($this->base().'/sessions/finish', ['session_id' => $second['id'], 'total' => '200', 'currency' => 'DOP'])
            ->assertOk()
            ->assertJsonPath('visit.trip_id', $first['id'])
            ->assertJsonPath('visit.purchased_items.0.id', $bread->id)
            ->assertJsonPath('visit.remaining_items.0.id', $rice->id)
            ->assertJsonPath('plan.session', null)
            ->assertJsonCount(2, 'plan.visits');

        foreach ([$milk, $bread, $rice] as $item) {
            $this->assertDatabaseHas('plan_items', ['id' => $item->id, 'state' => 'pending']);
        }
        $this->assertSame([], $reviews->status($this->plan)['reviewed_ids']);
        $this->assertSame([], $reviews->status($this->plan)['item_details']);
    }

    public function test_a_normal_finish_still_resets_the_whole_list(): void
    {
        [$milk, $bread] = $this->items('Leche', 'Pan');
        $session = $this->start();
        $this->buy($milk);

        $this->postJson($this->base().'/sessions/finish', ['session_id' => $session['id'], 'total' => '120', 'currency' => 'DOP', 'continue' => false])
            ->assertOk()
            ->assertJsonPath('visit.trip_id', $session['id'])
            ->assertJsonPath('plan.session', null);

        $this->assertDatabaseHas('plan_items', ['id' => $milk->id, 'state' => 'pending']);
        $this->assertDatabaseHas('plan_items', ['id' => $bread->id, 'state' => 'pending']);
    }

    public function test_continuing_needs_items_left_to_buy(): void
    {
        [$milk] = $this->items('Leche');
        $session = $this->start();
        $this->buy($milk);

        $this->postJson($this->base().'/sessions/finish', ['session_id' => $session['id'], 'total' => '50', 'currency' => 'DOP', 'continue' => true])
            ->assertUnprocessable();
        $this->postJson($this->base().'/sessions/finish', ['session_id' => $session['id'], 'total' => '50', 'currency' => 'DOP', 'continue' => 'tal vez'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('continue');

        $this->getJson($this->base().'/review')->assertOk()->assertJsonPath('plan.session.id', $session['id'])->assertJsonCount(0, 'plan.visits');
    }

    public function test_a_visit_can_be_split_into_two_with_their_own_totals_and_stores(): void
    {
        [$milk, $bread, $rice] = $this->items('Leche', 'Pan', 'Arroz');
        $first = $this->store('Bravo');
        $second = $this->store('La Sirena');
        $visit = $this->finishedVisit([$milk, $bread, $rice], '500.50', $first);

        $response = $this->postJson($this->base().'/visits/'.$visit['id'].'/split', ['item_ids' => [$rice->id], 'total' => '200.25', 'payee_id' => $second->id, 'note' => 'Segunda tienda'])
            ->assertOk()
            ->assertJsonPath('visit.id', $visit['id'])
            ->assertJsonPath('visit.total', '300.25')
            ->assertJsonPath('visit.trip_id', $visit['id'])
            ->assertJsonPath('visit.payee.name', 'Bravo')
            ->assertJsonCount(2, 'visit.purchased_items')
            ->assertJsonPath('split_visit.total', '200.25')
            ->assertJsonPath('split_visit.trip_id', $visit['id'])
            ->assertJsonPath('split_visit.split_from', $visit['id'])
            ->assertJsonPath('split_visit.payee.name', 'La Sirena')
            ->assertJsonPath('split_visit.note', 'Segunda tienda')
            ->assertJsonPath('split_visit.purchased_items.0.id', $rice->id)
            ->assertJsonPath('split_visit.remaining_items', [])
            ->assertJsonPath('split_visit.completed_at', $visit['completed_at'])
            ->assertJsonCount(2, 'plan.visits');

        $this->assertTrue(Str::isUuid($response->json('split_visit.id')));
        $this->assertNotSame($visit['id'], $response->json('split_visit.id'));
        $this->assertNotContains($rice->id, array_column($response->json('visit.purchased_items'), 'id'));

        $this->postJson($this->base().'/visits/'.$response->json('split_visit.id').'/split', ['item_ids' => [$rice->id], 'total' => '10'])
            ->assertUnprocessable();
    }

    public function test_a_split_is_rejected_when_the_items_or_the_total_do_not_fit_the_visit(): void
    {
        [$milk, $bread] = $this->items('Leche', 'Pan');
        [$foreign] = $this->items('Ajeno');
        $foreign->update(['state' => 'skip']);
        $visit = $this->finishedVisit([$milk, $bread], '400');
        $url = $this->base().'/visits/'.$visit['id'].'/split';

        $this->postJson($url, ['item_ids' => [$bread->id], 'total' => '400'])->assertUnprocessable();
        $this->postJson($url, ['item_ids' => [$bread->id], 'total' => '450'])->assertUnprocessable();
        $this->postJson($url, ['item_ids' => [$bread->id], 'total' => '0'])->assertUnprocessable()->assertJsonValidationErrors('total');
        $this->postJson($url, ['item_ids' => [], 'total' => '100'])->assertUnprocessable()->assertJsonValidationErrors('item_ids');
        $this->postJson($url, ['item_ids' => [$milk->id, $bread->id], 'total' => '100'])->assertUnprocessable();
        $this->postJson($url, ['item_ids' => [$foreign->id], 'total' => '100'])->assertUnprocessable();
        $this->postJson($url, ['item_ids' => [$bread->id], 'total' => '100', 'payee_id' => $this->foreignStore()->id])->assertUnprocessable()->assertJsonValidationErrors('payee_id');
        $this->postJson($this->base().'/visits/'.Str::uuid().'/split', ['item_ids' => [$bread->id], 'total' => '100'])->assertNotFound();

        $this->assertSame('400.00', app(ShoppingListReviewService::class)->visit($this->plan, $visit['id'])['total']);
        $this->assertCount(1, app(ShoppingListReviewService::class)->visits($this->plan));
    }

    public function test_a_trip_paid_in_cash_records_the_expense_and_links_it_to_each_visit(): void
    {
        [$milk, $bread] = $this->items('Leche', 'Pan');
        $store = $this->store('Bravo');
        $cash = $this->account($this->plan->team_id, 'Efectivo');
        $groceries = $this->category($this->plan->team_id, 'Supermercado');
        Setting::create(['team_id' => $this->plan->team_id, 'user_id' => $this->user->id, 'name' => 'team_timezone', 'value' => 'Pacific/Kiritimati']);
        $first = $this->start();
        $this->buy($milk);
        $payload = ['session_id' => $first['id'], 'total' => '350.50', 'currency' => 'DOP', 'payee_id' => $store->id, 'account_id' => $cash->id, 'category_id' => $groceries->id, 'continue' => true];

        $visit = $this->postJson($this->base().'/sessions/finish', $payload)->assertOk()->json('visit');

        $this->assertNotNull($visit['transaction_id']);
        $this->assertDatabaseHas('transactions', ['id' => $visit['transaction_id'], 'team_id' => $this->plan->team_id, 'account_id' => $cash->id, 'category_id' => $groceries->id, 'payee_id' => $store->id, 'total' => 350.50, 'direction' => 'WITHDRAW', 'status' => 'verified', 'description' => 'Compra · Compra', 'date' => now('Pacific/Kiritimati')->format('Y-m-d')]);
        $this->postJson($this->base().'/sessions/finish', $payload)->assertOk()->assertJsonPath('visit.transaction_id', $visit['transaction_id']);
        $this->assertDatabaseCount('transactions', 1);

        $second = $this->postJson($this->base().'/sessions/start')->assertOk()->json('plan.session');
        $this->buy($bread);
        $last = $this->postJson($this->base().'/sessions/finish', ['session_id' => $second['id'], 'total' => '120', 'currency' => 'DOP', 'account_id' => $cash->id])->assertOk()->json('visit');

        $this->assertNotSame($visit['transaction_id'], $last['transaction_id']);
        $this->assertDatabaseHas('transactions', ['id' => $last['transaction_id'], 'account_id' => $cash->id, 'category_id' => null, 'payee_id' => null, 'total' => 120, 'direction' => 'WITHDRAW']);
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_a_cash_payment_on_another_household_account_or_category_is_rejected(): void
    {
        [$milk] = $this->items('Leche');
        $other = User::factory()->withPersonalTeam()->create();
        $foreignAccount = $this->account($other->ownedTeams()->first()->id, 'Ajena');
        $foreignCategory = $this->category($other->ownedTeams()->first()->id, 'Ajena');
        $session = $this->start();
        $this->buy($milk);
        $payload = ['session_id' => $session['id'], 'total' => '100', 'currency' => 'DOP'];

        $this->postJson($this->base().'/sessions/finish', [...$payload, 'account_id' => $foreignAccount->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('account_id');
        $this->postJson($this->base().'/sessions/finish', [...$payload, 'account_id' => $this->account($this->plan->team_id, 'Efectivo')->id, 'category_id' => $foreignCategory->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');

        $this->assertDatabaseCount('transactions', 0);
        $this->getJson($this->base().'/review')->assertOk()->assertJsonPath('plan.session.id', $session['id'])->assertJsonCount(0, 'plan.visits');
    }

    public function test_a_trip_paid_by_card_creates_no_expense(): void
    {
        [$milk] = $this->items('Leche');
        $visit = $this->finishedVisit([$milk], '200', $this->store('Bravo'));

        $this->assertArrayNotHasKey('transaction_id', $visit);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_another_team_cannot_split_a_visit(): void
    {
        [$milk, $bread] = $this->items('Leche', 'Pan');
        $visit = $this->finishedVisit([$milk, $bread], '400');

        $this->actingAs(User::factory()->withPersonalTeam()->create())
            ->postJson($this->base().'/visits/'.$visit['id'].'/split', ['item_ids' => [$bread->id], 'total' => '100'])
            ->assertForbidden();
        $this->actingAs(User::factory()->withPersonalTeam()->create())
            ->postJson($this->base().'/sessions/finish', ['session_id' => (string) Str::uuid(), 'total' => '10', 'currency' => 'DOP', 'continue' => true])
            ->assertForbidden();

        $this->assertSame('400.00', app(ShoppingListReviewService::class)->visit($this->plan, $visit['id'])['total']);
    }

    private function base(): string
    {
        return '/shopping/'.$this->plan->id;
    }

    /** @return array<int, PlanItem> */
    private function items(string ...$titles): array
    {
        $stage = $this->plan->stages()->first();

        return array_map(fn (string $title) => PlanItem::create(['plan_id' => $this->plan->id, 'team_id' => $this->plan->team_id, 'user_id' => $this->user->id, 'stage_id' => $stage->id, 'title' => $title, 'state' => 'pending', 'order' => 1]), $titles);
    }

    private function store(string $name): Payee
    {
        return Payee::create(['team_id' => $this->plan->team_id, 'user_id' => $this->user->id, 'name' => $name]);
    }

    private function account(int $teamId, string $name): Account
    {
        return Account::create(['team_id' => $teamId, 'user_id' => $this->user->id, 'name' => $name, 'currency_code' => 'DOP', 'balance_type' => 'DEBIT', 'type' => 1]);
    }

    private function category(int $teamId, string $name): Category
    {
        return Category::create(['name' => $name, 'team_id' => $teamId, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
    }

    private function foreignStore(): Payee
    {
        $other = User::factory()->withPersonalTeam()->create();

        return Payee::create(['team_id' => $other->ownedTeams()->first()->id, 'user_id' => $other->id, 'name' => 'Ajena']);
    }

    private function start(): array
    {
        return $this->actingAs($this->user)->postJson($this->base().'/sessions/start')->assertOk()->json('plan.session');
    }

    private function buy(PlanItem $item): void
    {
        $this->postJson($this->base().'/items/'.$item->id.'/cycle', ['state' => 'buy'])->assertOk();
    }

    /** @param  array<int, PlanItem>  $items */
    private function finishedVisit(array $items, string $total, ?Payee $store = null): array
    {
        $session = $this->start();
        foreach ($items as $item) {
            $this->buy($item);
        }

        return $this->postJson($this->base().'/sessions/finish', ['session_id' => $session['id'], 'total' => $total, 'currency' => 'DOP', ...($store ? ['payee_id' => $store->id] : [])])->assertOk()->json('visit');
    }
}
