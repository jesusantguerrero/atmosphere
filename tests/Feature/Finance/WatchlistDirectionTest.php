<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Watchlist\Models\Watchlist;
use Tests\TestCase;

/**
 * WL-6: watchlists honor a `direction` enum (outflow|inflow|both) so they can
 * track income (freelance from these payees) or net flow, not just expenses.
 */
class WatchlistDirectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_outflow_is_the_default_direction(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Defaults',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
        ]);

        $this->assertSame(Watchlist::DIRECTION_OUTFLOW, $watchlist->fresh()->direction);
    }

    public function test_inflow_watchlist_only_sums_deposits(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Freelance income',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
            'direction' => Watchlist::DIRECTION_INFLOW,
        ]);

        $month = now()->startOfMonth()->addDays(5)->format('Y-m-d');
        Transaction::forceCreate([
            'team_id' => $team->id, 'user_id' => $user->id, 'category_id' => $category->id,
            'status' => 'verified', 'direction' => Transaction::DIRECTION_DEBIT,
            'date' => $month, 'total' => 1500, 'description' => 'income', 'currency_code' => 'DOP',
        ]);
        Transaction::forceCreate([
            'team_id' => $team->id, 'user_id' => $user->id, 'category_id' => $category->id,
            'status' => 'verified', 'direction' => Transaction::DIRECTION_CREDIT,
            'date' => $month, 'total' => 200, 'description' => 'expense', 'currency_code' => 'DOP',
        ]);

        $row = Watchlist::expensesInRange(
            $team->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d'),
            $watchlist
        );

        $this->assertSame(1500.0, (float) $row->total, 'inflow direction must skip the WITHDRAW (expense) row');
    }

    public function test_outflow_watchlist_skips_deposits(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Default expenses',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
        ]);

        $month = now()->startOfMonth()->addDays(5)->format('Y-m-d');
        Transaction::forceCreate([
            'team_id' => $team->id, 'user_id' => $user->id, 'category_id' => $category->id,
            'status' => 'verified', 'direction' => Transaction::DIRECTION_DEBIT,
            'date' => $month, 'total' => 1500, 'description' => 'income', 'currency_code' => 'DOP',
        ]);
        Transaction::forceCreate([
            'team_id' => $team->id, 'user_id' => $user->id, 'category_id' => $category->id,
            'status' => 'verified', 'direction' => Transaction::DIRECTION_CREDIT,
            'date' => $month, 'total' => 200, 'description' => 'expense', 'currency_code' => 'DOP',
        ]);

        $row = Watchlist::expensesInRange(
            $team->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d'),
            $watchlist
        );

        $this->assertSame(200.0, (float) $row->total);
    }

    public function test_both_direction_sums_inflow_and_outflow(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Net flow',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
            'direction' => Watchlist::DIRECTION_BOTH,
        ]);

        $month = now()->startOfMonth()->addDays(5)->format('Y-m-d');
        Transaction::forceCreate([
            'team_id' => $team->id, 'user_id' => $user->id, 'category_id' => $category->id,
            'status' => 'verified', 'direction' => Transaction::DIRECTION_DEBIT,
            'date' => $month, 'total' => 1500, 'description' => 'in', 'currency_code' => 'DOP',
        ]);
        Transaction::forceCreate([
            'team_id' => $team->id, 'user_id' => $user->id, 'category_id' => $category->id,
            'status' => 'verified', 'direction' => Transaction::DIRECTION_CREDIT,
            'date' => $month, 'total' => 200, 'description' => 'out', 'currency_code' => 'DOP',
        ]);

        $row = Watchlist::expensesInRange(
            $team->id,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d'),
            $watchlist
        );

        // both = sum of total regardless of direction (no offset since SUM(total))
        $this->assertSame(1700.0, (float) $row->total);
    }
}
