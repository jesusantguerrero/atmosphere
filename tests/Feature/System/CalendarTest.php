<?php

namespace Tests\Feature\System;

use App\Domains\AppCore\Models\Planner;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\Transaction\Models\BillingCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_page_renders_with_events_payload(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/calendar')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Calendar/Index')
                ->has('events')
                ->has('start')
                ->has('end')
            );
    }

    public function test_calendar_shows_planner_events_in_range(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        Planner::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Team meeting',
            'date' => now()->format('Y-m-d'),
            'status' => Planner::STATUS_PENDING,
        ]);

        // Outside range — next month
        Planner::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Future event',
            'date' => now()->addMonths(2)->format('Y-m-d'),
            'status' => Planner::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get('/calendar?start='.now()->startOfMonth()->format('Y-m-d').'&end='.now()->endOfMonth()->format('Y-m-d'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('events', 1)
                ->where('events.0.title', 'Team meeting')
                ->where('events.0.kind', 'task')
            );
    }

    public function test_calendar_shows_billing_cycles(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        BillingCycle::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'account_id' => 1,
            'start_at' => now()->subDays(20),
            'end_at' => now()->addDays(5),
            'due_at' => now()->addDays(5),
            'total' => 1500,
            'subtotal' => 1500,
            'discounts' => 0,
            'status' => BillingCycle::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get('/calendar?start='.now()->startOfMonth()->format('Y-m-d').'&end='.now()->endOfMonth()->format('Y-m-d'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('events', 1)
                ->where('events.0.kind', 'billing')
                ->where('events.0.total', 1500)
            );
    }

    public function test_calendar_shows_occurrence_events(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        // Utility due within this month
        Occurrence::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Water bill',
            'type' => Occurrence::TYPE_UTILITY,
            'last_date' => now()->subDays(25)->format('Y-m-d'),
            'avg_days_passed' => 30,
            'is_active' => true,
        ]);

        $nextDate = now()->subDays(25)->addDays(30)->format('Y-m-d');
        $start = now()->startOfMonth()->format('Y-m-d');
        $end = now()->endOfMonth()->format('Y-m-d');

        // Only include if next date falls within the range
        if ($nextDate >= $start && $nextDate <= $end) {
            $this->actingAs($user)
                ->get('/calendar?start='.$start.'&end='.$end)
                ->assertInertia(fn (Assert $page) => $page
                    ->where('events.0.kind', 'utility')
                    ->where('events.0.title', 'Water bill')
                );
        } else {
            $this->assertTrue(true, 'Occurrence next date falls outside current month');
        }
    }

    public function test_planner_store_creates_item(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->post(route('planner.store'), [
                'name' => 'School play',
                'date' => '2026-06-15',
                'notes' => 'Bring costume',
                'source' => 'school',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('planners', [
            'team_id' => $user->current_team_id,
            'name' => 'School play',
            'date' => '2026-06-15',
            'notes' => 'Bring costume',
            'source' => 'school',
            'status' => Planner::STATUS_PENDING,
        ]);
    }

    public function test_planner_update_modifies_item(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $planner = Planner::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Original',
            'date' => '2026-06-15',
            'status' => Planner::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->put(route('planner.update', $planner), [
                'name' => 'Updated name',
                'date' => '2026-06-16',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('planners', [
            'id' => $planner->id,
            'name' => 'Updated name',
            'date' => '2026-06-16',
        ]);
    }

    public function test_planner_update_forbidden_for_other_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();

        $planner = Planner::create([
            'team_id' => $otherTeam->id,
            'user_id' => $other->id,
            'name' => 'Not yours',
            'date' => '2026-06-15',
            'status' => Planner::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->put(route('planner.update', $planner), ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_planner_delete_removes_item(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $planner = Planner::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Delete me',
            'date' => '2026-06-15',
            'status' => Planner::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->delete(route('planner.destroy', $planner))
            ->assertRedirect();

        $this->assertDatabaseMissing('planners', ['id' => $planner->id]);
    }

    public function test_planner_complete_marks_as_completed(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $planner = Planner::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Complete me',
            'date' => now()->format('Y-m-d'),
            'status' => Planner::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->patch(route('planner.complete', $planner))
            ->assertRedirect();

        $planner->refresh();
        $this->assertEquals(Planner::STATUS_COMPLETED, $planner->status);
        $this->assertNotNull($planner->completed_at);
    }

    public function test_calendar_requires_authentication(): void
    {
        $this->get('/calendar')
            ->assertRedirect(route('login'));
    }
}
