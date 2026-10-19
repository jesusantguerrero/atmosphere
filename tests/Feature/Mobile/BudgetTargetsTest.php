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
