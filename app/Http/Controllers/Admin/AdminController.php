<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin overview dashboard. Surfaces the metrics an operator wants at a
 * glance without drilling into individual pages: raw counts, 30-day
 * signup trend, and recent user/team joiners. Kept lightweight — heavy
 * analytics live on their own routes if we add them later.
 */
class AdminController extends Controller
{
    public function index(): Response
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);

        $usersLast30 = User::query()->where('created_at', '>=', $thirtyDaysAgo)->count();
        $usersPrior30 = User::query()
            ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])
            ->count();

        $teamsLast30 = Team::query()->where('created_at', '>=', $thirtyDaysAgo)->count();
        $teamsPrior30 = Team::query()
            ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])
            ->count();

        return Inertia::render('Admin/Index', [
            'stats' => [
                'users' => [
                    'total' => User::query()->count(),
                    'last30' => $usersLast30,
                    'trend' => $this->trend($usersLast30, $usersPrior30),
                ],
                'teams' => [
                    'total' => Team::query()->count(),
                    'last30' => $teamsLast30,
                    'trend' => $this->trend($teamsLast30, $teamsPrior30),
                ],
                'admins' => User::query()->whereIn('role', ['admin', 'super_admin'])->count(),
            ],
            'recentUsers' => User::query()
                ->latest()
                ->take(10)
                ->get(['id', 'name', 'email', 'role', 'created_at']),
            'recentTeams' => Team::query()
                ->latest()
                ->take(10)
                ->with('owner:id,name,email')
                ->get(['id', 'name', 'user_id', 'created_at']),
        ]);
    }

    /**
     * Percentage change between two counts. Returns 0 when the prior
     * bucket was empty (avoids divide-by-zero and misleading "+∞%"
     * badges on brand-new instances).
     */
    private function trend(int $current, int $prior): int
    {
        if ($prior === 0) {
            return 0;
        }

        return (int) round((($current - $prior) / $prior) * 100);
    }
}
