<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

class ChoreAreasMobileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-13 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_toggling_a_chore_done_stamps_last_done_at(): void
    {
        [$user, $item] = $this->makeChore();
        Sanctum::actingAs($user);

        $this->patchJson("/api/mobile/chores/{$item->id}/toggle")
            ->assertOk()
            ->assertJsonPath('is_done', true);

        $this->assertSame('2026-11-13 10:00:00', Carbon::parse($item->fresh()->last_done_at)->toDateTimeString());
    }

    public function test_areas_endpoint_shape(): void
    {
        [$user, $item] = $this->makeChore();
        PlanItem::withoutEvents(fn () => $item->forceFill([
            'area' => 'bathroom',
            'rrule' => 'FREQ=WEEKLY',
            'last_done_at' => '2026-11-01 10:00:00',
        ])->save());
        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/chores/areas')
            ->assertOk()
            ->assertJsonCount(7, 'areas')
            ->assertJsonStructure([
                'areas' => [['key', 'label', 'total', 'counts' => ['fresh', 'due_soon', 'due_today', 'overdue'], 'worst_status', 'color', 'max_overdue_days']],
                'chores' => [['id', 'title', 'area', 'is_done', 'interval_days', 'last_done_at', 'due_date', 'days_left', 'status', 'color', 'freshness']],
                'most_overdue' => ['id', 'title', 'area', 'area_label', 'days_left', 'status', 'color'],
            ])
            ->assertJsonPath('chores.0.area', 'bathroom')
            ->assertJsonPath('chores.0.days_left', -5)
            ->assertJsonPath('chores.0.status', 'overdue')
            ->assertJsonPath('chores.0.color', 'red')
            ->assertJsonPath('areas.1.key', 'bathroom')
            ->assertJsonPath('areas.1.label', 'Baño')
            ->assertJsonPath('areas.1.worst_status', 'overdue')
            ->assertJsonPath('most_overdue.area_label', 'Baño');
    }

    public function test_today_exposes_the_most_overdue_chore(): void
    {
        [$user, $item] = $this->makeChore();
        PlanItem::withoutEvents(fn () => $item->forceFill([
            'rrule' => 'FREQ=DAILY;INTERVAL=2',
            'last_done_at' => '2026-11-08 10:00:00',
        ])->save());
        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/today')
            ->assertOk()
            ->assertJsonPath('most_overdue_chore.id', $item->id)
            ->assertJsonPath('most_overdue_chore.days_left', -3)
            ->assertJsonPath('most_overdue_chore.area_label', 'General');
    }

    public function test_update_chore_area(): void
    {
        [$user, $item] = $this->makeChore();
        Sanctum::actingAs($user);

        $this->patchJson("/api/mobile/chores/{$item->id}/area", ['area' => 'kitchen'])
            ->assertOk()
            ->assertJsonPath('id', $item->id)
            ->assertJsonPath('area', 'kitchen');

        $this->assertSame('kitchen', $item->fresh()->area);
    }

    public function test_update_chore_area_rejects_unknown_keys(): void
    {
        [$user, $item] = $this->makeChore();
        Sanctum::actingAs($user);

        $this->patchJson("/api/mobile/chores/{$item->id}/area", ['area' => 'attic'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('area');
    }

    public function test_cannot_update_another_teams_chore_area(): void
    {
        [, $item] = $this->makeChore();
        [$other] = $this->makeChore();
        Sanctum::actingAs($other);

        $this->patchJson("/api/mobile/chores/{$item->id}/area", ['area' => 'kitchen'])
            ->assertForbidden();
    }

    public function test_areas_only_include_the_current_teams_chores(): void
    {
        [, $item] = $this->makeChore();
        [$other] = $this->makeChore();
        Sanctum::actingAs($other);

        $chores = $this->getJson('/api/mobile/chores/areas')->assertOk()->json('chores');

        $this->assertNotContains($item->id, array_column($chores, 'id'));
        $this->assertCount(1, $chores);
    }

    /**
     * @return array{0: User, 1: PlanItem}
     */
    private function makeChore(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $plan = app(PlanService::class)->createPlanBoard($user->currentTeam, PlanTypes::CHORES);

        $item = PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $team->id,
            'user_id' => $user->id,
            'stage_id' => $plan->stages()->first()->id,
            'title' => 'Limpiar baño',
        ]);

        return [$user, $item];
    }
}
