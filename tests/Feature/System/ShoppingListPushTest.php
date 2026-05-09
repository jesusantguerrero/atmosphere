<?php

namespace Tests\Feature\System;

use App\Events\ShoppingListItemUpdated;
use App\Models\User;
use App\Services\OneSignalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

/**
 * Web push contract — covers OneSignalService payload shape and the listener
 * filter (push for `created` / `reset`, skip pure cycle-state updates so the
 * spouse isn't pinged on every checkbox tick).
 */
class ShoppingListPushTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.onesignal.app_id' => 'test-app-id',
            'services.onesignal.rest_api_key' => 'test-rest-key',
        ]);
    }

    public function test_one_signal_service_posts_expected_payload(): void
    {
        Http::fake([
            OneSignalService::ENDPOINT => Http::response(['id' => 'notif-id'], 200),
        ]);

        $service = app(OneSignalService::class);
        $sent = $service->sendToTag('shopping_list', 'tok-123', 'My list', 'Trip reset', ['web' => 'https://example.test/x']);

        $this->assertTrue($sent);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === OneSignalService::ENDPOINT
                && $request->hasHeader('Authorization', 'Basic test-rest-key')
                && $body['app_id'] === 'test-app-id'
                && $body['headings']['en'] === 'My list'
                && $body['contents']['en'] === 'Trip reset'
                && $body['filters'][0]['key'] === 'shopping_list'
                && $body['filters'][0]['value'] === 'tok-123'
                && $body['web_url'] === 'https://example.test/x';
        });
    }

    public function test_one_signal_service_no_ops_without_credentials(): void
    {
        config([
            'services.onesignal.app_id' => null,
            'services.onesignal.rest_api_key' => null,
        ]);
        Http::fake();

        $sent = app(OneSignalService::class)->sendToTag('shopping_list', 'tok', 'h', 'm');

        $this->assertFalse($sent);
        Http::assertNothingSent();
    }

    public function test_listener_pushes_on_created_action(): void
    {
        Http::fake([
            OneSignalService::ENDPOINT => Http::response(['id' => 'notif'], 200),
        ]);

        $plan = $this->makeSharedPlan();
        $item = $this->makeItem($plan, 'Wiper');

        ShoppingListItemUpdated::dispatch($plan, $item, 'created');

        Http::assertSent(fn ($req) => $req->data()['contents']['en'] === 'Added: Wiper');
    }

    public function test_listener_pushes_on_reset_action(): void
    {
        Http::fake([
            OneSignalService::ENDPOINT => Http::response(['id' => 'notif'], 200),
        ]);

        $plan = $this->makeSharedPlan();

        ShoppingListItemUpdated::dispatch($plan, null, 'reset');

        Http::assertSent(
            fn ($req) => str_contains($req->data()['contents']['en'], 'Trip reset')
        );
    }

    public function test_listener_skips_pure_state_updates(): void
    {
        Http::fake();

        $plan = $this->makeSharedPlan();
        $item = $this->makeItem($plan, 'Salami');

        ShoppingListItemUpdated::dispatch($plan, $item, 'updated');

        Http::assertNothingSent();
    }

    public function test_listener_skips_when_list_not_shared(): void
    {
        Http::fake();

        $plan = $this->makeSharedPlan();
        $plan->share_token = null;
        $plan->save();

        $item = $this->makeItem($plan, 'Pan');

        ShoppingListItemUpdated::dispatch($plan, $item, 'created');

        Http::assertNothingSent();
    }

    public function test_actor_subscription_id_is_sent_as_excluded_subscription_ids(): void
    {
        Http::fake([
            OneSignalService::ENDPOINT => Http::response(['id' => 'notif'], 200),
        ]);

        $plan = $this->makeSharedPlan();
        $item = $this->makeItem($plan, 'Pizza');

        ShoppingListItemUpdated::dispatch($plan, $item, 'created', 'sub-abc-123');

        Http::assertSent(fn ($req) => ($req->data()['excluded_subscription_ids'] ?? []) === ['sub-abc-123']);
    }

    public function test_payload_omits_exclusion_key_when_actor_id_missing(): void
    {
        Http::fake([
            OneSignalService::ENDPOINT => Http::response(['id' => 'notif'], 200),
        ]);

        $plan = $this->makeSharedPlan();
        $item = $this->makeItem($plan, 'Salami');

        ShoppingListItemUpdated::dispatch($plan, $item, 'created');

        Http::assertSent(fn ($req) => ! array_key_exists('excluded_subscription_ids', $req->data()));
    }

    public function test_authed_endpoint_threads_subscription_header_into_event(): void
    {
        Http::fake([
            OneSignalService::ENDPOINT => Http::response(['id' => 'notif'], 200),
        ]);

        $user = User::factory()->withPersonalTeam()->create();
        $plan = app(PlanService::class)->createPlanBoard(
            $user->currentTeam,
            PlanTypes::SHOPPING_LIST,
            'Weekly groceries',
        );
        $plan->share_token = 'tok-share';
        $plan->save();

        $this->actingAs($user)
            ->withHeaders(['X-OneSignal-Subscription-Id' => 'sub-laptop'])
            ->postJson("/shopping/{$plan->id}/items", ['title' => 'Wiper'])
            ->assertCreated();

        Http::assertSent(fn ($req) => ($req->data()['excluded_subscription_ids'] ?? []) === ['sub-laptop']);
    }

    private function makeSharedPlan(): Plan
    {
        $user = User::factory()->withPersonalTeam()->create();
        $plan = app(PlanService::class)->createPlanBoard(
            $user->currentTeam,
            PlanTypes::SHOPPING_LIST,
            'Weekly groceries',
        );
        $plan->share_token = 'tok-share';
        $plan->save();

        return $plan->fresh(['stages.items']);
    }

    private function makeItem(Plan $plan, string $title): PlanItem
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
}
