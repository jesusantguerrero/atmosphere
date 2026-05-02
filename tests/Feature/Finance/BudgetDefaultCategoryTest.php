<?php

namespace Tests\Feature\Finance;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetDefaultCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetDefaultCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_team_seeds_default_roles_for_ahorro_and_gasto_personal(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $defaults = BudgetDefaultCategory::where('team_id', $team->id)->pluck('category_id', 'role')->all();

        $ahorro = Category::where(['team_id' => $team->id, 'display_id' => 'savings_general'])->firstOrFail();
        $gasto = Category::where(['team_id' => $team->id, 'display_id' => 'personal_spending'])->firstOrFail();

        $this->assertSame($ahorro->id, $defaults['savings'] ?? null);
        $this->assertSame($gasto->id, $defaults['spending'] ?? null);
    }

    public function test_user_can_set_a_default_role_on_any_category(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        BudgetDefaultCategory::where('team_id', $team->id)->delete();

        $category = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'Vacation Fund',
            'resource_type' => 'transactions',
        ]);

        $this->actingAs($user)
            ->patch("/budgets/{$category->id}/default-role", ['role' => 'savings'])
            ->assertRedirect();

        $this->assertDatabaseHas('budget_default_categories', [
            'team_id' => $team->id,
            'role' => 'savings',
            'category_id' => $category->id,
        ]);
    }

    public function test_setting_role_swaps_atomically_with_existing_one(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $newCat = Category::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'name' => 'New Savings Pot',
            'resource_type' => 'transactions',
        ]);

        $oldCatId = BudgetDefaultCategory::where(['team_id' => $team->id, 'role' => 'savings'])->value('category_id');
        $this->assertNotNull($oldCatId, 'expected initial savings default to be seeded');

        $this->actingAs($user)
            ->patch("/budgets/{$newCat->id}/default-role", ['role' => 'savings'])
            ->assertRedirect();

        $this->assertSame(
            $newCat->id,
            BudgetDefaultCategory::where(['team_id' => $team->id, 'role' => 'savings'])->value('category_id')
        );

        $this->assertDatabaseMissing('budget_default_categories', [
            'team_id' => $team->id,
            'category_id' => $oldCatId,
            'role' => 'savings',
        ]);
    }

    public function test_clearing_a_default_role_removes_the_record(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $catId = BudgetDefaultCategory::where(['team_id' => $team->id, 'role' => 'savings'])->value('category_id');
        $this->assertNotNull($catId);

        $this->actingAs($user)
            ->patch("/budgets/{$catId}/default-role", ['role' => null])
            ->assertRedirect();

        $this->assertDatabaseMissing('budget_default_categories', [
            'team_id' => $team->id,
            'category_id' => $catId,
            'role' => 'savings',
        ]);
    }

    public function test_invalid_role_is_rejected(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $catId = BudgetDefaultCategory::where(['team_id' => $team->id, 'role' => 'savings'])->value('category_id');

        $this->actingAs($user)
            ->patch("/budgets/{$catId}/default-role", ['role' => 'bogus'])
            ->assertSessionHasErrors('role');
    }

    public function test_budget_index_exposes_defaults_to_inertia(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->ownedTeams()->first();

        $response = $this->actingAs($user)->get('/budgets');

        $response->assertOk();
        $defaults = $response->viewData('page')['props']['budgetDefaults'] ?? null;
        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('savings', $defaults);
        $this->assertArrayHasKey('spending', $defaults);
    }
}
