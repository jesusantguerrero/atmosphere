<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetFutureMonthTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_to_current_month_updates_budget(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $month = now()->format('Y-m');

        $this->postJson('/api/mobile/budget/assign', [
            'category_id' => $category->id,
            'month' => $month,
            'budgeted' => 8000,
        ])->assertOk();

        $bm = BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'month' => $month.'-01',
        ])->first();

        $this->assertNotNull($bm);
        $this->assertEquals(8000.0, (float) $bm->budgeted);
    }

    public function test_assign_to_future_month_creates_budget_row(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $futureMonth = now()->addMonths(2)->format('Y-m');

        $this->postJson('/api/mobile/budget/assign', [
            'category_id' => $category->id,
            'month' => $futureMonth,
            'budgeted' => 12000,
        ])->assertOk();

        $bm = BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'month' => $futureMonth.'-01',
        ])->first();

        $this->assertNotNull($bm, 'Budget row for future month should exist');
        $this->assertEquals(12000.0, (float) $bm->budgeted);
    }

    public function test_assign_to_past_month_creates_budget_row(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $pastMonth = now()->subMonth()->format('Y-m');

        $this->postJson('/api/mobile/budget/assign', [
            'category_id' => $category->id,
            'month' => $pastMonth,
            'budgeted' => 5000,
        ])->assertOk();

        $bm = BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'month' => $pastMonth.'-01',
        ])->first();

        $this->assertNotNull($bm, 'Budget row for past month should exist');
        $this->assertEquals(5000.0, (float) $bm->budgeted);
    }

    public function test_past_month_assignment_rolls_forward_to_current(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $pastMonth = now()->subMonth()->format('Y-m');
        $currentMonth = now()->format('Y-m');

        $this->postJson('/api/mobile/budget/assign', [
            'category_id' => $category->id,
            'month' => $pastMonth,
            'budgeted' => 7000,
        ])->assertOk();

        $currentBm = BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'month' => $currentMonth.'-01',
        ])->first();

        $this->assertNotNull($currentBm, 'Rollover should create current month row');
        $this->assertEquals(7000.0, (float) $currentBm->left_from_last_month,
            'Unspent from past month should roll forward as left_from_last_month');
    }

    public function test_move_budget_in_current_month(): void
    {
        [$user, $team, $category, $group] = $this->makeSetup();
        Sanctum::actingAs($user);

        $dest = Category::create([
            'name' => 'Savings',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 1,
        ]);

        $month = now()->format('Y-m');

        $this->postJson('/api/mobile/budget/assign', [
            'category_id' => $category->id,
            'month' => $month,
            'budgeted' => 10000,
        ])->assertOk();

        $this->postJson('/api/mobile/budget/move', [
            'source_category_id' => $category->id,
            'destination_category_id' => $dest->id,
            'month' => $month,
            'amount' => 3000,
        ])->assertOk();

        $sourceBm = BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'month' => $month.'-01',
        ])->first();

        $destBm = BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $dest->id,
            'month' => $month.'-01',
        ])->first();

        $this->assertNotNull($destBm, 'Destination should have a budget row');
        $this->assertEquals(3000.0, (float) $destBm->budgeted);
        $this->assertEquals(7000.0, (float) $sourceBm->budgeted,
            'Source budgeted should decrease by moved amount');
    }

    public function test_reassign_overwrites_budgeted_amount(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $month = now()->format('Y-m');

        $this->postJson('/api/mobile/budget/assign', [
            'category_id' => $category->id,
            'month' => $month,
            'budgeted' => 5000,
        ])->assertOk();

        $this->postJson('/api/mobile/budget/assign', [
            'category_id' => $category->id,
            'month' => $month,
            'budgeted' => 9000,
        ])->assertOk();

        $bm = BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'month' => $month.'-01',
        ])->first();

        $this->assertEquals(9000.0, (float) $bm->budgeted,
            'Second assign should overwrite, not add');
    }

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $group = Category::create([
            'name' => 'Monthly Bills',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 0,
        ]);

        $category = Category::create([
            'name' => 'Rent',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        return [$user, $team, $category, $group];
    }
}
