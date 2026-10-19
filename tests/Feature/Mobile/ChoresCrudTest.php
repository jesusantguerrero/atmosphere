<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

class ChoresCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_chore_title(): void
    {
        [$user, $item] = $this->makeChore();
        Sanctum::actingAs($user);

        $this->patchJson("/api/mobile/chores/{$item->id}", [
            'title' => 'Wash dishes',
        ])->assertOk()->assertJsonFragment(['title' => 'Wash dishes']);

        $this->assertEquals('Wash dishes', $item->fresh()->title);
    }

    public function test_update_chore_recurrence(): void
    {
        [$user, $item] = $this->makeChore();
        Sanctum::actingAs($user);

        $this->patchJson("/api/mobile/chores/{$item->id}", [
            'recurrence' => 'daily',
        ])->assertOk();

        $this->assertStringContainsString('DAILY', $item->fresh()->rrule);
    }

    public function test_update_chore_owner(): void
    {
        [$user, $item] = $this->makeChore();
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/mobile/chores/{$item->id}", [
            'owner' => 'María',
        ])->assertOk();

        $this->assertEquals('María', $response->json('owner'));
    }

    public function test_update_chore_once_clears_rrule(): void
    {
        [$user, $item] = $this->makeChore();
        Sanctum::actingAs($user);

        $this->patchJson("/api/mobile/chores/{$item->id}", [
            'recurrence' => 'once',
        ])->assertOk();

        $this->assertNull($item->fresh()->rrule);
    }

    public function test_delete_chore(): void
    {
        [$user, $item] = $this->makeChore();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/mobile/chores/{$item->id}")
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertNull(PlanItem::find($item->id));
    }

    public function test_cannot_update_other_teams_chore(): void
    {
        [$user, $item] = $this->makeChore();

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->patchJson("/api/mobile/chores/{$item->id}", [
            'title' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_update_chore_due_date(): void
    {
        [$user, $item] = $this->makeChore();
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/mobile/chores/{$item->id}", [
            'due_date' => '2026-10-25',
        ])->assertOk();

        $this->assertEquals('2026-10-25', $item->fresh()->commit_date);
        $this->assertEquals('2026-10-25', $response->json('commit_date'));
    }

    public function test_update_chore_clear_due_date(): void
    {
        [$user, $item] = $this->makeChore();
        Sanctum::actingAs($user);

        $item->forceFill(['commit_date' => '2026-10-20'])->save();

        $this->patchJson("/api/mobile/chores/{$item->id}", [
            'due_date' => null,
        ])->assertOk();

        $this->assertNull($item->fresh()->commit_date);
    }

    public function test_store_chore_with_due_date(): void
    {
        [$user, $item] = $this->makeChore();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/chores', [
            'title' => 'New chore with date',
            'due_date' => '2026-11-01',
        ])->assertCreated();

        $this->assertEquals('2026-11-01', $response->json('data.commit_date'));
    }

    public function test_cannot_delete_other_teams_chore(): void
    {
        [$user, $item] = $this->makeChore();

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->deleteJson("/api/mobile/chores/{$item->id}")
            ->assertForbidden();
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeChore(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $service = app(PlanService::class);
        $plan = $service->createPlanBoard($user->currentTeam, PlanTypes::CHORES);
        $stage = $plan->stages()->first();

        $item = PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'stage_id' => $stage->id,
            'title' => 'Take out trash',
        ]);

        return [$user, $item];
    }
}
