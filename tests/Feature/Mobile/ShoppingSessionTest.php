<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use App\Services\ShoppingListReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

class ShoppingSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_shopping_list_includes_session_and_visits(): void
    {
        [$user, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/shopping')
            ->assertOk();

        $this->assertNull($response->json('plan.session'));
        $this->assertIsArray($response->json('plan.visits'));
        $this->assertEmpty($response->json('plan.visits'));
    }

    public function test_start_session(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->addItem($plan, $stage, $user, 'Milk');
        $this->addItem($plan, $stage, $user, 'Bread');

        $response = $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start")
            ->assertCreated();

        $session = $response->json('session');
        $this->assertNotNull($session['id']);
        $this->assertCount(2, $session['items']);
        $this->assertEmpty($session['purchased_ids']);
    }

    public function test_start_session_resumes_existing(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->addItem($plan, $stage, $user, 'Milk');

        $first = $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start")
            ->assertCreated()
            ->json('session.id');

        $second = $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start")
            ->json('session.id');

        $this->assertEquals($first, $second);
    }

    public function test_start_session_fails_when_no_pending_items(): void
    {
        [$user, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start")
            ->assertStatus(422);
    }

    public function test_cycle_item_updates_session_cart(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);

        $item = $this->addItem($plan, $stage, $user, 'Milk');
        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start")
            ->assertCreated();

        $this->postJson("/api/mobile/shopping/{$plan->id}/items/{$item->id}/cycle", [
            'state' => 'buy',
        ])->assertOk();

        $session = app(ShoppingListReviewService::class)->activeSession($plan->fresh());
        $this->assertContains($item->id, $session['purchased_ids']);
    }

    public function test_finish_session(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);

        $milk = $this->addItem($plan, $stage, $user, 'Milk');
        $bread = $this->addItem($plan, $stage, $user, 'Bread');

        $sessionId = $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start")
            ->json('session.id');

        // Mark milk as bought
        $this->postJson("/api/mobile/shopping/{$plan->id}/items/{$milk->id}/cycle", [
            'state' => 'buy',
        ]);

        $response = $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/finish", [
            'session_id' => $sessionId,
            'total' => 500.50,
            'currency' => 'DOP',
            'note' => 'Quick trip',
        ])->assertOk();

        $visit = $response->json('visit');
        $this->assertEquals('500.50', $visit['total']);
        $this->assertEquals('DOP', $visit['currency']);
        $this->assertCount(1, $visit['purchased_items']);
        $this->assertEquals('Milk', $visit['purchased_items'][0]['title']);
        $this->assertCount(1, $visit['remaining_items']);
        $this->assertEquals('Bread', $visit['remaining_items'][0]['title']);

        // Plan should be reset
        $this->assertNull($response->json('plan.session'));
        $this->assertNotEmpty($response->json('plan.visits'));

        // Items should be back to pending
        $this->assertDatabaseHas('plan_items', ['id' => $milk->id, 'state' => 'pending']);
        $this->assertDatabaseHas('plan_items', ['id' => $bread->id, 'state' => 'pending']);
    }

    public function test_finish_session_requires_cart_items(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->addItem($plan, $stage, $user, 'Milk');

        $sessionId = $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start")
            ->json('session.id');

        // Try to finish without buying anything
        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/finish", [
            'session_id' => $sessionId,
            'total' => 100,
            'currency' => 'DOP',
        ])->assertStatus(422);
    }

    public function test_finish_session_rejects_wrong_session_id(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);

        $item = $this->addItem($plan, $stage, $user, 'Milk');
        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start");

        // Buy something
        $this->postJson("/api/mobile/shopping/{$plan->id}/items/{$item->id}/cycle", [
            'state' => 'buy',
        ]);

        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/finish", [
            'session_id' => (string) Str::uuid(),
            'total' => 100,
            'currency' => 'DOP',
        ])->assertStatus(409);
    }

    public function test_finish_session_validation(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->addItem($plan, $stage, $user, 'Milk');
        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start");

        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/finish", [
            'total' => 'not-a-number',
        ])->assertStatus(422);
    }

    public function test_visits_accumulate_in_history(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);

        // First visit
        $item = $this->addItem($plan, $stage, $user, 'Milk');
        $sid = $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start")
            ->json('session.id');
        $this->postJson("/api/mobile/shopping/{$plan->id}/items/{$item->id}/cycle", ['state' => 'buy']);
        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/finish", [
            'session_id' => $sid,
            'total' => 100,
            'currency' => 'DOP',
        ])->assertOk();

        // Second visit (items reset to pending after first finish)
        $sid2 = $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start")
            ->json('session.id');
        $this->postJson("/api/mobile/shopping/{$plan->id}/items/{$item->id}/cycle", ['state' => 'buy']);
        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/finish", [
            'session_id' => $sid2,
            'total' => 200,
            'currency' => 'USD',
        ])->assertOk();

        // Check history (pass plan ID to ensure same plan is resolved)
        $response = $this->getJson("/api/mobile/shopping?plan={$plan->id}")
            ->assertOk();

        $visits = $response->json('plan.visits');
        $this->assertCount(2, $visits);
        // Most recent first
        $this->assertEquals('200.00', $visits[0]['total']);
        $this->assertEquals('100.00', $visits[1]['total']);
    }

    public function test_team_isolation_on_sessions(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        $this->addItem($plan, $stage, $user, 'Milk');

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start")
            ->assertForbidden();

        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/finish", [
            'session_id' => (string) Str::uuid(),
            'total' => 100,
            'currency' => 'DOP',
        ])->assertForbidden();
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $plan = app(PlanService::class)->createPlanBoard(
            $user->currentTeam,
            PlanTypes::SHOPPING_LIST,
            'Test Shopping List',
        );
        $stage = $plan->stages()->first();

        return [$user, $plan, $stage];
    }

    private function addItem($plan, $stage, $user, string $title): PlanItem
    {
        return PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'user_id' => $user->id,
            'stage_id' => $stage->id,
            'title' => $title,
            'state' => PlanItem::STATE_PENDING,
            'order' => 0,
        ]);
    }
}
