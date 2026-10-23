<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\CoreModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobilePreferencesTest extends TestCase
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

    private function enabledModuleKeys(): array
    {
        return collect($this->authed()->getJson('/api/mobile/me/modules')->json())
            ->where('enabled', true)->pluck('key')->values()->all();
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/mobile/me/notification-preferences')->assertUnauthorized();
        $this->putJson('/api/mobile/me/notification-preferences', [])->assertUnauthorized();
        $this->getJson('/api/mobile/me/modules')->assertUnauthorized();
        $this->putJson('/api/mobile/me/modules', [])->assertUnauthorized();
    }

    public function test_notification_preferences_default_to_all_channels_on(): void
    {
        $this->authed()->getJson('/api/mobile/me/notification-preferences')
            ->assertOk()
            ->assertExactJson(['channels' => ['email' => true, 'push' => true]]);
    }

    public function test_notification_preferences_can_be_updated_and_partially_updated(): void
    {
        $this->authed()->putJson('/api/mobile/me/notification-preferences', ['channels' => ['email' => false, 'push' => true]])
            ->assertOk()
            ->assertExactJson(['channels' => ['email' => false, 'push' => true]]);

        $this->authed()->putJson('/api/mobile/me/notification-preferences', ['channels' => ['push' => false]])
            ->assertOk()
            ->assertExactJson(['channels' => ['email' => false, 'push' => false]]);

        $this->assertFalse($this->user->fresh()->wantsNotificationChannel('email'));
        $this->assertFalse($this->user->fresh()->wantsNotificationChannel('push'));
    }

    public function test_notification_preferences_validate_input(): void
    {
        $this->authed()->putJson('/api/mobile/me/notification-preferences', [])
            ->assertStatus(422)->assertJsonStructure(['message', 'errors' => ['channels']]);

        $this->authed()->putJson('/api/mobile/me/notification-preferences', ['channels' => ['email' => 'maybe']])
            ->assertStatus(422)->assertJsonValidationErrors('channels.email');
    }

    public function test_modules_list_reports_finance_enabled_and_unknown_modules_disabled(): void
    {
        $response = $this->authed()->getJson('/api/mobile/me/modules')->assertOk();

        $this->assertSame(['finance', 'meals', 'housing', 'profiles'], collect($response->json())->pluck('key')->all());
        $this->assertSame(['finance'], $this->enabledModuleKeys());
    }

    public function test_modules_can_be_updated_and_finance_stays_on(): void
    {
        $this->authed()->putJson('/api/mobile/me/modules', ['modules' => ['meals', 'housing']])->assertOk();

        $this->assertEqualsCanonicalizing(['finance', 'meals', 'housing'], $this->enabledModuleKeys());

        $this->authed()->putJson('/api/mobile/me/modules', ['modules' => []])->assertOk();

        $this->assertSame(['finance'], $this->enabledModuleKeys());
        $this->assertTrue($this->user->currentTeam->fresh()->isModuleEnabled('Finance') > 0);
    }

    public function test_modules_update_only_touches_current_team(): void
    {
        $otherOwner = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherOwner->ownedTeams()->first();
        CoreModule::updateOrCreate(
            ['team_id' => $otherTeam->id, 'name' => 'Meals'],
            ['user_id' => $otherOwner->id, 'alias' => 'Meals', 'enabled' => true, 'created_from' => 'test'],
        );

        $this->authed()->putJson('/api/mobile/me/modules', ['modules' => []])->assertOk();

        $this->assertTrue((bool) CoreModule::where('team_id', $otherTeam->id)->where('name', 'Meals')->value('enabled'));
    }

    public function test_modules_reject_unknown_and_duplicate_keys(): void
    {
        $this->authed()->putJson('/api/mobile/me/modules', ['modules' => ['today']])
            ->assertStatus(422)->assertJsonValidationErrors('modules.0');

        $this->authed()->putJson('/api/mobile/me/modules', ['modules' => ['meals', 'meals']])
            ->assertStatus(422);

        $this->authed()->putJson('/api/mobile/me/modules', [])
            ->assertStatus(422)->assertJsonValidationErrors('modules');
    }
}
