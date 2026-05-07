<?php

namespace Tests\Feature\System;

use App\Domains\AppCore\Models\Planner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Bulk-add Planner — covers the "school month tracker" use case: a parent
 * dumps a monthly batch of dated activities and expects each to surface in
 * Today on its date with the correct name, total, notes, and source.
 */
class PlannerBulkAddTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_endpoint_creates_one_planner_row_per_item(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $payload = [
            'source' => 'school',
            'items' => [
                ['date' => now()->format('Y-m-d'), 'name' => 'Bring planet costume', 'total' => null, 'notes' => null],
                ['date' => now()->addDays(2)->format('Y-m-d'), 'name' => 'Pizza combo', 'total' => 250, 'notes' => 'pro fondo banda MKR'],
                ['date' => now()->addDays(5)->format('Y-m-d'), 'name' => 'Planetarium maqueta', 'total' => null, 'notes' => 'build with parents'],
            ],
        ];

        $this->actingAs($user)
            ->post('/planner/bulk', $payload)
            ->assertRedirect();

        $this->assertSame(3, Planner::where('team_id', $team->id)->count());
        $this->assertSame(
            'Pizza combo',
            Planner::where('team_id', $team->id)->where('total', 250)->value('name')
        );
        $this->assertSame(
            'school',
            Planner::where('team_id', $team->id)->where('name', 'Bring planet costume')->value('source')
        );
    }

    public function test_bulk_endpoint_validates_each_row(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->post('/planner/bulk', [
                'items' => [
                    ['date' => now()->format('Y-m-d'), 'name' => ''],
                ],
            ])
            ->assertSessionHasErrors('items.0.name');

        $this->actingAs($user)
            ->post('/planner/bulk', [
                'items' => [
                    ['date' => 'not-a-date', 'name' => 'x'],
                ],
            ])
            ->assertSessionHasErrors('items.0.date');
    }

    public function test_bulk_endpoint_rejects_empty_items(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->post('/planner/bulk', ['items' => []])
            ->assertSessionHasErrors('items');
    }

    public function test_today_payload_surfaces_planner_name_and_total(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        Planner::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'frequency' => 'DAILY',
            'name' => 'Pizza combo',
            'notes' => 'pro fondo banda',
            'total' => 250,
            'source' => 'school',
            'status' => Planner::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get('/today')
            ->assertInertia(fn (Assert $page) => $page
                ->has('today.today', 1)
                ->where('today.today.0.kind', 'planner')
                ->where('today.today.0.name', 'Pizza combo')
                ->where('today.today.0.subtitle', 'pro fondo banda')
                ->where('today.today.0.total', 250)
                ->where('today.today.0.source', 'school')
            );
    }

    public function test_upcoming_includes_planner_items_within_30_days(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        // Planner item in 9 days — appears (within 30d)
        Planner::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date' => now()->addDays(9)->format('Y-m-d'),
            'end_date' => now()->addDays(9)->format('Y-m-d'),
            'frequency' => 'DAILY',
            'name' => 'Bring 8x12 canvas',
            'source' => 'school',
            'status' => Planner::STATUS_PENDING,
        ]);
        // Planner item in 45 days — outside window
        Planner::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date' => now()->addDays(45)->format('Y-m-d'),
            'end_date' => now()->addDays(45)->format('Y-m-d'),
            'frequency' => 'DAILY',
            'name' => 'Far future thing',
            'status' => Planner::STATUS_PENDING,
        ]);
        // Planner item without a name — must NOT appear (legacy/dateable-driven)
        Planner::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date' => now()->addDays(3)->format('Y-m-d'),
            'end_date' => now()->addDays(3)->format('Y-m-d'),
            'frequency' => 'DAILY',
            'status' => Planner::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get('/today')
            ->assertInertia(fn (Assert $page) => $page
                ->has('today.upcoming', 1)
                ->where('today.upcoming.0.kind', 'planner')
                ->where('today.upcoming.0.name', 'Bring 8x12 canvas')
                ->where('today.upcoming.0.source', 'school')
            );
    }

    public function test_today_payload_falls_back_to_dateable_label_when_no_name(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        Planner::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'dateable_id' => 99,
            'dateable_type' => 'App\\Domains\\Meal\\Models\\Meal',
            'date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'frequency' => 'DAILY',
            'status' => Planner::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get('/today')
            ->assertInertia(fn (Assert $page) => $page
                ->has('today.today', 1)
                ->where('today.today.0.name', 'Meal')
            );
    }
}
