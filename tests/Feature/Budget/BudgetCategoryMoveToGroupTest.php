<?php

namespace Tests\Feature\Budget;

use App\Domains\AppCore\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetCategoryMoveToGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_moved_to_another_group(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $groupA = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Group A',
            'resource_type' => 'transactions',
        ]);

        $groupB = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Group B',
            'resource_type' => 'transactions',
        ]);

        $category = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Groceries',
            'parent_id' => $groupA->id,
            'resource_type' => 'transactions',
        ]);

        $this->actingAs($user)
            ->patch("/budgets/{$category->id}/move-to-group/{$groupB->id}")
            ->assertRedirect();

        $category->refresh();
        $this->assertSame($groupB->id, $category->parent_id);
    }

    public function test_move_updates_index_when_provided(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $groupA = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Group A',
            'resource_type' => 'transactions',
        ]);

        $groupB = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Group B',
            'resource_type' => 'transactions',
        ]);

        $category = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Transport',
            'parent_id' => $groupA->id,
            'resource_type' => 'transactions',
        ]);

        $this->actingAs($user)
            ->patch("/budgets/{$category->id}/move-to-group/{$groupB->id}", ['index' => 3])
            ->assertRedirect();

        $category->refresh();
        $this->assertSame($groupB->id, $category->parent_id);
        $this->assertSame(3, $category->index);
    }

    public function test_cannot_move_to_non_group_category(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $group = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Group',
            'resource_type' => 'transactions',
        ]);

        $subcategory = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Sub',
            'parent_id' => $group->id,
            'resource_type' => 'transactions',
        ]);

        $anotherSub = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Another',
            'parent_id' => $group->id,
            'resource_type' => 'transactions',
        ]);

        $this->actingAs($user)
            ->patch("/budgets/{$anotherSub->id}/move-to-group/{$subcategory->id}")
            ->assertStatus(422);
    }

    public function test_cannot_move_category_from_another_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherTeam = $otherUser->ownedTeams()->first();

        $group = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'My Group',
            'resource_type' => 'transactions',
        ]);

        $foreignCategory = Category::create([
            'team_id' => $otherTeam->id,
            'user_id' => $otherUser->id,
            'name' => 'Foreign',
            'resource_type' => 'transactions',
        ]);

        $this->actingAs($user)
            ->patch("/budgets/{$foreignCategory->id}/move-to-group/{$group->id}")
            ->assertForbidden();
    }
}
