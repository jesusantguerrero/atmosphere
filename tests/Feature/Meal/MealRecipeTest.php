<?php

namespace Tests\Feature\Meal;

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
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user);
        $this->get('/meal-planner')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meals/Planner')
                ->has('meals')
                ->has('mealTypes', 4));
    }
}
