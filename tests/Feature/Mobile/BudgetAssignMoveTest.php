<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetAssignMoveTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_budget_to_category(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = $this->createGroup($team);
        $category = $this->createCategory($team, $group);
        $month = now()->format('Y-m');

        $this->postJson('/api/mobile/budget/assign', [
            'category_id' => $category->id,
            'month' => $month,
            'budgeted' => 5000,
        ])->assertOk()->assertJson(['success' => true]);

        $bm = BudgetMonth::where([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'month' => $month.'-01',
        ])->first();

        $this->assertNotNull($bm);
        $this->assertEquals(5000.0, (float) $bm->budgeted);
    }

    public function test_assign_rejects_other_teams_category(): void
    {
        [$user] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $otherGroup = $this->createGroup($otherTeam);
        $otherCat = $this->createCategory($otherTeam, $otherGroup);

        $this->postJson('/api/mobile/budget/assign', [
            'category_id' => $otherCat->id,
            'month' => now()->format('Y-m'),
            'budgeted' => 1000,
        ])->assertNotFound();
    }

    public function test_move_budget_between_categories(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = $this->createGroup($team);
        $source = $this->createCategory($team, $group, 'Food');
        $dest = $this->createCategory($team, $group, 'Transport');
        $month = now()->format('Y-m');

        $this->postJson('/api/mobile/budget/assign', [
            'category_id' => $source->id,
            'month' => $month,
            'budgeted' => 3000,
        ])->assertOk();

        $this->postJson('/api/mobile/budget/move', [
            'source_category_id' => $source->id,
            'destination_category_id' => $dest->id,
            'month' => $month,
            'amount' => 1000,
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_move_rejects_same_category(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = $this->createGroup($team);
        $cat = $this->createCategory($team, $group);

        $this->postJson('/api/mobile/budget/move', [
            'source_category_id' => $cat->id,
            'destination_category_id' => $cat->id,
            'month' => now()->format('Y-m'),
            'amount' => 100,
        ])->assertStatus(422);
    }

    public function test_move_rejects_other_teams_category(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = $this->createGroup($team);
        $source = $this->createCategory($team, $group);

        $other = User::factory()->withPersonalTeam()->create();
        $otherTeam = $other->ownedTeams()->first();
        $otherGroup = $this->createGroup($otherTeam);
        $otherDest = $this->createCategory($otherTeam, $otherGroup);

        $this->postJson('/api/mobile/budget/move', [
            'source_category_id' => $source->id,
            'destination_category_id' => $otherDest->id,
            'month' => now()->format('Y-m'),
            'amount' => 100,
        ])->assertNotFound();
    }

    public function test_assign_validates_input(): void
    {
        [$user] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/budget/assign', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'month', 'budgeted']);

        $this->postJson('/api/mobile/budget/assign', [
            'category_id' => 999999,
            'month' => 'not-a-month',
            'budgeted' => -50,
        ])->assertStatus(422);
    }

    private function makeUserAndTeam(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user->fresh(), $team];
    }

    private function createGroup($team): Category
    {
        return Category::create([
            'name' => 'Bills',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => 0,
        ]);
    }

    private function createCategory($team, Category $group, string $name = 'Rent'): Category
    {
        return Category::create([
            'name' => $name,
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);
    }
}
