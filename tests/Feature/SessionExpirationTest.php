<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_force_closed_session_lands_on_login_with_an_explanation(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)->get('/dashboard');

        $user->forceFill(['password' => Hash::make('a-brand-new-password')])->save();

        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $response->assertSessionMissing('password_hash_web');
    }

    public function test_a_force_closed_session_is_logged_with_its_context(): void
    {
        Log::spy();

        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)->get('/dashboard');

        $user->forceFill(['password' => Hash::make('a-brand-new-password')])->save();

        $this->get('/dashboard');

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($user) {
                return str_contains($message, 'Session force-closed')
                    && $context['user_id'] === $user->id
                    && str_contains($context['url'], '/dashboard');
            });
    }

    public function test_a_normal_sign_out_is_not_reported_as_a_forced_logout(): void
    {
        Log::spy();

        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        Log::shouldNotHaveReceived('warning');
    }

    public function test_a_stale_csrf_token_returns_to_the_page_with_a_banner_instead_of_page_expired(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        // VerifyCsrfToken short-circuits while running tests, so raise the exception
        // it would raise on a stale token and assert how the handler renders it.
        Route::middleware('web')->get('/__test/token-mismatch', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $response = $this->actingAs($user)
            ->from('/finance')
            ->get('/__test/token-mismatch');

        $response->assertRedirect('/finance');
        $response->assertSessionHas('flash.banner');
    }

    public function test_an_unauthenticated_api_request_still_gets_json(): void
    {
        $response = $this->getJson('/api/payees');

        $response->assertStatus(401);
        $response->assertJsonStructure(['message']);
    }
}
