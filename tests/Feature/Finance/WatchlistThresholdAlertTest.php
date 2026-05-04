<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use App\Notifications\WatchlistThresholdAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Watchlist\Models\Watchlist;
use Tests\TestCase;

class WatchlistThresholdAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_cleanly_with_no_watchlists(): void
    {
        $this->artisan('watchlists:check-thresholds')
            ->expectsOutputToContain('Notified: 0')
            ->assertSuccessful();
    }

    public function test_does_not_notify_when_total_is_below_target(): void
    {
        Notification::fake();

        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Restaurants',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
            'target' => 1000.00,
        ]);

        $this->artisan('watchlists:check-thresholds')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_notify_when_target_is_null(): void
    {
        Notification::fake();

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

        $this->artisan('watchlists:check-thresholds')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_notifies_once_when_total_crosses_target_and_is_idempotent(): void
    {
        Notification::fake();

        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Coffee',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
            'target' => 50.00,
        ]);

        // forceCreate bypasses $fillable. The project's Transaction model overrides
        // $fillable to just ['exchange_rate', 'exchange_amount'], so a normal create()
        // would silently drop team_id et al.
        Transaction::forceCreate([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'verified',
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => now()->startOfMonth()->addDays(2)->format('Y-m-d'),
            'total' => 200.00,
            'description' => 'test coffee',
            'currency_code' => 'DOP',
        ]);

        $monthStart = now()->startOfMonth()->format('Y-m-d');
        $monthEnd = now()->endOfMonth()->format('Y-m-d');
        $expenses = Watchlist::expensesInRange($team->id, $monthStart, $monthEnd, $watchlist);
        $this->assertNotNull($expenses, 'expensesInRange returned null');
        $this->assertEquals(200.00, (float) $expenses->total, 'expensesInRange total mismatch');

        $this->artisan('watchlists:check-thresholds')
            ->expectsOutputToContain('Notified: 1')
            ->assertSuccessful();

        Notification::assertSentTo($user, WatchlistThresholdAlert::class);
        Notification::assertSentToTimes($user, WatchlistThresholdAlert::class, 1);

        // Re-run: the in-memory fake doesn't persist DB notifications, so we test
        // bookkeeping by inserting a real DatabaseNotification and re-running.
        Notification::fake();

        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => WatchlistThresholdAlert::class,
            'notifiable_type' => $user::class,
            'notifiable_id' => $user->id,
            'data' => [
                'watchlist_id' => $watchlist->id,
                'month' => now()->startOfMonth()->format('Y-m'),
                'message' => 'pre-existing',
            ],
        ]);

        $this->artisan('watchlists:check-thresholds')
            ->expectsOutputToContain('Notified: 0')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }
}
