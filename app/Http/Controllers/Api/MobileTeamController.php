<?php

namespace App\Http\Controllers\Api;

use App\Actions\Jetstream\SyncGuestGrants;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\CreateHouseholdRequest;
use App\Http\Requests\Mobile\InviteTeamMemberRequest;
use App\Http\Requests\Mobile\SwitchCurrentTeamRequest;
use App\Http\Requests\Mobile\UpdateGuestGrantsRequest;
use App\Http\Requests\Mobile\UpdateTeamMemberRoleRequest;
use App\Http\Resources\Mobile\MobileUserResource;
use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use App\Notifications\GuestAccessRequested;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Jetstream\Actions\UpdateTeamMemberRole;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Contracts\InvitesTeamMembers;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;
use Laravel\Jetstream\TeamInvitation;

class MobileTeamController extends Controller
{
    public function members(Request $request): JsonResponse
    {
        $user = $request->user();
        $team = $this->currentTeam($user);

        Gate::forUser($user)->authorize('view', $team);

        $team->load(['owner', 'users', 'teamInvitations']);

        $members = collect([$this->memberPayload($team->owner, 'owner')])->merge(
            $team->users->map(fn (User $member) => $this->memberPayload($member, $member->membership->role))
        );

        $role = $this->roleKey($user, $team);
        $invitations = $role === TeamMemberGrant::GUEST_ROLE ? collect() : $team->teamInvitations;

        return response()->json([
            'members' => $members->values(),
            'invitations' => $invitations->map(fn (TeamInvitation $invitation) => $this->invitationPayload($invitation))->values(),
            'my_role' => $role,
        ]);
    }

    public function invite(InviteTeamMemberRequest $request, InvitesTeamMembers $inviter): JsonResponse
    {
        $team = $this->currentTeam($request->user());

        $inviter->invite($request->user(), $team, $request->validated('email'), $request->validated('role'));

        $invitation = $team->teamInvitations()->where('email', $request->validated('email'))->latest('id')->first();
        if ($request->validated('role') === TeamMemberGrant::GUEST_ROLE) {
            $invitation->forceFill(['grants' => collect(SyncGuestGrants::normalize($request->validated('grants') ?? []))
                ->map(fn (string $access, int $profileId) => ['profile_id' => $profileId, 'access' => $access])->values()->all()])->save();
        }

        return response()->json($this->invitationPayload($invitation), 201);
    }

    /**
     * GET /api/mobile/team/members/{user}/grants — the profiles a guest was given.
     */
    public function grants(Request $request, User $user): JsonResponse
    {
        $team = $this->guestTeam($request->user(), $user);

        return response()->json(['grants' => SyncGuestGrants::payload($user->grantsIn($team))]);
    }

    /**
     * PUT /api/mobile/team/members/{user}/grants { grants: [{ profile_id, access }] } — replaces them all.
     */
    public function updateGrants(UpdateGuestGrantsRequest $request, User $user, SyncGuestGrants $syncGuestGrants): JsonResponse
    {
        $team = $this->guestTeam($request->user(), $user);

        return response()->json(['grants' => SyncGuestGrants::payload($syncGuestGrants->sync($team, $user, $request->validated('grants')))]);
    }

    public function cancelInvitation(Request $request, int $invitation): JsonResponse
    {
        $team = $this->currentTeam($request->user());

        Gate::forUser($request->user())->authorize('removeTeamMember', $team);

        $team->teamInvitations()->findOrFail($invitation)->delete();

        return response()->json(['success' => true]);
    }

    public function updateRole(UpdateTeamMemberRoleRequest $request, User $user, UpdateTeamMemberRole $updater): JsonResponse
    {
        $team = $this->currentTeam($request->user());

        $this->ensureIsNonOwnerMember($team, $user);

        $updater->update($request->user(), $team, $user->id, $request->validated('role'));
        if ($request->validated('role') !== TeamMemberGrant::GUEST_ROLE) {
            app(SyncGuestGrants::class)->revoke($team, $user);
        }

        return response()->json($this->memberPayload($user, $request->validated('role')));
    }

    public function removeMember(Request $request, User $user, RemovesTeamMembers $remover): JsonResponse
    {
        $team = $this->currentTeam($request->user());

        $this->ensureIsNonOwnerMember($team, $user);

        $remover->remove($request->user(), $team, $user);
        app(SyncGuestGrants::class)->revoke($team, $user);

        $this->fallBackToPersonalTeam($user->refresh());

        return response()->json(['success' => true]);
    }

    public function switchCurrentTeam(SwitchCurrentTeamRequest $request): JsonResponse
    {
        $team = Team::query()->findOrFail($request->validated('team_id'));

        abort_unless($request->user()->switchTeam($team), 403);

        return response()->json(MobileUserResource::make($request->user()->refresh()));
    }

    /**
     * POST /api/mobile/teams — a household of one's own (a guest elsewhere, say), made current.
     */
    public function store(CreateHouseholdRequest $request, CreatesTeams $creator): JsonResponse
    {
        $team = $creator->create($request->user(), ['name' => $request->validated('name')]);

        return response()->json(['id' => $team->id, 'name' => $team->name], 201);
    }

    /**
     * POST /api/mobile/team/access-request — a guest asks the owner and administrators to share
     * something with them. Once an hour at most, so the button cannot become a doorbell.
     */
    public function requestAccess(Request $request): JsonResponse
    {
        $user = $request->user();
        $team = $this->currentTeam($user);

        abort_unless($user->isGuestOf($team), 422, __('Only a guest asks for access.'));

        $key = "guest-access-request:{$team->id}:{$user->id}";
        abort_if(RateLimiter::tooManyAttempts($key, 1), 429, __('You already asked. They will see it when they open Loger.'));
        RateLimiter::hit($key, 3600);

        $admins = $team->users->filter(fn (User $member) => $member->membership->role === 'admin');
        Notification::send(
            $admins->prepend($team->owner)->unique('id'),
            new GuestAccessRequested($team->id, $user->id, $user->name, $team->name)
        );

        return response()->json(['success' => true]);
    }

    private function currentTeam(User $user): Team
    {
        return $user->currentTeam ?? abort(404);
    }

    /**
     * Only the owner hands out grants, and only to a guest of the household.
     */
    private function guestTeam(User $actor, User $guest): Team
    {
        $team = $this->currentTeam($actor);
        Gate::forUser($actor)->authorize('updateTeamMember', $team);
        $this->ensureIsNonOwnerMember($team, $guest);
        abort_unless($guest->isGuestOf($team), 422, __('Only guests are given access profile by profile.'));

        return $team;
    }

    /**
     * @return array{id: int, email: string, role: string|null, grants: array<int, array{profile_id: int, access: string}>, created_at: string|null}
     */
    private function invitationPayload(TeamInvitation $invitation): array
    {
        return [
            'id' => $invitation->id,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'grants' => $invitation->grants ?? [],
            'created_at' => $invitation->created_at?->toIso8601String(),
        ];
    }

    private function ensureIsNonOwnerMember(Team $team, User $member): void
    {
        abort_unless($team->users()->whereKey($member->getKey())->exists(), 404);
    }

    private function fallBackToPersonalTeam(User $user): void
    {
        if ($user->current_team_id === null && $personalTeam = $user->personalTeam()) {
            $user->forceFill(['current_team_id' => $personalTeam->id])->save();
        }
    }

    private function roleKey(User $user, Team $team): ?string
    {
        if ($user->ownsTeam($team)) {
            return 'owner';
        }

        return $user->teamRole($team)?->key;
    }

    /**
     * @return array{id: int, name: string, email: string, role: string|null, photo_url: string}
     */
    private function memberPayload(User $member, ?string $role): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => $role,
            'photo_url' => $member->profile_photo_url,
        ];
    }
}
