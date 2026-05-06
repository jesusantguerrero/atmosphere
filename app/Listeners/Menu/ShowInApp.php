<?php

namespace App\Listeners\Menu;

use App\Events\Menu\AppCreated;

class ShowInApp
{
    public function handle(AppCreated $event): void
    {
        $menu = $event->menu;

        // Today (TODAY-1 v0.1 — daily-first command center; coexists with Dashboard)
        $menu->add('Today', route('today'));
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
