<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetCopyFromPreviousTest extends TestCase
{
    use RefreshDatabase;

    public function test_copies_budgeted_amounts_from_previous_month(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();
        $gasto = Category::where(['team_id' => $team->id, 'display_id' => 'personal_spending'])->firstOrFail();

        $previousMonth = now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $targetMonth = now()->startOfMonth()->format('Y-m');

        $this->seedBudgetMonth($team->id, $user->id, $ahorro->id, $previousMonth, 500);
        $this->seedBudgetMonth($team->id, $user->id, $gasto->id, $previousMonth, 200);

        $this->postJson("/api/mobile/budget/months/{$targetMonth}/copy-from-previous")
            ->assertOk()
            ->assertJson(['success' => true, 'copied' => 2, 'skipped' => 0]);

        $this->assertEquals(500, $this->budgeted($team->id, $ahorro->id, now()->startOfMonth()->format('Y-m-d')));
        $this->assertEquals(200, $this->budgeted($team->id, $gasto->id, now()->startOfMonth()->format('Y-m-d')));
    }

    public function test_returns_409_when_month_has_assignments_and_overwrite_not_set(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();

        $previousMonth = now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $targetMonth = now()->startOfMonth();

        $this->seedBudgetMonth($team->id, $user->id, $ahorro->id, $previousMonth, 500);
        $this->seedBudgetMonth($team->id, $user->id, $ahorro->id, $targetMonth->format('Y-m-d'), 100);

        $this->postJson("/api/mobile/budget/months/{$targetMonth->format('Y-m')}/copy-from-previous")
            ->assertStatus(409)
            ->assertJson(['has_assignments' => true]);
    }

    public function test_overwrites_when_flag_is_true(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();

        $previousMonth = now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $targetMonth = now()->startOfMonth();

        $this->seedBudgetMonth($team->id, $user->id, $ahorro->id, $previousMonth, 500);
        $this->seedBudgetMonth($team->id, $user->id, $ahorro->id, $targetMonth->format('Y-m-d'), 100);

        $this->postJson("/api/mobile/budget/months/{$targetMonth->format('Y-m')}/copy-from-previous", ['overwrite' => true])
            ->assertOk()
            ->assertJson(['success' => true, 'copied' => 1]);

        $this->assertEquals(500, $this->budgeted($team->id, $ahorro->id, $targetMonth->format('Y-m-d')));
    }

    public function test_skips_reserved_categories(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $rta = Category::where(['team_id' => $team->id, 'name' => 'Ready to Assign'])->firstOrFail();

        $previousMonth = now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $targetMonth = now()->startOfMonth();

        $this->seedBudgetMonth($team->id, $user->id, $rta->id, $previousMonth, 999);

        $this->postJson("/api/mobile/budget/months/{$targetMonth->format('Y-m')}/copy-from-previous")
            ->assertOk()
            ->assertJson(['success' => true, 'copied' => 0]);

        $row = BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $rta->id,
            'month' => $targetMonth->format('Y-m-d'),
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
