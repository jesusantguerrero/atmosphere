<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class MobileTwoFactorTest extends TestCase
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

    private function enableTwoFactor(): array
    {
        return $this->authed()->postJson('/api/mobile/me/two-factor', ['password' => 'password'])->assertOk()->json();
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->postJson('/api/mobile/me/two-factor', ['password' => 'password'])->assertUnauthorized();
        $this->deleteJson('/api/mobile/me/two-factor', ['password' => 'password'])->assertUnauthorized();
    }

    public function test_enabling_requires_correct_password(): void
    {
        $this->authed()->postJson('/api/mobile/me/two-factor', ['password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertNull($this->user->fresh()->two_factor_secret);
    }

    public function test_enabling_returns_qr_secret_and_recovery_codes(): void
    {
        $payload = $this->enableTwoFactor();

        $this->assertStringContainsString('<svg', $payload['qr_svg']);
        $this->assertNotEmpty($payload['secret']);
        $this->assertCount(8, $payload['recovery_codes']);
        $this->assertNull($this->user->fresh()->two_factor_confirmed_at);
    }

    public function test_confirming_with_valid_code_marks_two_factor_confirmed(): void
    {
        $secret = $this->enableTwoFactor()['secret'];

        $this->authed()->postJson('/api/mobile/me/two-factor/confirm', ['code' => (new Google2FA)->getCurrentOtp($secret)])
            ->assertOk();

        $this->assertNotNull($this->user->fresh()->two_factor_confirmed_at);
    }

    public function test_confirming_with_invalid_code_fails(): void
    {
        $this->enableTwoFactor();

        $this->authed()->postJson('/api/mobile/me/two-factor/confirm', ['code' => '000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
        $this->authed()->postJson('/api/mobile/me/two-factor/confirm', [])->assertStatus(422);

        $this->assertNull($this->user->fresh()->two_factor_confirmed_at);
    }

    public function test_confirming_without_enabling_fails(): void
    {
        $this->authed()->postJson('/api/mobile/me/two-factor/confirm', ['code' => '123456'])->assertStatus(422);
    }

    public function test_enabling_again_after_confirmation_is_rejected_and_keeps_secret(): void
    {
        $secret = $this->enableTwoFactor()['secret'];
        $this->authed()->postJson('/api/mobile/me/two-factor/confirm', ['code' => (new Google2FA)->getCurrentOtp($secret)])->assertOk();

        $this->authed()->postJson('/api/mobile/me/two-factor', ['password' => 'password'])->assertStatus(422);

        $this->assertSame($secret, decrypt($this->user->fresh()->two_factor_secret));
    }

    public function test_recovery_codes_require_recent_password_confirmation(): void
    {
        $this->enableTwoFactor();
        $this->token = $this->user->createToken('fresh-device')->plainTextToken;
        $this->app['auth']->forgetGuards();

        $this->authed()->getJson('/api/mobile/me/two-factor/recovery-codes')->assertStatus(423);
        $this->authed()->postJson('/api/mobile/me/two-factor/recovery-codes')->assertStatus(423);

        $this->authed()->postJson('/api/mobile/me/confirm-password', ['password' => 'wrong'])->assertStatus(422);
        $this->authed()->postJson('/api/mobile/me/confirm-password', ['password' => 'password'])->assertOk();

        $this->authed()->getJson('/api/mobile/me/two-factor/recovery-codes')
            ->assertOk()
            ->assertJsonCount(8, 'recovery_codes');
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        $original = $this->enableTwoFactor()['recovery_codes'];

        $regenerated = $this->authed()->postJson('/api/mobile/me/two-factor/recovery-codes')
            ->assertOk()
            ->assertJsonCount(8, 'recovery_codes')
            ->json('recovery_codes');

        $this->assertCount(8, array_diff($regenerated, $original));
    }

    public function test_recovery_codes_unavailable_when_two_factor_is_off(): void
    {
        $this->authed()->postJson('/api/mobile/me/confirm-password', ['password' => 'password'])->assertOk();

        $this->authed()->getJson('/api/mobile/me/two-factor/recovery-codes')->assertStatus(422);
    }

    public function test_disabling_requires_password_and_clears_secret(): void
    {
        $this->enableTwoFactor();

        $this->authed()->deleteJson('/api/mobile/me/two-factor', ['password' => 'wrong'])->assertStatus(422);
        $this->assertNotNull($this->user->fresh()->two_factor_secret);

        $this->authed()->deleteJson('/api/mobile/me/two-factor', ['password' => 'password'])->assertOk();

        $fresh = $this->user->fresh();
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_recovery_codes);
    }

    public function test_login_requires_code_once_two_factor_is_enabled(): void
    {
        $secret = $this->enableTwoFactor()['secret'];

        $this->postJson('/api/sanctum/token', [
            'email' => $this->user->email,
            'password' => 'password',
            'device_name' => 'new-device',
        ])->assertStatus(402);

        $this->postJson('/api/sanctum/token', [
            'email' => $this->user->email,
            'password' => 'password',
            'device_name' => 'new-device',
            'code' => (new Google2FA)->getCurrentOtp($secret),
        ])->assertOk();
    }
}
