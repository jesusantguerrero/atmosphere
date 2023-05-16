<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DemoSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate demo environment';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Artisan::call('migrate:fresh --force --seed');
        Artisan::call('demo:seed');
        return 0;
    }
}
