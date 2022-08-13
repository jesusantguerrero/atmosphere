<?php

namespace Tests\Feature;

use App\Models\MealType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

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
        MealType::factory()->count(3)->create([
            'user_id' => $user->id,
            'team_id' => $user->ownedTeams()->first()->id,
        ]);


        $this->actingAs($user);
        $this->get('/meal-planner')
        ->assertInertia(fn (Assert $page) => $page
        ->component('Meals/Planner')
        ->has('meals')
        ->has('mealTypes', 3));
    }
}
