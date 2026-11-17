<?php

namespace Tests\Feature\Mobile;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class NewAccountAccessTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        $owner = User::factory()->withPersonalTeam()->create();
        $this->team = $owner->ownedTeams()->first();
    }

    private function invite(string $email, string $role = 'editor'): TeamInvitation
    {
        return $this->team->teamInvitations()->create(['email' => $email, 'role' => $role]);
    }

    private function login(User $user): TestResponse
    {
        return $this->postJson('/api/sanctum/token', ['email' => $user->email, 'password' => 'password', 'device_name' => 'android']);
    }

    public function test_wrong_credentials_answer_422_with_a_code(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/sanctum/token', ['email' => $user->email, 'password' => 'nope', 'device_name' => 'android'])
            ->assertStatus(422)->assertJsonPath('code', 'invalid_credentials')->assertJsonValidationErrors('email');
    }

    public function test_an_unverified_invitee_signs_in_accepts_and_ends_up_verified_in_the_household(): void
    {
        $invitee = User::factory()->unverified()->create();
        $invitation = $this->invite($invitee->email);

        $token = $this->login($invitee)->assertOk()->json();
        $this->assertIsString($token);

        $this->withToken($token)->getJson('/api/mobile/me')->assertOk()
            ->assertJsonPath('current_team', null)
            ->assertJsonPath('pending_invitations.0.id', $invitation->id)
            ->assertJsonPath('pending_invitations.0.team_name', $this->team->name)
            ->assertJsonPath('pending_invitations.0.role', 'editor');
        $this->withToken($token)->getJson('/api/mobile/today')->assertStatus(409)
            ->assertJsonPath('code', 'no_household')
            ->assertJsonPath('pending_invitations.0.id', $invitation->id);

        $this->withToken($token)->postJson('/api/mobile/team-invitations/'.$invitation->id.'/accept')->assertOk()
            ->assertJsonPath('current_team.id', $this->team->id)
            ->assertJsonPath('role', 'editor')
            ->assertJsonPath('email_verified', true)
            ->assertJsonPath('pending_invitations', []);
        $this->assertTrue($invitee->fresh()->hasVerifiedEmail());
    }

    public function test_an_invitation_can_be_rejected_but_not_answered_by_someone_else(): void
    {
        $invitee = User::factory()->create();
        $stranger = User::factory()->create();
        $invitation = $this->invite($invitee->email);

        Sanctum::actingAs($stranger, ['*']);
        $this->postJson('/api/mobile/team-invitations/'.$invitation->id.'/accept')->assertForbidden();
        $this->deleteJson('/api/mobile/team-invitations/'.$invitation->id)->assertForbidden();

        Sanctum::actingAs($invitee, ['*']);
        $this->deleteJson('/api/mobile/team-invitations/'.$invitation->id)->assertOk()->assertJsonPath('pending_invitations', []);
        $this->assertFalse($invitee->fresh()->belongsToTeam($this->team));
    }

    public function test_the_app_resends_the_link_on_request_and_never_reveals_accounts(): void
    {
        Notification::fake();
        $unverified = User::factory()->unverified()->create();
        $verified = User::factory()->create();

        $this->postJson('/api/mobile/email/verification-notification', ['email' => $unverified->email])->assertStatus(202);
        $this->postJson('/api/mobile/email/verification-notification', ['email' => $verified->email])->assertStatus(202);
        $this->postJson('/api/mobile/email/verification-notification', ['email' => 'nobody@example.com'])->assertStatus(202);

        Notification::assertSentTo($unverified, VerifyEmailNotification::class);
        Notification::assertSentTimes(VerifyEmailNotification::class, 1);
    }

    public function test_the_verification_link_is_queued_and_a_mailer_failure_never_breaks_the_caller(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new VerifyEmailNotification);
        $this->assertInstanceOf(VerifyEmail::class, new VerifyEmailNotification);

        $user = User::factory()->unverified()->create();
        Notification::shouldReceive('send')->andThrow(new RuntimeException('An email must have a "From" or a "Sender" header.'));

        $user->sendEmailVerificationNotification();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
