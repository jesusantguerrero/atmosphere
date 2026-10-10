<?php

namespace App\Http\Controllers\Api;

use App\Domains\AppCore\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CategoryApiController extends Controller
{
    /**
     * Fields a bulk update may change; ownership columns are never taken from the request.
     */
    private const BULK_UPDATABLE = ['name', 'display_id', 'description', 'index', 'color', 'icon', 'parent_id', 'hidden', 'resource_type_id'];

    /**
     * Team transaction categories as top-level groups with their
     * subCategories eager-loaded -- the same shape HandleInertiaRequests
     * ships to the web app, so the mobile category picker matches it.
     */
    public function index(Request $request)
    {
        return Category::where([
            'categories.team_id' => $request->user()->current_team_id,
            'categories.resource_type' => 'transactions',
        ])
            ->whereNull('parent_id')
            ->orderBy('index')
            ->with('subCategories')
            ->get();
    }

    public function store(Request $request)
    {
        $session = [
            'user_id' => $request->user()->id,
            'team_id' => $request->user()->current_team_id,
        ];
        $category = Category::create(array_merge($session, [
            'name' => $request->post('name'),
            'display_id' => $request->post('display_id') ?? Str::slug($request->post('display_id')),
            'parent_id' => Category::findOrCreateByName($session, $request->post('parent_id')),
        ]));

        return $category;
    }

    public function bulkUpdate(Request $request)
    {
        $data = $request->post('data') ?? [];
        $teamId = $request->user()->current_team_id;

        Category::where('team_id', $teamId)->whereIn('id', array_keys($data))->chunkById(100, function ($savedData) use ($data) {
            foreach ($savedData as $item) {
                $item->update(Arr::only($data[$item->id], self::BULK_UPDATABLE));
            }
        });

        return response()->json(['success' => true]);
    }
}
