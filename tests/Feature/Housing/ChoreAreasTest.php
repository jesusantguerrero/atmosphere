<?php

namespace Tests\Feature\Housing;

use App\Domains\Housing\Services\ChoreAreaService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

class ChoreAreasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-13 10:00:00');
        [$this->user, $this->plan] = $this->makeTeamWithChoreBoard();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_completing_a_chore_on_the_web_board_stamps_last_done_at(): void
    {
        $item = $this->makeChore('Lavar platos');

        $this->actingAs($this->user)
            ->putJson("/housing/plans/{$this->plan->id}/items/{$item->id}", ['is_done' => true])
            ->assertOk();

        $this->assertSame('2026-11-13 10:00:00', Carbon::parse($item->fresh()->last_done_at)->toDateTimeString());
    }

    public function test_reopening_a_chore_keeps_the_last_done_at_stamp(): void
    {
        $item = $this->makeChore('Lavar platos');
        $item->update(['is_done' => true]);

        Carbon::setTestNow('2026-11-14 08:00:00');
        $item->fresh()->update(['is_done' => false]);

        $this->assertSame('2026-11-13 10:00:00', Carbon::parse($item->fresh()->last_done_at)->toDateTimeString());
    }

    public function test_saving_an_already_done_chore_does_not_restamp_it(): void
    {
        $item = $this->makeChore('Lavar platos');
        $item->update(['is_done' => true]);

        Carbon::setTestNow('2026-11-15 08:00:00');
        $item->fresh()->update(['title' => 'Lavar los platos']);

        $this->assertSame('2026-11-13 10:00:00', Carbon::parse($item->fresh()->last_done_at)->toDateTimeString());
    }

    public function test_interval_days_reads_the_recurrence_rule(): void
    {
        $service = app(ChoreAreaService::class);

        $this->assertSame(1, $service->intervalDays("DTSTART:20261113\nRRULE:FREQ=DAILY;INTERVAL=1;UNTIL=30991231"));
        $this->assertSame(8, $service->intervalDays('FREQ=DAILY;INTERVAL=8'));
        $this->assertSame(7, $service->intervalDays('FREQ=WEEKLY'));
        $this->assertSame(7, $service->intervalDays('FREQ=WEEKLY;INTERVAL=1;BYDAY=FR'));
        $this->assertSame(14, $service->intervalDays('FREQ=WEEKLY;INTERVAL=2'));
        $this->assertSame(1, $service->intervalDays('FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR'));
        $this->assertSame(30, $service->intervalDays('FREQ=MONTHLY'));
        $this->assertSame(90, $service->intervalDays('FREQ=MONTHLY;INTERVAL=3'));
        $this->assertNull($service->intervalDays(null));
        $this->assertNull($service->intervalDays('not a rule'));
    }

    public function test_daily_interval_chore_done_recently_is_fresh(): void
    {
        $chore = $this->describe($this->makeChore('Barrer', [
            'rrule' => 'FREQ=DAILY;INTERVAL=8',
            'last_done_at' => '2026-11-08 18:00:00',
        ]));

        $this->assertSame(8, $chore['interval_days']);
        $this->assertSame('2026-11-16', $chore['due_date']);
        $this->assertSame(3, $chore['days_left']);
        $this->assertSame('fresh', $chore['status']);
        $this->assertSame('green', $chore['color']);
        $this->assertSame(0.38, $chore['freshness']);
    }

    public function test_daily_interval_chore_near_its_due_date_is_due_soon(): void
    {
        $chore = $this->describe($this->makeChore('Barrer', [
            'rrule' => 'FREQ=DAILY;INTERVAL=8',
            'last_done_at' => '2026-11-06 09:00:00',
        ]));

        $this->assertSame(1, $chore['days_left']);
        $this->assertSame('due_soon', $chore['status']);
        $this->assertSame('yellow', $chore['color']);
    }

    public function test_weekly_chore_done_a_week_ago_is_due_today(): void
    {
        $chore = $this->describe($this->makeChore('Cambiar sábanas', [
            'rrule' => 'FREQ=WEEKLY',
            'last_done_at' => '2026-11-06 20:00:00',
        ]));

        $this->assertSame(0, $chore['days_left']);
        $this->assertSame('due_today', $chore['status']);
        $this->assertSame('yellow', $chore['color']);
        $this->assertSame(0.0, $chore['freshness']);
    }

    public function test_monthly_chore_a_few_days_late_is_orange_and_more_is_red(): void
    {
        $mildly = $this->describe($this->makeChore('Limpiar nevera', [
            'rrule' => 'FREQ=MONTHLY',
            'last_done_at' => '2026-10-11 10:00:00',
        ]));
        $badly = $this->describe($this->makeChore('Limpiar horno', [
            'rrule' => 'FREQ=MONTHLY',
            'last_done_at' => '2026-10-01 10:00:00',
        ]));

        $this->assertSame(-3, $mildly['days_left']);
        $this->assertSame('overdue', $mildly['status']);
        $this->assertSame('orange', $mildly['color']);
        $this->assertSame(-13, $badly['days_left']);
        $this->assertSame('red', $badly['color']);
        $this->assertSame(0.0, $badly['freshness']);
    }

    public function test_legacy_done_chore_without_last_done_at_uses_its_completion_day(): void
    {
        $item = $this->makeChore('Sacar basura', ['rrule' => 'FREQ=WEEKLY']);
        PlanItem::withoutEvents(fn () => $item->forceFill(['is_done' => true, 'commit_date' => '2026-11-10'])->save());

        $chore = $this->describe($item);

        $this->assertStringStartsWith('2026-11-10', $chore['last_done_at']);
        $this->assertSame('2026-11-17', $chore['due_date']);
        $this->assertSame('fresh', $chore['status']);
    }

    public function test_one_off_chore_uses_its_commit_date(): void
    {
        $tomorrow = $this->describe($this->makeChore('Pintar puerta', ['rrule' => null, 'commit_date' => '2026-11-14']));
        $undated = $this->describe($this->makeChore('Arreglar grifo', ['rrule' => null]));

        $this->assertNull($tomorrow['interval_days']);
        $this->assertSame(1, $tomorrow['days_left']);
        $this->assertSame('due_soon', $tomorrow['status']);
        $this->assertSame('2026-11-13', $undated['due_date']);
        $this->assertSame('due_today', $undated['status']);
    }

    public function test_never_done_recurring_chore_is_due_today(): void
    {
        $chore = $this->describe($this->makeChore('Regar plantas', ['rrule' => 'FREQ=DAILY;INTERVAL=3']));

        $this->assertNull($chore['last_done_at']);
        $this->assertSame('due_today', $chore['status']);
    }

    public function test_areas_group_chores_and_report_the_worst_status(): void
    {
        $this->makeChore('Fregar', ['area' => 'kitchen', 'rrule' => 'FREQ=WEEKLY', 'last_done_at' => '2026-11-12 10:00:00']);
        $this->makeChore('Limpiar nevera', ['area' => 'kitchen', 'rrule' => 'FREQ=MONTHLY', 'last_done_at' => '2026-10-01 10:00:00']);
        $this->makeChore('Ordenar juguetes', ['area' => 'kids', 'rrule' => 'FREQ=WEEKLY', 'last_done_at' => '2026-11-06 10:00:00']);
        $this->makeChore('Sin área', ['rrule' => 'FREQ=WEEKLY', 'last_done_at' => '2026-11-12 10:00:00']);
        $this->makeChore('Ya hecha', ['rrule' => null, 'is_done' => true]);

        [$otherUser] = $this->makeTeamWithChoreBoard();
        $otherPlan = Plan::where('team_id', $otherUser->current_team_id)->first();
        $this->makeChore('De otro equipo', ['area' => 'kitchen'], $otherPlan);

        $response = $this->actingAs($this->user)->getJson('/housing/chores/areas')->assertOk();

        $areas = collect($response->json('areas'))->keyBy('key');
        $this->assertSame(['kitchen', 'bathroom', 'bedroom', 'living', 'laundry', 'kids', 'general'], $areas->keys()->all());

        $this->assertSame('Cocina', $areas['kitchen']['label']);
        $this->assertSame(2, $areas['kitchen']['total']);
        $this->assertSame('overdue', $areas['kitchen']['worst_status']);
        $this->assertSame('red', $areas['kitchen']['color']);
        $this->assertSame(13, $areas['kitchen']['max_overdue_days']);
        $this->assertSame(1, $areas['kitchen']['counts']['overdue']);
        $this->assertSame(1, $areas['kitchen']['counts']['fresh']);

        $this->assertSame('due_today', $areas['kids']['worst_status']);
        $this->assertSame(1, $areas['general']['total']);
        $this->assertSame(0, $areas['bathroom']['total']);
        $this->assertNull($areas['bathroom']['worst_status']);

        $titles = collect($response->json('chores'))->pluck('title')->all();
        $this->assertSame(['Limpiar nevera', 'Ordenar juguetes', 'Fregar', 'Sin área'], $titles);
        $this->assertSame('Limpiar nevera', $response->json('most_overdue.title'));
        $this->assertSame('Cocina', $response->json('most_overdue.area_label'));
    }

    public function test_most_overdue_is_null_when_nothing_is_late(): void
    {
        $this->makeChore('Fregar', ['rrule' => 'FREQ=WEEKLY', 'last_done_at' => '2026-11-12 10:00:00']);

        $this->actingAs($this->user)
            ->getJson('/housing/chores/areas')
            ->assertOk()
            ->assertJsonPath('most_overdue', null);
    }

    public function test_moving_a_chore_to_another_area_keeps_its_due_date(): void
    {
        $item = $this->makeChore('Lavar ropa', ['commit_date' => '2026-11-20']);

        $this->actingAs($this->user)
            ->patchJson("/housing/chores/{$item->id}/area", ['area' => 'laundry'])
            ->assertOk()
            ->assertJsonPath('area', 'laundry')
            ->assertJsonPath('due_date', '2026-11-20');

        $this->assertSame('laundry', $item->fresh()->area);
        $this->assertSame('2026-11-20', $item->fresh()->commit_date);
    }

    public function test_clearing_a_chore_area_moves_it_to_general(): void
    {
        $item = $this->makeChore('Lavar ropa', ['area' => 'laundry']);

        $this->actingAs($this->user)
            ->patchJson("/housing/chores/{$item->id}/area", ['area' => null])
            ->assertOk()
            ->assertJsonPath('area', 'general');

        $this->assertNull($item->fresh()->area);
    }

    public function test_area_must_be_a_known_key(): void
    {
        $item = $this->makeChore('Lavar ropa');

        $this->actingAs($this->user)
            ->patchJson("/housing/chores/{$item->id}/area", ['area' => 'garage'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['area' => 'El área seleccionada no existe.']);

        $this->actingAs($this->user)
            ->patchJson("/housing/chores/{$item->id}/area", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('area');
    }

    public function test_cannot_move_another_teams_chore(): void
    {
        $item = $this->makeChore('Lavar ropa');
        [$otherUser] = $this->makeTeamWithChoreBoard();

        $this->actingAs($otherUser)
            ->patchJson("/housing/chores/{$item->id}/area", ['area' => 'kitchen'])
            ->assertForbidden();

        $this->assertNull($item->fresh()->area);
    }

    /**
     * @return array{0: User, 1: Plan}
     */
    private function makeTeamWithChoreBoard(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $plan = app(PlanService::class)->createPlanBoard($user->currentTeam, PlanTypes::CHORES);

        return [$user, $plan];
    }

    /**
     * Creates a chore, then writes the raw columns without model events (the
     * module's creating hook forces a daily rrule and its updating hook nulls
     * commit_date on pending chores).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeChore(string $title, array $attributes = [], ?Plan $plan = null): PlanItem
    {
        $plan ??= $this->plan;

        $item = PlanItem::create([
            'plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'user_id' => $plan->user_id,
            'stage_id' => $plan->stages()->first()->id,
            'title' => $title,
        ]);

        if ($attributes) {
            PlanItem::withoutEvents(fn () => $item->forceFill($attributes)->save());
        }

        return $item->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(PlanItem $item): array
    {
        return app(ChoreAreaService::class)->describe($item);
    }
}
