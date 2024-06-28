<?php

namespace App\Domains\Budget\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\AppCore\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'source_category_id',
        'destination_category_id',
        'amount',
        'date',
    ];

    public function source()
    {
        return $this->belongsTo(Category::class, 'source_category_id');
    }

    public function destination()
    {
        return $this->belongsTo(Category::class, 'destination_category_id');
    }

    protected static function booted()
    {
        static::saving(function ($movement) {
            $movement->destination_category_name = $movement->destination->name;
            $movement->source_category_name = $movement->source->name;
        });
    }
}
