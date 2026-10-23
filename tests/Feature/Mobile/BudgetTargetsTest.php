<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetTargetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_spending_target(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->postJson("/api/mobile/budget/categories/{$category->id}/targets", [
            'target_type' => 'spending',
            'amount' => 5000,
            'frequency' => 'MONTHLY',
            'frequency_month_date' => 15,
        ])
            ->assertStatus(201)
            ->assertJsonStructure(['id', 'target_type', 'amount', 'frequency', 'frequency_month_date'])
            ->assertJson([
                'target_type' => 'spending',
                'amount' => 5000,
                'frequency' => 'MONTHLY',
                'frequency_month_date' => 15,
            ]);

        $this->assertDatabaseHas('budget_targets', [
            'category_id' => $category->id,
            'target_type' => 'spending',
        ]);
    }

    public function test_create_loan_target(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $this->postJson("/api/mobile/budget/categories/{$category->id}/targets", [
            'target_type' => 'loan',
            'amount' => 8333.33,
            'principal' => 100000,
            'interest_rate' => 10,
            'term_months' => 12,
            'loan_start_date' => '2026-01-01',
            'frequency' => 'MONTHLY',
        ])
            ->assertStatus(201)
            ->assertJson([
                'target_type' => 'loan',
                'loan' => [
                    'principal' => 100000,
                    'interest_rate' => 10,
                    'term_months' => 12,
                    'loan_start_date' => '2026-01-01',
                ],
            ]);
    }

    public function test_cannot_create_duplicate_target(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        BudgetTarget::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => $category->name,
            'target_type' => 'spending',
            'amount' => 1000,
            'frequency' => 'MONTHLY',
        ]);

        $this->postJson("/api/mobile/budget/categories/{$category->id}/targets", [
            'target_type' => 'spending',
            'amount' => 2000,
            'frequency' => 'MONTHLY',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Category already has a target. Use PUT to update.']);
    }

    public function test_update_target(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $target = BudgetTarget::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => $category->name,
            'target_type' => 'spending',
            'amount' => 1000,
            'frequency' => 'MONTHLY',
        ]);

        $this->putJson("/api/mobile/budget/categories/{$category->id}/targets/{$target->id}", [
            'amount' => 2500,
            'frequency_month_date' => 20,
        ])
            ->assertOk()
            ->assertJson([
                'amount' => 2500,
                'frequency_month_date' => 20,
            ]);

        $this->assertEquals(2500, (float) $target->fresh()->amount);
    }

    public function test_complete_target(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $target = BudgetTarget::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => $category->name,
            'target_type' => 'saving_balance',
            'amount' => 50000,
            'frequency' => 'MONTHLY',
        ]);

        $this->postJson("/api/mobile/budget/categories/{$category->id}/targets/{$target->id}/complete", [
            'completed_at' => '2026-10',
        ])
            ->assertOk();

        $this->assertNotNull($target->fresh()->completed_at);
    }

    public function test_snooze_target_toggles(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        BudgetTarget::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => $category->name,
            'target_type' => 'spending',
            'amount' => 1000,
            'frequency' => 'MONTHLY',
        ]);

        // Snooze on
        $this->patchJson("/api/mobile/budget/categories/{$category->id}/snooze-target", [
            'month' => '2026-10',
        ])
            ->assertOk()
            ->assertJson(['snoozed_month' => '2026-10']);

        // Snooze off (toggle)
        $this->patchJson("/api/mobile/budget/categories/{$category->id}/snooze-target", [
            'month' => '2026-10',
        ])
            ->assertOk()
            ->assertJson(['snoozed_month' => null]);
    }

    public function test_budget_response_includes_full_target(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        BudgetTarget::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => $category->name,
            'target_type' => 'spending',
            'amount' => 5000,
            'frequency' => 'MONTHLY',
            'frequency_month_date' => 25,
            'notify' => true,
        ]);

        $response = $this->getJson('/api/mobile/budget?month='.now()->format('Y-m'))
            ->assertOk();

        $found = false;
        foreach ($response->json('groups') as $group) {
            foreach ($group['children'] as $child) {
                if ($child['id'] === $category->id) {
                    $this->assertArrayHasKey('id', $child['target']);
                    $this->assertArrayHasKey('frequency_month_date', $child['target']);
                    $this->assertArrayHasKey('notify', $child['target']);
                    $this->assertEquals('spending', $child['target']['target_type']);
                    $this->assertEquals(25, $child['target']['frequency_month_date']);
                    $this->assertTrue($child['target']['notify']);
                    $found = true;
                }
            }
        }

        $this->assertTrue($found, 'Category with target not found in budget response.');
    }

    public function test_archive_target_takes_it_down_but_keeps_its_history(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);

        $id = $this->postJson("/api/mobile/budget/categories/{$category->id}/targets", [
            'target_type' => 'spending',
            'amount' => 24000,
            'frequency' => 'MONTHLY',
            'frequency_month_date' => 7,
        ])->assertStatus(201)->json('id');

        $month = now()->format('Y-m');
        $this->assertTrue($this->categoryOf($category->id, $month)['has_target']);

        $this->deleteJson("/api/mobile/budget/categories/{$category->id}/targets/{$id}")
            ->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonStructure(['id', 'archived_at']);

        // The category no longer has a target: nothing "still needs" and no alert for it.
        $row = $this->categoryOf($category->id, $month);
        $this->assertFalse($row['has_target']);
        $this->assertNull($row['target']);

        // The row and its data are kept.
        $this->assertDatabaseHas('budget_targets', ['id' => $id, 'category_id' => $category->id, 'amount' => 24000]);
        $this->assertNotNull(BudgetTarget::withoutGlobalScope('live')->find($id)->archived_at);
        $this->assertNull(BudgetTarget::find($id));

        // It is listed in the category's history, newest first.
        $this->getJson("/api/mobile/budget/categories/{$category->id}/targets/archived")
            ->assertOk()
            ->assertJsonPath('targets.0.id', $id)
            ->assertJsonPath('targets.0.amount', 24000);

        // Nothing live can be done to it any more.
        $this->putJson("/api/mobile/budget/categories/{$category->id}/targets/{$id}", ['target_type' => 'spending', 'amount' => 1])->assertNotFound();
        $this->postJson("/api/mobile/budget/categories/{$category->id}/targets/{$id}/complete")->assertNotFound();
        $this->patchJson("/api/mobile/budget/categories/{$category->id}/snooze-target", ['month' => $month])->assertNotFound();
        $this->deleteJson("/api/mobile/budget/categories/{$category->id}/targets/{$id}")->assertNotFound();

        // And the category can get a new target afterwards, while the old one stays in the history.
        $newId = $this->postJson("/api/mobile/budget/categories/{$category->id}/targets", [
            'target_type' => 'spending',
            'amount' => 500,
            'frequency' => 'MONTHLY',
        ])->assertStatus(201)->json('id');
        $this->assertNotEquals($id, $newId);
        $this->assertTrue($this->categoryOf($category->id, $month)['has_target']);
        $this->getJson("/api/mobile/budget/categories/{$category->id}/targets/archived")->assertJsonCount(1, 'targets');
    }

    public function test_archive_target_is_scoped_to_the_team(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/budget/categories/{$category->id}/targets", ['target_type' => 'spending', 'amount' => 100, 'frequency' => 'MONTHLY'])->json('id');

        $other = User::factory()->withPersonalTeam()->create();
        $other->forceFill(['current_team_id' => $other->ownedTeams()->first()->id])->save();
        Sanctum::actingAs($other->fresh());

        $this->deleteJson("/api/mobile/budget/categories/{$category->id}/targets/{$id}")->assertNotFound();
        $this->assertNull(BudgetTarget::withoutGlobalScope('live')->find($id)->archived_at);
    }

    public function test_archived_target_stops_feeding_the_planned_transactions_and_today_alerts(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);
        $id = $this->postJson("/api/mobile/budget/categories/{$category->id}/targets", ['target_type' => 'spending', 'amount' => 24000, 'frequency' => 'MONTHLY', 'frequency_month_date' => 7, 'notify' => true])->json('id');

        $this->assertSame(1, BudgetTarget::whereNotNull('frequency_month_date')->where('notify', 1)->count());

        $this->deleteJson("/api/mobile/budget/categories/{$category->id}/targets/{$id}")->assertOk();

        // The queries the planned-transaction and alert jobs run no longer see it.
        $this->assertSame(0, BudgetTarget::whereNotNull('frequency_month_date')->where('notify', 1)->count());
        $this->assertSame(0, BudgetTarget::where('team_id', $team->id)->count());
        $this->assertSame(1, BudgetTarget::archived()->where('team_id', $team->id)->count());
    }

    public function test_an_archived_target_can_be_brought_back_while_the_category_has_none(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);
        $month = now()->format('Y-m');
        $id = $this->postJson("/api/mobile/budget/categories/{$category->id}/targets", ['target_type' => 'spending', 'amount' => 24000, 'frequency' => 'MONTHLY', 'frequency_month_date' => 7])->json('id');
        $this->deleteJson("/api/mobile/budget/categories/{$category->id}/targets/{$id}")->assertOk();
        $this->assertFalse($this->categoryOf($category->id, $month)['has_target']);

        $this->postJson("/api/mobile/budget/categories/{$category->id}/targets/{$id}/restore")
            ->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('amount', 24000);

        $row = $this->categoryOf($category->id, $month);
        $this->assertTrue($row['has_target']);
        $this->assertEquals(24000, $row['target']['amount']);
        $this->getJson("/api/mobile/budget/categories/{$category->id}/targets/archived")->assertJsonCount(0, 'targets');
        // It is live again, so it is not "archived" and cannot be restored twice.
        $this->postJson("/api/mobile/budget/categories/{$category->id}/targets/{$id}/restore")->assertNotFound();
    }

    public function test_an_archived_target_cannot_come_back_over_a_live_one_or_across_teams(): void
    {
        [$user, $team, $category] = $this->makeSetup();
        Sanctum::actingAs($user);
        $old = $this->postJson("/api/mobile/budget/categories/{$category->id}/targets", ['target_type' => 'spending', 'amount' => 100, 'frequency' => 'MONTHLY'])->json('id');
        $this->deleteJson("/api/mobile/budget/categories/{$category->id}/targets/{$old}")->assertOk();
        $current = $this->postJson("/api/mobile/budget/categories/{$category->id}/targets", ['target_type' => 'spending', 'amount' => 200, 'frequency' => 'MONTHLY'])->json('id');

        // One live target at a time: the new one has to be archived first.
        $this->postJson("/api/mobile/budget/categories/{$category->id}/targets/{$old}/restore")->assertStatus(422);
        $this->assertNotNull(BudgetTarget::withoutGlobalScope('live')->find($old)->archived_at);
        $this->deleteJson("/api/mobile/budget/categories/{$category->id}/targets/{$current}")->assertOk();
        $this->postJson("/api/mobile/budget/categories/{$category->id}/targets/{$old}/restore")->assertOk();

        $other = User::factory()->withPersonalTeam()->create();
        $other->forceFill(['current_team_id' => $other->ownedTeams()->first()->id])->save();
        Sanctum::actingAs($other->fresh());
        $this->postJson("/api/mobile/budget/categories/{$category->id}/targets/{$current}/restore")->assertNotFound();
    }

    /** The category's row in GET /budget. */
    private function categoryOf(int $categoryId, string $month): array
    {
        return collect($this->getJson("/api/mobile/budget?month={$month}")->assertOk()->json('groups'))
            ->flatMap(fn ($group) => $group['children'])
            ->firstWhere('id', $categoryId);
    }

    private function makeSetup(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user = $user->fresh();

        $group = Category::create([
            'name' => 'Test Group',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 99,
        ]);

        $category = Category::create([
            'name' => 'Test Category',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        return [$user, $team, $category];
    }
}
