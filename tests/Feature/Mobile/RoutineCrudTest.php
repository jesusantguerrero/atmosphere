<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

class RoutineCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_block(): void
    {
        [$user, $team, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/mobile/routine/{$plan->id}/blocks", [
            'day' => 0,
            'title' => 'Morning workout',
            'start' => '09:00',
            'end' => '10:00',
            'color' => '#FF5733',
        ])->assertCreated();

        $this->assertEquals('Morning workout', $response->json('title'));
        $this->assertEquals('09:00', $response->json('start'));
        $this->assertEquals('10:00', $response->json('end'));
        $this->assertEquals(0, $response->json('day'));
        $this->assertEquals('#FF5733', $response->json('color'));
    }

    public function test_update_block(): void
    {
        [$user, $team, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $block = $this->createBlock($user, $plan, 0, 'Gym', '08:00', '09:00');

        $response = $this->putJson("/api/mobile/routine/{$plan->id}/blocks/{$block->id}", [
            'day' => 1,
            'title' => 'Yoga',
            'start' => '07:00',
            'end' => '08:00',
            'color' => '#33FF57',
        ])->assertOk();

        $this->assertEquals('Yoga', $response->json('title'));
        $this->assertEquals(1, $response->json('day'));
        $this->assertEquals('07:00', $response->json('start'));
    }

    public function test_delete_block(): void
    {
        [$user, $team, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $block = $this->createBlock($user, $plan, 0, 'Nap', '14:00', '15:00');

        $this->deleteJson("/api/mobile/routine/{$plan->id}/blocks/{$block->id}")
            ->assertOk()->assertJson(['deleted' => true]);

        $this->assertNull(PlanItem::find($block->id));
    }

    public function test_copy_day(): void
    {
        [$user, $team, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->createBlock($user, $plan, 0, 'Meeting', '10:00', '11:00');
        $this->createBlock($user, $plan, 0, 'Lunch', '12:00', '13:00');

        $response = $this->postJson("/api/mobile/routine/{$plan->id}/copy-day", [
            'from' => 0,
            'to' => [1, 2],
            'mode' => 'replace',
        ])->assertCreated();

        $this->assertCount(4, $response->json('blocks'));
        $this->assertEquals('replace', $response->json('mode'));
    }

    public function test_copy_day_append(): void
    {
        [$user, $team, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->createBlock($user, $plan, 0, 'Source block', '10:00', '11:00');
        $this->createBlock($user, $plan, 1, 'Existing block', '14:00', '15:00');

        $response = $this->postJson("/api/mobile/routine/{$plan->id}/copy-day", [
            'from' => 0,
            'to' => [1],
            'mode' => 'append',
        ])->assertCreated();

        $this->assertCount(1, $response->json('blocks'));
        $this->assertEmpty($response->json('replaced'));
    }

    public function test_cannot_modify_other_teams_routine(): void
    {
        [$user, $team, $plan] = $this->makeSetup();

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->postJson("/api/mobile/routine/{$plan->id}/blocks", [
            'day' => 0,
            'title' => 'Hack',
            'start' => '09:00',
            'end' => '10:00',
        ])->assertForbidden();
    }

    public function test_get_categories_empty(): void
    {
        [$user, $team, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/mobile/routine/{$plan->id}/categories")
            ->assertOk();

        $this->assertEquals([], $response->json('categories'));
    }

    public function test_save_and_get_categories(): void
    {
        [$user, $team, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->putJson("/api/mobile/routine/{$plan->id}/categories", [
            'categories' => [
                ['color' => '#FF0000', 'name' => 'Work'],
                ['color' => '#00FF00', 'name' => 'Exercise'],
            ],
        ])->assertOk();

        $response = $this->getJson("/api/mobile/routine/{$plan->id}/categories")
            ->assertOk();

        $this->assertCount(2, $response->json('categories'));
        $this->assertEquals('Work', $response->json('categories.0.name'));
    }

    public function test_validation_rejects_invalid_block(): void
    {
        [$user, $team, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->postJson("/api/mobile/routine/{$plan->id}/blocks", [
            'day' => 8,
            'title' => '',
            'start' => 'bad',
            'end' => 'bad',
        ])->assertStatus(422);
    }

    public function test_store_block_with_note_and_member(): void
    {
        [$user, $team, $plan] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/mobile/routine/{$plan->id}/blocks", [
            'day' => 3,
            'title' => 'Team standup',
            'start' => '09:00',
            'end' => '09:15',
            'note' => 'Daily sync',
            'member_id' => null,
        ])->assertCreated();

        $this->assertEquals('Team standup', $response->json('title'));
        $this->assertEquals('Daily sync', $response->json('note'));
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $planService = app(PlanService::class);
        $plan = $planService->createPlanBoard($team, PlanTypes::ROUTINE, 'Weekly Routine');
        $plan->stages()->delete();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($days as $i => $day) {
            $plan->stages()->create([
                'user_id' => $user->id,
                'team_id' => $team->id,
                'name' => $day,
                'order' => $i,
            ]);
        }
        $plan = $plan->fresh();

        return [$user, $team, $plan];
    }

    private function createBlock(User $user, Plan $plan, int $day, string $title, string $start, string $end): PlanItem
    {
        $stage = $plan->stages->firstWhere('order', $day);

        $parts = explode(':', $start);
        $order = ((int) $parts[0]) * 60 + ((int) ($parts[1] ?? 0));

        $item = PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'user_id' => $user->id,
            'stage_id' => $stage->id,
            'title' => $title,
            'state' => PlanItem::STATE_PENDING,
            'order' => $order,
        ]);

        $item->saveFields([
            ['name' => 'start', 'value' => $start],
            ['name' => 'end', 'value' => $end],
            ['name' => 'color', 'value' => '#6E9BE6'],
            ['name' => 'member', 'value' => ''],
            ['name' => 'note', 'value' => ''],
        ]);

        return $item->fresh();
    }
}
