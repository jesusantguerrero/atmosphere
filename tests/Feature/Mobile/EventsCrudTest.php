<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Planner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventsCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_event(): void
    {
        [$user, $event] = $this->makeEvent();
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/mobile/events/{$event->id}", [
            'name' => 'Updated Event',
            'total' => 2500,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertEquals('Updated Event', $response->json('event.title'));
        $this->assertEquals(2500, $response->json('event.total'));
    }

    public function test_delete_event(): void
    {
        [$user, $event] = $this->makeEvent();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/mobile/events/{$event->id}")
            ->assertOk()
            ->assertJson(['deleted' => true]);

        $this->assertNull(Planner::find($event->id));
    }

    public function test_cancel_event(): void
    {
        [$user, $event] = $this->makeEvent();
        Sanctum::actingAs($user);

        $this->patchJson("/api/mobile/events/{$event->id}/cancel")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals(Planner::STATUS_CANCELLED, $event->fresh()->status);
    }

    public function test_reopen_completed_event(): void
    {
        [$user, $event] = $this->makeEvent();
        Sanctum::actingAs($user);

        $event->markAsCompleted(null);

        $this->patchJson("/api/mobile/events/{$event->id}/reopen")
            ->assertOk()
            ->assertJson(['success' => true]);

        $fresh = $event->fresh();
        $this->assertEquals(Planner::STATUS_PENDING, $fresh->status);
        $this->assertNull($fresh->completed_at);
    }

    public function test_cannot_update_other_teams_event(): void
    {
        [$user, $event] = $this->makeEvent();

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $other->forceFill(['current_team_id' => $otherTeam->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->patchJson("/api/mobile/events/{$event->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_an_event_is_stored_with_its_kind_and_the_calendar_returns_it(): void
    {
        [$user, $legacy] = $this->makeEvent();
        Sanctum::actingAs($user);
        $date = now()->format('Y-m-d');

        $defaulted = $this->postJson('/api/mobile/events', ['name' => 'Dentist', 'date' => $date])->assertCreated();
        $task = $this->postJson('/api/mobile/events', ['name' => 'Call the bank', 'date' => $date, 'kind' => 'task'])->assertCreated();

        $this->assertSame('event', $defaulted->json('data.kind'));
        $this->assertSame('task', $task->json('data.kind'));
        $this->assertSame('task', Planner::find($task->json('data.id'))->kind);

        $legacy->update(['date' => $date]);
        $kinds = collect($this->getJson('/api/mobile/calendar?month='.now()->format('Y-m'))->assertOk()->json('events'))->pluck('kind', 'planner_id');

        $this->assertSame('event', $kinds[$defaulted->json('data.id')]);
        $this->assertSame('task', $kinds[$task->json('data.id')]);
        $this->assertSame('task', $kinds[$legacy->id]); // rows from before kinds existed stay tasks
    }

    public function test_an_unknown_event_kind_is_rejected(): void
    {
        [$user] = $this->makeEvent();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/events', ['name' => 'Party', 'date' => now()->format('Y-m-d'), 'kind' => 'party'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('kind');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeEvent(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $event = Planner::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Pay Internet',
            'date' => now()->addDays(3)->format('Y-m-d'),
            'status' => Planner::STATUS_PENDING,
        ]);

        return [$user, $event];
    }
}
