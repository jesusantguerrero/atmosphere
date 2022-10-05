<?php

namespace Database\Seeders;

use App\Domains\AppCore\Data\Demo;
use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Payee;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::reguard();
        $this->down();

        $count =  10;
        $payees = [];
        $user = User::factory(['email' => 'demo@example.com'])->withPersonalTeam()->create();
        $team = Team::where('user_id', $user->id)->first();
        $demoData = new Demo($team);
        $faker = \Faker\Factory::create();

        $this->command->info('Creating sample data...');

        $bar = $this->command->getOutput()->createProgressBar(7);
        $bar->setFormat('verbose');
        $bar->start();

        $userSession = [
            'user_id' => $user->id,
            'team_id' => $team->id
        ];

        // Creating accounts

        foreach ($demoData->accounts() as $account) {
            $account = Account::guessAccount(
                $userSession,
                [$account['name']],
                array_merge($account, [
                    'currency_code' => 'USD',
                    'created_from' => 'core::seed',
                ])
            );
        }
        $bar->advance();

        // creating payees
        foreach ($demoData->payees() as $payee) {
            $payees[] = Payee::findOrCreateByName($userSession, $payee ?? 'General Provider');
        }
        $bar->advance();


        foreach ($demoData->categoryGroups() as $category) {
            $groupId = Category::findOrCreateByName($userSession, $category);
            Category::factory()
            ->team($team)
            ->count(5)
            ->transactions()
            ->create([
                'parent_id' => $groupId
            ]);
            $bar->advance();
        }


        // previous month
        // $previousMonth = now()->subMonth(1);
        // $demoData->count(2)->transactions([
        //     'total' => 4000,
        //     'payee_id' => $payees[0]->id,
        //     'direction' => Transaction::DIRECTION_DEBIT,
        //     'date' => $faker->dateTimeBetween($previousMonth->startOfMonth(), $previousMonth->endOfMonth())->format('Y-m-d H:i:s')
        // ])
        // ->createTransactions();
        // $bar->advance();

        // $demoData->count(20)->transactions([
        //     'direction' => Transaction::DIRECTION_CREDIT,
        //     'date' => $faker->dateTimeBetween($previousMonth->startOfMonth(), $previousMonth->endOfMonth())->format('Y-m-d H:i:s')
        // ])->createTransactions();
        // $bar->advance();

        // current month
        $month = now();
        $demoData->count(1)->transactions([
            'total' => 40000,
            'payee_id' => $payees[0]->id,
            'direction' => Transaction::DIRECTION_DEBIT,
            'date' => $faker->dateTimeBetween($month->startOfMonth(), $month->endOfMonth())->format('Y-m-d H:i:s')
        ])
        ->createTransactions();
        $bar->advance();

        $demoData->count(20)->transactions([
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => $faker->dateTimeBetween($month->startOfMonth(), $month->endOfMonth())->format('Y-m-d H:i:s')
        ])->createTransactions();
        $bar->finish();

        $this->command->info('');
        $this->command->info('Sample data created.');

        Model::unguard();
    }

    public function down() {
        $user = User::where('email','demo@example.com')->first();
        if ($team = Team::where('user_id', $user->id)->first()) {
            (new Demo($team))->removeSampleData();
            $team->delete();
        }
        $user->delete();
    }
}
