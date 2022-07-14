<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetMovement extends Model
{
    use HasFactory;


    public static function registerMovement($formData) {
        return self::create($formData);
    }
}
