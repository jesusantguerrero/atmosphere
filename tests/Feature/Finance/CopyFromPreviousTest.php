<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopyFromPreviousTest extends TestCase
{
    use RefreshDatabase;

    public function test_copies_budgeted_amounts_from_previous_month_into_empty_target(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        $this->actingAs($user);

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();
        $gasto = Category::where(['team_id' => $team->id, 'display_id' => 'personal_spending'])->firstOrFail();

        $previousMonth = now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $targetMonth = now()->startOfMonth()->format('Y-m-d');

        $this->seedBudgetMonth($team->id, $user->id, $ahorro->id, $previousMonth, 500);
        $this->seedBudgetMonth($team->id, $user->id, $gasto->id, $previousMonth, 200);

        $this->post("/budgets/months/{$targetMonth}/copy-from-previous")
            ->assertRedirect();

        $this->assertEquals(500, $this->budgeted($team->id, $ahorro->id, $targetMonth));
        $this->assertEquals(200, $this->budgeted($team->id, $gasto->id, $targetMonth));
    }

    public function test_skips_categories_already_budgeted_when_overwrite_false(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        $this->actingAs($user);

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();

        $previousMonth = now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $targetMonth = now()->startOfMonth()->format('Y-m-d');

        $this->seedBudgetMonth($team->id, $user->id, $ahorro->id, $previousMonth, 500);
        $this->seedBudgetMonth($team->id, $user->id, $ahorro->id, $targetMonth, 100);

        $this->post("/budgets/months/{$targetMonth}/copy-from-previous", ['overwrite' => false])
            ->assertRedirect();

        $this->assertEquals(100, $this->budgeted($team->id, $ahorro->id, $targetMonth));
    }

    public function test_overwrites_when_flag_is_true(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        $this->actingAs($user);

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();

        $previousMonth = now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $targetMonth = now()->startOfMonth()->format('Y-m-d');

        $this->seedBudgetMonth($team->id, $user->id, $ahorro->id, $previousMonth, 500);
        $this->seedBudgetMonth($team->id, $user->id, $ahorro->id, $targetMonth, 100);

        $this->post("/budgets/months/{$targetMonth}/copy-from-previous", ['overwrite' => true])
            ->assertRedirect();

        $this->assertEquals(500, $this->budgeted($team->id, $ahorro->id, $targetMonth));
    }

    public function test_skips_reserved_categories(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        $this->actingAs($user);

        $rta = Category::where(['team_id' => $team->id, 'name' => 'Ready to Assign'])->firstOrFail();

        $previousMonth = now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $targetMonth = now()->startOfMonth()->format('Y-m-d');

        $this->seedBudgetMonth($team->id, $user->id, $rta->id, $previousMonth, 999);

        $this->post("/budgets/months/{$targetMonth}/copy-from-previous")
            ->assertRedirect();

        $row = BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $rta->id,
            'month' => $targetMonth,
        ])->first();

        $this->assertTrue($row === null || (float) $row->budgeted === 0.0);
    }

    private function makeUserAndTeam(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user->fresh(), $team];
    }

    private function seedBudgetMonth(int $teamId, int $userId, int $categoryId, string $month, float $budgeted): BudgetMonth
    {
        return BudgetMonth::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'category_id' => $categoryId,
            'month' => $month,
            'name' => $month,
            'budgeted' => $budgeted,
            'activity' => 0,
            'available' => $budgeted,
        ]);
    }

    private function budgeted(int $teamId, int $categoryId, string $month): float
    {
        return (float) BudgetMonth::where([
            'team_id' => $teamId,
            'category_id' => $categoryId,
            'month' => $month,
        ])->value('budgeted');
    }
}
