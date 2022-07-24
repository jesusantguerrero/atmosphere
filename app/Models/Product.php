<?php

namespace App\Models;

use Freesgen\Atmosphere\Models\Label;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Insane\Journal\Models\Product\Product as ProductProduct;

class Product extends ProductProduct
{
    protected $with = ['labels'];
    public function labels(): MorphToMany {
        return $this->morphToMany(Label::class, 'labelable');
    }
}
