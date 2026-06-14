<?php

namespace App\Listeners\Menu;

use App\Events\Menu\AppCreated;

class ShowInApp
{
    public function handle(AppCreated $event): void
    {
        $menu = $event->menu;

        // Today merged into Dashboard (its Due Today + Upcoming widgets now live
        // in Pages/Dashboard/Index.vue side column). Route /today still redirects
        // to /dashboard for old bookmarks; sidebar entry retired.
        // Calendar (unified view across domains)
        $menu->add('Calendar', route('calendar'));
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
