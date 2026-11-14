<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferTeamOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_can_hand_the_team_to_a_member(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $team->users()->attach($member = User::factory()->create(), ['role' => 'editor']);

        $this->actingAs($owner)
            ->put("/teams/{$team->id}/owner", ['user_id' => $member->id, 'password' => 'password'])
            ->assertSessionHasNoErrors();

        $team->refresh();
        $this->assertSame($member->id, $team->user_id);
        $this->assertFalse($team->personal_team);
        $this->assertFalse($team->users()->whereKey($member->id)->exists());
        $this->assertSame('admin', $team->users()->whereKey($owner->id)->first()->membership->role);
    }

    public function test_only_the_owner_can_transfer(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $team->users()->attach($admin = User::factory()->create(), ['role' => 'admin']);
        $admin->forceFill(['current_team_id' => $team->id])->save();

        $this->actingAs($admin)
            ->put("/teams/{$team->id}/owner", ['user_id' => $admin->id, 'password' => 'password'])
            ->assertForbidden();

        $this->assertSame($owner->id, $team->fresh()->user_id);
    }

    public function test_the_new_owner_must_be_a_member(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $stranger = User::factory()->create();

        $this->actingAs($owner)
            ->put("/teams/{$team->id}/owner", ['user_id' => $stranger->id, 'password' => 'password'])
            ->assertSessionHasErrorsIn('transferTeamOwnership', ['user_id']);

        $this->assertSame($owner->id, $team->fresh()->user_id);
    }

    public function test_the_password_is_required(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->currentTeam;
        $team->users()->attach($member = User::factory()->create(), ['role' => 'editor']);

        $this->actingAs($owner)
            ->put("/teams/{$team->id}/owner", ['user_id' => $member->id, 'password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->assertSame($owner->id, $team->fresh()->user_id);
    }
}
