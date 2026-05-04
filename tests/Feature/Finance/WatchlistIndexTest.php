<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Watchlist\Models\Watchlist;
use Tests\TestCase;

class WatchlistIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_renders_when_team_has_no_watchlists(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/finance/watchlist');

        $response->assertOk();
    }

    public function test_index_renders_when_team_has_a_watchlist(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Restaurants',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [],
            'target' => 250.00,
        ]);

        $response = $this->actingAs($user)->get('/finance/watchlist');

        $response->assertOk();
    }

    public function test_target_column_persists_and_casts_to_decimal(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Coffee',
            'type' => Watchlist::TYPE_PAYEE,
            'input' => [],
            'target' => 80.00,
        ]);

        $this->assertSame('80.00', $watchlist->fresh()->target);
    }

    public function test_update_persists_target_and_name_changes(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Initial',
            'type' => Watchlist::TYPE_PAYEE,
            'input' => [],
            'target' => null,
        ]);

        $this->actingAs($user)
            ->put("/finance/watchlist/{$watchlist->id}", [
                'name' => 'Renamed',
                'type' => Watchlist::TYPE_PAYEE,
                'input' => [],
                'target' => 250.00,
            ])
            ->assertRedirect();

        $fresh = $watchlist->fresh();
        $this->assertSame('Renamed', $fresh->name);
        $this->assertSame('250.00', $fresh->target);
    }

    public function test_destroy_removes_the_watchlist(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Goodbye',
            'type' => Watchlist::TYPE_PAYEE,
            'input' => [],
            'target' => null,
        ]);

        $this->actingAs($user)
            ->delete("/finance/watchlist/{$watchlist->id}")
            ->assertRedirect();

        $this->assertNull(Watchlist::find($watchlist->id));
    }
}
