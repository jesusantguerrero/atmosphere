<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\InviteTeamMemberRequest;
use App\Http\Requests\Mobile\UpdateTeamMemberRoleRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Actions\UpdateTeamMemberRole;
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

        return response()->json([
            'members' => $members->values(),
            'invitations' => $team->teamInvitations->map(fn (TeamInvitation $invitation) => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'created_at' => $invitation->created_at?->toIso8601String(),
            ])->values(),
            'my_role' => $this->roleKey($user, $team),
        ]);
    }

    public function invite(InviteTeamMemberRequest $request, InvitesTeamMembers $inviter): JsonResponse
    {
        $team = $this->currentTeam($request->user());

        $inviter->invite($request->user(), $team, $request->validated('email'), $request->validated('role'));

        $invitation = $team->teamInvitations()->where('email', $request->validated('email'))->latest('id')->first();

        return response()->json([
            'id' => $invitation->id,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'created_at' => $invitation->created_at?->toIso8601String(),
        ], 201);
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

        return response()->json($this->memberPayload($user, $request->validated('role')));
    }

    public function removeMember(Request $request, User $user, RemovesTeamMembers $remover): JsonResponse
    {
        $team = $this->currentTeam($request->user());

        $this->ensureIsNonOwnerMember($team, $user);

        $remover->remove($request->user(), $team, $user);

        $this->fallBackToPersonalTeam($user->refresh());

        return response()->json(['success' => true]);
    }

    private function currentTeam(User $user): Team
    {
        return $user->currentTeam ?? abort(404);
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
