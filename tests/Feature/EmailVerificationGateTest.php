<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Email verification was off, so anyone could sign up with someone else's
 * address and have digests and alerts mailed to it.
 */
class EmailVerificationGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unverified_user_is_sent_to_the_verification_screen(): void
    {
        $user = User::factory()->withPersonalTeam()->unverified()->create();
        $user->forceFill(['current_team_id' => $user->ownedTeams()->first()->id])->save();

        $this->actingAs($user)->get('/budgets')->assertRedirect('/email/verify');
    }

    public function test_the_mobile_login_asks_an_unverified_user_to_verify_without_mailing_on_every_attempt(): void
    {
        Notification::fake();
        $user = User::factory()->withPersonalTeam()->unverified()->create();

        $this->postJson('/api/sanctum/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'android',
        ])->assertStatus(422)->assertJsonPath('code', 'email_unverified')->assertJsonValidationErrors('email');

        $this->assertSame(0, $user->tokens()->count());
        Notification::assertNothingSent();
    }

    public function test_existing_accounts_are_marked_verified_by_the_migration(): void
    {
        $user = User::factory()->unverified()->create();
        $migration = require database_path('migrations/2026_11_14_120000_mark_existing_users_email_verified.php');

        $migration->up();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame(0, DB::table('users')->whereNull('email_verified_at')->count());
    }
}
