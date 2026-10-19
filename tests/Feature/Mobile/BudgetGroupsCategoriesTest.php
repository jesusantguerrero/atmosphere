<?php

namespace Tests\Feature\Mobile;

use App\Domains\AppCore\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BudgetGroupsCategoriesTest extends TestCase
{
    use RefreshDatabase;

    // ── Groups ───────────────────────────────────────────────────

    public function test_create_budget_group(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/budget/groups', ['name' => 'Entertainment'])
            ->assertStatus(201)
            ->assertJsonStructure(['id', 'name'])
            ->assertJson(['name' => 'Entertainment']);

        $this->assertDatabaseHas('categories', [
            'team_id' => $team->id,
            'name' => 'Entertainment',
            'parent_id' => null,
            'resource_type' => 'transactions',
        ]);
    }

    public function test_rename_budget_group(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = $this->createGroup($team->id, 'Old Name');

        $this->patchJson("/api/mobile/budget/groups/{$group->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJson(['name' => 'New Name']);

        $this->assertEquals('New Name', $group->fresh()->name);
    }

    public function test_cannot_rename_reserved_group(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $reserved = Category::where('team_id', $team->id)
            ->where('name', 'Inflow')
            ->whereNull('parent_id')
            ->first();

        if (! $reserved) {
            $this->markTestSkipped('No reserved Inflow group in seeded data.');
        }

        $this->patchJson("/api/mobile/budget/groups/{$reserved->id}", ['name' => 'Hacked'])
            ->assertStatus(422);
    }

    public function test_delete_budget_group_moves_children(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $groupToDelete = $this->createGroup($team->id, 'Doomed Group');

        $cat = Category::create([
            'name' => 'Test Cat',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $groupToDelete->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $this->deleteJson("/api/mobile/budget/groups/{$groupToDelete->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(Category::find($groupToDelete->id));
        // Category was moved to another non-reserved group (whichever has the lowest index).
        $fresh = $cat->fresh();
        $this->assertNotNull($fresh->parent_id);
        $this->assertNotEquals($groupToDelete->id, $fresh->parent_id);
    }

    public function test_delete_empty_budget_group(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = $this->createGroup($team->id, 'Empty Group');

        $this->deleteJson("/api/mobile/budget/groups/{$group->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(Category::find($group->id));
    }

    // ── Categories ───────────────────────────────────────────────

    public function test_create_budget_category(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = $this->createGroup($team->id, 'My Group');

        $this->postJson('/api/mobile/budget/categories', [
            'name' => 'Groceries',
            'group_id' => $group->id,
        ])
            ->assertStatus(201)
            ->assertJson(['name' => 'Groceries', 'group_id' => $group->id]);

        $this->assertDatabaseHas('categories', [
            'team_id' => $team->id,
            'name' => 'Groceries',
            'parent_id' => $group->id,
        ]);
    }

    public function test_update_budget_category_note(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = $this->createGroup($team->id, 'G');
        $cat = Category::create([
            'name' => 'Rent',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $this->patchJson("/api/mobile/budget/categories/{$cat->id}", ['note' => 'Due on the 5th'])
            ->assertOk()
            ->assertJson(['note' => 'Due on the 5th']);

        $this->assertEquals('Due on the 5th', $cat->fresh()->description);
    }

    public function test_move_category_to_another_group(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $groupA = $this->createGroup($team->id, 'Bills');
        $groupB = $this->createGroup($team->id, 'Wants');
        $cat = Category::create([
            'name' => 'Netflix',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $groupA->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $this->patchJson("/api/mobile/budget/categories/{$cat->id}", ['group_id' => $groupB->id])
            ->assertOk()
            ->assertJson(['group_id' => $groupB->id]);

        $this->assertEquals($groupB->id, $cat->fresh()->parent_id);
    }

    public function test_delete_budget_category(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        $group = $this->createGroup($team->id, 'G');
        $cat = Category::create([
            'name' => 'Temp',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
        ]);

        $this->deleteJson("/api/mobile/budget/categories/{$cat->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(Category::find($cat->id));
    }

    public function test_budget_response_includes_note(): void
    {
        [$user, $team] = $this->makeUserAndTeam();
        Sanctum::actingAs($user);

        // Create a group + category so we control what appears in the response.
        $group = $this->createGroup($team->id, 'Note Test Group');
        $cat = Category::create([
            'name' => 'Noted Cat',
            'team_id' => $team->id,
            'resource_type' => 'transactions',
            'parent_id' => $group->id,
            'depth' => 1,
            'index' => 0,
            'description' => 'My budget note',
        ]);

        $response = $this->getJson('/api/mobile/budget?month='.now()->format('Y-m'))
            ->assertOk();

        $found = false;
        foreach ($response->json('groups') as $g) {
            foreach ($g['children'] as $child) {
                if ($child['id'] === $cat->id) {
                    $this->assertEquals('My budget note', $child['note']);
                    $found = true;
                }
            }
        }

        $this->assertTrue($found, 'The category with the note was not found in the budget response.');
    }

    // ── Helpers ──────────────────────────────────────────────────

    private function makeUserAndTeam(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        return [$user->fresh(), $team];
    }

    private function createGroup(int $teamId, string $name): Category
    {
        return Category::create([
            'name' => $name,
            'team_id' => $teamId,
            'resource_type' => 'transactions',
            'parent_id' => null,
            'depth' => 0,
            'index' => Category::where('team_id', $teamId)->whereNull('parent_id')->max('index') + 1,
        ]);
    }
}
