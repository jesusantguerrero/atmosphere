<?php

namespace Database\Factories;

use App\Concerns\Factory;
use App\Domains\AppCore\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class CategoryFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->text(15),
            'resource_type' => 'transactions',
            'color' => $this->faker->hexColor(),
        ];
    }

    /**
     * Indicate that the user is suspended.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function transactions()
    {
        return $this->state(function (array $attributes) {
            return [
                'resource_type' => 'transactions',
            ];
        });
    }

    /**
     * Indicate that the user is suspended.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function transactionGroup()
    {
        return $this->state(function (array $attributes) {
            return [
                'resource_type' => 'transactions',
            ];
        });
    }
}
