<?php

namespace App\Domains\AppCore\Models;

use Freesgen\Atmosphere\Models\Label as FreesgenLabel;

class Label extends FreesgenLabel
{
    /**
     * Loger-specific additions to the parent fillable list.
     * Parent has: user_id, team_id, name, label, color.
     * We add polymorphic grouping fields used by the Plan / shopping
     * pipeline (Modules\Plan\Entities\Plan::setUp passes both keys
     * via mass-assignment).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'label',
        'color',
        'labelable_group_id',
        'labelable_group_type',
    ];

    public function products()
    {
        return $this->morphedByMany(Product::class, 'labelable');
    }

    public function transactions()
    {
        return $this->morphedByMany(Transaction::class, 'labelable');
    }
}
