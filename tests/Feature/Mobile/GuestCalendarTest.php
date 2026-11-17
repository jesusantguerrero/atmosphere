<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Planner;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GuestCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $guest;

    private Team $team;

    private LogerProfile $dianita;

    private LogerProfile $house;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->withPersonalTeam()->create();
        $this->team = $this->owner->ownedTeams()->first();
        $this->owner->forceFill(['current_team_id' => $this->team->id])->save();
        $this->guest = User::factory()->create();
        $this->team->users()->attach($this->guest, ['role' => TeamMemberGrant::GUEST_ROLE]);
        $this->guest->forceFill(['current_team_id' => $this->team->id])->save();
        $this->dianita = LogerProfile::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Dianita']);
        $this->house = LogerProfile::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Casa']);
    }

    private function grant(LogerProfile $profile, string $access): void
    {
        TeamMemberGrant::create(['team_id' => $this->team->id, 'user_id' => $this->guest->id, 'loger_profile_id' => $profile->id, 'access' => $access]);
    }

    private function event(string $name, ?LogerProfile $profile = null): Planner
    {
        $planner = Planner::create([
            'team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => $name,
            'date' => now()->format('Y-m-d'), 'kind' => Planner::KIND_EVENT, 'status' => Planner::STATUS_PENDING,
        ]);
        app(ProfileWorkspaceService::class)->setEventProfile($planner, $profile?->id);

        return $planner;
    }

    private function calendar(string $query = ''): array
    {
        return array_column($this->getJson('/api/mobile/calendar?month='.now()->format('Y-m').$query)->assertOk()->json('events'), 'planner_id');
    }

    public function test_a_guest_only_ever_sees_her_profiles_events(): void
    {
        $school = $this->event('Disfraz de planeta', $this->dianita);
        $this->event('Fumigar', $this->house);
        $this->event('Pagar luz');
        Sanctum::actingAs($this->guest, ['*']);

        $this->getJson('/api/mobile/calendar')->assertForbidden();

        $this->grant($this->dianita, TeamMemberGrant::ACCESS_READ);
        $this->assertSame([$school->id], $this->calendar());
        $this->assertSame([$school->id], $this->calendar('&profile_id='.$this->dianita->id));
        $this->getJson('/api/mobile/calendar?profile_id='.$this->house->id)->assertForbidden();
        $this->getJson('/api/mobile/calendar?profile_id[]='.$this->dianita->id.'&profile_id[]='.$this->house->id)->assertForbidden();
    }

    public function test_a_guest_with_read_cannot_touch_events(): void
    {
        $school = $this->event('Disfraz de planeta', $this->dianita);
        $this->grant($this->dianita, TeamMemberGrant::ACCESS_READ);
        Sanctum::actingAs($this->guest, ['*']);

        $this->postJson('/api/mobile/events', ['name' => 'Excursión', 'date' => now()->format('Y-m-d')])->assertForbidden();
        $this->patchJson('/api/mobile/events/'.$school->id, ['name' => 'Otro'])->assertForbidden();
        $this->patchJson('/api/mobile/events/'.$school->id.'/complete')->assertForbidden();
    }

    public function test_a_guest_with_write_works_on_her_profiles_events_only(): void
    {
        $school = $this->event('Disfraz de planeta', $this->dianita);
        $chore = $this->event('Fumigar', $this->house);
        $household = $this->event('Pagar luz');
        $this->grant($this->dianita, TeamMemberGrant::ACCESS_WRITE);
        Sanctum::actingAs($this->guest, ['*']);

        $this->postJson('/api/mobile/events', ['name' => 'Excursión', 'date' => now()->format('Y-m-d')])->assertCreated()
            ->assertJsonPath('data.profile_id', $this->dianita->id);
        $this->postJson('/api/mobile/tasks', ['name' => 'Firmar agenda', 'date' => now()->format('Y-m-d')])->assertCreated()
            ->assertJsonPath('data.profile_id', $this->dianita->id);
        $this->postJson('/api/mobile/events', ['name' => 'Intruso', 'date' => now()->format('Y-m-d'), 'profile_id' => $this->house->id])->assertForbidden();

        $this->patchJson('/api/mobile/events/'.$school->id, ['name' => 'Disfraz listo'])->assertOk()->assertJsonPath('event.profile_id', $this->dianita->id);
        $this->patchJson('/api/mobile/events/'.$school->id.'/complete')->assertOk();
        $this->patchJson('/api/mobile/events/'.$school->id.'/reopen')->assertOk();
        $this->patchJson('/api/mobile/events/'.$school->id.'/cancel')->assertOk();
        $this->patchJson('/api/mobile/events/'.$school->id, ['profile_id' => $this->house->id])->assertForbidden();
        $this->patchJson('/api/mobile/events/'.$school->id, ['profile_id' => null])->assertForbidden();
        $this->deleteJson('/api/mobile/events/'.$school->id)->assertForbidden();

        $this->patchJson('/api/mobile/events/'.$chore->id, ['name' => 'x'])->assertForbidden();
        $this->patchJson('/api/mobile/events/'.$household->id.'/complete')->assertForbidden();
        $this->assertSame('Disfraz listo', $school->fresh()->name);
    }

    public function test_with_several_write_profiles_the_guest_picks_one(): void
    {
        $this->grant($this->dianita, TeamMemberGrant::ACCESS_WRITE);
        $this->grant($this->house, TeamMemberGrant::ACCESS_WRITE);
        Sanctum::actingAs($this->guest, ['*']);

        $this->postJson('/api/mobile/events', ['name' => 'Sin perfil', 'date' => now()->format('Y-m-d')])->assertForbidden();
        $this->postJson('/api/mobile/events', ['name' => 'Fumigar', 'date' => now()->format('Y-m-d'), 'profile_id' => $this->house->id])->assertCreated()
            ->assertJsonPath('data.profile_name', 'Casa');
        $this->assertCount(1, $this->calendar('&profile_id='.$this->house->id));
    }

    public function test_today_upcoming_carries_the_event_profile(): void
    {
        $planner = Planner::create([
            'team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Vacuna',
            'date' => now()->addDays(3)->format('Y-m-d'), 'kind' => Planner::KIND_EVENT, 'status' => Planner::STATUS_PENDING,
        ]);
        app(ProfileWorkspaceService::class)->setEventProfile($planner, $this->dianita->id);
        Sanctum::actingAs($this->owner, ['*']);

        $upcoming = collect($this->getJson('/api/mobile/today')->assertOk()->json('upcoming'))->firstWhere('id', 'planner-up-'.$planner->id);
        $this->assertSame(['profile_id' => $this->dianita->id, 'profile_name' => 'Dianita', 'profile_color' => $this->dianita->color],
            array_intersect_key($upcoming, array_flip(['profile_id', 'profile_name', 'profile_color'])));
    }
}
