<?php

namespace Tests\Feature\Budget;

use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Insane\Journal\Models\Core\Category;
use Tests\TestCase;

/**
 * Moving money out of Ready to Assign in a month whose Ready to Assign row was
 * never opened used to read a null "available" and crash with a 500.
 */
class BudgetMoveFromUnopenedMonthTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_from_ready_to_assign_in_an_unopened_month_does_not_crash(): void
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

        BudgetMonth::where('team_id', $team->id)->where('month', '2025-04-01')->delete();

        $this->actingAs($user)
            ->post("/budgets/{$destination->id}/months/2025-04-01", [
                'type' => 'movement',
                'source_category_id' => $readyToAssign->id,
                'destination_category_id' => $destination->id,
                'date' => '2025-04-01',
                'amount' => 4901.37,
            ])
            ->assertRedirect()
            ->assertSessionMissing('flash');

        $this->assertSame(0, BudgetMovement::where('team_id', $team->id)->where('date', '2025-04-01')->count());
    }
}
