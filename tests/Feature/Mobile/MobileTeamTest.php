<?php

namespace Tests\Feature\Mobile;

use App\Mail\TeamInvitationMail;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MobileTeamTest extends TestCase
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

    private function addMember(string $role, ?Team $team = null): User
    {
        $team ??= $this->team;
        $member = User::factory()->withPersonalTeam()->create();
        $team->users()->attach($member, ['role' => $role]);
        $member->forceFill(['current_team_id' => $team->id])->save();

        return $member;
    }

    private function as(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('test')->plainTextToken);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/mobile/team/members')->assertUnauthorized();
        $this->postJson('/api/mobile/team/invitations', [])->assertUnauthorized();
        $this->putJson('/api/mobile/team/members/1', [])->assertUnauthorized();
        $this->deleteJson('/api/mobile/team/members/1')->assertUnauthorized();
        $this->deleteJson('/api/mobile/team/invitations/1')->assertUnauthorized();
    }

    public function test_members_lists_owner_members_invitations_and_my_role(): void
    {
        $editor = $this->addMember('editor');
        $this->team->teamInvitations()->create(['email' => 'pending@example.com', 'role' => 'admin']);

        $response = $this->as($editor)->getJson('/api/mobile/team/members')->assertOk();

        $response->assertJsonPath('my_role', 'editor')
            ->assertJsonCount(2, 'members')
            ->assertJsonPath('members.0.role', 'owner')
            ->assertJsonPath('members.0.email', $this->owner->email)
            ->assertJsonPath('members.1.role', 'editor')
            ->assertJsonPath('invitations.0.email', 'pending@example.com')
            ->assertJsonStructure(['members' => [['id', 'name', 'email', 'role', 'photo_url']], 'invitations' => [['id', 'email', 'role', 'created_at']]]);

        $this->as($this->owner)->getJson('/api/mobile/team/members')->assertJsonPath('my_role', 'owner');
    }

    public function test_members_never_leaks_other_teams(): void
    {
        $stranger = User::factory()->withPersonalTeam()->create();
        $stranger->forceFill(['current_team_id' => $stranger->ownedTeams()->first()->id])->save();

        $emails = collect($this->as($this->owner)->getJson('/api/mobile/team/members')->json('members'))->pluck('email');

        $this->assertNotContains($stranger->email, $emails);
    }

    public function test_owner_can_invite_member(): void
    {
        Mail::fake();

        $this->as($this->owner)->postJson('/api/mobile/team/invitations', ['email' => 'new@example.com', 'role' => 'editor'])
            ->assertCreated()
            ->assertJsonPath('email', 'new@example.com')
            ->assertJsonPath('role', 'editor');

        $this->assertDatabaseHas('team_invitations', ['team_id' => $this->team->id, 'email' => 'new@example.com']);
        Mail::assertSent(TeamInvitationMail::class);
    }

    public function test_invitation_validates_email_role_and_duplicates(): void
    {
        Mail::fake();
        $member = $this->addMember('editor');

        $this->as($this->owner)->postJson('/api/mobile/team/invitations', ['email' => 'nope', 'role' => 'editor'])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $this->as($this->owner)->postJson('/api/mobile/team/invitations', ['email' => 'a@example.com', 'role' => 'superuser'])
            ->assertStatus(422)->assertJsonValidationErrors('role');

        $this->as($this->owner)->postJson('/api/mobile/team/invitations', ['email' => $member->email, 'role' => 'editor'])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $this->as($this->owner)->postJson('/api/mobile/team/invitations', ['email' => 'a@example.com', 'role' => 'editor'])->assertCreated();
        $this->as($this->owner)->postJson('/api/mobile/team/invitations', ['email' => 'a@example.com', 'role' => 'editor'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_non_owner_cannot_invite_cancel_update_or_remove(): void
    {
        Mail::fake();
        $admin = $this->addMember('admin');
        $editor = $this->addMember('editor');
        $invitation = $this->team->teamInvitations()->create(['email' => 'pending@example.com', 'role' => 'editor']);

        $this->as($admin)->postJson('/api/mobile/team/invitations', ['email' => 'x@example.com', 'role' => 'editor'])->assertForbidden();
        $this->as($admin)->deleteJson("/api/mobile/team/invitations/{$invitation->id}")->assertForbidden();
        $this->as($admin)->putJson("/api/mobile/team/members/{$editor->id}", ['role' => 'admin'])->assertForbidden();
        $this->as($admin)->deleteJson("/api/mobile/team/members/{$editor->id}")->assertForbidden();

        $this->assertDatabaseHas('team_invitations', ['id' => $invitation->id]);
        $this->assertSame('editor', $editor->teamRole($this->team)->key);
        $this->assertTrue($this->team->fresh()->hasUser($editor));
    }

    public function test_owner_can_cancel_invitation(): void
    {
        $invitation = $this->team->teamInvitations()->create(['email' => 'pending@example.com', 'role' => 'editor']);

        $this->as($this->owner)->deleteJson("/api/mobile/team/invitations/{$invitation->id}")->assertOk();

        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
    }

    public function test_cannot_cancel_invitation_of_another_team(): void
    {
        $otherOwner = User::factory()->withPersonalTeam()->create();
        $foreign = $otherOwner->ownedTeams()->first()->teamInvitations()->create(['email' => 'p@example.com', 'role' => 'editor']);

        $this->as($this->owner)->deleteJson("/api/mobile/team/invitations/{$foreign->id}")->assertNotFound();

        $this->assertDatabaseHas('team_invitations', ['id' => $foreign->id]);
    }

    public function test_owner_can_change_member_role(): void
    {
        $editor = $this->addMember('editor');

        $this->as($this->owner)->putJson("/api/mobile/team/members/{$editor->id}", ['role' => 'admin'])
            ->assertOk()->assertJsonPath('role', 'admin');

        $this->assertSame('admin', $editor->fresh()->teamRole($this->team)->key);
    }

    public function test_role_change_rejects_invalid_role_and_non_members(): void
    {
        $editor = $this->addMember('editor');
        $stranger = User::factory()->withPersonalTeam()->create();

        $this->as($this->owner)->putJson("/api/mobile/team/members/{$editor->id}", ['role' => 'god'])
            ->assertStatus(422)->assertJsonValidationErrors('role');
        $this->as($this->owner)->putJson("/api/mobile/team/members/{$editor->id}", [])
            ->assertStatus(422)->assertJsonValidationErrors('role');

        $this->as($this->owner)->putJson("/api/mobile/team/members/{$stranger->id}", ['role' => 'admin'])->assertNotFound();
        $this->as($this->owner)->putJson("/api/mobile/team/members/{$this->owner->id}", ['role' => 'admin'])->assertNotFound();
    }

    public function test_cannot_manage_members_of_another_team_via_client_ids(): void
    {
        $otherOwner = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherOwner->ownedTeams()->first();
        $foreignMember = $this->addMember('editor', $otherTeam);

        $this->as($this->owner)->putJson("/api/mobile/team/members/{$foreignMember->id}", ['role' => 'admin'])->assertNotFound();
        $this->as($this->owner)->deleteJson("/api/mobile/team/members/{$foreignMember->id}")->assertNotFound();

        $this->assertTrue($otherTeam->fresh()->hasUser($foreignMember));
        $this->assertSame('editor', $foreignMember->teamRole($otherTeam)->key);
    }

    public function test_owner_can_remove_member_and_member_falls_back_to_personal_team(): void
    {
        $editor = $this->addMember('editor');

        $this->as($this->owner)->deleteJson("/api/mobile/team/members/{$editor->id}")->assertOk();

        $this->assertFalse($this->team->fresh()->hasUser($editor));
        $this->assertSame($editor->personalTeam()->id, $editor->fresh()->current_team_id);
    }

    public function test_member_can_leave_team_but_owner_cannot_be_removed(): void
    {
        $editor = $this->addMember('editor');

        $this->as($editor)->deleteJson("/api/mobile/team/members/{$editor->id}")->assertOk();
        $this->assertFalse($this->team->fresh()->hasUser($editor));

        $this->as($this->owner)->deleteJson("/api/mobile/team/members/{$this->owner->id}")->assertNotFound();
        $this->assertSame($this->owner->id, $this->team->fresh()->user_id);
    }
}
