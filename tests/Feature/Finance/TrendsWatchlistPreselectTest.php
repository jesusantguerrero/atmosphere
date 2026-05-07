<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Watchlist\Models\Watchlist;
use Tests\TestCase;

class TrendsWatchlistPreselectTest extends TestCase
{
    use RefreshDatabase;

    public function test_trends_index_passes_team_watchlists_to_the_page(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Restaurants',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [],
            'target' => null,
        ]);

        $response = $this->actingAs($user)->get('/trends/categories');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Trends/Overview')
            ->where('activeWatchlist', null)
            ->has('watchlists', 1)
            ->where('watchlists.0.id', $watchlist->id)
            ->where('watchlists.0.name', 'Restaurants')
        );
    }

    public function test_trends_index_hydrates_active_watchlist_from_query_param(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Coffee',
            'type' => Watchlist::TYPE_PAYEE,
            'input' => [1, 2],
            'target' => null,
        ]);

        $response = $this->actingAs($user)->get("/trends/payees?watchlist={$watchlist->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Trends/Overview')
            ->where('activeWatchlist.id', $watchlist->id)
            ->where('activeWatchlist.name', 'Coffee')
            ->where('activeWatchlist.type', Watchlist::TYPE_PAYEE)
        );
    }

    public function test_active_watchlist_is_null_when_id_belongs_to_another_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->ownedTeams()->first();

        $foreign = Watchlist::create([
            'team_id' => $otherTeam->id,
            'user_id' => $otherUser->id,
            'name' => 'Foreign',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [],
            'target' => null,
        ]);

        $response = $this->actingAs($user)->get("/trends/categories?watchlist={$foreign->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('activeWatchlist', null));
    }
}
