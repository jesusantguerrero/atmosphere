<?php

namespace Tests\Feature;

use App\Events\ShoppingListItemUpdated;
use App\Models\User;
use App\Notifications\ShoppingListReady;
use App\Services\ShoppingListReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

class ShoppingListReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_is_persistent_idempotent_and_does_not_copy_the_list(): void
    {
        Event::fake([ShoppingListItemUpdated::class]);
        Notification::fake();
        $user = User::factory()->withPersonalTeam()->create();
        $plan = app(PlanService::class)->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, 'Compra');
        $plan->forceFill(['share_token' => (string) Str::uuid()])->save();
        $item = PlanItem::create(['plan_id' => $plan->id, 'team_id' => $plan->team_id, 'user_id' => $plan->user_id, 'stage_id' => $plan->stages()->first()->id, 'title' => 'Leche', 'state' => 'pending', 'order' => 1]);
        $service = app(ShoppingListReviewService::class);
        $url = '/shared/list/'.$plan->share_token.'/review';
        $version = $this->getJson($url)->assertOk()->json('plan.review.current_version');
        $payload = ['note' => 'Marca de siempre', 'current_version' => $version];
        $id = $this->postJson($url, $payload)->assertOk()->assertJsonPath('review.status', 'ready')->json('review.id');
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('review.id', $id);
        Notification::assertSentToTimes($user, ShoppingListReady::class, 1);
        $item->update(['state' => 'buy']);
        $this->assertSame('ready', $service->status($plan)['status']);
        $item->update(['state' => 'skip']);
        $this->assertSame('changed', $service->status($plan)['status']);
        $this->postJson($url, $payload)->assertStatus(409);
        $this->postJson($url, ['note' => str_repeat('a', 2001), 'current_version' => $version])->assertUnprocessable();
        $this->actingAs($user)->postJson('/shopping/'.$plan->id.'/review/complete', ['review_id' => $id])->assertOk()->assertJsonPath('review.status', 'completed');
        $this->assertDatabaseHas('plan_items', ['id' => $item->id, 'state' => 'skip']);
        $this->assertSame(1, PlanItem::query()->where('plan_id', $plan->id)->count());
        $next = $service->confirm($plan, '', $service->status($plan)['current_version']);
        $this->assertSame('', $next['note']);
        $this->assertNotSame($id, $next['id']);
        $this->postJson('/shopping/'.$plan->id.'/review/complete', ['review_id' => $id])->assertStatus(409);
        $other = User::factory()->withPersonalTeam()->create();
        $this->actingAs($other)->getJson('/shopping/'.$plan->id.'/review')->assertForbidden();
        $this->actingAs($other)->postJson('/shopping/'.$plan->id.'/review/complete', ['review_id' => $next['id']])->assertForbidden();
        $this->getJson('/shared/list/invalid/review')->assertNotFound();
    }

    public function test_guest_can_choose_or_create_categories_only_inside_the_shared_list(): void
    {
        Event::fake([ShoppingListItemUpdated::class]);
        $user = User::factory()->withPersonalTeam()->create();
        $service = app(PlanService::class);
        $plan = $service->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, 'Compra');
        $plan->forceFill(['share_token' => (string) Str::uuid()])->save();
        $url = '/shared/list/'.$plan->share_token.'/items';
        $response = $this->postJson($url, ['title' => 'Manzana', 'category_name' => 'Frutas'])->assertCreated()->assertJsonPath('stage.name', 'Frutas');
        $stageId = $response->json('stage_id');
        $this->postJson($url, ['title' => 'Pera', 'stage_id' => $stageId])->assertCreated()->assertJsonPath('stage_id', $stageId);
        $this->postJson($url, ['title' => 'Guineo', 'category_name' => 'Frutas'])->assertCreated()->assertJsonPath('stage_id', $stageId);
        $this->assertSame(1, $plan->stages()->where('name', 'Frutas')->count());
        $other = $service->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, 'Otra lista');
        $this->postJson($url, ['title' => 'No permitido', 'stage_id' => $other->stages()->first()->id])->assertUnprocessable();
        $this->postJson($url, ['title' => 'Manzana', 'category_name' => str_repeat('a', 121)])->assertUnprocessable();
        $this->assertDatabaseHas('plan_items', ['plan_id' => $plan->id, 'title' => 'Pera', 'stage_id' => $stageId]);
    }

    public function test_guest_review_marks_persist_and_category_moves_preserve_the_item(): void
    {
        Event::fake([ShoppingListItemUpdated::class]);
        Notification::fake();
        $user = User::factory()->withPersonalTeam()->create();
        $plan = app(PlanService::class)->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, 'Compra');
        $plan->forceFill(['share_token' => (string) Str::uuid()])->save();
        $stage = $plan->stages()->first();
        $item = PlanItem::create(['plan_id' => $plan->id, 'team_id' => $plan->team_id, 'user_id' => $plan->user_id, 'stage_id' => $stage->id, 'title' => 'Leche', 'state' => 'pending', 'order' => 1]);
        $base = '/shared/list/'.$plan->share_token;
        $this->getJson($base.'/review')->assertJsonPath('plan.review.reviewed_ids', []);
        $this->postJson($base.'/toggle/'.$item->id, ['state' => 'pending'])->assertOk()->assertJsonPath('review.reviewed_ids', [$item->id]);
        $this->getJson($base.'/review')->assertJsonPath('plan.review.reviewed_ids', [$item->id]);
        $this->postJson($base.'/toggle/'.$item->id, ['state' => 'skip', 'reviewed' => false])->assertOk()->assertJsonPath('review.reviewed_ids', []);
        $this->postJson($base.'/toggle/'.$item->id, ['state' => 'pending'])->assertOk()->assertJsonPath('review.reviewed_ids', [$item->id]);

        $target = $plan->stages()->create(['team_id' => $plan->team_id, 'user_id' => $plan->user_id, 'name' => 'Lácteos', 'order' => 2]);
        $this->patchJson($base.'/items/'.$item->id.'/category', ['stage_id' => $target->id])->assertOk()->assertJsonPath('plan.review.reviewed_ids', [$item->id]);
        $this->assertDatabaseHas('plan_items', ['id' => $item->id, 'stage_id' => $target->id, 'state' => 'pending']);
        $originalOrder = $item->fresh()->order;
        $version = app(ShoppingListReviewService::class)->status($plan)['current_version'];
        $this->patchJson($base.'/items/'.$item->id.'/category', ['stage_id' => $target->id, 'quantity' => '2 paquetes', 'note' => 'Sin azúcar'])->assertOk()->assertJsonPath('plan.review.item_details.'.$item->id.'.quantity', '2 paquetes');
        $this->assertSame($originalOrder, $item->fresh()->order);
        $this->assertNotSame($version, app(ShoppingListReviewService::class)->status($plan)['current_version']);
        $this->getJson($base.'/review')->assertJsonPath('plan.review.item_details.'.$item->id.'.note', 'Sin azúcar');
        $this->patchJson($base.'/items/'.$item->id.'/category', ['stage_id' => $target->id, 'note' => str_repeat('a', 501)])->assertUnprocessable();

        $this->patchJson($base.'/items/'.$item->id.'/category', ['stage_id' => 999999])->assertUnprocessable();
        $this->patchJson('/shared/list/invalid/items/'.$item->id.'/category', ['stage_id' => $target->id])->assertNotFound();
        $service = app(ShoppingListReviewService::class);
        $review = $service->confirm($plan, '', $service->status($plan)['current_version']);
        $service->complete($plan, $review['id']);
        $this->assertSame([], $service->status($plan)['reviewed_ids']);
        $this->assertSame([], $service->status($plan)['item_details']);
        $this->assertDatabaseHas('plan_items', ['id' => $item->id, 'state' => 'pending']);
    }

    public function test_guest_review_revision_increases_on_every_mark_change_so_clients_can_drop_stale_snapshots(): void
    {
        Event::fake([ShoppingListItemUpdated::class]);
        $user = User::factory()->withPersonalTeam()->create();
        $plan = app(PlanService::class)->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, 'Compra');
        $plan->forceFill(['share_token' => (string) Str::uuid()])->save();
        $stage = $plan->stages()->first();
        $milk = PlanItem::create(['plan_id' => $plan->id, 'team_id' => $plan->team_id, 'user_id' => $plan->user_id, 'stage_id' => $stage->id, 'title' => 'Leche', 'state' => 'pending', 'order' => 1]);
        $bread = PlanItem::create(['plan_id' => $plan->id, 'team_id' => $plan->team_id, 'user_id' => $plan->user_id, 'stage_id' => $stage->id, 'title' => 'Pan', 'state' => 'pending', 'order' => 2]);
        $base = '/shared/list/'.$plan->share_token;

        $this->getJson($base.'/review')->assertJsonPath('plan.review.reviewed_revision', 0);
        $this->postJson($base.'/toggle/'.$milk->id, ['state' => 'skip'])->assertJsonPath('review.reviewed_revision', 1);
        $this->postJson($base.'/toggle/'.$bread->id, ['state' => 'pending'])->assertJsonPath('review.reviewed_revision', 2)->assertJsonPath('review.reviewed_ids', [$milk->id, $bread->id]);
        $this->postJson($base.'/toggle/'.$milk->id, ['state' => 'pending', 'reviewed' => false])->assertJsonPath('review.reviewed_revision', 3)->assertJsonPath('review.reviewed_ids', [$bread->id]);
        $this->postJson($base.'/reset')->assertOk();
        $status = app(ShoppingListReviewService::class)->status($plan);
        $this->assertSame([], $status['reviewed_ids']);
        $this->assertSame(4, $status['reviewed_revision']);
        $this->getJson($base.'/review')->assertJsonPath('plan.review.reviewed_revision', 4);
    }
}
