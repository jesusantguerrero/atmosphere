<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Insane\Journal\Models\Core\Account;
use Insane\Journal\Models\Core\Payee;

class GeneratePayees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-payees {teamId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $teamId = $this->argument('teamId');
        $accounts = Account::where([
            'team_id' => $teamId,
        ])->get();

        foreach ($accounts as $account) {
            Payee::create([
                'team_id' => $teamId,
                'user_id' => 0,
                'name' => "Transfer: $account->name",
                'account_id' => $account->id
            ]);
        }
    }
}
