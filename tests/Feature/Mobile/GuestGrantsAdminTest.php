<?php

namespace Tests\Feature\Mobile;

use App\Domains\LogerProfile\Models\LogerProfile;
use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class GuestGrantsAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Team $team;

    private LogerProfile $dianita;

    private LogerProfile $house;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->withPersonalTeam()->create();
        $this->team = $this->owner->ownedTeams()->first();
        $this->owner->forceFill(['current_team_id' => $this->team->id])->save();
        $this->dianita = LogerProfile::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Dianita']);
        $this->house = LogerProfile::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Casa']);
    }

    private function member(string $role): User
    {
        $member = User::factory()->create();
        $this->team->users()->attach($member, ['role' => $role]);
        $member->forceFill(['current_team_id' => $this->team->id])->save();

        return $member;
    }

    private function grantsPath(User $member): string
    {
        return '/api/mobile/team/members/'.$member->id.'/grants';
    }

    public function test_the_owner_replaces_a_guest_grants_and_write_implies_read(): void
    {
        $guest = $this->member(TeamMemberGrant::GUEST_ROLE);
        Sanctum::actingAs($this->owner, ['*']);

        $this->getJson($this->grantsPath($guest))->assertOk()->assertJsonPath('grants', []);

        $this->putJson($this->grantsPath($guest), ['grants' => [
            ['profile_id' => $this->dianita->id, 'access' => 'read'],
            ['profile_id' => $this->dianita->id, 'access' => 'write'],
            ['profile_id' => $this->house->id, 'access' => 'read'],
        ]])->assertOk()
            ->assertJsonCount(2, 'grants')
            ->assertJsonPath('grants.0.profile_id', $this->dianita->id)
            ->assertJsonPath('grants.0.access', 'write')
            ->assertJsonPath('grants.0.name', 'Dianita')
            ->assertJsonPath('grants.0.color', $this->dianita->color)
            ->assertJsonPath('grants.1.access', 'read');
        $this->assertSame([$this->dianita->id, $this->house->id], $guest->grantedProfileIds($this->team));
        $this->assertSame([$this->dianita->id], $guest->grantedProfileIds($this->team, TeamMemberGrant::ACCESS_WRITE));

        $this->putJson($this->grantsPath($guest), ['grants' => [['profile_id' => $this->house->id, 'access' => 'write']]])
            ->assertOk()->assertJsonCount(1, 'grants');
        $this->putJson($this->grantsPath($guest), ['grants' => []])->assertOk()->assertJsonPath('grants', []);
        $this->assertDatabaseCount('team_member_grants', 0);
    }

    public function test_grants_only_name_profiles_of_the_household_and_only_go_to_guests(): void
    {
        $guest = $this->member(TeamMemberGrant::GUEST_ROLE);
        $editor = $this->member('editor');
        $otherOwner = User::factory()->withPersonalTeam()->create();
        $foreign = LogerProfile::create(['team_id' => $otherOwner->ownedTeams()->first()->id, 'user_id' => $otherOwner->id, 'name' => 'Ajeno']);
        Sanctum::actingAs($this->owner, ['*']);

        $this->putJson($this->grantsPath($guest), ['grants' => [['profile_id' => $foreign->id, 'access' => 'read']]])
            ->assertUnprocessable()->assertJsonValidationErrors('grants.0.profile_id');
        $this->putJson($this->grantsPath($guest), ['grants' => [['profile_id' => $this->dianita->id, 'access' => 'admin']]])
            ->assertUnprocessable()->assertJsonValidationErrors('grants.0.access');
        $this->putJson($this->grantsPath($guest), [])->assertUnprocessable()->assertJsonValidationErrors('grants');
        $this->getJson($this->grantsPath($editor))->assertUnprocessable();
        $this->getJson('/api/mobile/team/members/'.$otherOwner->id.'/grants')->assertNotFound();
    }

    public function test_only_the_owner_manages_grants(): void
    {
        $guest = $this->member(TeamMemberGrant::GUEST_ROLE);
        $admin = $this->member('admin');

        Sanctum::actingAs($admin, ['*']);
        $this->putJson($this->grantsPath($guest), ['grants' => [['profile_id' => $this->dianita->id, 'access' => 'write']]])->assertForbidden();

        Sanctum::actingAs($guest, ['*']);
        $this->putJson($this->grantsPath($guest), ['grants' => [['profile_id' => $this->dianita->id, 'access' => 'write']]])->assertForbidden();
        $this->assertDatabaseCount('team_member_grants', 0);
    }

    public function test_a_guest_invitation_carries_its_grants_and_applies_them_on_accept(): void
    {
        Mail::fake();
        Sanctum::actingAs($this->owner, ['*']);

        $invitation = $this->postJson('/api/mobile/team/invitations', [
            'email' => 'nanny@example.com', 'role' => 'guest',
            'grants' => [['profile_id' => $this->dianita->id, 'access' => 'write']],
        ])->assertCreated()
            ->assertJsonPath('role', 'guest')
            ->assertJsonPath('grants.0.profile_id', $this->dianita->id)
            ->json();
        $this->getJson('/api/mobile/team/members')->assertJsonPath('invitations.0.grants.0.access', 'write');

        $this->postJson('/api/mobile/team/invitations', ['email' => 'chef@example.com', 'role' => 'editor', 'grants' => [['profile_id' => $this->dianita->id, 'access' => 'read']]])
            ->assertUnprocessable()->assertJsonValidationErrors('grants');

        $nanny = User::factory()->unverified()->create(['email' => 'nanny@example.com']);
        Sanctum::actingAs($nanny, ['*']);
        $this->postJson('/api/mobile/team-invitations/'.$invitation['id'].'/accept')->assertOk()
            ->assertJsonPath('role', 'guest')
            ->assertJsonPath('grants.0.profile_id', $this->dianita->id)
            ->assertJsonPath('grants.0.access', 'write');
    }

    public function test_leaving_the_guest_role_or_the_household_drops_the_grants(): void
    {
        $guest = $this->member(TeamMemberGrant::GUEST_ROLE);
        $other = $this->member(TeamMemberGrant::GUEST_ROLE);
        TeamMemberGrant::create(['team_id' => $this->team->id, 'user_id' => $guest->id, 'loger_profile_id' => $this->dianita->id, 'access' => 'read']);
        TeamMemberGrant::create(['team_id' => $this->team->id, 'user_id' => $other->id, 'loger_profile_id' => $this->dianita->id, 'access' => 'read']);
        Sanctum::actingAs($this->owner, ['*']);

        $this->putJson('/api/mobile/team/members/'.$guest->id, ['role' => 'editor'])->assertOk();
        $this->assertSame([], $guest->grantedProfileIds($this->team));

        $this->deleteJson('/api/mobile/team/members/'.$other->id)->assertOk();
        $this->assertDatabaseCount('team_member_grants', 0);
    }

    public function test_an_invitation_survives_a_mailer_failure(): void
    {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('An email must have a "From" or a "Sender" header.'));
        Sanctum::actingAs($this->owner, ['*']);

        $this->postJson('/api/mobile/team/invitations', ['email' => 'nanny@example.com', 'role' => 'guest'])->assertCreated();
        $this->assertDatabaseHas('team_invitations', ['email' => 'nanny@example.com', 'role' => 'guest']);
    }
}
