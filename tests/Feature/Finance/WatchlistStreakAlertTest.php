<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use App\Notifications\WatchlistStreakAlert;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Watchlist\Models\Watchlist;
use Tests\TestCase;

class WatchlistStreakAlertTest extends TestCase
{
    use RefreshDatabase;

    private function setupChallenge(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Delivery',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
        ]);

        $target = BudgetTarget::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Spend < $500 on delivery',
            'amount' => 500,
            'target_type' => BudgetTarget::TYPE_CHALLENGE_UNDER_AMOUNT,
            'watchlist_id' => $watchlist->id,
        ]);

        return [$user, $team, $category, $watchlist, $target];
    }

    public function test_command_runs_cleanly_with_no_targets(): void
    {
        $this->artisan('watchlists:check-streaks')
            ->expectsOutputToContain('Notified: 0')
            ->assertSuccessful();
    }

    public function test_notifies_when_streak_hits_a_milestone(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 15));
        Notification::fake();

        try {
            [$user, $team, $category, , ] = $this->setupChallenge();

            // Three months under threshold ending May 2026 → streak = 3 → milestone.
            $months = [
                ['date' => '2026-05-10', 'total' => 100],
                ['date' => '2026-04-10', 'total' => 200],
                ['date' => '2026-03-10', 'total' => 300],
                ['date' => '2026-02-10', 'total' => 700],
            ];
            foreach ($months as $m) {
                Transaction::forceCreate([
                    'team_id' => $team->id, 'user_id' => $user->id, 'category_id' => $category->id,
                    'status' => 'verified', 'direction' => Transaction::DIRECTION_CREDIT,
                    'date' => $m['date'], 'total' => $m['total'], 'description' => 'tx', 'currency_code' => 'DOP',
                ]);
            }

            $this->artisan('watchlists:check-streaks')
                ->expectsOutputToContain('Notified: 1')
                ->assertSuccessful();

            Notification::assertSentTo($user, WatchlistStreakAlert::class);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_does_not_notify_when_streak_is_not_a_milestone(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 15));
        Notification::fake();

        try {
            [, $team, $category, , ] = $this->setupChallenge();

            // Two months under → streak = 2 → NOT a milestone (1, 3, 6, 12, 24 only).
            $months = [
                ['date' => '2026-05-10', 'total' => 100],
                ['date' => '2026-04-10', 'total' => 200],
                ['date' => '2026-03-10', 'total' => 700],
            ];
            foreach ($months as $m) {
                Transaction::forceCreate([
                    'team_id' => $team->id, 'user_id' => $team->user_id, 'category_id' => $category->id,
                    'status' => 'verified', 'direction' => Transaction::DIRECTION_CREDIT,
                    'date' => $m['date'], 'total' => $m['total'], 'description' => 'tx', 'currency_code' => 'DOP',
                ]);
            }

            $this->artisan('watchlists:check-streaks')->assertSuccessful();

            Notification::assertNothingSent();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_notifies_break_when_streak_drops_to_zero(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 15));
        Notification::fake();

        try {
            [$user, $team, $category, $watchlist, ] = $this->setupChallenge();

            // Current month broke ($800), but a prior milestone (3 months) was previously notified.
            Transaction::forceCreate([
                'team_id' => $team->id, 'user_id' => $user->id, 'category_id' => $category->id,
                'status' => 'verified', 'direction' => Transaction::DIRECTION_CREDIT,
                'date' => '2026-05-05', 'total' => 800, 'description' => 'broke it', 'currency_code' => 'DOP',
            ]);

            DatabaseNotification::create([
                'id' => (string) Str::uuid(),
                'type' => WatchlistStreakAlert::class,
                'notifiable_type' => $user::class,
                'notifiable_id' => $user->id,
                'created_at' => now()->subMonth(),
                'data' => [
                    'watchlist_id' => $watchlist->id,
                    'event' => WatchlistStreakAlert::EVENT_MILESTONE,
                    'streak_months' => 3,
                    'month' => now()->subMonth()->format('Y-m'),
                ],
            ]);

            $this->artisan('watchlists:check-streaks')
                ->expectsOutputToContain('Notified: 1')
                ->assertSuccessful();

            Notification::assertSentTo($user, WatchlistStreakAlert::class);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_milestone_notification_is_idempotent_within_the_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 15));
        Notification::fake();

        try {
            [$user, $team, $category, $watchlist, ] = $this->setupChallenge();

            $months = [
                ['date' => '2026-05-10', 'total' => 100],
                ['date' => '2026-04-10', 'total' => 200],
                ['date' => '2026-03-10', 'total' => 300],
                ['date' => '2026-02-10', 'total' => 700],
            ];
            foreach ($months as $m) {
                Transaction::forceCreate([
                    'team_id' => $team->id, 'user_id' => $user->id, 'category_id' => $category->id,
                    'status' => 'verified', 'direction' => Transaction::DIRECTION_CREDIT,
                    'date' => $m['date'], 'total' => $m['total'], 'description' => 'tx', 'currency_code' => 'DOP',
                ]);
            }

            // Pre-existing milestone notification for this month → should skip.
            DatabaseNotification::create([
                'id' => (string) Str::uuid(),
                'type' => WatchlistStreakAlert::class,
                'notifiable_type' => $user::class,
                'notifiable_id' => $user->id,
                'data' => [
                    'watchlist_id' => $watchlist->id,
                    'event' => WatchlistStreakAlert::EVENT_MILESTONE,
                    'streak_months' => 3,
                    'month' => '2026-05',
                ],
            ]);

            $this->artisan('watchlists:check-streaks')
                ->expectsOutputToContain('Notified: 0')
                ->assertSuccessful();

            Notification::assertNothingSent();
        } finally {
            Carbon::setTestNow();
        }
    }
}
