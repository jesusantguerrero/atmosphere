<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutomationCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check automatic flows of users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return 0;
    }
}
