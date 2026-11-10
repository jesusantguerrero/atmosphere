<?php

namespace Tests\Unit;

use App\Domains\Meal\Services\MealService;
use PHPUnit\Framework\TestCase;

/**
 * Planning the same ingredient in several meals used to keep the first
 * quantity (a post-increment that never stuck), so the shopping list asked for
 * 1 cup of rice for a whole week of rice.
 */
class MealIngredientsTotalsTest extends TestCase
{
    public function test_quantities_add_up_per_unit_across_planned_meals(): void
    {
        $plans = [
            $this->plannedMeal([['Arroz', 2, 'taza'], ['Pollo', 1, 'lb']]),
            $this->plannedMeal([['Arroz', 1.5, 'taza'], ['Arroz', 1, 'paquete']]),
            $this->plannedMeal([]),
            (object) ['dateable' => null],
        ];

        $ingredients = MealService::getIngredients($plans);

        $this->assertSame(['taza' => 3.5, 'paquete' => 1], $ingredients['Arroz']['amounts']);
        $this->assertSame(3.5, $ingredients['Arroz']['quantity']);
        $this->assertSame('taza', $ingredients['Arroz']['unit']);
        $this->assertSame('3.5 taza + 1 paquete', MealService::ingredientAmountLabel($ingredients['Arroz']));
        $this->assertSame('1 lb', MealService::ingredientAmountLabel($ingredients['Pollo']));
    }

    /**
     * @param  list<array{0: string, 1: int|float, 2: string}>  $ingredients
     */
    private function plannedMeal(array $ingredients): object
    {
        return (object) ['dateable' => (object) ['meal' => (object) [
            'ingredients' => array_map(fn ($ingredient) => (object) [
                'name' => $ingredient[0],
                'quantity' => $ingredient[1],
                'unit' => $ingredient[2],
            ], $ingredients),
        ]]];
    }
}
