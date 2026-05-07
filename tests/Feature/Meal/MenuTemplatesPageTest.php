<?php

namespace Tests\Feature\Meal;

use App\Domains\Meal\Models\MealMenu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MenuTemplatesPageTest extends TestCase
{
    use RefreshDatabase;

    private function userWithMealsEnabled(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->switchTeam($team);
        $team->modules()->where('name', 'Meals')->update(['enabled' => true]);
        $team->unsetRelation('modules');

        return $user->fresh();
    }

    public function test_templates_page_renders_for_authenticated_user(): void
    {
        $user = $this->userWithMealsEnabled();

        $this->actingAs($user);
        $this->get(route('meals.menus.templates'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meals/Templates')
                ->has('templates')
            );
    }

    public function test_templates_page_returns_only_templates(): void
    {
        $user = $this->userWithMealsEnabled();
        $team = $user->ownedTeams()->first();
        $teamId = $team->id;

        $template = MealMenu::factory()->template()->create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'name' => 'Healthy Week',
        ]);

        MealMenu::factory()->create([
            'team_id' => $teamId,
            'user_id' => $user->id,
            'name' => 'Not a template',
            'is_template' => false,
        ]);

        $this->actingAs($user);
        $this->get(route('meals.menus.templates'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meals/Templates')
                ->has('templates', 1)
                ->where('templates.0.id', $template->id)
                ->where('templates.0.name', 'Healthy Week')
                ->where('templates.0.meal_plans_count', 0)
            );
    }

    public function test_templates_page_requires_authentication(): void
    {
        $this->get(route('meals.menus.templates'))
            ->assertRedirect(route('login'));
    }
}
