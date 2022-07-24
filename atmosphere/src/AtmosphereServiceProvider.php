<?php

namespace Freesgen\Atmosphere;

use Illuminate\Support\ServiceProvider;

class AtmosphereServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->registerRoutes();

    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    private function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
    }

}
