<?php

namespace Tests\Feature\Budget;

use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insane\Journal\Models\Core\Category;
use Tests\TestCase;

/**
 * Older months carry sub-cent leftovers (e.g. 0.0001) that Brick\Money refused
 * to add without a rounding mode, so moving money in those months was a 500.
 */
class BudgetSubCentCarryTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_money_in_a_month_with_a_sub_cent_carry_works(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $readyToAssign = Category::where([
            'team_id' => $team->id,
            'name' => BudgetReservedNames::READY_TO_ASSIGN->value,
        ])->firstOrFail();
        $destination = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Unregistered',
            'display_id' => 'unregistered',
            'resource_type' => 'transactions',
            'depth' => 0,
            'parent_id' => null,
        ]);

        foreach ([$readyToAssign, $destination] as $category) {
            BudgetMonth::updateOrCreate([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'category_id' => $category->id,
                'month' => '2025-04-01',
                'name' => '2025-04-01',
            ], [
                'budgeted' => 0,
                'left_from_last_month' => 0.0001,
                'available' => $category->is($readyToAssign) ? 10000 : 0,
            ]);
        }

        $this->actingAs($user)
            ->post("/budgets/{$destination->id}/months/2025-04-01", [
                'type' => 'movement',
                'source_category_id' => $readyToAssign->id,
                'destination_category_id' => $destination->id,
                'date' => '2025-04-01',
                'amount' => 4901.37,
            ])
            ->assertRedirect();

        $this->assertEqualsWithDelta(4901.37, (float) BudgetMovement::where('team_id', $team->id)->where('date', '2025-04-01')->value('amount'), 0.001);
    }

    public function test_budget_info_rounds_a_sub_cent_carry(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $category = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Groceries',
            'display_id' => 'groceries',
            'resource_type' => 'transactions',
            'depth' => 0,
            'parent_id' => null,
        ]);
        BudgetMonth::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'month' => '2025-04-01',
            'name' => '2025-04-01',
            'budgeted' => 100,
            'left_from_last_month' => 0.0001,
        ]);

        $info = app(BudgetCategoryService::class)->getBudgetInfo($category, '2025-04-01');

        $this->assertEqualsWithDelta(100, $info['available'], 0.001);
    }
}
