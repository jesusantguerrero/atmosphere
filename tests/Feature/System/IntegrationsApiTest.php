<?php

namespace Tests\Feature\System;

use App\Domains\Automation\Models\AutomationService;
use App\Domains\Integration\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the disconnect flow on /api/integrations/{id} — ownership scoping
 * (user can't delete other users' integrations) and the markSynced helper
 * powering the "Last synced" UI on /integrations.
 */
class IntegrationsApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->current_team_id = $user->fresh()->ownedTeams()->latest('id')->first()->id;
        $user->save();

        return $user;
    }

    private function makeService(): AutomationService
    {
        return AutomationService::create([
            'name' => 'Google',
            'label' => 'Google',
            'type' => 'external',
            'entity' => 'gmail',
            'description' => 'Read bank emails',
            'logo' => '',
        ]);
    }

    private function makeIntegration(User $user, AutomationService $service, array $overrides = []): Integration
    {
        return Integration::create(array_merge([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'automation_service_id' => $service->id,
            'name' => $service->name,
            'hash' => $user->email,
            'token' => json_encode(['access_token' => 'fake-access-token']),
        ], $overrides));
    }

    public function test_user_can_disconnect_their_own_integration(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService();
        $integration = $this->makeIntegration($user, $service);

        $response = $this->actingAs($user)->deleteJson("/api/integrations/{$integration->id}");

        // Even if the live Google revoke fails (no real network), the
        // integration row must be deleted so the user is in fact disconnected.
        $response->assertOk();
        $this->assertDatabaseMissing('integrations', ['id' => $integration->id]);
    }

    public function test_user_cannot_disconnect_another_users_integration(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        $service = $this->makeService();
        $integration = $this->makeIntegration($owner, $service);

        $response = $this->actingAs($intruder)->deleteJson("/api/integrations/{$integration->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('integrations', ['id' => $integration->id]);
    }

    public function test_mark_synced_updates_last_synced_at(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService();
        $integration = $this->makeIntegration($user, $service);

        $this->assertNull($integration->last_synced_at);

        $integration->markSynced();

        $this->assertNotNull($integration->fresh()->last_synced_at);
    }
}
