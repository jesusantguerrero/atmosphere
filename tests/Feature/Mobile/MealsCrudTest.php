<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Meal\Models\Meal;
use App\Domains\Meal\Models\MealPlan;
use App\Domains\Meal\Models\MealType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MealsCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_meal_plan(): void
    {
        [$user, $team, $mealType, $meal] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/meals/plan', [
            'meal_id' => $meal->id,
            'date' => '2026-10-20',
            'meal_type_id' => $mealType->id,
        ])->assertCreated()->assertJson(['success' => true]);

        $this->assertNotNull($response->json('plan'));
        $this->assertEquals($meal->name, $response->json('plan.name'));
    }

    public function test_store_meal_plan_rejects_other_teams_meal(): void
    {
        [$user] = $this->makeSetup();

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $otherType = MealType::create([
            'team_id' => $otherTeam->id,
            'user_id' => $other->id,
            'name' => 'Lunch',
        ]);
        $otherMeal = Meal::create([
            'team_id' => $otherTeam->id,
            'user_id' => $other->id,
            'name' => 'Other Meal',
            'meal_type_id' => $otherType->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/meals/plan', [
            'meal_id' => $otherMeal->id,
            'date' => '2026-10-20',
            'meal_type_id' => $otherType->id,
        ])->assertForbidden();
    }

    public function test_update_meal_plan_date(): void
    {
        [$user, $team, $mealType, $meal] = $this->makeSetup();
        Sanctum::actingAs($user);

        $planner = $this->createPlannedMeal($user, $team, $mealType, $meal, '2026-10-20');

        $this->patchJson("/api/mobile/meals/plan/{$planner->id}", [
            'date' => '2026-10-22',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertEquals('2026-10-22', $planner->fresh()->date->format('Y-m-d'));
    }

    public function test_update_meal_plan_meal_type(): void
    {
        [$user, $team, $mealType, $meal] = $this->makeSetup();
        Sanctum::actingAs($user);

        $dinner = MealType::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Dinner',
        ]);

        $planner = $this->createPlannedMeal($user, $team, $mealType, $meal, '2026-10-20');

        $this->patchJson("/api/mobile/meals/plan/{$planner->id}", [
            'meal_type_id' => $dinner->id,
        ])->assertOk();

        $this->assertEquals($dinner->id, $planner->fresh()->dateable->meal_type_id);
    }

    public function test_delete_meal_plan(): void
    {
        [$user, $team, $mealType, $meal] = $this->makeSetup();
        Sanctum::actingAs($user);

        $planner = $this->createPlannedMeal($user, $team, $mealType, $meal, '2026-10-20');
        $mealPlanId = $planner->dateable_id;

        $this->deleteJson("/api/mobile/meals/plan/{$planner->id}")
            ->assertOk()->assertJson(['success' => true]);

        $this->assertNull(Planner::find($planner->id));
        $this->assertNull(MealPlan::find($mealPlanId));
    }

    public function test_cannot_delete_other_teams_meal_plan(): void
    {
        [$user, $team, $mealType, $meal] = $this->makeSetup();
        $planner = $this->createPlannedMeal($user, $team, $mealType, $meal, '2026-10-20');

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->deleteJson("/api/mobile/meals/plan/{$planner->id}")
            ->assertForbidden();
    }

    public function test_store_recipe(): void
    {
        [$user, $team, $mealType] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/meals/recipes', [
            'name' => 'Pasta Carbonara',
            'meal_type_id' => $mealType->id,
            'notes' => 'Classic Italian recipe',
            'ingredients' => [
                ['name' => 'Spaghetti', 'quantity' => 500, 'unit' => 'g'],
                ['name' => 'Bacon', 'quantity' => 200, 'unit' => 'g'],
            ],
        ])->assertCreated()->assertJson(['success' => true]);

        $this->assertEquals('Pasta Carbonara', $response->json('recipe.name'));
        $this->assertCount(2, $response->json('recipe.ingredients'));
        $this->assertEquals('Spaghetti', $response->json('recipe.ingredients.0.name'));
    }

    public function test_store_recipe_minimal(): void
    {
        [$user] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/meals/recipes', [
            'name' => 'Simple Salad',
        ])->assertCreated();

        $this->assertEquals('Simple Salad', $response->json('recipe.name'));
    }

    public function test_update_recipe(): void
    {
        [$user, $team, $mealType, $meal] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->patchJson("/api/mobile/meals/recipes/{$meal->id}", [
            'name' => 'Renamed Recipe',
            'notes' => 'Updated notes',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertEquals('Renamed Recipe', $meal->fresh()->name);
        $this->assertEquals('Updated notes', $meal->fresh()->notes);
    }

    public function test_update_recipe_ingredients(): void
    {
        [$user, $team, $mealType, $meal] = $this->makeSetup();
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/mobile/meals/recipes/{$meal->id}", [
            'ingredients' => [
                ['name' => 'Rice', 'quantity' => 2, 'unit' => 'cups'],
                ['name' => 'Chicken', 'quantity' => 1, 'unit' => 'lb'],
            ],
        ])->assertOk();

        $this->assertCount(2, $response->json('recipe.ingredients'));
    }

    public function test_delete_recipe(): void
    {
        [$user, $team, $mealType, $meal] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/mobile/meals/recipes/{$meal->id}")
            ->assertOk()->assertJson(['success' => true]);

        $this->assertNull(Meal::find($meal->id));
    }

    public function test_cannot_delete_other_teams_recipe(): void
    {
        [$user, $team, $mealType, $meal] = $this->makeSetup();

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->deleteJson("/api/mobile/meals/recipes/{$meal->id}")
            ->assertForbidden();
    }

    public function test_toggle_recipe_favorite(): void
    {
        [$user, $team, $mealType, $meal] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->assertFalse((bool) $meal->is_liked);

        $response = $this->postJson("/api/mobile/meals/recipes/{$meal->id}/favorite")
            ->assertOk()->assertJson(['success' => true]);

        $this->assertTrue($response->json('is_liked'));
        $this->assertTrue((bool) $meal->fresh()->is_liked);

        // Toggle back
        $response = $this->postJson("/api/mobile/meals/recipes/{$meal->id}/favorite")
            ->assertOk();

        $this->assertFalse($response->json('is_liked'));
    }

    public function test_recipe_to_shopping_list_rejects_other_team(): void
    {
        [$user, $team, $mealType, $meal] = $this->makeSetup();

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->postJson("/api/mobile/meals/recipes/{$meal->id}/shopping-list")
            ->assertForbidden();
    }

    public function test_random_meal(): void
    {
        [$user, $team, $mealType] = $this->makeSetup();
        Sanctum::actingAs($user);

        Meal::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Meal A', 'meal_type_id' => $mealType->id]);
        Meal::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Meal B', 'meal_type_id' => $mealType->id]);

        $response = $this->getJson('/api/mobile/meals/random')
            ->assertOk();

        $this->assertNotNull($response->json('meal'));
        $this->assertContains($response->json('meal.name'), ['Meal A', 'Meal B', 'Test Meal']);
    }

    public function test_random_meal_empty(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        Sanctum::actingAs($user->fresh());

        $response = $this->getJson('/api/mobile/meals/random')
            ->assertOk();

        $this->assertNull($response->json('meal'));
    }

    public function test_random_meal_biases_favorites(): void
    {
        [$user, $team, $mealType] = $this->makeSetup();
        Sanctum::actingAs($user);

        // Create many non-favorites and one favorite
        for ($i = 0; $i < 10; $i++) {
            Meal::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => "Regular $i", 'meal_type_id' => $mealType->id]);
        }
        $fav = Meal::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Favorite', 'meal_type_id' => $mealType->id, 'is_liked' => true]);

        $response = $this->getJson('/api/mobile/meals/random')
            ->assertOk();

        // With favorites existing, random should return only from favorites pool
        $this->assertEquals('Favorite', $response->json('meal.name'));
    }

    public function test_random_meal_filter_by_type(): void
    {
        [$user, $team, $mealType] = $this->makeSetup();
        Sanctum::actingAs($user);

        $dinner = MealType::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Dinner']);
        Meal::create(['team_id' => $team->id, 'user_id' => $user->id, 'name' => 'Dinner Meal', 'meal_type_id' => $dinner->id]);

        $response = $this->getJson("/api/mobile/meals/random?meal_type_id={$dinner->id}")
            ->assertOk();

        $this->assertEquals('Dinner Meal', $response->json('meal.name'));
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $mealType = MealType::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Breakfast',
        ]);

        $meal = Meal::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Test Meal',
            'meal_type_id' => $mealType->id,
        ]);

        return [$user, $team, $mealType, $meal];
    }

    private function createPlannedMeal(User $user, $team, MealType $mealType, Meal $meal, string $date): Planner
    {
        $mealPlan = MealPlan::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => $meal->name,
            'date' => $date,
            'meal_type_id' => $mealType->id,
            'meal_id' => $meal->id,
        ]);

        return Planner::create([
            'dateable_type' => MealPlan::class,
            'dateable_id' => $mealPlan->id,
            'date' => $mealPlan->date,
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);
    }
}
