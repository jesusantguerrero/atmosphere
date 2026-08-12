<?php

namespace Tests\Feature\Housing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
