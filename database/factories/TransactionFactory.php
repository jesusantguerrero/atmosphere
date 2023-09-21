<?php

namespace Database\Factories;

use App\Concerns\Factory;
use App\Domains\Transaction\Models\Transaction;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $category = $this->team->budgetCategories()->whereNotNull('parent_id')->get()->random(1)->first();
        $account = $this->team->accounts()->get()->random(1)->first();
        $payee = $this->team->payees()->get()->random(1)->first();

        Transaction::createTransaction([
            'team_id' => $this->team->team_id,
            'user_id' => $this->team->user_id,
            'account_id' => $account->id,
            'payee_id' => $payee->id,
            'date' => $this->faker->dateTimeBetween(now()->subMonth(1)->startOfMonth(), now()->endOfMonth())->format('Y-m-d H:i:s'),
            'currency_code' => 'USD',
            'category_id' => $category->id,
            'counter_account_id' => $payee->account_id,
            'description' => fake()->text(22),
            'direction' => $this->faker->randomElement([
                Transaction::DIRECTION_CREDIT,
                Transaction::DIRECTION_DEBIT,
            ]),
            'total' => $this->faker->randomFloat(2, 1, 1000),
            'items' => json_encode([]),
        ]);
    }
}
