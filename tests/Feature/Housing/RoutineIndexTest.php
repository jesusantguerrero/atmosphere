<?php

namespace Tests\Feature\Housing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Plan\Entities\PlanTypes;
use Tests\TestCase;

class RoutineIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guards against the Plan module shipping without the ROUTINE case, which
     * makes every routine route fatal with "Undefined constant ...::ROUTINE".
     */
    public function test_plan_module_exposes_the_routine_type(): void
    {
        $this->assertSame('routine', PlanTypes::ROUTINE->value);
    }

    public function test_bootstraps_a_routine_plan_with_one_stage_per_weekday(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/housing/routine')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Routine/Index')
                ->has('plan.id')
                ->has('plan.blocks', 0)
                ->has('members')
            );
    }

    public function test_reuses_the_existing_routine_plan_on_a_second_visit(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $first = $this->actingAs($user)->get('/housing/routine');
        $second = $this->actingAs($user)->get('/housing/routine');

        $this->assertSame(
            $first->viewData('page')['props']['plan']['id'],
            $second->viewData('page')['props']['plan']['id'],
        );
    }

    public function test_current_endpoint_returns_current_and_next_blocks(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->getJson('/housing/routine/current')
            ->assertOk()
            ->assertJsonStructure(['current', 'next']);
    }

    public function test_requires_authentication(): void
    {
        $this->get('/housing/routine')->assertRedirect('/login');
    }

    public function test_week_includes_sunday_exception_and_excludes_next_monday(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $planId = $this->actingAs($user)->get('/housing/routine')->viewData('page')['props']['plan']['id'];

        foreach (['2026-09-27' => 'Sunday exception', '2026-09-28' => 'Next Monday exception'] as $date => $title) {
            $this->postJson("/housing/routine/{$planId}/blocks", [
                'day' => 0,
                'date' => $date,
                'title' => $title,
                'start' => '16:00',
                'end' => '17:30',
            ])->assertCreated();
        }

        $this->getJson("/housing/routine/{$planId}/week?date=2026-09-21")
            ->assertOk()
            ->assertJsonPath('week_start', '2026-09-21')
            ->assertJsonPath('week_end', '2026-09-27')
            ->assertJsonCount(1, 'blocks')
            ->assertJsonPath('blocks.0.date', '2026-09-27')
            ->assertJsonPath('blocks.0.day', 6);

        $this->getJson("/housing/routine/{$planId}/week?date=2026-09-27")
            ->assertJsonCount(1, 'blocks')
            ->assertJsonPath('blocks.0.date', '2026-09-27');
    }

    public function test_current_uses_local_sunday_when_utc_is_already_monday(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $planId = $this->actingAs($user)->get('/housing/routine')->viewData('page')['props']['plan']['id'];

        $this->postJson("/housing/routine/{$planId}/blocks", [
            'day' => 0,
            'date' => '2026-09-27',
            'title' => 'Sunday evening',
            'start' => '20:00',
            'end' => '21:00',
        ])->assertCreated();

        Carbon::setTestNow(Carbon::parse('2026-09-28 00:30:00', 'UTC'));
        try {
            $this->getJson('/housing/routine/current')
                ->assertOk()
                ->assertJsonPath('current.date', '2026-09-27')
                ->assertJsonPath('current.title', 'Sunday evening');
        } finally {
            Carbon::setTestNow();
        }
    }
}
