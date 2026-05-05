<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Watchlist\Models\Watchlist;
use Tests\TestCase;

/**
 * WL-MARKETING (sub-bullet 1): public share token + read-only view.
 */
class WatchlistShareTokenTest extends TestCase
{
    use RefreshDatabase;

    private function newOwnedWatchlist(User $user): Watchlist
    {
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        return Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Dining out',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
            'target' => 1500,
        ]);
    }

    public function test_owner_enables_share_and_token_is_persisted(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $watchlist = $this->newOwnedWatchlist($user);

        $this->actingAs($user)
            ->post("/finance/watchlist/{$watchlist->id}/share")
            ->assertRedirect();

        $watchlist->refresh();
        $this->assertNotNull($watchlist->share_token);
        $this->assertSame(32, strlen($watchlist->share_token));
    }

    public function test_enabling_share_twice_returns_same_token(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $watchlist = $this->newOwnedWatchlist($user);

        $this->actingAs($user)->post("/finance/watchlist/{$watchlist->id}/share");
        $first = $watchlist->fresh()->share_token;

        $this->actingAs($user)->post("/finance/watchlist/{$watchlist->id}/share");
        $second = $watchlist->fresh()->share_token;

        $this->assertSame($first, $second, 'share endpoint must be idempotent');
    }

    public function test_owner_can_revoke_token(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $watchlist = $this->newOwnedWatchlist($user);
        $watchlist->ensureShareToken();

        $this->actingAs($user)
            ->delete("/finance/watchlist/{$watchlist->id}/share")
            ->assertRedirect();

        $this->assertNull($watchlist->fresh()->share_token);
    }

    public function test_non_owner_cannot_share_or_revoke(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $intruder = User::factory()->withPersonalTeam()->create();
        $watchlist = $this->newOwnedWatchlist($owner);

        $this->actingAs($intruder)
            ->post("/finance/watchlist/{$watchlist->id}/share")
            ->assertForbidden();

        $watchlist->ensureShareToken();
        $this->actingAs($intruder)
            ->delete("/finance/watchlist/{$watchlist->id}/share")
            ->assertForbidden();
    }

    public function test_public_show_renders_with_valid_token_and_no_auth(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $watchlist = $this->newOwnedWatchlist($user);
        $token = $watchlist->ensureShareToken();

        $this->get("/share/watchlist/{$token}")
            ->assertOk()
            ->assertSee('Dining out')
            ->assertSee('This month');
    }

    public function test_public_show_returns_404_for_invalid_token(): void
    {
        $this->get('/share/watchlist/this-is-not-a-real-token')
            ->assertNotFound();
    }

    public function test_revoked_token_breaks_public_show(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $watchlist = $this->newOwnedWatchlist($user);
        $token = $watchlist->ensureShareToken();
        $watchlist->revokeShareToken();

        $this->get("/share/watchlist/{$token}")
            ->assertNotFound();
    }
}
