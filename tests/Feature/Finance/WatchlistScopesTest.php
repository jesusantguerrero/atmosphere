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
}
