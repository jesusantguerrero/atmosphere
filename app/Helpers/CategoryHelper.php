<?php

namespace App\Helpers;

use Illuminate\Support\Arr;
use Insane\Journal\Category;

class CategoryHelper
{
    public static function getSubcategories(string $teamId, array $displayIds, $depth = 0)
    {
        $categories = Category::where([
            'depth' => $depth,
        ])->whereIn('display_id', $displayIds)
        ->with([
            'subCategories',
            'subcategories.accounts' => function ($query) use ($teamId) {
                $query->where('team_id', '=', $teamId);
            }
        ])->get();

        return Arr::collapse(Arr::pluck($categories, 'subcategories'));
    }

    public static function getAccounts(string $teamId, array $displayIds, $depth = 1) {
        return Category::where([
            'depth' => $depth,
        ])
        ->whereIn('display_id', $displayIds)
        ->with([
            'accounts' => function ($query) use ($teamId) {
                $query->where('team_id', '=', $teamId);
            }
        ])->get();
    }
}
