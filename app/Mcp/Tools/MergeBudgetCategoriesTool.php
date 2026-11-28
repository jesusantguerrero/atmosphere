<?php

namespace App\Mcp\Tools;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Services\CategoryMergeService;
use App\Mcp\Concerns\InteractsWithTeam;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Title('Merge budget categories')]
#[Name('merge_budget_categories')]
#[Description('Merge one budget category into another, like Category manager > Unify: every transaction, split line, monthly assignment and rule of the source moves to the target and the source disappears from the budget. Call it first without token to get the preview (what moves, the goals of each side, blockers, and the token); show it to the user and wait for their confirmation, then call again with the same ids and that token. When both categories have a goal the preview lists it as a blocker: ask the user which goal stays and send keep_goal (source or target) with the token. It can be undone from Category manager in Loger.')]
class MergeBudgetCategoriesTool extends Tool
{
    use InteractsWithTeam;

    public function handle(Request $request, CategoryMergeService $merges): Response
    {
        if ($denied = $this->denyWithoutWriteAbility($request)) {
            return $denied;
        }

        $validated = $request->validate([
            'source_category_id' => ['required', 'integer'],
            'target_category_id' => ['required', 'integer'],
            'token' => ['nullable', 'string', 'size:64'],
            'keep_goal' => ['nullable', 'in:source,target'],
        ], [
            'source_category_id.required' => 'Send source_category_id: the category that goes away.',
            'target_category_id.required' => 'Send target_category_id: the category that stays.',
        ]);

        $categories = Category::query()->where('team_id', $this->teamId($request))->whereIn('id', [$validated['source_category_id'], $validated['target_category_id']])->get()->keyBy('id');
        $source = $categories->get($validated['source_category_id']);
        $target = $categories->get($validated['target_category_id']);
        if (! $source || ! $target) {
            return Response::error('Both categories must be in this household. Call list_accounts_and_categories for the ids.');
        }

        if (empty($validated['token'])) {
            return Response::json(['preview' => true, ...$merges->preview($source, $target)]);
        }

        try {
            $merge = $merges->merge($source, $target, $request->user()->id, $validated['token'], $validated['keep_goal'] ?? null);
        } catch (ValidationException $exception) {
            return Response::error(implode(' ', $exception->validator->errors()->all()));
        }

        return Response::json([
            'merged' => true,
            'merge_id' => $merge->id,
            'source' => $merge->source_name,
            'target' => $merge->target_name,
            'target_category_id' => $target->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'source_category_id' => $schema->integer()->description('The category that goes away, from list_accounts_and_categories.')->required(),
            'target_category_id' => $schema->integer()->description('The category that stays and receives everything.')->required(),
            'token' => $schema->string()->description('The token from the preview. Omit it to get the preview.'),
            'keep_goal' => $schema->string()->enum(['source', 'target'])->description('When both have a goal, which one stays. The other goal is deleted.'),
        ];
    }
}
