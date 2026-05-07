<?php

namespace Tests\Feature\System;

use App\Events\ShoppingListItemUpdated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

/**
 * Shopping list chat-style UI — covers the data path that backs both the
 * authed `/shopping` page and the public `/shared/list/{token}` view. The
 * chat-style frontend is exercised end-to-end via Cypress; here we lock down
 * the API contract + 3-state semantics + Mercure broadcast.
 */
class ShoppingListChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mercure broadcast is end-to-end tested by Cypress. Faking the event
        // here keeps the test from touching the real hub during HTTP requests
        // and lets the assertion-style tests below re-fake to count dispatches.
        Event::fake([ShoppingListItemUpdated::class]);
    }

    private function makeShoppingList(User $user): Plan
    {
        $service = app(PlanService::class);

        $plan = $service->createPlanBoard(
            $user->currentTeam,
            PlanTypes::SHOPPING_LIST,
            'Weekly groceries',
        );

        return $plan->fresh(['stages.items']);
    }

    private function addItem(Plan $plan, string $title): PlanItem
    {
        $stage = $plan->stages->first();

        return PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'user_id' => $plan->user_id,
            'stage_id' => $stage->id,
            'title' => $title,
            'state' => PlanItem::STATE_PENDING,
            'order' => 1,
        ]);
    }

    public function test_authed_index_renders_chat_view_with_payload(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/shopping')
            ->assertOk();
    }

    public function test_cycle_advances_pending_to_buy_to_skip_to_pending(): void
    {
        Event::fake([ShoppingListItemUpdated::class]);

        $user = User::factory()->withPersonalTeam()->create();
        $plan = $this->makeShoppingList($user);
        $item = $this->addItem($plan, 'Pan');

        $this->actingAs($user)
            ->postJson("/shopping/{$plan->id}/items/{$item->id}/cycle")
            ->assertOk()
            ->assertJsonPath('state', PlanItem::STATE_BUY);

        $this->actingAs($user)
            ->postJson("/shopping/{$plan->id}/items/{$item->id}/cycle")
            ->assertOk()
            ->assertJsonPath('state', PlanItem::STATE_SKIP);

        $this->actingAs($user)
            ->postJson("/shopping/{$plan->id}/items/{$item->id}/cycle")
            ->assertOk()
            ->assertJsonPath('state', PlanItem::STATE_PENDING);

        Event::assertDispatched(ShoppingListItemUpdated::class, 3);
    }

    public function test_state_buy_syncs_is_done_true(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $plan = $this->makeShoppingList($user);
        $item = $this->addItem($plan, 'Salami');

        $this->actingAs($user)
            ->postJson("/shopping/{$plan->id}/items/{$item->id}/cycle", ['state' => 'buy'])
            ->assertOk();

        $this->assertSame(PlanItem::STATE_BUY, $item->fresh()->state);
        $this->assertTrue((bool) $item->fresh()->is_done);
    }

    public function test_add_item_creates_pending_row_at_end(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $plan = $this->makeShoppingList($user);
        $this->addItem($plan, 'Existing');

        $this->actingAs($user)
            ->postJson("/shopping/{$plan->id}/items", ['title' => 'Wiper'])
            ->assertCreated()
            ->assertJsonPath('title', 'Wiper')
            ->assertJsonPath('state', PlanItem::STATE_PENDING);

        $this->assertSame(2, PlanItem::where('plan_id', $plan->id)->count());
    }

    public function test_reset_returns_every_item_to_pending(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $plan = $this->makeShoppingList($user);
        $a = $this->addItem($plan, 'Confle');
        $b = $this->addItem($plan, 'Juguito');

        $a->setState(PlanItem::STATE_BUY);
        $b->setState(PlanItem::STATE_SKIP);

        $this->actingAs($user)
            ->postJson("/shopping/{$plan->id}/reset")
            ->assertOk();

        $this->assertSame(PlanItem::STATE_PENDING, $a->fresh()->state);
        $this->assertSame(PlanItem::STATE_PENDING, $b->fresh()->state);
        $this->assertFalse((bool) $a->fresh()->is_done);
    }

    public function test_destroy_item_removes_row_and_broadcasts(): void
    {
        Event::fake([ShoppingListItemUpdated::class]);

        $user = User::factory()->withPersonalTeam()->create();
        $plan = $this->makeShoppingList($user);
        $item = $this->addItem($plan, 'Old item');

        $this->actingAs($user)
            ->deleteJson("/shopping/{$plan->id}/items/{$item->id}")
            ->assertOk();

        $this->assertNull(PlanItem::find($item->id));
        Event::assertDispatched(
            ShoppingListItemUpdated::class,
            fn ($e) => $e->action === 'deleted'
        );
    }

    public function test_authed_endpoints_block_other_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $stranger = User::factory()->withPersonalTeam()->create();
        $plan = $this->makeShoppingList($owner);
        $item = $this->addItem($plan, 'Salami');

        $this->actingAs($stranger)
            ->postJson("/shopping/{$plan->id}/items/{$item->id}/cycle")
            ->assertForbidden();

        $this->actingAs($stranger)
            ->postJson("/shopping/{$plan->id}/items", ['title' => 'malicious'])
            ->assertForbidden();

        $this->actingAs($stranger)
            ->postJson("/shopping/{$plan->id}/reset")
            ->assertForbidden();
    }

    public function test_toggle_share_mints_token_then_revokes(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $plan = $this->makeShoppingList($user);

        $response = $this->actingAs($user)
            ->postJson("/shopping/{$plan->id}/share")
            ->assertOk()
            ->assertJsonPath('shared', true);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertSame($token, $plan->fresh()->share_token);

        $this->actingAs($user)
            ->postJson("/shopping/{$plan->id}/share")
            ->assertOk()
            ->assertJsonPath('shared', false);

        $this->assertNull($plan->fresh()->share_token);
    }

    public function test_public_share_endpoints_work_without_auth(): void
    {
        Event::fake([ShoppingListItemUpdated::class]);

        $user = User::factory()->withPersonalTeam()->create();
        $plan = $this->makeShoppingList($user);
        $item = $this->addItem($plan, 'Pizza');

        $this->actingAs($user)->postJson("/shopping/{$plan->id}/share")->assertOk();
        $token = $plan->fresh()->share_token;

        // No actingAs — anyone with the link should be able to mutate.
        $this->postJson("/shared/list/{$token}/toggle/{$item->id}", ['state' => 'buy'])
            ->assertOk()
            ->assertJsonPath('state', 'buy');

        $this->postJson("/shared/list/{$token}/items", ['title' => 'Wife added this'])
            ->assertCreated()
            ->assertJsonPath('title', 'Wife added this');

        $this->postJson("/shared/list/{$token}/reset")
            ->assertOk()
            ->assertJsonPath('reset', true);
    }

    public function test_public_share_returns_404_for_invalid_token(): void
    {
        $this->postJson('/shared/list/not-a-real-token/reset')
            ->assertNotFound();
    }
}
