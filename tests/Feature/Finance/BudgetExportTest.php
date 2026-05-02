<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Exports\BudgetExport;
use App\Domains\Budget\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_rta_available_in_export_matches_ui_balance(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $rta = Category::where(['team_id' => $team->id, 'display_id' => 'ready_to_assign'])->firstOrFail();
        $ahorro = Category::where(['team_id' => $team->id, 'name' => 'Ahorro'])->firstOrFail();

        $month = '2026-05-01';

        BudgetMonth::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $rta->id,
            'month' => $month,
            'name' => $month,
            'budgeted' => 1050,
            'activity' => 1000,
            'available' => 1050,
            'left_from_last_month' => 0,
            'currency_code' => 'DOP',
        ]);

        BudgetMonth::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $ahorro->id,
            'month' => $month,
            'name' => $month,
            'budgeted' => 50,
            'activity' => 0,
            'available' => 50,
            'left_from_last_month' => 0,
            'currency_code' => 'DOP',
        ]);

        $export = new BudgetExport($team->id, $month);
        $rows = $export->query()->get()->map(fn ($budgetMonth) => $export->map($budgetMonth));

        $rtaRow = $rows->firstWhere(fn ($row) => $row[3] === 'Ready to Assign');
        $this->assertNotNull($rtaRow, 'Ready to Assign row missing from export');

        $this->assertSame('-DOP$50', $rtaRow[6], 'RTA Available should be activity + leftover - budgeted = -50, not the raw budget_months.available');
    }

    public function test_regular_category_available_in_export_uses_stored_value(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $ahorro = Category::where(['team_id' => $team->id, 'name' => 'Ahorro'])->firstOrFail();

        $month = '2026-05-01';

        BudgetMonth::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $ahorro->id,
            'month' => $month,
            'name' => $month,
            'budgeted' => 200,
            'activity' => 0,
            'available' => 200,
            'left_from_last_month' => 0,
            'currency_code' => 'DOP',
        ]);

        $export = new BudgetExport($team->id, $month);
        $rows = $export->query()->get()->map(fn ($budgetMonth) => $export->map($budgetMonth));

        $ahorroRow = $rows->firstWhere(fn ($row) => $row[3] === 'Ahorro');
        $this->assertSame('DOP$200', $ahorroRow[6]);
    }
}
