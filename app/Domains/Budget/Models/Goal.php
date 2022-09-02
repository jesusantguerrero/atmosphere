<?php

namespace App\Domains\Budget\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id', 'due_date', 'name', 'target', 'amount'];
}
