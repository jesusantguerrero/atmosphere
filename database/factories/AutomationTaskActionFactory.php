<?php

namespace Database\Factories;

use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutomationTaskActionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AutomationTaskAction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::factory()->create();
        $automation = Automation::factory()->create([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id ?? 1,
        ]);

        return [
            'team_id' => $automation->team_id,
            'user_id' => $automation->user_id,
            'automation_id' => $automation->id,
            'automation_task_id' => null,
            'integration_id' => null,
            'name' => $this->faker->words(2, true),
            'entity' => 'App\\Domains\\Integration\\Actions\\TestAction',
            'task_type' => 'action',
            'accepts_config' => false,
            'values' => json_encode([]),
        ];
    }
}
