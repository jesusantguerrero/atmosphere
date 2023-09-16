<?php

namespace App\Domains\Journal\Actions;

use Insane\Journal\Models\Core\Category;

class TransactionCategoriesCreate
{
    public function create($team)
    {
        $generalInfo = [
            'team_id' => $team->id,
            'user_id' => $team->user_id,
            "depth" => 0
        ];


        Category::where([
            'team_id' => $team->id,
            'resource_type' => 'transactions'
        ])->delete();

        $categories = config('journal.categories');
        Category::saveBulk($categories, $generalInfo);

    }
}
