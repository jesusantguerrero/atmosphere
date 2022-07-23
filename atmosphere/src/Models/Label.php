<?php

namespace Freesgen\Atmosphere\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'team_id', 'name', 'label', 'color'];
}
