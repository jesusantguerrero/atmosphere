<?php

namespace Database\Factories;

use App\Domains\Automation\Models\Automation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutomationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Automation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'team_id' => $user->current_team_id ?? 1,
            'automatable_id' => null,
            'automatable_type' => null,
            'automation_recipe_id' => null,
            'integration_id' => null,
            'trigger_id' => 1,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'sentence' => $this->faker->sentence,
            'config' => [],
            'track' => [],
            'status' => true,
            'is_background' => true,
        ];
    }
}
