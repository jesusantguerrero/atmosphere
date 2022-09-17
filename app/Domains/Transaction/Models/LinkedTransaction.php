<?php

namespace App\Domains\Transaction\Models;

use Illuminate\Database\Eloquent\Model;

class LinkedTransaction extends Model
{
    protected $fillable = ['team_id', 'user_id', 'transaction_id', 'linked_transaction_id'];
}
