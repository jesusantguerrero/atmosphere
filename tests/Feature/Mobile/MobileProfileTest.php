<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->user->forceFill(['current_team_id' => $this->user->ownedTeams()->first()->id])->save();
        $this->token = $this->user->createToken('iphone')->plainTextToken;
    }

    private function authed(): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->token);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/mobile/me')->assertUnauthorized();
        $this->patchJson('/api/mobile/me', ['name' => 'X'])->assertUnauthorized();
    }

    public function test_me_exposes_profile_fields(): void
    {
        $this->authed()->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('email', $this->user->email)
            ->assertJsonPath('has_password', true)
            ->assertJsonPath('auth_provider', null)
            ->assertJsonStructure(['id', 'name', 'email', 'photo_url', 'has_password', 'auth_provider', 'two_factor_enabled']);
    }

    public function test_profile_name_and_email_can_be_updated_partially(): void
    {
        $this->authed()->patchJson('/api/mobile/me', ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('name', 'New Name');

        $this->authed()->patchJson('/api/mobile/me', ['email' => 'new@example.com'])->assertOk();

        $fresh = $this->user->fresh();
        $this->assertSame('New Name', $fresh->name);
        $this->assertSame('new@example.com', $fresh->email);
    }

    public function test_profile_rejects_invalid_and_duplicate_email(): void
    {
        $other = User::factory()->create();

        $this->authed()->patchJson('/api/mobile/me', ['email' => $other->email])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email')
            ->assertJsonStructure(['message', 'errors']);

        $this->authed()->patchJson('/api/mobile/me', ['email' => 'nope', 'name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'name']);
    }

    public function test_profile_photo_can_be_uploaded(): void
    {
        Storage::fake('public');

        $this->authed()->post('/api/mobile/me', [
            '_method' => 'PATCH',
            'photo' => UploadedFile::fake()->createWithContent('me.png', base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
            )),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertNotNull($this->user->fresh()->profile_photo_path);
    }

    public function test_profile_photo_must_be_an_image(): void
    {
        $this->authed()->post('/api/mobile/me', [
            '_method' => 'PATCH',
            'photo' => UploadedFile::fake()->create('virus.exe', 10),
        ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors('photo');
    }

    public function test_password_can_be_updated(): void
    {
        $this->authed()->putJson('/api/mobile/me/password', [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-123', $this->user->fresh()->password));
    }

    public function test_password_update_validates_fields(): void
    {
        $this->authed()->putJson('/api/mobile/me/password', [
            'current_password' => 'wrong',
            'password' => 'new-password-123',
            'password_confirmation' => 'different',
        ])->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['current_password', 'password']]);

        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }

    public function test_sessions_list_includes_tokens_and_marks_current(): void
    {
        $this->user->createToken('ipad');

        $response = $this->authed()->getJson('/api/mobile/me/sessions')->assertOk();

        $sessions = collect($response->json('sessions'));
        $this->assertCount(2, $sessions);
        $this->assertSame(['iphone'], $sessions->where('is_current', true)->pluck('device')->all());
    }

    public function test_sessions_only_list_own_tokens(): void
    {
        User::factory()->create()->createToken('stranger-device');

        $response = $this->authed()->getJson('/api/mobile/me/sessions')->assertOk();

        $this->assertNotContains('stranger-device', collect($response->json('sessions'))->pluck('device')->all());
    }

    public function test_sessions_list_includes_browser_sessions_when_driver_is_database(): void
    {
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert([
            'id' => 'abc123', 'user_id' => $this->user->id, 'ip_address' => '10.0.0.5',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
            'payload' => '', 'last_activity' => now()->timestamp,
        ]);

        $browser = collect($this->authed()->getJson('/api/mobile/me/sessions')->json('sessions'))->firstWhere('type', 'browser');

        $this->assertSame('10.0.0.5', $browser['ip_address']);
    }

    public function test_revoking_other_sessions_requires_correct_password(): void
    {
        $this->user->createToken('ipad');

        $this->authed()->deleteJson('/api/mobile/me/sessions/others', ['password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->authed()->deleteJson('/api/mobile/me/sessions/others')->assertStatus(422);

        $this->assertSame(2, $this->user->tokens()->count());
    }

    public function test_revoking_other_sessions_keeps_current_token_and_removes_browser_sessions(): void
    {
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert([
            'id' => 'abc123', 'user_id' => $this->user->id, 'ip_address' => '10.0.0.5',
            'user_agent' => 'x', 'payload' => '', 'last_activity' => now()->timestamp,
        ]);
        $this->user->createToken('ipad');
        $strangerToken = User::factory()->create()->createToken('stranger');

        $this->authed()->deleteJson('/api/mobile/me/sessions/others', ['password' => 'password'])->assertOk();

        $this->assertSame(['iphone'], $this->user->tokens()->pluck('name')->all());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $this->user->id)->count());
        $this->assertNotNull($strangerToken->accessToken->fresh());
    }
}
