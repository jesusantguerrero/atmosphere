<?php

namespace App\Models;

use Insane\Journal\Models\Core\Category as CoreCategory;

class Category extends CoreCategory
{
    protected $with = ['budget'];

    public function budget() {
        return $this->hasOne(Budget::class);
    }

    public function subCategories() {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('index');
    }
}
