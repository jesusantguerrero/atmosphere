<?php

namespace App\Listeners;

use App\Domains\AppCore\Services\TeamDataPurger;
use Laravel\Jetstream\Events\TeamDeleted;

class TrashTeamSettings
{
    public function __construct(private TeamDataPurger $purger) {}

    public function handle(TeamDeleted $event): void
    {
        $this->purger->purge($event->team->id);
    }
}
