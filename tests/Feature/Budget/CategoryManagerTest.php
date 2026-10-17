<?php

namespace Tests\Feature\Budget;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Budget\Models\BudgetMovement;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Models\CategoryMerge;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Watchlist\Models\Watchlist;
use Tests\TestCase;

class CategoryManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $source;

    private Category $target;

    private Category $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $teamId = $this->user->ownedTeams()->first()->id;
        $this->user->forceFill(['current_team_id' => $teamId])->save();
        $this->actingAs($this->user);
        $this->group = Category::create(['team_id' => $teamId, 'user_id' => $this->user->id, 'name' => 'Test group', 'resource_type' => 'transactions']);
        $this->source = $this->category('Dining Out');
        $this->target = $this->category('Comida fuera');
    }

    private function category(string $name): Category
    {
        return Category::create(['team_id' => $this->group->team_id, 'user_id' => $this->user->id, 'parent_id' => $this->group->id, 'resource_type' => 'transactions', 'name' => $name]);
    }

    private function preview(): array
    {
        return $this->postJson("/finance/category-manager/{$this->source->id}/preview", ['target_id' => $this->target->id])->assertOk()->json();
    }

    public function test_manager_lists_categories_and_can_create_rename_move_and_hide_without_deleting(): void
    {
        $this->get('/finance/category-manager')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Finance/CategoryManager')->has('managedCategories'));
        $group = $this->postJson('/finance/category-manager', ['name' => 'Daily expenses'])->assertCreated()->json('id');
        $this->patchJson("/finance/category-manager/{$this->source->id}", ['name' => 'Dining', 'parent_id' => $group])->assertOk();
        $this->assertDatabaseHas('categories', ['id' => $this->source->id, 'name' => 'Dining', 'parent_id' => $group]);
        $this->patchJson("/finance/category-manager/{$this->source->id}", ['archive' => true])->assertOk();
        $this->assertSame('hidden_categories', $this->source->fresh()->group->display_id);
        $this->patchJson("/finance/category-manager/{$this->source->id}", ['parent_id' => $this->group->id])->assertOk();
        $this->assertSame($this->group->id, $this->source->fresh()->parent_id);
        $this->patchJson("/finance/category-manager/{$this->group->id}", ['archive' => true])->assertUnprocessable();
        $this->patchJson("/finance/category-manager/{$group}", ['archive' => true])->assertOk();
        $this->assertDatabaseHas('categories', ['id' => $group, 'resource_type' => 'archived_group']);
        $this->patchJson("/finance/category-manager/{$group}", ['archive' => false])->assertOk();
        $this->assertDatabaseHas('categories', ['id' => $group, 'resource_type' => 'transactions']);
    }

    public function test_merge_preserves_exact_monthly_totals_split_lines_and_deleted_transactions_and_can_undo(): void
    {
        $transaction = (new Transaction)->forceFill(['team_id' => $this->group->team_id, 'user_id' => $this->user->id, 'category_id' => $this->source->id, 'account_id' => 1001, 'currency_code' => 'DOP', 'date' => '2026-04-19', 'description' => 'Dinner', 'direction' => Transaction::DIRECTION_CREDIT, 'status' => Transaction::STATUS_VERIFIED, 'total' => '20.0023', 'has_splits' => true]);
        $transaction->save();
        $line = (new TransactionLine)->forceFill(['transaction_id' => $transaction->id, 'team_id' => $this->group->team_id, 'user_id' => $this->user->id, 'category_id' => $this->source->id, 'account_id' => 1001, 'date' => '2026-04-19', 'concept' => 'Dinner', 'index' => 0, 'anchor' => 1, 'type' => -1, 'amount' => '20.0023', 'is_split' => true]);
        $line->save();
        $transaction->forceFill(['deleted_at' => now()])->saveQuietly();
        BudgetMonth::whereIn('category_id', [$this->source->id, $this->target->id])->delete();
        foreach ([$this->source, $this->target] as $category) {
            BudgetMonth::create(['team_id' => $this->group->team_id, 'user_id' => $this->user->id, 'category_id' => $category->id, 'name' => $category->name, 'month' => '2026-04-01', 'budgeted' => '100.1234', 'activity' => '-20.0023', 'available' => '80.1211']);
        }
        $watchlist = Watchlist::create(['team_id' => $this->group->team_id, 'user_id' => $this->user->id, 'name' => 'Food watch', 'type' => 'categories', 'input' => [$this->source->id, $this->target->id]]);
        $movement = BudgetMovement::create(['team_id' => $this->group->team_id, 'user_id' => $this->user->id, 'source_category_id' => $this->source->id, 'destination_category_id' => $this->target->id, 'date' => '2026-04-01', 'amount' => 10]);
        $preview = $this->preview();
        $this->assertSame([], $preview['blockers']);
        $this->assertSame(1, $preview['transactions']);
        $this->assertSame(1, $preview['lines']);
        $result = $this->postJson("/finance/category-manager/{$this->source->id}/merge", ['target_id' => $this->target->id, 'token' => $preview['token']])->assertOk();
        $this->assertSame($this->target->id, (int) $line->fresh()->category_id);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'category_id' => $this->target->id, 'total' => '20.00']);
        $this->assertDatabaseHas('budget_months', ['category_id' => $this->target->id, 'budgeted' => '200.2468', 'activity' => '-40.0046', 'available' => '160.2422']);
        $this->assertDatabaseMissing('budget_months', ['category_id' => $this->source->id]);
        $this->assertSame('merged_category', $this->source->fresh()->resource_type);
        $this->assertSame([$this->target->id], $watchlist->fresh()->input);
        $this->assertSame($this->target->id, (int) $movement->fresh()->source_category_id);
        $this->postJson('/finance/category-manager/merges/'.$result->json('id').'/undo')->assertOk();
        $this->assertSame($this->source->id, (int) $line->fresh()->category_id);
        $this->assertSame('transactions', $this->source->fresh()->resource_type);
        $this->assertSame(2, BudgetMonth::whereIn('category_id', [$this->source->id, $this->target->id])->count());
        $this->assertSame([$this->source->id, $this->target->id], $watchlist->fresh()->input);
        $this->assertSame($this->source->id, (int) $movement->fresh()->source_category_id);
    }

    public function test_stale_preview_and_undo_after_new_changes_are_rejected(): void
    {
        $preview = $this->preview();
        $this->source->update(['name' => 'Updated name']);
        $this->postJson("/finance/category-manager/{$this->source->id}/merge", ['target_id' => $this->target->id, 'token' => $preview['token']])->assertUnprocessable();
        $result = $this->postJson("/finance/category-manager/{$this->source->id}/merge", ['target_id' => $this->target->id, 'token' => $this->preview()['token']])->assertOk();
        $this->target->update(['name' => 'New name']);
        $this->postJson('/finance/category-manager/merges/'.$result->json('id').'/undo')->assertUnprocessable();
        $this->assertSame('New name', $this->target->fresh()->name);
    }

    public function test_conflicting_goals_and_special_categories_are_protected(): void
    {
        foreach ([$this->source, $this->target] as $category) {
            BudgetTarget::create(['team_id' => $category->team_id, 'user_id' => $this->user->id, 'category_id' => $category->id, 'name' => 'Goal', 'amount' => 100, 'target_type' => 'spending']);
        }
        $preview = $this->preview();
        $this->assertNotEmpty($preview['blockers']);
        $this->postJson("/finance/category-manager/{$this->source->id}/merge", ['target_id' => $this->target->id, 'token' => $preview['token']])->assertUnprocessable();
        $this->assertSame(0, CategoryMerge::count());
        $this->source->forceFill(['account_id' => 1001])->save();
        $this->patchJson("/finance/category-manager/{$this->source->id}", ['archive' => true])->assertUnprocessable();
    }

    public function test_foreign_teams_and_invalid_groups_are_rejected(): void
    {
        $foreign = User::factory()->withPersonalTeam()->create();
        $category = Category::where('team_id', $foreign->ownedTeams()->first()->id)->firstOrFail();
        $this->postJson("/finance/category-manager/{$this->source->id}/preview", ['target_id' => $category->id])->assertForbidden();
        $this->patchJson("/finance/category-manager/{$category->id}", ['name' => 'No'])->assertForbidden();
        $this->patchJson("/finance/category-manager/{$this->source->id}", ['parent_id' => $this->target->id])->assertUnprocessable();
        $this->postJson("/finance/category-manager/{$this->source->id}/merge", ['target_id' => $this->target->id])->assertUnprocessable();
        $this->postJson('/finance/category-manager', ['name' => ''])->assertUnprocessable();
        auth()->guard('web')->logout();
        $this->app['auth']->forgetGuards();
        $this->get('/finance/category-manager')->assertRedirect('/login');
    }

    public function test_single_goal_follows_the_surviving_category(): void
    {
        $goal = BudgetTarget::create(['team_id' => $this->source->team_id, 'user_id' => $this->user->id, 'category_id' => $this->source->id, 'name' => 'Dining goal', 'amount' => 150, 'target_type' => 'spending']);
        $this->postJson("/finance/category-manager/{$this->source->id}/merge", ['target_id' => $this->target->id, 'token' => $this->preview()['token']])->assertOk();
        $this->assertSame($this->target->id, (int) $goal->fresh()->category_id);

    }

    public function test_conflicting_goals_require_a_choice_and_the_original_configuration_is_recoverable(): void
    {
        foreach ([$this->source, $this->target] as $category) {
            BudgetTarget::create(['team_id' => $category->team_id, 'user_id' => $this->user->id, 'category_id' => $category->id, 'name' => $category->name.' goal', 'amount' => $category->id, 'target_type' => 'spending']);
        }
        $sourceGoal = $this->source->fresh()->budget;
        $targetGoal = $this->target->fresh()->budget;
        $result = $this->postJson("/finance/category-manager/{$this->source->id}/merge", ['target_id' => $this->target->id, 'token' => $this->preview()['token'], 'goal' => 'source'])->assertOk();
        $this->assertSame($sourceGoal->id, $this->target->fresh()->budget->id);
        $merge = CategoryMerge::findOrFail($result->json('id'));
        $this->assertCount(2, $merge->snapshot['budget_targets']);
        $this->postJson('/finance/category-manager/merges/'.$merge->id.'/undo')->assertOk();
        $this->assertSame($sourceGoal->id, $this->source->fresh()->budget->id);
        $this->assertSame($targetGoal->id, $this->target->fresh()->budget->id);
    }
}
