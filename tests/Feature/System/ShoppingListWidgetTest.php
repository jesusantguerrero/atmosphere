<?php

namespace Tests\Feature\System;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

/**
 * GET /shopping/current is the JSON endpoint feeding the right-side shopping
 * widget. Same payload shape as the inertia page, no share/mercure fields.
 */
class ShoppingListWidgetTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->fresh()->ownedTeams()->latest('id')->first()->id;
        $user->save();

        return $user;
    }

    public function test_widget_endpoint_returns_active_shopping_list_with_items(): void
    {
        $user = $this->makeUser();
        $plan = app(PlanService::class)->getPlanTypeModel($user->current_team_id, PlanTypes::SHOPPING_LIST)
            ?? app(PlanService::class)->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, 'List');

        $stage = $plan->stages->first();
        PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'user_id' => $plan->user_id ?? $user->id,
            'stage_id' => $stage->id,
            'title' => 'milk',
            'state' => PlanItem::STATE_PENDING,
            'order' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/shopping/current');

        $response->assertOk()
            ->assertJsonPath('plan.id', $plan->id)
            ->assertJsonStructure([
                'plan' => ['id', 'name', 'stages' => [['id', 'name', 'items']]],
                'shareUrl',
                'shareToken',
            ]);

        $items = collect($response->json('plan.stages.0.items'));
        $this->assertTrue($items->contains(fn ($i) => $i['title'] === 'milk'));
    }

    public function test_widget_endpoint_includes_share_url_when_list_is_shared(): void
    {
        $user = $this->makeUser();
        $plan = app(PlanService::class)->getPlanTypeModel($user->current_team_id, PlanTypes::SHOPPING_LIST)
            ?? app(PlanService::class)->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, 'List');

        $plan->share_token = 'test-share-token-abc';
        $plan->save();

        $response = $this->actingAs($user)->getJson('/shopping/current');

        $response->assertOk()
            ->assertJsonPath('shareToken', 'test-share-token-abc')
            ->assertJsonPath('shareUrl', route('shared.list', 'test-share-token-abc'));
    }

    public function test_widget_endpoint_returns_a_valid_plan_for_a_fresh_user(): void
    {
        // Fresh users get a seeded shopping list during signup, so the
        // endpoint should always return a valid plan; the resolveActivePlan()
        // fallback ensures it never 500s even if seeding ever changes.
        $user = $this->makeUser();

        $response = $this->actingAs($user)->getJson('/shopping/current');

        $response->assertOk()
            ->assertJsonStructure(['plan' => ['id', 'name', 'stages'], 'shareUrl', 'shareToken']);

        $this->assertIsInt($response->json('plan.id'));
    }

    public function test_widget_endpoint_requires_auth(): void
    {
        $this->getJson('/shopping/current')->assertUnauthorized();
    }

    /**
     * LM-9 regression: cycling an item to "buy" must leave it in the list.
     * Marking-while-shopping is the whole supermarket flow — reset trip is the
     * only thing that hides items.
     */
    public function test_buy_state_items_remain_in_list_payload(): void
    {
        $user = $this->makeUser();
        $plan = app(PlanService::class)->getPlanTypeModel($user->current_team_id, PlanTypes::SHOPPING_LIST)
            ?? app(PlanService::class)->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, 'List');

        $stage = $plan->stages->first();
        $item = PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'user_id' => $user->id,
            'stage_id' => $stage->id,
            'title' => 'milk',
            'state' => PlanItem::STATE_PENDING,
            'order' => 1,
        ]);

        // Cycle the item to "buy" via the API the chat widget uses.
        $this->actingAs($user)
            ->postJson("/shopping/{$plan->id}/items/{$item->id}/cycle", ['state' => PlanItem::STATE_BUY])
            ->assertOk()
            ->assertJsonPath('state', PlanItem::STATE_BUY);

        // Server should still return the item so it stays visible while shopping.
        $response = $this->actingAs($user)->getJson('/shopping/current');

        $items = collect($response->json('plan.stages.0.items'));
        $milk = $items->firstWhere('title', 'milk');

        $this->assertNotNull($milk, 'Item in "buy" state must still appear in the list payload (LM-9).');
        $this->assertSame(PlanItem::STATE_BUY, $milk['state']);
    }

    public function test_skip_state_items_remain_in_list_payload(): void
    {
        $user = $this->makeUser();
        $plan = app(PlanService::class)->getPlanTypeModel($user->current_team_id, PlanTypes::SHOPPING_LIST)
            ?? app(PlanService::class)->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, 'List');

        $stage = $plan->stages->first();
        $item = PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'user_id' => $user->id,
            'stage_id' => $stage->id,
            'title' => 'bread',
            'state' => PlanItem::STATE_PENDING,
            'order' => 1,
        ]);

        $this->actingAs($user)
            ->postJson("/shopping/{$plan->id}/items/{$item->id}/cycle", ['state' => PlanItem::STATE_SKIP])
            ->assertOk();

        $response = $this->actingAs($user)->getJson('/shopping/current');

        $items = collect($response->json('plan.stages.0.items'));
        $bread = $items->firstWhere('title', 'bread');

        $this->assertNotNull($bread, 'Item in "skip" state must still appear in the list payload.');
        $this->assertSame(PlanItem::STATE_SKIP, $bread['state']);
    }
}
