<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            AutomationSeeder::class,
            CountriesTableSeeder::class,
            TimeZonesTableSeeder::class,
            LanguagesTableSeeder::class,
            ThemeSeeder::class
        ]);
        Artisan::call('journal:set-accounts');
    }
}
