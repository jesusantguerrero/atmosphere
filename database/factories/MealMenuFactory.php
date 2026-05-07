<?php

namespace Database\Factories;

use App\Domains\Meal\Models\MealMenu;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealMenu>
 */
class MealMenuFactory extends Factory
{
    protected $model = MealMenu::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'is_template' => false,
        ];
    }

    public function template(): static
    {
        return $this->state(['is_template' => true]);
    }
}
