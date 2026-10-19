<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLoginTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_succeeds_when_two_factor_secret_is_empty_string(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'password' => bcrypt('password'),
        ]);

        // Simulate the bug: empty string instead of NULL
        $user->forceFill(['two_factor_secret' => ''])->save();

        $response = $this->postJson('/api/sanctum/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response->assertOk();
        $this->assertIsString($response->json());
    }

    public function test_login_succeeds_when_two_factor_secret_is_null(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/sanctum/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response->assertOk();
        $this->assertIsString($response->json());
    }

    public function test_login_requires_code_when_two_factor_is_properly_enabled(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'password' => bcrypt('password'),
        ]);

        // Properly enabled 2FA: non-empty secret
        $user->forceFill([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        ])->save();

        $response = $this->postJson('/api/sanctum/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        // Should demand 2FA code
        $response->assertStatus(402);
    }
}
