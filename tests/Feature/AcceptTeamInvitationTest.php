<?php

namespace Tests\Feature;

use App\Domains\LogerProfile\Models\LogerProfile;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMemberGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
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

    public function test_a_guest_invited_again_as_editor_takes_the_new_role_and_drops_the_guest_grants(): void
    {
        $guest = User::factory()->create(['email' => 'caro@example.com']);
        $this->team->users()->attach($guest, ['role' => TeamMemberGrant::GUEST_ROLE]);
        $guest->forceFill(['current_team_id' => $this->team->id])->save();
        $profile = LogerProfile::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Dianita']);
        TeamMemberGrant::create(['team_id' => $this->team->id, 'user_id' => $guest->id, 'loger_profile_id' => $profile->id, 'access' => TeamMemberGrant::ACCESS_READ]);
        $invitation = $this->invite('caro@example.com');

        $this->actingAs($guest->fresh())
            ->from(route('profile.show'))
            ->patch(route('api.team-invitations.accept-internal', $invitation))
            ->assertRedirect(config('fortify.home'))
            ->assertSessionHasNoErrors();

        $guest->refresh();
        $this->assertSame('editor', $guest->roleIn($this->team));
        $this->assertSame(1, $this->team->users()->whereKey($guest->id)->count());
        $this->assertSame(0, TeamMemberGrant::where('user_id', $guest->id)->count());
        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
        $this->assertSame($this->team->id, (int) $guest->current_team_id);
    }

    public function test_the_owner_accepting_an_invitation_to_their_own_household_stays_owner(): void
    {
        $invitation = $this->invite($this->owner->email, TeamMemberGrant::GUEST_ROLE);

        $this->actingAs($this->owner)->patch(route('api.team-invitations.accept-internal', $invitation))->assertRedirect();

        $this->assertSame('owner', $this->owner->fresh()->roleIn($this->team));
        $this->assertSame(0, $this->team->users()->whereKey($this->owner->id)->count());
        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
    }

    public function test_the_account_page_lists_households_with_their_role_and_pending_invitations(): void
    {
        $guest = User::factory()->withPersonalTeam()->create(['email' => 'caro@example.com']);
        $this->team->users()->attach($guest, ['role' => TeamMemberGrant::GUEST_ROLE]);
        $guest->forceFill(['current_team_id' => $this->team->id])->save();
        $this->invite('caro@example.com');

        $this->actingAs($guest->fresh())->get(route('profile.show'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Profile/Show')
                ->has('households', 2)
                ->where('households.0.role', fn ($role) => in_array($role, ['owner', 'guest'], true))
                ->has('invitations', 1)
                ->where('invitations.0.team', $this->team->name)
                ->where('invitations.0.owner', $this->owner->name)
                ->where('invitations.0.role', 'editor'));
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
