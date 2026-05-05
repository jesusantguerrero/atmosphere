<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetFund;
use App\Domains\Budget\Models\BudgetMonth;
use App\Http\Controllers\Finance\FinancialOverviewController;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinancialOverviewGoalCategoryLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_pinned_goal_current_balance_includes_linked_category_available(): void
    {
        [$user, $companyTeam, $companyCategory] = $this->makeUserWithCompanyTeamAndCategory();
        $this->actingAs($user);

        $fund = BudgetFund::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => 'Emergency Fund',
            'amount' => 10000,
        ]);

        $goalId = "fund-{$fund->id}";

        $this->storeGoalCategoryLinks($user, [$goalId => [$companyCategory->id]]);
        $this->storePinnedGoals($user, [$goalId]);

        BudgetMonth::create([
            'team_id' => $companyTeam->id,
            'user_id' => $user->id,
            'category_id' => $companyCategory->id,
            'month' => now()->startOfMonth()->format('Y-m-d'),
            'name' => $companyCategory->name,
            'budgeted' => 0,
            'activity' => 0,
            'available' => 3500,
        ]);

        $this->get(route('finance.financial-overview'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Trends/FinancialOverview')
                ->where('pinnedGoals.0.current_balance', fn ($v) => (float) $v === 3500.0)
                ->where('pinnedGoals.0.linked_categories.0.id', $companyCategory->id)
                ->where('pinnedGoals.0.linked_categories.0.available_in_quote', fn ($v) => (float) $v === 3500.0)
            );
    }

    public function test_pinned_goal_sums_linked_accounts_and_categories(): void
    {
        [$user, $companyTeam, $companyCategory] = $this->makeUserWithCompanyTeamAndCategory();
        $this->actingAs($user);

        $fund = BudgetFund::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => 'Emergency Fund',
            'amount' => 10000,
        ]);

        $goalId = "fund-{$fund->id}";

        BudgetMonth::create([
            'team_id' => $companyTeam->id,
            'user_id' => $user->id,
            'category_id' => $companyCategory->id,
            'month' => now()->startOfMonth()->format('Y-m-d'),
            'name' => $companyCategory->name,
            'budgeted' => 0,
            'activity' => 0,
            'available' => 2000,
        ]);

        $this->storeGoalCategoryLinks($user, [$goalId => [$companyCategory->id]]);
        $this->storePinnedGoals($user, [$goalId]);

        $this->get(route('finance.financial-overview'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pinnedGoals.0.current_balance', fn ($v) => (float) $v === 2000.0)
                ->where('pinnedGoals.0.linked_accounts', [])
                ->has('pinnedGoals.0.linked_categories', 1)
            );
    }

    public function test_goal_category_links_endpoint_rejects_category_from_foreign_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $foreignTeam = Team::factory()->create(['user_id' => $user->id, 'personal_team' => false]);
        $this->actingAs($user);

        $foreignCategory = Category::create([
            'team_id' => $foreignTeam->id,
            'user_id' => $user->id,
            'name' => 'Foreign Category',
            'display_id' => 'foreign_category',
            'resource_type' => 'transactions',
            'parent_id' => 1,
        ]);

        $this->postJson('/trends/financial-overview/goal-category-links', [
            'links' => [
                'fund-1' => [$foreignCategory->id],
            ],
        ])->assertStatus(422);
    }

    public function test_goal_category_links_endpoint_persists_and_index_reflects_links(): void
    {
        [$user, $companyTeam, $companyCategory] = $this->makeUserWithCompanyTeamAndCategory();
        $this->actingAs($user);

        $fund = BudgetFund::create([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => 'House Fund',
            'amount' => 5000,
        ]);

        $goalId = "fund-{$fund->id}";

        $this->postJson('/trends/financial-overview/goal-category-links', [
            'links' => [$goalId => [$companyCategory->id]],
        ])->assertOk()->assertJson(['success' => true]);

        $setting = Setting::where([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => FinancialOverviewController::SETTING_GOAL_CATEGORY_LINKS,
        ])->firstOrFail();

        $this->assertEquals(
            [$goalId => [$companyCategory->id]],
            json_decode($setting->value, true)
        );

        BudgetMonth::create([
            'team_id' => $companyTeam->id,
            'user_id' => $user->id,
            'category_id' => $companyCategory->id,
            'month' => now()->startOfMonth()->format('Y-m-d'),
            'name' => $companyCategory->name,
            'budgeted' => 0,
            'activity' => 0,
            'available' => 1500,
        ]);

        $this->storePinnedGoals($user, [$goalId]);

        $this->get(route('finance.financial-overview'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('goalCategoryLinks', fn ($v) => isset($v[$goalId]) && in_array($companyCategory->id, $v[$goalId]))
                ->where('pinnedGoals.0.current_balance', fn ($v) => (float) $v === 1500.0)
            );
    }

    public function test_goal_category_links_endpoint_requires_authentication(): void
    {
        $this->postJson('/trends/financial-overview/goal-category-links', [
            'links' => [],
        ])->assertStatus(401);
    }

    public function test_goal_category_links_endpoint_rejects_non_integer_category_id(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/trends/financial-overview/goal-category-links', [
            'links' => ['fund-1' => ['not-an-int']],
        ])->assertStatus(422);
    }

    public function test_goal_category_links_endpoint_accepts_empty_links(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/trends/financial-overview/goal-category-links', [
            'links' => [],
        ])->assertOk();
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    /**
     * @return array{0: User, 1: Team, 2: Category}
     */
    private function makeUserWithCompanyTeamAndCategory(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $personalTeam = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $personalTeam->id])->save();

        $companyTeam = Team::factory()->create(['user_id' => $user->id, 'personal_team' => false]);
        $user->teams()->attach($companyTeam, ['role' => 'admin']);

        $parentCategory = Category::create([
            'team_id' => $companyTeam->id,
            'user_id' => $user->id,
            'name' => 'Savings',
            'display_id' => 'savings_group',
            'resource_type' => 'transactions',
            'parent_id' => null,
        ]);

        $companyCategory = Category::create([
            'team_id' => $companyTeam->id,
            'user_id' => $user->id,
            'name' => 'Emergency Fund',
            'display_id' => 'emergency_fund',
            'resource_type' => 'transactions',
            'parent_id' => $parentCategory->id,
        ]);

        return [$user->fresh(), $companyTeam, $companyCategory];
    }

    private function storeGoalCategoryLinks(User $user, array $links): void
    {
        Setting::storeBulk([
            FinancialOverviewController::SETTING_GOAL_CATEGORY_LINKS => json_encode((object) $links),
        ], [
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
        ]);
    }

    private function storePinnedGoals(User $user, array $goalIds): void
    {
        Setting::storeBulk([
            FinancialOverviewController::SETTING_PINNED_GOALS => json_encode($goalIds),
        ], [
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
        ]);
    }
}
