<?php

namespace Tests\Feature\Housing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Guards the kitchen-screen chores route against Plan-module drift. The
 * FamilyView board links to /housing/chores/screen with a hardcoded path, so a
 * deploy whose plan-module predates that route silently falls through to
 * Route::resource's `show` with {chore}="screen" and fatals with
 * "Call to undefined method ChoreController::show()".
 */
class ChoresScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_module_registers_the_dedicated_chores_screen_route(): void
    {
        $route = Route::getRoutes()->getByName('chores.screen');

        $this->assertNotNull($route, 'The plan-module is missing the chores.screen route.');
        $this->assertSame('housing/chores/screen', $route->uri());
        $this->assertStringEndsWith('@screen', $route->getActionName());
    }

    public function test_chores_resource_only_registers_implemented_verbs(): void
    {
        $this->assertNull(
            Route::getRoutes()->getByName('chores.show'),
            'Route::resource registered a `show` route ChoreController does not implement.'
        );
    }

    public function test_screen_renders_the_chores_screen_page(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/housing/chores/screen')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Housing/ChoresScreen')
                ->has('chores')
                ->has('users')
            );
    }
}
