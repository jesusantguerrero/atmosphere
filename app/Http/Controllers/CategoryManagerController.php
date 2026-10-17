<?php

namespace App\Http\Controllers;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\CategoryMerge;
use App\Domains\Budget\Services\CategoryMergeService;
use App\Http\Requests\ManageCategoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class CategoryManagerController extends Controller
{
    private function check(Category $category, Request $request): void
    {
        abort_unless((int) $category->team_id === (int) $request->user()->current_team_id, 403);
    }

    public function index(Request $request, CategoryMergeService $service): Response
    {
        $categories = Category::query()->where('team_id', $request->user()->current_team_id)
            ->whereIn('resource_type', ['transactions', 'archived_group'])->orderBy('index')->orderBy('name')->get();

        return inertia('Finance/CategoryManager', [
            'managedCategories' => $categories->map(fn ($category) => [
                'id' => $category->id, 'name' => $category->name,
                'parent_id' => $category->parent_id, 'display_id' => $category->display_id,
                'archived' => $category->resource_type === 'archived_group',
                'protected' => $category->parent_id ? $service->protected($category) : in_array($category->display_id, ['ready_to_assign', 'inflow', 'credit_card_payments', 'hidden_categories']),
                'target' => $category->budget?->name,
            ]),
            'merges' => CategoryMerge::query()->where('team_id', $request->user()->current_team_id)
                ->latest()->limit(20)->get()->map(fn ($merge) => [
                    'id' => $merge->id, 'source_name' => $merge->source_name, 'target_name' => $merge->target_name,
                    'created_at' => $merge->created_at, 'undone_at' => $merge->undone_at,
                    'goals' => collect($merge->snapshot['budget_targets'] ?? [])->map(fn ($row) => ['name' => $row['name'], 'amount' => $row['amount'], 'type' => $row['target_type']])->values(),
                ]),
        ]);
    }

    public function store(ManageCategoryRequest $request): JsonResponse
    {
        $parent = $request->input('parent_id') ? Category::findOrFail($request->integer('parent_id')) : null;
        if ($parent) {
            $this->check($parent, $request);
            abort_unless($parent->resource_type === 'transactions' && ! $parent->parent_id, 422);
        }
        $category = Category::create([
            'team_id' => $request->user()->current_team_id, 'user_id' => $request->user()->id,
            'name' => trim($request->string('name')->toString()), 'parent_id' => $parent?->id,
            'depth' => $parent ? 1 : 0, 'resource_type' => 'transactions',
            'index' => Category::where('team_id', $request->user()->current_team_id)->where('parent_id', $parent?->id)->max('index') + 1,
        ]);

        return response()->json(['id' => $category->id], 201);
    }

    public function update(ManageCategoryRequest $request, Category $category, CategoryMergeService $service): JsonResponse
    {
        $this->check($category, $request);
        abort_unless(in_array($category->resource_type, ['transactions', 'archived_group']), 422);
        abort_if($category->parent_id ? $service->protected($category) : in_array($category->display_id, ['ready_to_assign', 'inflow', 'credit_card_payments', 'hidden_categories']), 422, 'Esta categoría está protegida.');
        $values = [];
        if ($category->resource_type === 'archived_group') {
            abort_unless($request->has('archive') && ! $request->boolean('archive'), 422);
            $category->update(['resource_type' => 'transactions']);

            return response()->json(['saved' => true]);
        }
        if ($request->has('name')) {
            $values['name'] = trim($request->string('name')->toString());
        }
        if ($request->boolean('archive')) {
            if (! $category->parent_id) {
                abort_if($category->subCategories()->exists(), 422, 'Mueve primero las categorías a otro grupo.');
                abort_if($category->transactionLines()->exists() || $category->getConnection()->table('budget_months')->where('category_id', $category->id)->exists(), 422, 'Este grupo tiene historial financiero directo y requiere revisión.');
                $category->update(['resource_type' => 'archived_group']);

                return response()->json(['saved' => true]);
            }
            $hidden = Category::firstOrCreate([
                'team_id' => $category->team_id, 'display_id' => 'hidden_categories', 'resource_type' => 'transactions',
            ], ['name' => 'Hidden Categories', 'user_id' => $request->user()->id, 'index' => 999]);
            $values['parent_id'] = $hidden->id;
        } elseif ($request->has('parent_id')) {
            abort_unless($category->parent_id && $request->input('parent_id'), 422);
            $group = Category::findOrFail($request->integer('parent_id'));
            $this->check($group, $request);
            abort_unless(! $group->parent_id && $group->resource_type === 'transactions' && ! in_array($group->display_id, ['ready_to_assign', 'inflow', 'credit_card_payments']), 422);
            $values['parent_id'] = $group->id;
            $values['index'] = $group->subCategories()->max('index') + 1;
        }
        $category->update($values);

        return response()->json(['saved' => true]);
    }

    public function preview(ManageCategoryRequest $request, Category $category, CategoryMergeService $service): JsonResponse
    {
        $target = Category::findOrFail($request->integer('target_id'));
        $this->check($category, $request);
        $this->check($target, $request);

        return response()->json($service->preview($category, $target));
    }

    public function merge(ManageCategoryRequest $request, Category $category, CategoryMergeService $service): JsonResponse
    {
        $target = Category::findOrFail($request->integer('target_id'));
        $this->check($category, $request);
        $this->check($target, $request);
        $merge = $service->merge($category, $target, $request->user()->id, $request->string('token')->toString(), $request->input('goal'));

        return response()->json(['id' => $merge->id]);
    }

    public function undo(Request $request, CategoryMerge $merge, CategoryMergeService $service): JsonResponse
    {
        abort_unless((int) $merge->team_id === (int) $request->user()->current_team_id, 403);
        $service->undo($merge);

        return response()->json(['saved' => true]);
    }
}
