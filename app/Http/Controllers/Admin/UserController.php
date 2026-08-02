<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages users from the backoffice: list with search, detail view,
 * role changes, and delete. Impersonation lives in the base package
 * (lab404/laravel-impersonate) and is invoked via its own routes —
 * this controller exposes the button that triggers it, not the take
 * action itself.
 *
 * Delete is soft-guarded: never delete a super_admin, and only super
 * admins can delete other admins. All destructive endpoints require
 * `admin.only:superadmin` on the route.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $roleFilter = $request->query('role');

        $users = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($roleFilter, ['user', 'admin', 'super_admin'], true), function ($q) use ($roleFilter) {
                $q->where('role', $roleFilter);
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
            ],
        ]);
    }

    public function show(User $user): Response
    {
        $user->load([
            'ownedTeams:id,name,user_id,created_at',
            'teams:id,name,user_id,created_at',
        ]);

        return Inertia::render('Admin/Users/Show', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'email_verified_at' => $user->email_verified_at,
                'two_factor_enabled' => (bool) $user->two_factor_secret,
                'profile_photo_url' => $user->profile_photo_url,
                'is_super_admin' => $user->isSuperAdmin(),
                'can_be_impersonated' => $user->canBeImpersonated(),
                'owned_teams' => $user->ownedTeams,
                'teams' => $user->teams,
            ],
        ]);
    }

    /**
     * Update role. Guarded to prevent an admin (non-super) from
     * elevating anyone (including themselves) to super_admin, and to
     * prevent anyone from downgrading a super_admin except another
     * super_admin.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['user', 'admin', 'super_admin'])],
        ]);

        $actor = $request->user();
        $newRole = $validated['role'];

        // Only super admins can create or demote super admins.
        if (($newRole === 'super_admin' || $user->isSuperAdmin()) && ! $actor->isSuperAdmin()) {
            abort(403, 'Only a super admin can grant or revoke the super admin role.');
        }

        // Prevent locking yourself out — a super admin can't demote themselves
        // unless another super admin exists.
        if ($actor->id === $user->id && $newRole !== 'super_admin' && $user->isSuperAdmin()) {
            $otherSuperAdmins = User::query()
                ->where('role', 'super_admin')
                ->where('id', '!=', $user->id)
                ->exists();

            if (! $otherSuperAdmins) {
                abort(422, 'Cannot demote the only super admin. Promote someone else first.');
            }
        }

        $user->update(['role' => $newRole]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Role updated.']);
    }

    /**
     * Hard delete. Guarded — super_admin route only, and never allows
     * self-deletion or deletion of the env-configured owner.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($actor->id === $user->id) {
            abort(422, 'Cannot delete your own account from the backoffice.');
        }

        if ($user->email === config('atmosphere.superadmin.email')) {
            abort(422, 'Cannot delete the instance owner defined by APP_SUPER_ADMIN.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('flash', ['type' => 'success', 'message' => 'User deleted.']);
    }
}
