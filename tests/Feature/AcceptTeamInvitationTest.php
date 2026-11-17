<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcceptTeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->withPersonalTeam()->create();
        $this->team = $this->owner->ownedTeams()->first();
        $this->owner->forceFill(['current_team_id' => $this->team->id])->save();
    }

    private function invite(string $email, string $role = 'editor'): TeamInvitation
    {
        return $this->team->teamInvitations()->create(['email' => $email, 'role' => $role]);
    }

    public function test_an_unverified_invitee_without_a_household_accepts_and_lands_in_it_verified(): void
    {
        $invitee = User::factory()->unverified()->create(['email' => 'Dianita@Example.com']);
        $invitation = $this->invite('dianita@example.com');

        $this->actingAs($invitee)
            ->patch(route('api.team-invitations.accept-internal', $invitation))
            ->assertRedirect(config('fortify.home'));

        $invitee->refresh();
        $this->assertTrue($invitee->belongsToTeam($this->team));
        $this->assertSame('editor', $invitee->teamRole($this->team)->key);
        $this->assertSame($this->team->id, (int) $invitee->current_team_id);
        $this->assertTrue($invitee->hasVerifiedEmail());
        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
        $this->assertSame($this->team->id, (int) $this->owner->fresh()->current_team_id);
    }

    public function test_an_invitation_for_another_email_cannot_be_accepted_or_rejected(): void
    {
        $stranger = User::factory()->create();
        $invitation = $this->invite('dianita@example.com');

        $this->actingAs($stranger)->patch(route('api.team-invitations.accept-internal', $invitation))->assertForbidden();
        $this->actingAs($stranger)->delete(route('api.team-invitations.reject', $invitation))->assertForbidden();

        $this->assertFalse($stranger->fresh()->belongsToTeam($this->team));
        $this->assertDatabaseHas('team_invitations', ['id' => $invitation->id]);
    }

    public function test_the_invitee_rejects_their_invitation(): void
    {
        $invitee = User::factory()->create(['email' => 'dianita@example.com']);
        $invitation = $this->invite('dianita@example.com');

        $this->actingAs($invitee)->delete(route('api.team-invitations.reject', $invitation))->assertRedirect();

        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
        $this->assertFalse($invitee->fresh()->belongsToTeam($this->team));
    }

    public function test_a_missing_invitation_is_not_found(): void
    {
        $this->actingAs(User::factory()->create())
            ->patch(route('api.team-invitations.accept-internal', 999999))
            ->assertNotFound();
    }
}
