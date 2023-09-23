<?php

namespace App\Domains\AppCore\Models;

use Freesgen\Atmosphere\Models\Label as FreesgenLabel;

class Label extends FreesgenLabel
{
    public function products()
    {
        return $this->morphedByMany(Product::class, 'labelable');
    }

    public function transactions()
    {
        return $this->morphedByMany(Transaction::class, 'labelable');
    }
}
