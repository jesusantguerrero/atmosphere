<?php

namespace Tests\Feature\LogerProfile;

use App\Domains\AppCore\Models\CoreModule;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotebookUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_notebook_name(): void
    {
        [$user, $profile, $board, $notebook] = $this->makeSetup();

        $this->actingAs($user)
            ->patchJson("/loger-profiles/{$profile->id}/workspace/boards/{$board->id}/notebooks/{$notebook->id}", [
                'name' => 'Updated Name',
            ])
            ->assertOk()
            ->assertJsonStructure(['workspace']);

        $this->assertEquals('Updated Name', $notebook->fresh()->name);
    }

    public function test_update_notebook_validates_name(): void
    {
        [$user, $profile, $board, $notebook] = $this->makeSetup();

        $this->actingAs($user)
            ->patchJson("/loger-profiles/{$profile->id}/workspace/boards/{$board->id}/notebooks/{$notebook->id}", [
                'name' => '',
            ])
            ->assertUnprocessable();
    }

    public function test_update_notebook_rejects_other_team(): void
    {
        [$user, $profile, $board, $notebook] = $this->makeSetup();

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();

        // Other team doesn't have 'profiles' module → blocked by middleware;
        // even if it did, profile() scopes by team_id so it would 404.
        $this->actingAs($other->fresh())
            ->patchJson("/loger-profiles/{$profile->id}/workspace/boards/{$board->id}/notebooks/{$notebook->id}", [
                'name' => 'Hacked',
            ]);

        $this->assertEquals('Primer cuaderno', $notebook->fresh()->name);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        CoreModule::updateOrCreate(
            ['team_id' => $team->id, 'name' => 'profiles'],
            ['user_id' => $user->id, 'enabled' => true],
        );

        $profile = LogerProfile::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Test Profile',
        ]);

        $service = app(ProfileWorkspaceService::class);
        $board = $service->createBoard($profile, ['name' => 'Test Board', 'view' => 'notebook'], $user->id);
        $notebook = $board->stages()->first();

        return [$user, $profile, $board, $notebook];
    }
}
