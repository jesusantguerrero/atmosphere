<?php

namespace Tests\Feature;

use App\Actions\Jetstream\TransferTeamOwnership;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use App\Http\Middleware\GuardGuestAccess;
use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use App\Notifications\GuestAccessRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Laravel\Sanctum\Sanctum;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanTypes;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: Team}
     */
    private function household(): array
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $team = $owner->ownedTeams()->first();
        $owner->forceFill(['current_team_id' => $team->id])->save();

        $guest = User::factory()->create();
        $team->users()->attach($guest, ['role' => TeamMemberGrant::GUEST_ROLE]);
        $guest->forceFill(['current_team_id' => $team->id])->save();

        return [$owner->fresh(), $guest->fresh(), $team];
    }

    private function grant(User $guest, Team $team, LogerProfile $profile, string $access): TeamMemberGrant
    {
        return TeamMemberGrant::create([
            'team_id' => $team->id,
            'user_id' => $guest->id,
            'loger_profile_id' => $profile->id,
            'access' => $access,
        ]);
    }

    public function test_a_guest_without_grants_is_denied_every_signed_in_route(): void
    {
        [, $guest] = $this->household();
        $leaks = [];
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            $middleware = $route->gatherMiddleware();
            $signedIn = collect($middleware)->contains(fn ($m) => is_string($m) && (str_starts_with($m, 'auth') || str_contains($m, 'Authenticate')));
            if (! $signedIn || $route->named(GuardGuestAccess::ACCOUNT_ROUTES)) {
                continue;
            }

            $this->actAs($guest, $middleware);
            $method = collect($route->methods())->first(fn ($m) => $m !== 'HEAD');
            $checked++;
            $status = $this->json($method, $this->fillUri($route), [], ['Accept' => 'application/json, text/event-stream'])->status();

            if ($status !== 403) {
                $leaks[] = "{$method} /{$route->uri()} → {$status}";
            }
        }

        $this->assertGreaterThan(400, $checked);
        $this->assertSame([], $leaks);
    }

    public function test_a_guest_keeps_account_self_service_and_owners_are_untouched(): void
    {
        [$owner, $guest] = $this->household();

        Sanctum::actingAs($guest, ['*']);
        $this->getJson('/api/mobile/me')->assertOk();
        $this->getJson('/api/mobile/notifications')->assertOk();
        $this->getJson('/api/mobile/calendar')->assertForbidden();

        Sanctum::actingAs($owner, ['*']);
        $this->getJson('/api/mobile/calendar')->assertOk();
    }

    public function test_a_guest_can_start_a_household_of_their_own(): void
    {
        [, $guest] = $this->household();

        $this->actingAs($guest)->get(route('teams.create'))->assertOk();
        $this->actingAs($guest)->getJson(route('api.timezones.index'))->assertOk();
        $this->actingAs($guest)->getJson(route('api.currencies.index'))->assertOk();
    }

    public function test_a_guest_sees_who_shares_the_household_but_not_pending_invitations(): void
    {
        [$owner, $guest, $team] = $this->household();
        $team->teamInvitations()->create(['email' => 'someone@example.com', 'role' => 'editor']);

        Sanctum::actingAs($guest, ['*']);
        $this->getJson('/api/mobile/team/members')
            ->assertOk()
            ->assertJsonPath('my_role', TeamMemberGrant::GUEST_ROLE)
            ->assertJsonCount(2, 'members')
            ->assertJsonPath('invitations', []);
        $this->putJson("/api/mobile/team/members/{$guest->id}", ['role' => 'editor'])->assertForbidden();

        Sanctum::actingAs($owner, ['*']);
        $this->getJson('/api/mobile/team/members')->assertOk()->assertJsonCount(1, 'invitations');
    }

    public function test_a_guest_loads_their_own_account_on_mobile(): void
    {
        [, $guest] = $this->household();

        Sanctum::actingAs($guest, ['*']);
        $this->getJson('/api/user')->assertOk()->assertJsonPath('id', $guest->id);
    }

    public function test_a_guest_starts_a_household_of_their_own_from_mobile(): void
    {
        [, $guest, $team] = $this->household();

        Sanctum::actingAs($guest, ['*']);
        $response = $this->postJson('/api/mobile/teams', ['name' => 'Casa de Caro'])->assertCreated();

        $guest->refresh();
        $this->assertSame($response->json('id'), $guest->current_team_id);
        $this->assertTrue($guest->ownsTeam($guest->currentTeam));
        $this->assertTrue($guest->belongsToTeam($team));
        $this->postJson('/api/mobile/teams', ['name' => ''])->assertUnprocessable();
    }

    public function test_a_guest_asks_the_owner_for_access_once_an_hour(): void
    {
        Notification::fake();
        [$owner, $guest] = $this->household();

        Sanctum::actingAs($guest, ['*']);
        $this->postJson('/api/mobile/team/access-request')->assertOk();
        $this->postJson('/api/mobile/team/access-request')->assertTooManyRequests();

        Notification::assertSentTo($owner, GuestAccessRequested::class);
        Notification::assertNotSentTo($guest, GuestAccessRequested::class);

        Sanctum::actingAs($owner, ['*']);
        $this->postJson('/api/mobile/team/access-request')->assertUnprocessable();
    }

    public function test_me_reports_the_role_and_the_grants(): void
    {
        [$owner, $guest, $team] = $this->household();
        $dianita = LogerProfile::create(['team_id' => $team->id, 'user_id' => $owner->id, 'name' => 'Dianita', 'color' => '#123456']);
        $house = LogerProfile::create(['team_id' => $team->id, 'user_id' => $owner->id, 'name' => 'Casa']);
        $list = (new Plan)->forceFill(['team_id' => $team->id, 'user_id' => $owner->id, 'name' => 'Super', 'plan_type_name' => PlanTypes::SHOPPING_LIST]);
        $list->save();
        app(ProfileWorkspaceService::class)->link($dianita, $list, 'plan');
        $this->grant($guest, $team, $dianita, TeamMemberGrant::ACCESS_WRITE);
        $this->grant($guest, $team, $house, TeamMemberGrant::ACCESS_READ);

        Sanctum::actingAs($guest, ['*']);
        $this->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('role', 'guest')
            ->assertJsonPath('grants.0', ['profile_id' => $dianita->id, 'access' => 'write', 'name' => 'Dianita', 'color' => '#123456', 'shopping_list_id' => $list->id])
            ->assertJsonPath('grants.1.profile_id', $house->id)
            ->assertJsonPath('grants.1.access', 'read')
            ->assertJsonPath('grants.1.color', LogerProfile::defaultColor((string) $house->id))
            ->assertJsonPath('grants.1.shopping_list_id', null);

        Sanctum::actingAs($owner, ['*']);
        $this->getJson('/api/mobile/me')->assertJsonPath('role', 'owner')->assertJsonPath('grants', []);
    }

    public function test_a_marked_route_opens_by_profile_and_access_level(): void
    {
        [$owner, $guest, $team] = $this->household();
        $dianita = LogerProfile::create(['team_id' => $team->id, 'user_id' => $owner->id, 'name' => 'Dianita']);
        $house = LogerProfile::create(['team_id' => $team->id, 'user_id' => $owner->id, 'name' => 'Casa']);
        Route::middleware(['api', 'auth:sanctum', 'guest.profile'])->group(function () {
            Route::get('/api/__guest-probe/{profileId}', fn () => 'read');
            Route::post('/api/__guest-probe/{profileId}', fn () => 'write');
            Route::get('/api/__guest-probe', fn () => 'any');
        });
        Sanctum::actingAs($guest, ['*']);

        $this->getJson('/api/__guest-probe/'.$dianita->id)->assertForbidden();
        $this->getJson('/api/__guest-probe')->assertForbidden();

        $read = $this->grant($guest, $team, $dianita, TeamMemberGrant::ACCESS_READ);
        $this->getJson('/api/__guest-probe/'.$dianita->id)->assertOk();
        $this->getJson('/api/__guest-probe')->assertOk();
        $this->postJson('/api/__guest-probe/'.$dianita->id)->assertForbidden();
        $this->getJson('/api/__guest-probe/'.$house->id)->assertForbidden();

        $read->update(['access' => TeamMemberGrant::ACCESS_WRITE]);
        $this->postJson('/api/__guest-probe/'.$dianita->id)->assertOk();
        $this->postJson('/api/__guest-probe/'.$house->id)->assertForbidden();

        Sanctum::actingAs($owner, ['*']);
        $this->postJson('/api/__guest-probe/'.$house->id)->assertOk();
    }

    public function test_a_guest_never_inherits_or_receives_the_household(): void
    {
        [$owner, $guest, $team] = $this->household();
        $transfer = app(TransferTeamOwnership::class);

        $this->assertNull($transfer->successorFor($team));

        $this->expectExceptionMessage('A guest cannot own this space.');
        $transfer->transfer($owner, $team, $guest);
    }

    private function actAs(User $user, array $middleware): void
    {
        if (in_array('auth:api', $middleware, true)) {
            Passport::actingAs($user, ['*']);
        } elseif (in_array('auth:sanctum', $middleware, true)) {
            Sanctum::actingAs($user, ['*']);
        } else {
            $this->actingAs($user);
        }
    }

    /**
     * Fill each parameter with a value its `where` constraint accepts, so the request reaches the route.
     */
    private function fillUri(RoutingRoute $route): string
    {
        $candidates = ['1', '2026-11', '2026-11-01', (string) Str::uuid(), 'a'];

        $uri = preg_replace_callback('/\{(\w+)(\?)?\}/', function (array $match) use ($route, $candidates) {
            if (($match[2] ?? '') === '?') {
                return '';
            }
            $pattern = $route->wheres[$match[1]] ?? null;

            return collect($candidates)->first(fn ($value) => $pattern === null || preg_match('#^(?:'.$pattern.')$#', $value)) ?? '1';
        }, $route->uri());

        return '/'.ltrim(preg_replace('#/+#', '/', $uri), '/');
    }
}
