<?php

namespace App\Concerns;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory as FactoriesFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

abstract class Factory extends FactoriesFactory {

    /**
     * @var Team
     */
    protected $team;

    /**
     * @var User|EloquentModel|object|null
     */
    protected $user;

    public function __construct(...$arguments)
    {
        parent::__construct(...$arguments);

        $this->setUser();

        $this->setTeam();
    }

    public function team(Team $team): static
    {
        return $this->state(function ($attributes) use ($team) {
           return [
                'team_id' => $team->id,
                'user_id' => $team->user_id
            ];
        });
    }

    public function setUser(): void
    {
        $this->user = User::where('email', 'demo@example.com')->first();
    }

    public function setTeam(): void
    {
        $this->team = $this->user->allTeams()->first();
    }
}
