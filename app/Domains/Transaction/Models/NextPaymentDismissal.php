<?php

namespace App\Domains\Transaction\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A user-dismissed next-payment for a given month ("ya lo pagué en otra cuenta").
 * NextPaymentsService filters these out when building the list.
 */
class NextPaymentDismissal extends Model
{
    protected $fillable = ['team_id', 'user_id', 'payment_key', 'period'];
}
