<?php

namespace Tests\Feature\Budget;

use App\Domains\Budget\Data\BudgetReservedNames;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Services\BudgetRolloverService;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Insane\Journal\Models\Core\Category;
use Tests\TestCase;

/**
 * Rolling a month whose Ready to Assign row was never opened used to blow up
 * with "Attempt to read property left_from_last_month on null".
 */
class BudgetRolloverMissingMonthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Team, 1: Category}
     */
    private function teamWithoutOpenMonth(string $month): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $readyToAssign = Category::where([
            'team_id' => $team->id,
            'name' => BudgetReservedNames::READY_TO_ASSIGN->value,
        ])->firstOrFail();

        BudgetMonth::where('team_id', $team->id)->delete();

        $this->assertSame(0, BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $readyToAssign->id,
            'month' => $month,
        ])->count());

        return [$team, $readyToAssign];
    }

    public function test_rolls_a_month_with_no_ready_to_assign_row(): void
    {
        Carbon::setTestNow('2026-08-01');
        [$team, $readyToAssign] = $this->teamWithoutOpenMonth('2026-08-01');

        app(BudgetRolloverService::class)->startFrom($team->id, '2026-08');

        $this->assertDatabaseHas('budget_months', [
            'team_id' => $team->id,
            'category_id' => $readyToAssign->id,
            'month' => '2026-08-01',
            'left_from_last_month' => 0,
        ]);
    }

    public function test_carries_the_leftover_into_the_next_month(): void
    {
        Carbon::setTestNow('2026-08-01');
        [$team, $readyToAssign] = $this->teamWithoutOpenMonth('2026-08-01');

        app(BudgetRolloverService::class)->startFrom($team->id, '2026-08');

        $this->assertDatabaseHas('budget_months', [
            'team_id' => $team->id,
            'category_id' => $readyToAssign->id,
            'month' => '2026-09-01',
            'left_from_last_month' => 0,
        ]);
    }
}
