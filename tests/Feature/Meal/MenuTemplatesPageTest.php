<?php

namespace Tests\Feature\Meal;

use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealMenu;
use App\Domains\Meal\Models\MealPlan;
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

    public function test_load_template_creates_new_meal_plans_with_shifted_dates(): void
    {
        $user = $this->userWithMealsEnabled();
        $team = $user->ownedTeams()->first();

        $menu = MealMenu::factory()->template()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Weekly Healthy',
        ]);

        $mealA = Meal::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Oatmeal',
        ]);
        $mealB = Meal::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Salmon',
        ]);

        // Source plans: Mon + Wed of some past week
        MealPlan::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'meal_id' => $mealA->id,
            'date' => '2026-04-07 00:00:00', // Monday
            'name' => 'Oatmeal',
            'menu_id' => $menu->id,
        ]);
        MealPlan::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'meal_id' => $mealB->id,
            'date' => '2026-04-09 00:00:00', // Wednesday (+2 days)
            'name' => 'Salmon',
            'menu_id' => $menu->id,
        ]);

        $this->actingAs($user);
        $response = $this->post(route('meals.menus.load', $menu), [
            'target_start_date' => '2026-05-12', // Next Monday
        ]);

        $response->assertRedirect();

        // Should create 2 new MealPlan rows with shifted dates
        $this->assertDatabaseHas('meal_plans', [
            'team_id' => $team->id,
            'name' => 'Oatmeal',
            'date' => '2026-05-12 00:00:00', // target start
        ]);
        $this->assertDatabaseHas('meal_plans', [
            'team_id' => $team->id,
            'name' => 'Salmon',
            'date' => '2026-05-14 00:00:00', // +2 days offset preserved
        ]);

        // Original plans still exist (load doesn't delete source)
        $this->assertDatabaseCount('meal_plans', 4); // 2 original + 2 new
    }

    public function test_load_template_of_another_team_is_forbidden(): void
    {
        $user = $this->userWithMealsEnabled();
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->ownedTeams()->first();

        $menu = MealMenu::factory()->template()->create([
            'team_id' => $otherTeam->id,
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($user);
        $this->post(route('meals.menus.load', $menu), [
            'target_start_date' => '2026-05-12',
        ])->assertForbidden();
    }

    public function test_load_template_with_no_meals_returns_warning(): void
    {
        $user = $this->userWithMealsEnabled();
        $team = $user->ownedTeams()->first();

        $menu = MealMenu::factory()->template()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);
        $this->post(route('meals.menus.load', $menu), [
            'target_start_date' => '2026-05-12',
        ])->assertRedirect()
            ->assertSessionHas('flash.type', 'warning');

        $this->assertDatabaseCount('meal_plans', 0);
    }
}
