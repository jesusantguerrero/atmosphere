<?php

namespace Tests\Feature\Housing;

use App\Domains\AppCore\Models\CoreModule;
use App\Domains\Housing\Models\Occurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UtilitiesIndexTest extends TestCase
{
    use RefreshDatabase;

    private function enableHousingModule(int $teamId, int $userId): void
    {
        CoreModule::where('team_id', $teamId)->where('name', 'Housing')->update(['enabled' => true]);
    }

    public function test_renders_utilities_page_with_utility_occurrence(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $this->enableHousingModule($team->id, $user->id);

        Occurrence::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Water Bill',
            'type' => Occurrence::TYPE_UTILITY,
            'last_date' => now()->subDays(20)->format('Y-m-d'),
            'avg_days_passed' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/housing/utilities')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Housing/Utilities')
                ->has('utilities', 1)
                ->where('utilities.0.name', 'Water Bill')
                ->has('utilities.0.days_until')
                ->has('utilities.0.due_at')
                ->has('utilities.0.is_overdue')
            );
    }

    public function test_excludes_non_utility_occurrences(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $this->enableHousingModule($team->id, $user->id);

        Occurrence::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Hope',
            'type' => Occurrence::TYPE_RELATIONSHIP,
            'last_date' => now()->subDays(10)->format('Y-m-d'),
            'avg_days_passed' => 14,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/housing/utilities')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Housing/Utilities')
                ->has('utilities', 0)
            );
    }

    public function test_excludes_utilities_belonging_to_other_teams(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $this->enableHousingModule($team->id, $user->id);
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->ownedTeams()->first();

        Occurrence::create([
            'team_id' => $otherTeam->id,
            'user_id' => $otherUser->id,
            'name' => 'Electric Bill',
            'type' => Occurrence::TYPE_UTILITY,
            'last_date' => now()->subDays(5)->format('Y-m-d'),
            'avg_days_passed' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/housing/utilities')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Housing/Utilities')
                ->has('utilities', 0)
            );
    }

    public function test_is_overdue_flag_is_true_when_past_due(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $this->enableHousingModule($team->id, $user->id);

        Occurrence::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Internet',
            'type' => Occurrence::TYPE_UTILITY,
            'last_date' => now()->subDays(35)->format('Y-m-d'),
            'avg_days_passed' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/housing/utilities')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('utilities.0.is_overdue', true)
            );
    }

    public function test_requires_authentication(): void
    {
        $this->get('/housing/utilities')->assertRedirect('/login');
    }
}
