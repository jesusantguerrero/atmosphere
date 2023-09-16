<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Plan\Database\Seeders\PlanDatabaseSeeder;
use Modules\Plan\Database\Seeders\PlanTemplateSeeder;
use Modules\Watchlist\Database\Seeders\WatchlistDatabaseSeeder;

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
            ThemeSeeder::class,
            PlanDatabaseSeeder::class,
            PlanTemplateSeeder::class,
            WatchlistDatabaseSeeder::class

        ]);
        Artisan::call('journal:set-accounts');
    }
}
