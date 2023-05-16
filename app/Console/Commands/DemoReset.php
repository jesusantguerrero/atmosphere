<?php

namespace App\Console\Commands;

use App\Domains\Transaction\Models\Transaction;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Category;
use Insane\Journal\Models\Core\Payment;
use Insane\Journal\Models\Core\PaymentDocument;
use Insane\Journal\Models\Core\TransactionLine;

class DemoReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset demo environment';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $user = User::where(['email' => 'demo@loger.com'])->first();
        $team = Team::where('user_id', $user->id)->first();

        Transaction::where(["team_id" => $team->id])->delete();
        TransactionLine::where(["team_id" => $team->id])->delete();
        Payment::where(["team_id" => $team->id])->delete();
        PaymentDocument::where(["team_id" => $team->id])->delete();

        // app
        Account::where(["team_id" => $team->id])->delete();
        Category::where(["team_id" => $team->id])->delete();

        Artisan::call("journal:set-chart-accounts $team->id");

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $class = $this->laravel->make(DemoDataSeeder::class);
        $seeder = $class->setContainer($this->laravel)->setCommand($this);
        $seeder->run($user, $team);

        echo "Done";
        return 0;
    }
}
