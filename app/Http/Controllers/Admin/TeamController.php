<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Team management. Lists all teams across the instance with search,
 * shows members + owner, and can delete a team (super_admin only).
 *
 * Deleting a team cascades through Jetstream's team relations — this
 * controller doesn't second-guess that. If we ever add "soft archive"
 * semantics it belongs here.
 */
class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $teams = Team::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->with(['owner:id,name,email'])
            ->withCount('users')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Teams/Index', [
            'teams' => $teams,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(Team $team): Response
    {
        $team->load([
            'owner:id,name,email',
            'users:id,name,email,role',
        ]);

        return Inertia::render('Admin/Teams/Show', [
            'targetTeam' => [
                'id' => $team->id,
                'name' => $team->name,
                'personal_team' => (bool) $team->personal_team,
                'created_at' => $team->created_at,
                'updated_at' => $team->updated_at,
                'trial_ends_at' => $team->trial_ends_at ?? null,
                'owner' => $team->owner,
                'users' => $team->users->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role,
                    'team_role' => $u->membership->role ?? null,
                ]),
            ],
        ]);
    }

    public function destroy(Team $team): RedirectResponse
    {
        // Personal teams are auto-created by Jetstream and coupled to
        // the user account — deleting one leaves the user in a weird
        // state. Delete the user instead if that's the goal.
        if ($team->personal_team) {
            abort(422, 'Cannot delete a personal team. Delete the owning user instead.');
        }

        $team->delete();

        return redirect()->route('admin.teams.index')
            ->with('flash', ['type' => 'success', 'message' => 'Team deleted.']);
    }
}
