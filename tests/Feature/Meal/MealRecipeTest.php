<?php

namespace Tests\Feature\Meal;

use App\Domains\AppCore\Models\CoreModule;
use App\Domains\Meal\Models\Meal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MealRecipeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_meal_planer_index()
    {
        $user = $this->mealsUser();

        $this->actingAs($user);
        $this->get('/meal-planner')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meals/Planner')
                ->has('meals')
                ->has('mealTypes', 4));
    }

    public function test_recipe_form_saves_times_servings_and_notes(): void
    {
        $user = $this->mealsUser();

        $this->actingAs($user)->post('/meals', [
            'name' => 'Arroz con pollo',
            'notes' => 'Sofrito first',
            'prep_minutes' => 15,
            'cook_minutes' => 40,
            'servings' => 4,
            'ingredients' => [['name' => 'Arroz', 'quantity' => 2, 'unit' => 'taza', 'product_id' => '']],
        ])->assertRedirect();

        $meal = Meal::where('name', 'Arroz con pollo')->firstOrFail();
        $this->assertSame([15, 40, 4, 'Sofrito first'], [$meal->prep_minutes, $meal->cook_minutes, $meal->servings, $meal->notes]);
        $this->assertSame(1, $meal->ingredients()->count());

        $this->actingAs($user)->put("/meals/{$meal->id}", ['name' => 'Arroz con pollo', 'servings' => 6, 'ingredients' => []])
            ->assertRedirect();
        $this->assertSame(6, $meal->fresh()->servings);
    }

    private function mealsUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $teamId = $user->ownedTeams()->first()->id;
        $user->forceFill(['current_team_id' => $teamId])->save();
        CoreModule::where('team_id', $teamId)->where('name', 'Meals')->update(['enabled' => true]);

        return $user->fresh();
    }
}
