<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Assigning Ready to Assign rolls the budget forward, which visits every category. The app waits for that call
 * ("Fix this" / "Assign"), so its cost has to stay flat per category: it was ~14 queries per category, mostly
 * eager loads nobody read and rewrites of values that had not changed.
 */
class BudgetRtaQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocating_ready_to_assign_does_not_query_per_relation_of_every_category(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        Sanctum::actingAs($user->fresh());

        $first = null;
        for ($g = 0; $g < 6; $g++) {
            $group = Category::create(['name' => "Group $g", 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => $g]);
            for ($c = 0; $c < 10; $c++) {
                $category = Category::create(['name' => "Cat $g-$c", 'team_id' => $team->id, 'resource_type' => 'transactions', 'parent_id' => $group->id, 'depth' => 1, 'index' => $c]);
                $first ??= $category;
            }
        }

        $month = now()->format('Y-m');
        $allocate = fn (float $amount) => $this->postJson('/api/mobile/budget/ready-to-assign/allocate', [
            'month' => $month,
            'splits' => [['destination_category_id' => $first->id, 'amount' => $amount]],
        ])->assertOk();

        $allocate(10); // first call also creates the reserved categories and month rows

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });
        $allocate(100);

        // 60 categories: measured at ~150 after the fix, 820 before it.
        $this->assertLessThan(300, $queries, "allocate ran $queries queries for 60 categories");

        $this->assertEquals(110.0, (float) BudgetMonth::where(['category_id' => $first->id, 'month' => $month.'-01'])->value('budgeted'));
    }
}
