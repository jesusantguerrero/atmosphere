<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GoalsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_goals_index_renders_only_savings_targets(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        $this->actingAs($user);

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();
        $gasto = Category::where(['team_id' => $team->id, 'display_id' => 'personal_spending'])->firstOrFail();

        $this->makeTarget($ahorro, $user, [
            'name' => 'Emergency fund',
            'target_type' => BudgetTarget::TYPE_SAVING_BALANCE,
            'frequency' => 'MONTHLY',
            'amount' => 5000,
        ]);

        $this->makeTarget($gasto, $user, [
            'name' => 'Groceries cap',
            'target_type' => 'spending',
            'frequency' => 'MONTHLY',
            'amount' => 800,
        ]);

        $this->get(route('finance.goals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Goals/Index')
                ->has('goals', 1)
                ->where('goals.0.name', 'Emergency fund')
                ->has('availableCategories')
            );
    }

    public function test_user_can_create_a_goal_directly_from_the_goals_page(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        $this->actingAs($user);

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();

        $this->post("/budgets/{$ahorro->id}/targets", [
            'target_type' => BudgetTarget::TYPE_SAVING_BALANCE,
            'frequency' => 'DATE',
            'frequency_date' => now()->addMonths(8)->format('Y-m-d'),
            'amount' => 4000,
            'notify' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('budget_targets', [
            'team_id' => $team->id,
            'category_id' => $ahorro->id,
            'target_type' => 'saving_balance',
            'frequency' => 'DATE',
            'amount' => 4000,
        ]);
    }

    public function test_available_categories_excludes_reserved_groups(): void
    {
        [$user] = $this->makeUserAndTeam();
        $this->actingAs($user);

        $this->get(route('finance.goals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('availableCategories')
                ->where(
                    'availableCategories',
                    fn ($cats) => collect($cats)->every(
                        fn ($c) => ! in_array($c['name'], ['Ready to Assign', 'Inflow', 'Credit Card Payments'])
                            && ! in_array($c['group_name'], ['Ready to Assign', 'Inflow', 'Credit Card Payments'])
                    )
                )
            );
    }

    public function test_goals_index_computes_monthly_needed_for_dated_goals(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        $this->actingAs($user);

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();

        $targetDate = now()->addMonths(10)->format('Y-m-d');

        $this->makeTarget($ahorro, $user, [
            'name' => 'Vacation 2027',
            'target_type' => BudgetTarget::TYPE_SAVING_BALANCE,
            'frequency' => 'DATE',
            'frequency_date' => $targetDate,
            'amount' => 10000,
        ]);

        BudgetMonth::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $ahorro->id,
            'month' => now()->startOfMonth()->format('Y-m-d'),
            'name' => $ahorro->name,
            'budgeted' => 0,
            'activity' => 0,
            'available' => 2000,
        ]);

        $this->get(route('finance.goals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('goals.0.target_amount', fn ($v) => (float) $v === 10000.0)
                ->where('goals.0.current_amount', fn ($v) => (float) $v === 2000.0)
                ->where('goals.0.remaining_amount', fn ($v) => (float) $v === 8000.0)
                ->where('goals.0.frequency', 'DATE')
                ->where('goals.0.monthly_needed', fn ($needed) => $needed >= 700.0 && $needed <= 900.0)
            );
    }

    public function test_filter_param_narrows_to_dated_goals(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        $this->actingAs($user);

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();
        $gasto = Category::where(['team_id' => $team->id, 'display_id' => 'personal_spending'])->firstOrFail();

        $this->makeTarget($ahorro, $user, [
            'name' => 'Monthly savings',
            'target_type' => BudgetTarget::TYPE_SAVING_MONTHLY,
            'frequency' => 'MONTHLY',
            'amount' => 500,
        ]);

        $this->makeTarget($gasto, $user, [
            'name' => 'House down payment',
            'target_type' => BudgetTarget::TYPE_SAVING_BALANCE,
            'frequency' => 'DATE',
            'frequency_date' => now()->addMonths(12)->format('Y-m-d'),
            'amount' => 20000,
        ]);

        $this->get(route('finance.goals.index', ['filter' => 'dated']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('goals', 1)
                ->where('goals.0.name', 'House down payment')
                ->where('counts.all', 2)
                ->where('counts.monthly', 1)
                ->where('counts.dated', 1)
            );
    }

    public function test_overdue_dated_goal_is_flagged(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        $this->actingAs($user);

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();

        $this->makeTarget($ahorro, $user, [
            'name' => 'Past goal',
            'target_type' => BudgetTarget::TYPE_SAVING_BALANCE,
            'frequency' => 'DATE',
            'frequency_date' => now()->subMonths(1)->format('Y-m-d'),
            'amount' => 1000,
        ]);

        $this->get(route('finance.goals.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('goals.0.is_overdue', true)
                ->where('goals.0.is_completed', false)
                ->where('counts.overdue', 1)
            );
    }

    private function makeUserAndTeam(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user->fresh(), $team];
    }

    private function makeTarget(Category $category, User $user, array $attrs): BudgetTarget
    {
        return $category->budget()->create(array_merge([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => $category->name,
        ], $attrs));
    }
}
