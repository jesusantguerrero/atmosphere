<?php

namespace Tests\Feature;

use App\Events\ShoppingListItemUpdated;
use App\Models\User;
use App\Services\ShoppingListReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

class ShoppingSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_session_resumes_and_saves_an_immutable_visit_once(): void
    {
        Event::fake([ShoppingListItemUpdated::class]);
        $user = User::factory()->withPersonalTeam()->create();
        $plan = app(PlanService::class)->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, 'Compra');
        $stage = $plan->stages()->first();
        $make = fn (string $title, string $state) => PlanItem::create(['plan_id' => $plan->id, 'team_id' => $plan->team_id, 'user_id' => $user->id, 'stage_id' => $stage->id, 'title' => $title, 'state' => $state, 'order' => 1]);
        $milk = $make('2 leches', 'pending');
        $bread = $make('Pan', 'pending');
        $make('Arroz', 'skip');
        $base = '/shopping/'.$plan->id;
        $session = $this->actingAs($user)->postJson($base.'/sessions/start')->assertOk()->json('plan.session');
        $this->assertCount(2, $session['items']);
        $this->postJson($base.'/sessions/start')->assertOk()->assertJsonPath('plan.session.id', $session['id']);
        $this->getJson($base.'/review')->assertOk()->assertJsonPath('plan.session.id', $session['id']);
        $later = $make('Producto añadido después', 'pending');
        $this->getJson($base.'/review')->assertOk()->assertJsonCount(3, 'plan.session.items');
        $later->update(['state' => 'skip']);
        $this->getJson($base.'/review')->assertOk()->assertJsonCount(2, 'plan.session.items');

        $payload = ['session_id' => $session['id'], 'total' => '1250.50', 'currency' => 'DOP', 'note' => 'Visita de prueba'];
        $this->postJson($base.'/sessions/finish', $payload)->assertUnprocessable();
        $this->postJson($base.'/items/'.$milk->id.'/cycle', ['state' => 'buy'])->assertOk()->assertJsonPath('session.purchased_ids', [$milk->id]);
        $milk->update(['title' => 'Nombre posterior']);
        $this->postJson($base.'/sessions/finish', [...$payload, 'total' => '-1'])->assertUnprocessable();
        $this->postJson($base.'/sessions/finish', [...$payload, 'session_id' => (string) Str::uuid()])->assertStatus(409);
        $response = $this->postJson($base.'/sessions/finish', $payload)->assertOk()->assertJsonPath('plan.session', null)->assertJsonPath('visit.total', '1250.50')->assertJsonPath('visit.purchased_items.0.title', '2 leches')->assertJsonPath('visit.remaining_items.0.id', $bread->id);
        $this->postJson($base.'/sessions/finish', $payload)->assertOk()->assertJsonCount(1, 'plan.visits');
        $this->assertSame($response->json('visit.completed_at'), app(ShoppingListReviewService::class)->visits($plan)[0]['completed_at']);
        $this->assertDatabaseHas('plan_items', ['id' => $bread->id, 'state' => 'pending']);
        $this->assertDatabaseHas('plan_items', ['id' => $milk->id, 'state' => 'pending']);
        $this->postJson($base.'/sessions/start')->assertOk()->assertJsonCount(4, 'plan.session.items');
        $this->assertSame([], app(ShoppingListReviewService::class)->status($plan)['reviewed_ids']);
        $this->assertSame([], app(ShoppingListReviewService::class)->status($plan)['item_details']);
        $other = User::factory()->withPersonalTeam()->create();
        $this->actingAs($other)->postJson($base.'/sessions/start')->assertForbidden();
        $this->actingAs($other)->postJson($base.'/sessions/finish', $payload)->assertForbidden();
    }
}
