<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Watchlist\Models\Watchlist;
use Tests\TestCase;

class WatchlistScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_groups_filters_transactions_by_parent_category(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        // Pick two top-level categories (groups), and one child of each
        $groups = Category::where('team_id', $team->id)->whereNull('parent_id')->take(2)->get();
        $this->assertCount(2, $groups, 'need at least 2 category groups seeded');

        $childOfGroupA = Category::where('team_id', $team->id)->where('parent_id', $groups[0]->id)->first();
        $childOfGroupB = Category::where('team_id', $team->id)->where('parent_id', $groups[1]->id)->first();
        $this->assertNotNull($childOfGroupA);
        $this->assertNotNull($childOfGroupB);

        Transaction::forceCreate([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $childOfGroupA->id,
            'status' => 'verified',
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => now()->format('Y-m-d'),
            'total' => 100.00,
            'description' => 'in group A',
            'currency_code' => 'DOP',
        ]);

        Transaction::forceCreate([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $childOfGroupB->id,
            'status' => 'verified',
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => now()->format('Y-m-d'),
            'total' => 50.00,
            'description' => 'in group B',
            'currency_code' => 'DOP',
        ]);

        $matched = Transaction::byTeam($team->id)
            ->verified()
            ->expenses()
            ->groups([$groups[0]->id])
            ->get();

        $this->assertCount(1, $matched, 'only the transaction in group A should match');
        $this->assertEquals($childOfGroupA->id, $matched->first()->category_id);
    }

    public function test_watchlist_type_groups_aggregates_correctly(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        // Find a group that has at least one child seeded; ensure 2 children by adding one
        $group = Category::where('team_id', $team->id)
            ->whereNull('parent_id')
            ->whereHas('subCategories')
            ->first();
        $childOne = Category::where('team_id', $team->id)->where('parent_id', $group->id)->first();

        $childTwo = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'parent_id' => $group->id,
            'name' => 'Test child two',
            'display_id' => 'test_child_two_'.uniqid(),
        ]);

        Transaction::forceCreate([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $childOne->id,
            'status' => 'verified',
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => now()->startOfMonth()->addDays(2)->format('Y-m-d'),
            'total' => 80.00,
            'description' => 'child 1',
            'currency_code' => 'DOP',
        ]);

        Transaction::forceCreate([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $childTwo->id,
            'status' => 'verified',
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => now()->startOfMonth()->addDays(3)->format('Y-m-d'),
            'total' => 70.00,
            'description' => 'child 2',
            'currency_code' => 'DOP',
        ]);

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Group W',
            'type' => Watchlist::TYPE_CATEGORY_GROUP,
            'input' => [$group->id],
            'target' => 200.00,
        ]);

        $monthStart = now()->startOfMonth()->format('Y-m-d');
        $monthEnd = now()->endOfMonth()->format('Y-m-d');
        $expenses = Watchlist::expensesInRange($team->id, $monthStart, $monthEnd, $watchlist);

        $this->assertEquals(150.00, (float) $expenses->total, 'group should sum both children');
    }

    public function test_watchlist_index_renders_when_team_has_a_groups_watchlist(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $group = Category::where('team_id', $team->id)->whereNull('parent_id')->first();

        Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Groups WL',
            'type' => Watchlist::TYPE_CATEGORY_GROUP,
            'input' => [$group->id],
            'target' => null,
        ]);

        $this->actingAs($user)->get('/finance/watchlist')->assertOk();
    }

    public function test_watchlist_index_renders_when_team_has_a_tags_watchlist(): void
    {
        // We don't seed labels here — just verify the index doesn't crash on a tags watchlist with empty input
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Tags WL',
            'type' => Watchlist::TYPE_TAGS,
            'input' => [],
            'target' => null,
        ]);

        $this->actingAs($user)->get('/finance/watchlist')->assertOk();
    }

    public function test_monthly_series_returns_n_consecutive_months_filling_zero_for_empty(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Series',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
            'target' => null,
        ]);

        // Seed a single transaction in the current month
        Transaction::forceCreate([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'verified',
            'direction' => Transaction::DIRECTION_CREDIT,
            'date' => now()->startOfMonth()->addDays(5)->format('Y-m-d'),
            'total' => 150.00,
            'description' => 'current month tx',
            'currency_code' => 'DOP',
        ]);

        $endOfMonth = now()->endOfMonth()->format('Y-m-d');
        $series = Watchlist::monthlySeries(12, $team->id, $watchlist, $endOfMonth);

        $this->assertCount(12, $series, 'should return exactly 12 months');

        // Last entry corresponds to the current month and contains the seeded total
        $lastEntry = end($series);
        $expectedKey = now()->startOfMonth()->format('Y-m-01');
        $this->assertSame($expectedKey, $lastEntry['month']);
        $this->assertSame(150.0, (float) $lastEntry['total']);

        // First entry should be 11 months before, total = 0 (no seeded data)
        $firstEntry = $series[0];
        $expectedFirst = now()->copy()->subMonths(11)->startOfMonth()->format('Y-m-01');
        $this->assertSame($expectedFirst, $firstEntry['month']);
        $this->assertSame(0.0, (float) $firstEntry['total']);

        // Months in between with no activity also report 0
        $middleZeros = array_filter($series, fn ($p) => $p['total'] == 0.0);
        $this->assertGreaterThan(10, count($middleZeros), 'most months should be zero in this scenario');
    }

    public function test_monthly_series_aggregates_multiple_transactions_in_same_month(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::where('team_id', $team->id)->whereNotNull('parent_id')->first();

        $watchlist = Watchlist::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Series multi',
            'type' => Watchlist::TYPE_CATEGORY,
            'input' => [$category->id],
            'target' => null,
        ]);

        $base = now()->startOfMonth();
        foreach ([10.00, 20.00, 30.00] as $amount) {
            Transaction::forceCreate([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'category_id' => $category->id,
                'status' => 'verified',
                'direction' => Transaction::DIRECTION_CREDIT,
                'date' => $base->copy()->addDays(2)->format('Y-m-d'),
                'total' => $amount,
                'description' => 'tx '.$amount,
                'currency_code' => 'DOP',
            ]);
        }

        $series = Watchlist::monthlySeries(12, $team->id, $watchlist, now()->endOfMonth()->format('Y-m-d'));
        $current = end($series);

        $this->assertSame(60.0, (float) $current['total'], 'three transactions of 10+20+30 should sum to 60');
    }
}
