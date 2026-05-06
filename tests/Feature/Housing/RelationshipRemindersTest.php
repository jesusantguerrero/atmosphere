<?php

namespace Tests\Feature\Housing;

use App\Domains\Housing\Models\Occurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RelationshipRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_renders_with_seeded_reminders(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

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
            ->get('/relationships')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Relationships/Index')
                ->has('relationships', 1)
                ->where('relationships.0.name', 'Hope')
                ->has('relationships.0.days_until_next')
                ->has('relationships.0.last_date')
            );
    }

    public function test_index_excludes_non_relationship_occurrences_and_inactive(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        Occurrence::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Water bill',
            'type' => Occurrence::TYPE_UTILITY,
            'last_date' => now()->subDays(5)->format('Y-m-d'),
            'avg_days_passed' => 30,
            'is_active' => true,
        ]);

        Occurrence::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Old friend',
            'type' => Occurrence::TYPE_RELATIONSHIP,
            'last_date' => now()->subDays(60)->format('Y-m-d'),
            'avg_days_passed' => 30,
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get('/relationships')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('relationships', 0)
            );
    }

    public function test_can_create_new_reminder_via_post(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->post('/relationships', [
                'name' => 'Mom',
                'avg_days_passed' => 7,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('occurrence_checks', [
            'name' => 'Mom',
            'type' => Occurrence::TYPE_RELATIONSHIP,
            'avg_days_passed' => 7,
            'team_id' => $user->current_team_id,
            'is_active' => true,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->post('/relationships', [])
            ->assertSessionHasErrors(['name', 'avg_days_passed']);
    }

    public function test_mark_as_occurred_bumps_last_date_to_today(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $occurrence = Occurrence::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Alex',
            'type' => Occurrence::TYPE_RELATIONSHIP,
            'last_date' => now()->subDays(15)->format('Y-m-d'),
            'avg_days_passed' => 14,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post("/relationships/{$occurrence->id}/mark-as-occurred")
            ->assertRedirect();

        $this->assertDatabaseHas('occurrence_checks', [
            'id' => $occurrence->id,
            'last_date' => now()->format('Y-m-d'),
        ]);
    }

    public function test_mark_as_occurred_recomputes_avg_days_passed_when_previous_date_exists(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $occurrence = Occurrence::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Sister',
            'type' => Occurrence::TYPE_RELATIONSHIP,
            'last_date' => now()->subDays(20)->format('Y-m-d'),
            'avg_days_passed' => 14,
            'total_days' => 14,
            'occurrence_count' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post("/relationships/{$occurrence->id}/mark-as-occurred");

        $fresh = $occurrence->fresh();
        $this->assertSame(now()->format('Y-m-d'), $fresh->last_date->format('Y-m-d'));
        // avg recomputed: (14 + 20) / 2 = 17
        $this->assertSame(17, (int) $fresh->avg_days_passed);
    }

    public function test_mass_assignment_does_not_throw_under_strict_mode(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $this->expectNotToPerformAssertions();

        Occurrence::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Friend',
            'type' => Occurrence::TYPE_RELATIONSHIP,
            'avg_days_passed' => 30,
            'is_active' => true,
            'notify_on_avg' => true,
        ]);
    }
}
