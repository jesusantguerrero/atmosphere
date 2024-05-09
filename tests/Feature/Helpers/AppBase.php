<?php

namespace Tests\Feature\Helpers;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class AppBase extends TestCase
{
  use WithFaker;
  use RefreshDatabase;

  protected User $user;

  protected function setup(): void {
    parent::setup();
    $this->seed();
    $user = User::factory()->withPersonalTeam()->create();
    $user->current_team_id = $user->fresh()->ownedTeams()->latest('id')->first()->id;
    $user->save();
    $this->user = $user;
  }
}