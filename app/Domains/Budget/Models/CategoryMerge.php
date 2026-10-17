<?php

namespace App\Domains\Budget\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryMerge extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'references' => 'array', 'undone_at' => 'datetime'];
    }
}
