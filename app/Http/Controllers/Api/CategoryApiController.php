<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryApiController extends Controller
{

    public function store(Request $request) {
        $session = [
            'user_id' => $request->user()->id,
            'team_id' => $request->user()->current_team_id,
        ];
        $category = Category::create(array_merge($session, [
            'name' => $request->post('name'),
            'display_id' => $request->post('display_id') ??  Str::slug($request->post('display_id')),
            'parent_id' => Category::findOrCreateByName($session, $request->post('parent_id')),
        ]));

        return $category;
    }

    public function bulkUpdate(Request $request) {
        $data = $request->post('data');
        Category::whereIn('id', array_keys($data))->chunkById(100, function($savedData) use ($data) {
            foreach ($savedData as $item) {
                $item->update($data[$item->id]);
            }
        });

        return response()->json(['success' => true]);
    }
}
