<?php

namespace App\Mcp\Tools;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Services\BudgetCategoryService;
use App\Mcp\Concerns\InteractsWithTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Title('Create budget category')]
#[Name('create_budget_category')]
#[Description('Add a budget category to a group of the household, placed last in that group, e.g. "Manutención" in the group Diana. Find group_id with list_accounts_and_categories. Before calling, confirm the name and the group with the user. Refuses a name that already exists in that group and returns its id instead, so the existing one can be used. Returns the new category id, ready for create_transaction or update_transaction.')]
class CreateBudgetCategoryTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request, BudgetCategoryService $categories): Response
    {
        if ($denied = $this->denyWithoutWriteAbility($request)) {
            return $denied;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'group_id' => ['required', 'integer'],
        ], [
            'name.required' => 'Send the name of the new category.',
            'group_id.required' => 'Send group_id: the group from list_accounts_and_categories.',
        ]);

        $group = Category::query()
            ->where('team_id', $this->teamId($request))
            ->where('resource_type', 'transactions')
            ->whereNull('parent_id')
            ->find($validated['group_id']);
        if (! $group) {
            return Response::error('That group is not in this household. Call list_accounts_and_categories for the group ids.');
        }

        $name = trim($validated['name']);
        $existing = $group->subCategories()->where('name', $name)->first();
        if ($existing) {
            return Response::error("{$group->name} already has {$existing->name} (id {$existing->id}); use that one.");
        }

        $category = $categories->create($request->user(), $name, $group);

        return Response::json([
            'id' => $category->id,
            'name' => $category->name,
            'group_id' => $group->id,
            'group' => $group->name,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Name of the new category.')->required(),
            'group_id' => $schema->integer()->description('Group id from list_accounts_and_categories.')->required(),
        ];
    }
}
