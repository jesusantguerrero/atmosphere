<?php

namespace Tests\Feature\Mcp;

use App\Domains\AppCore\Models\Category;
use App\Mcp\Servers\LogerServer;
use App\Mcp\Tools\CreateBudgetCategoryTool;
use App\Mcp\Tools\ListAccountsAndCategoriesTool;
use App\Models\Team;
use App\Models\User;
use App\Providers\PassportServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\AccessToken;
use Tests\TestCase;

/**
 * Adding a budget category from Claude, e.g. splitting Diana's spending out of Groceries.
 */
class McpCreateBudgetCategoryTest extends TestCase
{
    use RefreshDatabase;

    private const WRITE_SCOPES = [PassportServiceProvider::READ_SCOPE, PassportServiceProvider::WRITE_SCOPE];

    private User $user;

    private Team $team;

    private Category $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->team = $this->user->ownedTeams()->first();
        $this->user->forceFill(['current_team_id' => $this->team->id])->save();
        $this->user = $this->user->fresh();
        $this->group = Category::create(['name' => 'Diana', 'team_id' => $this->team->id, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
        Category::create(['name' => 'Baby Diana', 'team_id' => $this->team->id, 'resource_type' => 'transactions', 'parent_id' => $this->group->id, 'depth' => 1, 'index' => 3]);
    }

    public function test_it_creates_the_category_last_in_its_group_and_lists_the_group_id(): void
    {
        $this->server()->tool(ListAccountsAndCategoriesTool::class)->assertOk()
            ->assertSee(['"name":"Baby Diana"', '"group":"Diana"', '"group_id":'.$this->group->id]);

        $this->server()->tool(CreateBudgetCategoryTool::class, ['name' => '  Manutención ', 'group_id' => $this->group->id])->assertOk()
            ->assertSee(['"name":"Manutenci', '"group_id":'.$this->group->id, '"group":"Diana"']);

        $category = Category::where('name', 'Manutención')->sole();
        $this->assertSame($this->group->id, (int) $category->parent_id);
        $this->assertSame($this->team->id, (int) $category->team_id);
        $this->assertSame($this->user->id, (int) $category->user_id);
        $this->assertSame('transactions', $category->resource_type);
        $this->assertSame(1, (int) $category->depth);
        $this->assertSame(4, (int) $category->index);
    }

    public function test_it_refuses_duplicates_other_households_categories_as_groups_and_read_only_connections(): void
    {
        $other = User::factory()->withPersonalTeam()->create();
        $foreignGroup = Category::create(['name' => 'Otra', 'team_id' => $other->ownedTeams()->first()->id, 'resource_type' => 'transactions', 'parent_id' => null, 'depth' => 0, 'index' => 0]);
        $babyDiana = Category::where('name', 'Baby Diana')->sole();

        $server = $this->server();
        $server->tool(CreateBudgetCategoryTool::class, ['group_id' => $this->group->id])->assertHasErrors();
        $server->tool(CreateBudgetCategoryTool::class, ['name' => 'Ropa'])->assertHasErrors();
        $server->tool(CreateBudgetCategoryTool::class, ['name' => 'Baby Diana', 'group_id' => $this->group->id])->assertHasErrors(["id {$babyDiana->id}"]);
        $server->tool(CreateBudgetCategoryTool::class, ['name' => 'Ropa', 'group_id' => $foreignGroup->id])->assertHasErrors(['not in this household']);
        $server->tool(CreateBudgetCategoryTool::class, ['name' => 'Ropa', 'group_id' => $babyDiana->id])->assertHasErrors(['not in this household']);
        LogerServer::actingAs($this->user->withAccessToken(new AccessToken(['oauth_user_id' => $this->user->id, 'oauth_scopes' => [PassportServiceProvider::READ_SCOPE]])))
            ->tool(CreateBudgetCategoryTool::class, ['name' => 'Ropa', 'group_id' => $this->group->id])->assertHasErrors(['read-only']);

        $this->assertSame(0, Category::where('name', 'Ropa')->count());
        $this->assertSame(0, $foreignGroup->subCategories()->count());
    }

    private function server(): mixed
    {
        return LogerServer::actingAs($this->user->withAccessToken(new AccessToken(['oauth_user_id' => $this->user->id, 'oauth_scopes' => self::WRITE_SCOPES])));
    }
}
