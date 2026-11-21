<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Cubrir lo que falta": fund every underfunded monthly target from Ready to Assign at once,
 * the ones due first before the rest when the money does not reach.
 */
class FundUnderfundedTest extends TestCase
{
    use RefreshDatabase;

    private const MONTH = '2026-11-01';

    private User $user;

    private Team $team;

    private Category $insurance;

    private Category $internet;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-21 12:00:00');
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->ownedTeams()->first();
        $this->user->forceFill(['current_team_id' => $this->team->id])->save();
        $this->user = $this->user->fresh();

        $inflow = Category::firstOrCreate(['team_id' => $this->team->id, 'name' => 'Inflow', 'parent_id' => null], ['resource_type' => 'transactions', 'depth' => 0, 'index' => 99]);
        $readyToAssign = Category::where(['team_id' => $this->team->id, 'parent_id' => $inflow->id])->first()
            ?? $this->child($inflow, 'Ready to Assign');
        $this->month($readyToAssign, 0, 40000);

        $bills = Category::create(['name' => 'Facturas', 'team_id' => $this->team->id, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
        $rent = $this->child($bills, 'Alquiler');
        $this->target($rent, 25000, 7);
        $this->month($rent, 25000);

        $this->insurance = $this->child($bills, 'Seguro de salud');
        $this->target($this->insurance, 6000, 15);
        $this->month($this->insurance, 1000);

        $this->internet = $this->child($bills, 'Internet');
        $this->target($this->internet, 3000, 17);

        $this->target($this->child($bills, 'Gimnasio'), 2500, 22, ['snoozed_month' => '2026-11']);
        $this->target($this->child($bills, 'Vacaciones'), 90000, null, ['target_type' => BudgetTarget::TYPE_SAVING_BALANCE]);
        $this->target($this->child($bills, 'Netflix'), 600, 22, ['completed_at' => now()]);

        // 40,000 in, 26,000 assigned: 14,000 left. Spend it down to 6,000 so it does not reach.
        $this->month($this->child($bills, 'Varios'), 8000);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_preview_lists_what_is_missing_due_first_capped_by_ready_to_assign(): void
    {
        $this->actingAs($this->user)
            ->getJson('/budgets/months/'.self::MONTH.'/underfunded')
            ->assertOk()
            ->assertJson([
                'month' => '2026-11',
                'ready_to_assign' => 6000,
                'needed' => 8000,
                'assigned' => 6000,
                'missing' => 2000,
                'items' => [
                    ['category_id' => $this->insurance->id, 'group_name' => 'Facturas', 'due_day' => 15, 'target' => 6000, 'funded' => 1000, 'needed' => 5000, 'amount' => 5000],
                    ['category_id' => $this->internet->id, 'due_day' => 17, 'target' => 3000, 'funded' => 0, 'needed' => 3000, 'amount' => 1000],
                ],
            ])
            ->assertJsonCount(2, 'items');
    }

    public function test_funding_adds_the_missing_amount_on_top_of_what_was_assigned(): void
    {
        $this->actingAs($this->user)
            ->post('/budgets/months/'.self::MONTH.'/underfunded')
            ->assertRedirect();

        $this->assertEquals(6000, $this->budgeted($this->insurance));
        $this->assertEquals(1000, $this->budgeted($this->internet));
    }

    public function test_unticked_categories_are_left_alone_and_free_money_for_the_rest(): void
    {
        $this->actingAs($this->user)
            ->post('/budgets/months/'.self::MONTH.'/underfunded', ['category_ids' => [$this->internet->id]])
            ->assertRedirect();

        $this->assertEquals(1000, $this->budgeted($this->insurance));
        $this->assertEquals(3000, $this->budgeted($this->internet));
    }

    public function test_nothing_is_assigned_when_ready_to_assign_is_empty(): void
    {
        BudgetMonth::where(['team_id' => $this->team->id, 'month' => self::MONTH, 'activity' => 40000])->update(['activity' => 26000 + 8000]);

        $this->actingAs($this->user)
            ->getJson('/budgets/months/'.self::MONTH.'/underfunded')
            ->assertOk()
            ->assertJson(['ready_to_assign' => 0, 'assigned' => 0, 'missing' => 8000]);

        $this->actingAs($this->user)->post('/budgets/months/'.self::MONTH.'/underfunded')->assertRedirect();

        $this->assertEquals(1000, $this->budgeted($this->insurance));
        $this->assertEquals(0, $this->budgeted($this->internet));
    }

    private function child(Category $group, string $name): Category
    {
        return Category::create(['name' => $name, 'team_id' => $group->team_id, 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => 0]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function target(Category $category, float $amount, ?int $day, array $attributes = []): BudgetTarget
    {
        return BudgetTarget::create(['team_id' => $this->team->id, 'user_id' => $this->user->id, 'category_id' => $category->id, 'target_type' => BudgetTarget::TYPE_SPENDING, 'amount' => $amount, 'frequency' => 'monthly', 'frequency_month_date' => $day, 'notify' => true, ...$attributes]);
    }

    private function month(Category $category, float $budgeted, float $activity = 0): void
    {
        BudgetMonth::create(['team_id' => $this->team->id, 'user_id' => $this->user->id, 'category_id' => $category->id, 'month' => self::MONTH, 'name' => self::MONTH, 'budgeted' => $budgeted, 'activity' => $activity, 'available' => $budgeted + $activity]);
    }

    private function budgeted(Category $category): float
    {
        return (float) BudgetMonth::where(['team_id' => $this->team->id, 'category_id' => $category->id, 'month' => self::MONTH])->value('budgeted');
    }
}
