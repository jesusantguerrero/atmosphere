<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Maps an incoming payee name to a canonical payee (per team). Written when a
 * payee is merged, so future imports of the merged name resolve to the target
 * instead of re-creating the duplicate. See PayeeResolver.
 */
class PayeeAlias extends Model
{
    protected $fillable = ['team_id', 'name', 'payee_id'];
}
