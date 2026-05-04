<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Watchlist\Models\Watchlist;
use Tests\TestCase;

/**
 * WL-7: Saving Challenges. A `BudgetTarget` of type `challenge_under_amount`
 * tied to a Watchlist returns its consecutive-months streak — counting back
 * from now while the watchlist's monthly total stayed strictly below `amount`.
 */
class WatchlistChallengeStreakTest extends TestCase
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

    public function test_streak_zero_for_non_challenge_target(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $target = BudgetTarget::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Regular',
            'amount' => 500,
            'target_type' => BudgetTarget::TYPE_SPENDING,
        ]);

        $this->assertSame(0, $target->streakInMonths());
    }

    public function test_streak_zero_when_current_month_broke_threshold(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 15));

        try {
            [, $team, $category, , $target] = $this->setupChallenge();

            // $700 spent this month → broke the $500 threshold.
            Transaction::forceCreate([
                'team_id' => $team->id, 'user_id' => $team->user_id, 'category_id' => $category->id,
                'status' => 'verified', 'direction' => Transaction::DIRECTION_CREDIT,
                'date' => '2026-05-10', 'total' => 700, 'description' => 'too much', 'currency_code' => 'DOP',
            ]);

            $this->assertSame(0, $target->fresh()->streakInMonths());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_streak_counts_consecutive_months_under_threshold(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 15));

        try {
            [, $team, $category, , $target] = $this->setupChallenge();

            // Apr 2026: $400 (under) → contributes
            // Mar 2026: $200 (under) → contributes
            // Feb 2026: $600 (broke) → streak ends
            $months = [
                ['date' => '2026-05-10', 'total' => 100],
                ['date' => '2026-04-10', 'total' => 400],
                ['date' => '2026-03-10', 'total' => 200],
                ['date' => '2026-02-10', 'total' => 600],
                ['date' => '2026-01-10', 'total' => 100],
            ];
            foreach ($months as $m) {
                Transaction::forceCreate([
                    'team_id' => $team->id, 'user_id' => $team->user_id, 'category_id' => $category->id,
                    'status' => 'verified', 'direction' => Transaction::DIRECTION_CREDIT,
                    'date' => $m['date'], 'total' => $m['total'], 'description' => 'tx', 'currency_code' => 'DOP',
                ]);
            }

            $this->assertSame(3, $target->fresh()->streakInMonths(), 'May, Apr, Mar all under; Feb broke');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_streak_with_no_transactions_at_all_counts_lookback_window(): void
    {
        // No transactions = total 0 every month = always under any positive threshold.
        // Should return the full lookback window (default 24).
        Carbon::setTestNow(Carbon::create(2026, 5, 15));

        try {
            [, , , , $target] = $this->setupChallenge();

            $this->assertSame(24, $target->fresh()->streakInMonths());
        } finally {
            Carbon::setTestNow();
        }
    }
}
