<?php

namespace Tests\Feature\System;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class UserLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_detects_spanish_from_accept_language_header(): void
    {
        $this->withHeaders(['Accept-Language' => 'es-DO,es;q=0.9,en;q=0.8'])
            ->post('/register', [
                'name' => 'Friend',
                'email' => 'friend@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
            ]);

        $this->assertSame('es', User::where('email', 'friend@example.com')->value('language'));
    }

    public function test_registration_defaults_to_english_when_no_match(): void
    {
        $this->withHeaders(['Accept-Language' => 'fr,de;q=0.9'])
            ->post('/register', [
                'name' => 'Friend',
                'email' => 'fr@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
            ]);

        $this->assertSame('en', User::where('email', 'fr@example.com')->value('language'));
    }

    public function test_locale_middleware_sets_app_locale_from_user_language(): void
    {
        $user = User::factory()->withPersonalTeam()->create(['language' => 'es']);

        $this->actingAs($user)->get('/dashboard');

        $this->assertSame('es', App::getLocale());
    }

    public function test_profile_update_persists_language(): void
    {
        $user = User::factory()->create(['language' => 'en']);
        $this->actingAs($user);

        $this->put('/user/profile-information', [
            'name' => 'Friend',
            'email' => $user->email,
            'language' => 'es',
        ]);

        $this->assertSame('es', $user->fresh()->language);
    }
}
