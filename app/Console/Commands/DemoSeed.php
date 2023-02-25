<?php

namespace App\Console\Commands;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class DemoSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed data for demo user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $class = $this->laravel->make(DemoDataSeeder::class);

        $seeder = $class->setContainer($this->laravel)->setCommand($this);

        $seeder->__invoke();
        return 0;
    }
}
