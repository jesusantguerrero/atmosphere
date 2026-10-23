<?php

namespace Tests\Feature\Mobile;

use App\Models\Account;
use App\Models\User;
use App\Services\ShoppingListReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Insane\Journal\Models\Core\Payee;
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

    public function test_lists_can_be_created_renamed_imported_and_deleted(): void
    {
        [$user, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $created = $this->postJson('/api/mobile/shopping/lists', ['name' => 'Farmacia'])->assertCreated();
        $listId = $created->json('activeListId');
        $before = count($created->json('lists')) - 1;

        $this->putJson("/api/mobile/shopping/{$listId}", ['name' => 'Farmacia 2'])->assertOk()->assertJsonPath('name', 'Farmacia 2');

        $imported = $this->postJson('/api/mobile/shopping/import', ['name' => 'Fiesta', 'text' => 'Bebidas
- Ron
- Hielo'])->assertCreated();
        $this->assertCount($before + 2, $imported->json('lists'));

        $this->deleteJson("/api/mobile/shopping/{$listId}")->assertOk()->assertJsonPath('deleted', true);
        $this->assertDatabaseMissing('plans', ['id' => $listId]);
    }

    public function test_lists_of_another_team_cannot_be_changed(): void
    {
        [$user, $plan] = $this->makeSetup();
        $other = User::factory()->withPersonalTeam()->create();
        $other->forceFill(['current_team_id' => $other->ownedTeams()->first()->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->putJson("/api/mobile/shopping/{$plan->id}", ['name' => 'Mine now'])->assertForbidden();
        $this->deleteJson("/api/mobile/shopping/{$plan->id}")->assertForbidden();
        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'Test Shopping List']);
    }

    // ── The trip and the card charge that paid for it ─────────────────────

    public function test_a_finished_trip_remembers_the_store_it_was_at(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);
        $store = $this->makeStore($user, 'La Sirena');

        $visit = $this->finishTrip($plan, $stage, $user, 1250.50, $store)->json('visit');

        $this->assertEquals($store->id, $visit['payee']['id']);
        $this->assertEquals('La Sirena', $visit['payee']['name']);
        $this->assertNull($visit['transaction']);
        $this->assertEquals('La Sirena', $this->getJson("/api/mobile/shopping?plan={$plan->id}")->json('plan.visits.0.payee.name'));

        $other = User::factory()->withPersonalTeam()->create();
        $foreign = $this->makeStore($other, 'Not mine');
        [$sid] = $this->startTrip($plan, $stage, $user, 'Eggs');
        $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/finish", ['session_id' => $sid, 'total' => 10, 'currency' => 'DOP', 'payee_id' => $foreign->id])->assertStatus(422);
    }

    public function test_the_charge_that_arrives_after_the_trip_is_linked_to_it(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);
        $store = $this->makeStore($user, 'La Sirena');
        $this->finishTrip($plan, $stage, $user, 1250.50, $store);

        $charge = $this->charge($user, 1250.50, $store);

        $visit = $this->getJson("/api/mobile/shopping?plan={$plan->id}")->json('plan.visits.0');
        $this->assertEquals($charge, $visit['transaction_id']);
        $this->assertEquals($charge, $visit['transaction']['id']);
        $this->assertEquals(1250.50, $visit['transaction']['total']);
    }

    public function test_a_charge_that_was_already_in_is_linked_when_the_trip_is_saved(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);
        $store = $this->makeStore($user, 'La Sirena');
        $charge = $this->charge($user, 980, $store);

        $visit = $this->finishTrip($plan, $stage, $user, 980, $store)->json('visit');

        $this->assertEquals($charge, $visit['transaction']['id']);
    }

    public function test_editing_the_visit_payee_links_the_charge_that_was_waiting_for_it(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);
        $store = $this->makeStore($user, 'La Sirena');
        $charge = $this->charge($user, 700, $store);
        $visit = $this->finishTrip($plan, $stage, $user, 700, $this->makeStore($user, 'Wrong store'))->json('visit');
        $this->assertNull($visit['transaction']);

        $updated = $this->patchJson("/api/mobile/shopping/{$plan->id}/visits/{$visit['id']}", ['payee_id' => $store->id])
            ->assertOk()
            ->json('visit');

        $this->assertEquals('La Sirena', $updated['payee']['name']);
        $this->assertEquals($charge, $updated['transaction']['id']);
    }

    public function test_editing_the_visit_payee_can_clear_it_and_keeps_an_existing_link(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);
        $store = $this->makeStore($user, 'La Sirena');
        $charge = $this->charge($user, 450, $store);
        $visit = $this->finishTrip($plan, $stage, $user, 450, $store)->json('visit');

        $updated = $this->patchJson("/api/mobile/shopping/{$plan->id}/visits/{$visit['id']}", ['payee_id' => null])
            ->assertOk()
            ->json('visit');

        $this->assertNull($updated['payee']);
        $this->assertEquals($charge, $updated['transaction']['id']);
    }

    public function test_editing_the_visit_payee_validates_and_guards_the_team(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);
        $visit = $this->finishTrip($plan, $stage, $user, 100)->json('visit');
        $url = "/api/mobile/shopping/{$plan->id}/visits/{$visit['id']}";

        $foreign = $this->makeStore(User::factory()->withPersonalTeam()->create(), 'Not mine');
        $this->patchJson($url, ['payee_id' => $foreign->id])->assertStatus(422)->assertJsonValidationErrors('payee_id');
        $this->patchJson($url, [])->assertStatus(422);
        $this->patchJson("/api/mobile/shopping/{$plan->id}/visits/".Str::uuid(), ['payee_id' => null])->assertNotFound();

        $other = User::factory()->withPersonalTeam()->create();
        $other->forceFill(['current_team_id' => $other->ownedTeams()->first()->id])->save();
        Sanctum::actingAs($other->fresh());
        $this->patchJson($url, ['payee_id' => null])->assertForbidden();
    }

    public function test_only_a_charge_that_fits_the_amount_the_store_and_the_days_is_linked(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);
        $store = $this->makeStore($user, 'La Sirena');
        $elsewhere = $this->makeStore($user, 'Gasolinera');
        $this->finishTrip($plan, $stage, $user, 500, $store);

        $this->charge($user, 499, $store); // other amount
        $this->charge($user, 500, $elsewhere); // other store
        $this->charge($user, 500, $store, now()->addDays(20)->toDateString()); // weeks later
        $this->charge($user, 500, $store, now()->subDays(5)->toDateString()); // before the trip
        $this->assertNull($this->getJson("/api/mobile/shopping?plan={$plan->id}")->json('plan.visits.0.transaction'));

        $fits = $this->charge($user, 500, $store, now()->addDays(3)->toDateString());
        $this->assertEquals($fits, $this->getJson("/api/mobile/shopping?plan={$plan->id}")->json('plan.visits.0.transaction.id'));
    }

    public function test_two_charges_that_both_fit_a_trip_are_not_guessed(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);
        $store = $this->makeStore($user, 'La Sirena');
        $this->charge($user, 700, $store);
        $this->charge($user, 700, $store);

        // Both are already in when the trip is saved: there is nothing to tell them apart, so it is left alone.
        $visit = $this->finishTrip($plan, $stage, $user, 700, $store)->json('visit');

        $this->assertNull($visit['transaction']);
    }

    public function test_the_first_charge_that_fits_keeps_the_trip(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);
        $store = $this->makeStore($user, 'La Sirena');
        $this->finishTrip($plan, $stage, $user, 700, $store);

        $first = $this->charge($user, 700, $store);
        $this->charge($user, 700, $store); // a look-alike later does not take it over

        $this->assertEquals($first, $this->getJson("/api/mobile/shopping?plan={$plan->id}")->json('plan.visits.0.transaction.id'));
    }

    public function test_a_charge_pays_only_one_trip(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);
        $store = $this->makeStore($user, 'La Sirena');
        $this->finishTrip($plan, $stage, $user, 300, $store);
        $first = $this->charge($user, 300, $store);

        // A second trip for the same amount does not take the charge the first one already has.
        $second = $this->finishTrip($plan, $stage, $user, 300, $store)->json('visit');

        $this->assertNull($second['transaction']);
        $visits = $this->getJson("/api/mobile/shopping?plan={$plan->id}")->json('plan.visits');
        $this->assertEquals([null, $first], array_map(fn ($visit) => $visit['transaction_id'] ?? null, $visits));
    }

    public function test_income_is_never_the_charge_of_a_trip(): void
    {
        [$user, $plan, $stage] = $this->makeSetup();
        Sanctum::actingAs($user);
        $store = $this->makeStore($user, 'La Sirena');
        $this->finishTrip($plan, $stage, $user, 400, $store);

        $this->charge($user, 400, $store, null, 'DEPOSIT');

        $this->assertNull($this->getJson("/api/mobile/shopping?plan={$plan->id}")->json('plan.visits.0.transaction'));
    }

    // ── Helpers for the trips ──────────────────────────────────────────

    private function makeStore(User $user, string $name): Payee
    {
        return Payee::create(['team_id' => $user->ownedTeams()->first()->id, 'user_id' => $user->id, 'name' => $name]);
    }

    /** @return array{0: string} */
    private function startTrip($plan, $stage, $user, string $item): array
    {
        $milk = $this->addItem($plan, $stage, $user, $item);
        $sid = $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/start")->json('session.id');
        $this->postJson("/api/mobile/shopping/{$plan->id}/items/{$milk->id}/cycle", ['state' => 'buy']);

        return [$sid];
    }

    private function finishTrip($plan, $stage, $user, float $total, ?Payee $store = null)
    {
        [$sid] = $this->startTrip($plan, $stage, $user, 'Item '.uniqid());

        return $this->postJson("/api/mobile/shopping/{$plan->id}/sessions/finish", [
            'session_id' => $sid,
            'total' => $total,
            'currency' => 'DOP',
            ...($store ? ['payee_id' => $store->id] : []),
        ])->assertOk();
    }

    private function charge(User $user, float $total, Payee $store, ?string $date = null, string $direction = 'WITHDRAW'): int
    {
        $teamId = $user->ownedTeams()->first()->id;
        $account = Account::where('team_id', $teamId)->first() ?? Account::create(['team_id' => $teamId, 'user_id' => $user->id, 'name' => 'Visa', 'currency_code' => 'DOP', 'balance_type' => 'DEBIT', 'type' => 1]);

        return $this->postJson('/api/mobile/transactions', [
            'account_id' => $account->id,
            'total' => $total,
            'currency_code' => 'DOP',
            'description' => 'VISA '.$store->name,
            'date' => $date ?? now()->toDateString(),
            'direction' => $direction,
            'payee_id' => $store->id,
        ])->assertStatus(201)->json('data.id');
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
