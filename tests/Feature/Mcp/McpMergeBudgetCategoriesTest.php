<?php

namespace Tests\Feature\Mcp;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Models\CategoryMerge;
use App\Domains\Budget\Services\CategoryMergeService;
use App\Domains\Transaction\Models\Transaction;
use App\Mcp\Servers\LogerServer;
use App\Mcp\Tools\MergeBudgetCategoriesTool;
use App\Models\User;
use App\Providers\PassportServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\AccessToken;
use Tests\TestCase;

/**
 * Folding a duplicated category into the one that stays, from Claude, with a preview first.
 */
class McpMergeBudgetCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $source;

    private Category $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->user->forceFill(['current_team_id' => $this->user->ownedTeams()->first()->id])->save();
        $this->user = $this->user->fresh();
        $group = Category::create(['team_id' => $this->user->current_team_id, 'user_id' => $this->user->id, 'name' => 'Savings', 'resource_type' => 'transactions']);
        $this->source = $this->category($group, 'Home office design original');
        $this->target = $this->category($group, 'Home Office Design');
    }

    public function test_the_preview_changes_nothing_and_the_token_merges_keeping_the_chosen_goal(): void
    {
        $desk = $this->expense($this->source, 'Mesa de trabajo');
        $this->goal($this->source, 50000);
        $kept = $this->goal($this->target, 50000);

        $this->server()->tool(MergeBudgetCategoriesTool::class, $this->ids())->assertOk()
            ->assertSee(['"preview":true', '"source":"Home office design original"', '"target":"Home Office Design"', '"transactions":1', '"side":"source"', '"side":"target"']);
        $token = app(CategoryMergeService::class)->preview($this->source, $this->target)['token'];
        $this->assertSame($this->source->id, $desk->fresh()->category_id);

        $this->server()->tool(MergeBudgetCategoriesTool::class, [...$this->ids(), 'token' => $token, 'keep_goal' => 'target'])->assertOk()
            ->assertSee(['"merged":true', '"target_category_id":'.$this->target->id]);

        $this->assertSame($this->target->id, $desk->fresh()->category_id);
        $this->assertSame('merged_category', $this->source->fresh()->resource_type);
        $this->assertSame([$kept->id], BudgetTarget::query()->whereIn('category_id', [$this->source->id, $this->target->id])->pluck('id')->all());
        $this->assertSame(1, CategoryMerge::query()->where('source_id', $this->source->id)->count());
    }

    public function test_a_stale_token_another_household_or_a_read_only_token_merge_nothing(): void
    {
        $this->server()->tool(MergeBudgetCategoriesTool::class, [...$this->ids(), 'token' => str_repeat('a', 64)])->assertHasErrors(['cambiaron']);

        $other = User::factory()->withPersonalTeam()->create();
        $foreign = $this->category(Category::create(['team_id' => $other->ownedTeams()->first()->id, 'user_id' => $other->id, 'name' => 'Ajeno', 'resource_type' => 'transactions']), 'Ajena');
        $this->server()->tool(MergeBudgetCategoriesTool::class, ['source_category_id' => $foreign->id, 'target_category_id' => $this->target->id])->assertHasErrors(['this household']);

        LogerServer::actingAs($this->user->withAccessToken(new AccessToken(['oauth_user_id' => $this->user->id, 'oauth_scopes' => [PassportServiceProvider::READ_SCOPE]])))
            ->tool(MergeBudgetCategoriesTool::class, $this->ids())->assertHasErrors(['read-only']);

        $this->assertSame('transactions', $this->source->fresh()->resource_type);
        $this->assertSame('transactions', $foreign->fresh()->resource_type);
        $this->assertSame(0, CategoryMerge::query()->count());
    }

    private function server(): mixed
    {
        return LogerServer::actingAs($this->user->withAccessToken(new AccessToken(['oauth_user_id' => $this->user->id, 'oauth_scopes' => [PassportServiceProvider::READ_SCOPE, PassportServiceProvider::WRITE_SCOPE]])));
    }

    /** @return array{source_category_id: int, target_category_id: int} */
    private function ids(): array
    {
        return ['source_category_id' => $this->source->id, 'target_category_id' => $this->target->id];
    }

    private function category(Category $group, string $name): Category
    {
        return Category::create(['team_id' => $group->team_id, 'user_id' => $group->user_id, 'parent_id' => $group->id, 'resource_type' => 'transactions', 'name' => $name]);
    }

    private function expense(Category $category, string $description): Transaction
    {
        $transaction = (new Transaction)->forceFill(['team_id' => $category->team_id, 'user_id' => $this->user->id, 'category_id' => $category->id, 'account_id' => 1001, 'currency_code' => 'DOP', 'date' => '2026-01-14', 'description' => $description, 'direction' => Transaction::DIRECTION_CREDIT, 'status' => Transaction::STATUS_VERIFIED, 'total' => '8200']);
        $transaction->saveQuietly();

        return $transaction;
    }

    private function goal(Category $category, float $amount): BudgetTarget
    {
        return BudgetTarget::create(['team_id' => $category->team_id, 'user_id' => $this->user->id, 'category_id' => $category->id, 'name' => $category->name, 'target_type' => 'saving_balance', 'amount' => $amount, 'frequency' => 'MONTHLY']);
    }
}
