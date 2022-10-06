<?php

namespace App\Domains\AppCore\Data;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Payee;

class Demo
{

    private $faker = null;
    private $team = null;
    private $userId = null;
    private $items = [];
    private $count = 0;

    public function __construct(Model $team)
    {
        $this->team = $team;
        $this->userId = $team->user_id;
        $this->faker = \Faker\Factory::create();
        $this->items = [];
        $this->status = [];
        $this->count = 10;
    }

    public function accounts() {
        return [[
                'display_id' => 'cash',
                'name' => trans('demo.accounts.cash'),
                'account_detail_type_id' => 2
            ],
            [
                'display_id' => 'bank',
                'name' => trans('demo.accounts.nomina'),
                'account_detail_type_id' => 1
            ],
            [
                'display_id' => 'credit_card',
                'name' => trans('demo.accounts.credit_card'),
                'account_detail_type_id' => 5
            ],
            [
                'display_id' => 'savings',
                'name' => trans('demo.accounts.savings'),
                'account_detail_type_id' => 4
            ]
        ];
    }

    public function payees() {
        return ["Payee 1", "Payee 2", "Payee 3", "Payee 4"];
    }

    public function categoryGroups() {
        return [
            'Savings',
            'Immediate Obligations',
            'True Expenses',
            'Quality of Life Goals',
            'Just for Fun',
            'Wish Farm'
        ];
    }

    public function count($count) {
        $this->count = $count;
        return $this;
    }

    public function transactions($attrs = []) {
        $faker = \Faker\Factory::create();

        for ($i=0; $i < $this->count; $i++) {
            $this->items[] = function () use ($attrs, $faker) {
                $category = $this->team->budgetCategories()
                ->whereNotNull('parent_id')
                ->whereNot('display_id', Category::READY_TO_ASSIGN)
                ->get()
                ->random(1)
                ->first();
                $account = $this->team->budgetAccounts()->get()->random(1)->first();
                $payee = $this->team->payees()->get()->random(1)->first();

                $balance = $this->team->balance();
                print("The balance ". $balance . "\n");
                return array_merge([
                    'team_id' => $this->team->id,
                    'user_id' => $this->team->user_id,
                    'account_id' => $account->id,
                    'payee_id' => $payee->id,
                    'date' => $faker->dateTimeBetween(now()->subMonth(1)->startOfMonth(), now()->endOfMonth())->format('Y-m-d H:i:s'),
                    'currency_code' => 'USD',
                    'category_id' => $category->id,
                    'counter_account_id' => $payee->account_id,
                    'description' => fake()->text(22),
                    'direction' => $faker->randomElement([
                        Transaction::DIRECTION_CREDIT,
                        Transaction::DIRECTION_DEBIT,
                    ]),
                    'status' => Transaction::STATUS_VERIFIED,
                    'total' => $faker->randomFloat(2, 100, 500),
                    'items' => [],
                ], $attrs);
            };
        }
        return $this;
    }

    public function createTransactions() {
        foreach ($this->items as $item) {
            $data = is_callable($item) ? $item(): $item;
            Transaction::createTransaction($data);
        }
    }

    public function removeSampleData() {
        Account::where('user_id', $this->userId)?->delete();
        Category::where('user_id', $this->userId)?->delete();
        Payee::where('user_id', $this->userId)?->delete();
        Transaction::where('user_id', $this->userId)?->delete();
    }
}
