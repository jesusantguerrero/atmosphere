<?php

namespace App\Listeners\Menu;

use App\Events\Menu\AppCreated;

class ShowInApp
{
    /**
     * Handle the event.
     */
    public function handle(AppCreated $event): void
    {
        $menu = $event->menu;

        // Dashboard
        $menu->add('Dashboard', route('dashboard'));
        // Meal
        $menu->add('Meals', route('meals.overview'));
        // Finance
        $menu->add('Finance', route('finance.overview'));
        // Housing
        $menu->add('Housing', route('housing.overview'));
        // Loger Profiles
        $menu->add('Profiles', route('loger-profiles.index'));
    }
}
