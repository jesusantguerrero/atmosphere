<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetDefaultCategory;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetRtaAllocateCoverTest extends TestCase
{
    use RefreshDatabase;

    // ── ready_to_assign_info in GET /budget ─────────────────────────

    public function test_budget_response_includes_ready_to_assign_info(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/budget?month='.now()->format('Y-m'))
            ->assertOk();

        $info = $response->json('ready_to_assign_info');
        $this->assertNotNull($info);
        $this->assertArrayHasKey('status', $info);
        $this->assertArrayHasKey('amount', $info);
        $this->assertArrayHasKey('scheduled_total', $info);
        $this->assertArrayHasKey('after_scheduled', $info);
        $this->assertArrayHasKey('default_savings_category_id', $info);
        $this->assertArrayHasKey('default_spending_category_id', $info);
        $this->assertContains($info['status'], ['available', 'overspent', 'empty']);
    }

    public function test_rta_info_reflects_defaults(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = Category::create([
            'name' => 'Goals',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 99,
        ]);

        $savings = Category::create([
            'name' => 'Emergency Fund',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        BudgetDefaultCategory::updateOrCreate(
            ['team_id' => $team->id, 'role' => 'savings'],
            ['category_id' => $savings->id],
        );

        $response = $this->getJson('/api/mobile/budget?month='.now()->format('Y-m'))
            ->assertOk();

        $this->assertEquals($savings->id, $response->json('ready_to_assign_info.default_savings_category_id'));
    }

    // ── POST /budget/ready-to-assign/allocate ───────────────────────

    public function test_allocate_leftover_succeeds(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = Category::create([
            'name' => 'Bills',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 99,
        ]);

        $cat = Category::create([
            'name' => 'Electricity',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $this->postJson('/api/mobile/budget/ready-to-assign/allocate', [
            'month' => now()->format('Y-m'),
            'splits' => [
                ['destination_category_id' => $cat->id, 'amount' => 500],
            ],
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_allocate_validates_splits(): void
    {
        [$user] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/budget/ready-to-assign/allocate', [
            'month' => now()->format('Y-m'),
        ])->assertStatus(422);
    }

    public function test_allocate_rejects_invalid_category(): void
    {
        [$user] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/budget/ready-to-assign/allocate', [
            'month' => now()->format('Y-m'),
            'splits' => [
                ['destination_category_id' => 999999, 'amount' => 100],
            ],
        ])->assertStatus(422);
    }

    // ── POST /budget/ready-to-assign/cover ──────────────────────────

    public function test_cover_overspend_succeeds(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = Category::create([
            'name' => 'Flex',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 99,
        ]);

        $source = Category::create([
            'name' => 'Dining',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $this->postJson('/api/mobile/budget/ready-to-assign/cover', [
            'month' => now()->format('Y-m'),
            'source_category_id' => $source->id,
            'amount' => 200,
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_cover_validates_required_fields(): void
    {
        [$user] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/budget/ready-to-assign/cover', [])
            ->assertStatus(422);
    }

    // ── PATCH /budget/categories/{id}/default-role ─────────────────

    public function test_set_default_role(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = Category::create([
            'name' => 'Goals',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 99,
        ]);

        $cat = Category::create([
            'name' => 'Emergency Fund',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $this->patchJson("/api/mobile/budget/categories/{$cat->id}/default-role", [
            'role' => 'savings',
        ])->assertOk()->assertJson(['id' => $cat->id, 'role' => 'savings']);

        $this->assertDatabaseHas('budget_default_categories', [
            'team_id' => $team->id,
            'role' => 'savings',
            'category_id' => $cat->id,
        ]);
    }

    public function test_clear_default_role(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = Category::create([
            'name' => 'Goals',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 99,
        ]);

        $cat = Category::create([
            'name' => 'Emergency Fund',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        BudgetDefaultCategory::updateOrCreate(
            ['team_id' => $team->id, 'role' => 'savings'],
            ['category_id' => $cat->id],
        );

        $this->patchJson("/api/mobile/budget/categories/{$cat->id}/default-role", [
            'role' => null,
        ])->assertOk()->assertJson(['id' => $cat->id, 'role' => null]);

        $this->assertDatabaseMissing('budget_default_categories', [
            'team_id' => $team->id,
            'category_id' => $cat->id,
        ]);
    }

    // ── BUG #7: needed calculation for spending targets ──────────

    public function test_spending_target_needed_uses_funded_not_available(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = Category::create([
            'name' => 'Bills',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 99,
        ]);

        $cat = Category::create([
            'name' => 'School Tuition',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $bt = BudgetTarget::create([
            'team_id' => $team->id,
            'category_id' => $cat->id,
            'target_type' => 'spending',
            'amount' => 11500,
            'frequency' => 'MONTHLY',
            'frequency_month_date' => 1,
        ]);

        // Simulate: budgeted 11500, spent 11500 → available = 0.
        $month = now()->format('Y-m').'-01';
        BudgetMonth::updateOrCreate(
            ['team_id' => $team->id, 'category_id' => $cat->id, 'month' => $month],
            ['budgeted' => 11500, 'activity' => -11500, 'available' => 0, 'left_from_last_month' => 0],
        );

        $response = $this->getJson('/api/mobile/budget?month='.now()->format('Y-m'))
            ->assertOk();

        // Find the target in the response.
        foreach ($response->json('groups') as $g) {
            foreach ($g['children'] as $child) {
                if ($child['id'] === $cat->id) {
                    // needed should be 0 (funded = 11500, target = 11500).
                    $this->assertEquals(0, $child['target']['needed']);

                    return;
                }
            }
        }

        $this->fail('Category not found in budget response.');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function makeUserAndTeam(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user->fresh(), $team];
    }
}
