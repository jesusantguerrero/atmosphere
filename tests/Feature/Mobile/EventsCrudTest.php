<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Planner;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
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

    public function test_an_event_belongs_to_a_profile_and_the_calendar_filters_by_it(): void
    {
        [$user, $household] = $this->makeEvent();
        Sanctum::actingAs($user);
        $dianita = $this->profile($user, 'Dianita');
        $date = now()->format('Y-m-d');
        $household->update(['date' => $date]);

        $school = $this->postJson('/api/mobile/events', ['name' => 'Disfraz de planeta', 'date' => $date, 'profile_id' => $dianita->id])
            ->assertCreated()
            ->assertJsonPath('data.profile_id', $dianita->id)
            ->assertJsonPath('data.profile_name', 'Dianita')
            ->assertJsonPath('data.profile_color', $dianita->color);

        $month = now()->format('Y-m');
        $all = collect($this->getJson("/api/mobile/calendar?month={$month}")->assertOk()->json('events'))->keyBy('planner_id');
        $this->assertSame('Dianita', $all[$school->json('data.id')]['profile_name']);
        $this->assertNull($all[$household->id]['profile_id']);
        $this->assertArrayHasKey('profile_color', $all[$household->id]);

        $only = $this->getJson("/api/mobile/calendar?month={$month}&profile_id={$dianita->id}")->assertOk()->json('events');
        $this->assertSame([$school->json('data.id')], array_column($only, 'planner_id'));

        $house = $this->profile($user, 'Casa');
        $chore = $this->postJson('/api/mobile/events', ['name' => 'Fumigar', 'date' => $date, 'profile_id' => $house->id])->assertCreated();
        $both = $this->getJson("/api/mobile/calendar?month={$month}&profile_id[]={$dianita->id}&profile_id[]={$house->id}")->assertOk()->json('events');
        $this->assertEqualsCanonicalizing([$school->json('data.id'), $chore->json('data.id')], array_column($both, 'planner_id'));
    }

    public function test_an_event_profile_can_be_changed_and_cleared(): void
    {
        [$user, $event] = $this->makeEvent();
        Sanctum::actingAs($user);
        $dianita = $this->profile($user, 'Dianita');

        $this->patchJson("/api/mobile/events/{$event->id}", ['profile_id' => $dianita->id])
            ->assertOk()
            ->assertJsonPath('event.profile_name', 'Dianita');
        $this->assertSame($dianita->id, $this->eventProfileId($event));

        $this->patchJson("/api/mobile/events/{$event->id}", ['name' => 'Renamed'])->assertOk();
        $this->assertSame($dianita->id, $this->eventProfileId($event));

        $house = $this->profile($user, 'Casa');
        $this->patchJson("/api/mobile/events/{$event->id}", ['profile_id' => $house->id])->assertOk()->assertJsonPath('event.profile_id', $house->id);
        $this->assertDatabaseCount('loger_profile_entities', 1);

        $this->patchJson("/api/mobile/events/{$event->id}", ['profile_id' => null])
            ->assertOk()
            ->assertJsonPath('event.profile_id', null);
        $this->assertNull($this->eventProfileId($event));
        $this->assertDatabaseCount('loger_profile_entities', 0);
    }

    public function test_a_profile_from_another_household_is_rejected(): void
    {
        [$user, $event] = $this->makeEvent();
        [$stranger] = $this->makeEvent();
        $foreign = $this->profile($stranger, 'Not yours');
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/events', ['name' => 'Party', 'date' => now()->format('Y-m-d'), 'profile_id' => $foreign->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('profile_id');
        $this->patchJson("/api/mobile/events/{$event->id}", ['profile_id' => $foreign->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('profile_id');
        $this->getJson('/api/mobile/calendar?profile_id='.$foreign->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors('profile_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function eventProfileId(Planner $event): ?int
    {
        return app(ProfileWorkspaceService::class)->eventProfiles($event->team_id, [$event->id])->get($event->id)?->id;
    }

    private function profile(User $user, string $name): LogerProfile
    {
        return LogerProfile::create(['team_id' => $user->current_team_id, 'user_id' => $user->id, 'name' => $name]);
    }

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
