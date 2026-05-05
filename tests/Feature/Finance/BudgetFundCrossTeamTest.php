<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetFund;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Services\BudgetFundService;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetFundCrossTeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a category in a given team, optionally nested under a parent group.
     */
    private function createCategoryInTeam(Team $team, User $user, ?int $parentId = null): Category
    {
        return Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Emergency Fund',
            'resource_type' => 'transactions',
            'parent_id' => $parentId,
            'depth' => $parentId ? 1 : 0,
        ]);
    }

    /**
     * Create a non-personal team owned by $owner and add $member to it.
     */
    private function createTeamForUser(User $owner, User $member): Team
    {
        $team = Team::factory()->create(['user_id' => $owner->id, 'personal_team' => false]);
        $team->users()->attach($member, ['role' => 'editor']);

        return $team;
    }

    // -------------------------------------------------------------------------
    // Test 1: EF created with category from another team uses that team's id
    // -------------------------------------------------------------------------

    public function test_creating_budget_fund_with_cross_team_category_stores_that_teams_id(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $user = User::factory()->withPersonalTeam()->create();

        $otherTeam = $this->createTeamForUser($owner, $user);

        $user->switchTeam($user->personalTeam);

        $categoryGroup = $this->createCategoryInTeam($otherTeam, $user);
        $category = $this->createCategoryInTeam($otherTeam, $user, $categoryGroup->id);

        $this->actingAs($user)
            ->post(route('budget-funds.store'), [
                'category_id' => $category->id,
                'watchlist_id' => null,
                'monthly_splits' => 6,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('budget_funds', [
            'category_id' => $category->id,
            'team_id' => $otherTeam->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseMissing('budget_funds', [
            'category_id' => $category->id,
            'team_id' => $user->current_team_id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Test 2: list() only returns EFs scoped to the passed team_id.
    //
    // Decision: leave list() as-is. It accepts a single team_id and returns
    // only EFs in that team. An EF whose category lives in a different team is
    // stored under that team's id and therefore NOT visible from the personal
    // team's list(). Users must switch to the category's team to see the widget.
    // This avoids a multi-team join and keeps the widget's balance scope clear.
    // -------------------------------------------------------------------------

    public function test_list_returns_only_funds_belonging_to_the_given_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $user = User::factory()->withPersonalTeam()->create();

        $otherTeam = $this->createTeamForUser($owner, $user);

        $categoryGroup = $this->createCategoryInTeam($otherTeam, $user);
        $category = $this->createCategoryInTeam($otherTeam, $user, $categoryGroup->id);

        BudgetFund::create([
            'team_id' => $otherTeam->id,
            'user_id' => $user->id,
            'name' => 'Emergency fund',
            'category_id' => $category->id,
            'watchlist_id' => null,
            'monthly_splits' => 6,
        ]);

        $service = app(BudgetFundService::class);

        $personalTeamFunds = $service->list($user->personalTeam->id);
        $this->assertCount(0, $personalTeamFunds, 'Cross-team EF must not appear in the personal team list');

        $otherTeamFunds = $service->list($otherTeam->id);
        $this->assertCount(1, $otherTeamFunds, 'EF must appear when listing by the category\'s own team');
    }

    // -------------------------------------------------------------------------
    // Test 3: getData returns the balance from the cross-team BudgetMonth
    // -------------------------------------------------------------------------

    public function test_get_data_returns_balance_from_cross_team_category_budget_month(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $user = User::factory()->withPersonalTeam()->create();

        $otherTeam = $this->createTeamForUser($owner, $user);

        $categoryGroup = $this->createCategoryInTeam($otherTeam, $user);
        $category = $this->createCategoryInTeam($otherTeam, $user, $categoryGroup->id);

        // Most recent month (index 0 in desc order)
        BudgetMonth::create([
            'team_id' => $otherTeam->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'month' => now()->startOfMonth()->format('Y-m-d'),
            'name' => $category->name,
            'budgeted' => 5000,
            'activity' => -1000,
            'available' => 4000,
        ]);

        // Second most recent month (index 1 in desc order) — this is what getData reads
        BudgetMonth::create([
            'team_id' => $otherTeam->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'month' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
            'name' => $category->name,
            'budgeted' => 5000,
            'activity' => 0,
            'available' => 5000,
        ]);

        $fund = BudgetFund::create([
            'team_id' => $otherTeam->id,
            'user_id' => $user->id,
            'name' => 'Emergency fund',
            'category_id' => $category->id,
            'watchlist_id' => null,
            'monthly_splits' => 4,
        ]);

        $service = app(BudgetFundService::class);
        $data = $service->getData($fund->load('category'));

        // budgets[1] is the second record in DESC order = older month with available = 5000
        $this->assertEquals(5000, $data['balance']);
        $this->assertEquals(5000 / 4, $data['total']);
    }

    // -------------------------------------------------------------------------
    // Test 4: Category from a team the user does NOT belong to is rejected (403)
    // -------------------------------------------------------------------------

    public function test_creating_budget_fund_with_unrelated_team_category_is_forbidden(): void
    {
        $stranger = User::factory()->withPersonalTeam()->create();
        $user = User::factory()->withPersonalTeam()->create();

        $categoryGroup = $this->createCategoryInTeam($stranger->personalTeam, $stranger);
        $foreignCategory = $this->createCategoryInTeam($stranger->personalTeam, $stranger, $categoryGroup->id);

        $this->actingAs($user)
            ->post(route('budget-funds.store'), [
                'category_id' => $foreignCategory->id,
                'watchlist_id' => null,
                'monthly_splits' => 6,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('budget_funds', [
            'category_id' => $foreignCategory->id,
        ]);
    }
}
