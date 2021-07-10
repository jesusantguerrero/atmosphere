<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Insane\Journal\Account;

class Budget extends Model
{
    use HasFactory;
    protected $fillable = ['team_id', 'user_id','account_id', 'amount'];

    public function account() {
        return $this->belongsTo(Account::class);
    }
}
