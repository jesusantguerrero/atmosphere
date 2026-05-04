<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Watchlist\Models\Watchlist;
use Tests\TestCase;

/**
 * WL-4: dashboard exposes the 3 watchlists most worth surfacing.
 *
 * Definition of "top": only watchlists with `target > 0` qualify (no target → no
 * semáforo to render). Sorted by % of target consumed this month (highest first).
 */
class DashboardTopWatchlistsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_exposes_top_watchlists_prop(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->has('topWatchlists')
            );
    }

    public function test_top_watchlists_excludes_those_without_target(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'No target',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
            'target' => null,
        ]);
        $withTarget = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Targeted',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
            'target' => 1000,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('topWatchlists', function ($watchlists) use ($withTarget) {
                    $names = collect($watchlists)->pluck('name')->all();
                    $this->assertSame(['Targeted'], $names, 'No-target watchlist must not appear');
                    $this->assertSame($withTarget->id, $watchlists[0]['id']);

                    return true;
                })
            );
    }

    public function test_top_watchlists_ranks_highest_consumed_first(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        // 30% consumed
        $low = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Low usage',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
            'target' => 1000,
        ]);
        // 90% consumed
        $high = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'High usage',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
            'target' => 1000,
        ]);

        // Differentiate the two watchlists by category so each gets its own total.
        $otherCategory = Category::where('team_id', $team->id)
            ->whereNotNull('parent_id')
            ->where('id', '!=', $category->id)
            ->first();
        $high->update(['input' => [$otherCategory->id]]);

        $month = now()->startOfMonth()->addDays(5)->format('Y-m-d');
        // Low: $300 spent of $1000 target → 30%
        Transaction::forceCreate([
            'team_id' => $team->id, 'user_id' => $user->id, 'category_id' => $category->id,
            'status' => 'verified', 'direction' => Transaction::DIRECTION_CREDIT,
            'date' => $month, 'total' => 300, 'description' => 'low', 'currency_code' => 'DOP',
        ]);
        // High: $900 spent of $1000 target → 90%
        Transaction::forceCreate([
            'team_id' => $team->id, 'user_id' => $user->id, 'category_id' => $otherCategory->id,
            'status' => 'verified', 'direction' => Transaction::DIRECTION_CREDIT,
            'date' => $month, 'total' => 900, 'description' => 'high', 'currency_code' => 'DOP',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('topWatchlists', function ($watchlists) use ($high, $low) {
                    $ids = collect($watchlists)->pluck('id')->all();
                    $this->assertSame([$high->id, $low->id], $ids, 'Highest consumed % must come first');

                    return true;
                })
            );
    }

    public function test_top_watchlists_caps_at_three(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        for ($i = 0; $i < 5; $i++) {
            Watchlist::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'name' => "Watch {$i}",
                'type' => Watchlist::TYPE_CATEGORY,
                'input' => [$category->id],
                'target' => 1000,
            ]);
        }

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('topWatchlists', function ($watchlists) {
                    $this->assertCount(3, $watchlists);

                    return true;
                })
            );
    }
}
