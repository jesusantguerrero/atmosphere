<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Insane\Journal\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{

    public function store(Request $request, Response $response) {
        $category = Category::create([
            'user_id' => $request->user()->id,
            'team_id' => $request->user()->current_team_id,
            'name' => $request->post('name'),
            'display_id' => $request->post('display_id') ??  Str::slug($request->post('display_id')),
            'parent_id' => Category::findOrCreateByName($request->post('parent_id')),
        ]);

        return $category;
    }

}
