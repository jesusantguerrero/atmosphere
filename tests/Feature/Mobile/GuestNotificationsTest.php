<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Planner;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use App\Notifications\Channels\OneSignalChannel;
use App\Notifications\GuestEventChanged;
use App\Notifications\GuestShoppingItemsAdded;
use App\Notifications\GuestWeeklyDigest;
use App\Notifications\WeeklyDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Tests\TestCase;

class GuestNotificationsTest extends TestCase
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
        Carbon::setTestNow('2026-11-15 19:00:00');
        Notification::fake();
        app()->setLocale('es');
        $this->owner = User::factory()->withPersonalTeam()->create();
        $this->team = $this->owner->ownedTeams()->first();
        $this->owner->forceFill(['current_team_id' => $this->team->id])->save();
        $this->guest = User::factory()->create(['language' => 'es']);
        $this->team->users()->attach($this->guest, ['role' => TeamMemberGrant::GUEST_ROLE]);
        $this->guest->forceFill(['current_team_id' => $this->team->id])->save();
        $this->dianita = LogerProfile::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Dianita']);
        $this->house = LogerProfile::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Casa']);
    }

    private function grant(string $access = TeamMemberGrant::ACCESS_READ, ?User $guest = null): void
    {
        TeamMemberGrant::create(['team_id' => $this->team->id, 'user_id' => ($guest ?? $this->guest)->id, 'loger_profile_id' => $this->dianita->id, 'access' => $access]);
    }

    private function event(string $date, string $name, ?LogerProfile $profile = null): Planner
    {
        $planner = Planner::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => $name, 'date' => $date, 'status' => Planner::STATUS_PENDING]);
        if ($profile) {
            app(ProfileWorkspaceService::class)->setEventProfile($planner, $profile->id);
        }

        return $planner;
    }

    public function test_a_guest_hears_about_events_of_her_profile_in_the_next_week(): void
    {
        $this->grant();
        $editor = User::factory()->create();
        $this->team->users()->attach($editor, ['role' => 'editor']);
        Sanctum::actingAs($this->owner, ['*']);

        $this->postJson('/api/mobile/events', ['name' => 'Vacuna', 'date' => '2026-11-18', 'profile_id' => $this->dianita->id])->assertCreated();
        $this->postJson('/api/mobile/events', ['name' => 'Pediatra', 'date' => '2026-12-10', 'profile_id' => $this->dianita->id])->assertCreated();
        $this->postJson('/api/mobile/events', ['name' => 'Plomero', 'date' => '2026-11-17', 'profile_id' => $this->house->id])->assertCreated();
        $this->postJson('/api/mobile/events', ['name' => 'Sin perfil', 'date' => '2026-11-17'])->assertCreated();

        Notification::assertSentToTimes($this->guest, GuestEventChanged::class, 1);
        Notification::assertSentTo($this->guest, GuestEventChanged::class, function (GuestEventChanged $notification, array $channels) {
            $data = $notification->toArray($this->guest);

            return $notification->change === GuestEventChanged::ADDED
                && $notification->locale === 'es'
                && $data['message'] === 'Nuevo en el calendario de Dianita: Vacuna, 18/11.'
                && $channels === ['database', OneSignalChannel::class];
        });
        Notification::assertNotSentTo([$this->owner, $editor], GuestEventChanged::class);
    }

    public function test_changes_cancellations_and_removals_reach_her_but_not_her_own_edits(): void
    {
        $moved = $this->event('2026-11-30', 'Natación', $this->dianita);
        $cancelled = $this->event('2026-11-19', 'Cumpleaños', $this->dianita);
        $removed = $this->event('2026-11-20', 'Teatro', $this->dianita);
        $done = $this->event('2026-11-21', 'Ya hecho', $this->dianita);
        $own = $this->event('2026-11-22', 'Parque', $this->dianita);
        $this->grant(TeamMemberGrant::ACCESS_WRITE);

        Sanctum::actingAs($this->owner, ['*']);
        $this->patchJson('/api/mobile/events/'.$moved->id, ['date' => '2026-11-17'])->assertOk();
        $this->patchJson('/api/mobile/events/'.$cancelled->id.'/cancel')->assertOk();
        $this->deleteJson('/api/mobile/events/'.$removed->id)->assertOk();
        $this->patchJson('/api/mobile/events/'.$done->id.'/complete')->assertOk();

        $changes = [];
        Notification::assertSentTo($this->guest, GuestEventChanged::class, function (GuestEventChanged $notification) use (&$changes) {
            $changes[$notification->name] = $notification->change;

            return true;
        });
        $this->assertSame(['Natación' => 'changed', 'Cumpleaños' => 'cancelled', 'Teatro' => 'removed'], $changes);

        Sanctum::actingAs($this->guest, ['*']);
        $this->patchJson('/api/mobile/events/'.$own->id, ['name' => 'Parque y helado'])->assertOk();
        Notification::assertSentToTimes($this->guest, GuestEventChanged::class, 3);
    }

    public function test_the_sunday_summary_lists_only_her_profiles_week_and_month_without_amounts(): void
    {
        $this->grant();
        $this->event('2026-11-18', 'Vacuna', $this->dianita);
        $this->event('2026-12-02', 'Pediatra', $this->dianita);
        $this->event('2026-11-17', 'Plomero', $this->house);
        $this->event('2026-12-30', 'Muy lejos', $this->dianita);
        Planner::create(['team_id' => $this->team->id, 'user_id' => $this->owner->id, 'name' => 'Colegio', 'date' => '2026-11-19', 'total' => 500, 'status' => Planner::STATUS_PLANNED]);
        Notification::fake();

        $this->artisan('loger:weekly-digest')->assertSuccessful();

        Notification::assertSentTo($this->owner, WeeklyDigest::class);
        Notification::assertNotSentTo($this->guest, WeeklyDigest::class);
        Notification::assertSentTo($this->guest, GuestWeeklyDigest::class, function (GuestWeeklyDigest $notification) {
            $message = $notification->toArray($this->guest)['message'];

            return $message === "1 evento(s) esta semana para Dianita.\n• 18/11 Vacuna\nMás adelante este mes:\n• 02/12 Pediatra"
                && ! str_contains($message, 'RD$');
        });
    }

    public function test_a_guest_without_events_or_grants_gets_no_summary(): void
    {
        $this->event('2026-11-18', 'Vacuna', $this->dianita);

        $this->artisan('loger:weekly-digest')->assertSuccessful();

        Notification::assertNotSentTo($this->guest, GuestWeeklyDigest::class);
    }

    public function test_new_shopping_items_arrive_grouped_per_list_and_skip_her_own(): void
    {
        $this->grant(TeamMemberGrant::ACCESS_WRITE);
        $babyList = app(PlanService::class)->createPlanBoard($this->team, PlanTypes::SHOPPING_LIST, 'Bebé');
        $houseList = app(PlanService::class)->createPlanBoard($this->team, PlanTypes::SHOPPING_LIST, 'Súper');
        app(ProfileWorkspaceService::class)->link($this->dianita, $babyList, 'plan');

        Carbon::setTestNow('2026-11-15 19:02:00');
        foreach (['Pañales', 'Toallitas', 'Leche', 'Crema', 'Gotas'] as $title) {
            $this->item($babyList, $title, $this->owner);
        }
        $this->item($babyList, 'Lo puse yo', $this->guest);
        $this->item($houseList, 'Arroz', $this->owner);

        Carbon::setTestNow('2026-11-15 19:10:00');
        $this->artisan('loger:guest-shopping-digest')->assertSuccessful();

        Notification::assertSentToTimes($this->guest, GuestShoppingItemsAdded::class, 1);
        Notification::assertSentTo($this->guest, GuestShoppingItemsAdded::class, function (GuestShoppingItemsAdded $notification) use ($babyList) {
            return $notification->planId === $babyList->id
                && $notification->toArray($this->guest)['message'] === '5 nuevo(s) en «Bebé»: Pañales, Toallitas, Leche y 2 más.';
        });
        Notification::assertNotSentTo($this->owner, GuestShoppingItemsAdded::class);

        Carbon::setTestNow('2026-11-15 19:20:00');
        $this->artisan('loger:guest-shopping-digest')->assertSuccessful();
        Notification::assertSentToTimes($this->guest, GuestShoppingItemsAdded::class, 1);
    }

    public function test_her_preferences_list_only_guest_alerts_and_push_off_keeps_them_in_the_inbox(): void
    {
        $this->grant();
        Sanctum::actingAs($this->guest, ['*']);
        $this->assertSame(
            ['guest_weekly_digest', 'guest_event_changed', 'guest_shopping_items_added'],
            collect($this->getJson('/api/mobile/me/notification-preferences')->assertOk()->json('types'))->pluck('key')->all(),
        );
        $this->putJson('/api/mobile/me/notification-preferences', ['channels' => ['email' => true, 'push' => true], 'types' => ['guest_event_changed' => ['push' => false]]])->assertOk();

        Sanctum::actingAs($this->owner, ['*']);
        $ownerTypes = collect($this->getJson('/api/mobile/me/notification-preferences')->assertOk()->json('types'))->pluck('key');
        $this->assertTrue($ownerTypes->contains('weekly_digest'));
        $this->assertFalse($ownerTypes->contains('guest_event_changed'));

        $this->postJson('/api/mobile/events', ['name' => 'Vacuna', 'date' => '2026-11-18', 'profile_id' => $this->dianita->id])->assertCreated();
        Notification::assertSentTo($this->guest, GuestEventChanged::class, fn ($notification, array $channels) => $channels === ['database']);
    }

    private function item(Plan $plan, string $title, User $by): PlanItem
    {
        return PlanItem::create([
            'plan_id' => $plan->id, 'team_id' => $plan->team_id, 'user_id' => $by->id,
            'stage_id' => $plan->stages()->first()->id, 'title' => $title, 'state' => PlanItem::STATE_PENDING, 'order' => 0,
        ]);
    }
}
