<?php

namespace App\Domains\Transaction\Services;

use App\Models\PayeeAlias;
use Insane\Journal\Models\Core\Payee;

/**
 * Resolve a payee by name, honoring team aliases first. If the name was merged
 * away (an alias exists pointing at a canonical payee), return that payee;
 * otherwise fall back to the package's find-or-create. This is what makes a
 * merge stick across recurring imports instead of re-spawning the duplicate.
 */
class PayeeResolver
{
    /**
     * @param  array|object  $session  carries team_id/user_id (array or a model like Automation)
     */
    public static function resolve($session, string $name): Payee
    {
        $teamId = is_array($session)
            ? ($session['team_id'] ?? null)
            : ($session->team_id ?? null);

        if ($teamId) {
            $alias = PayeeAlias::where('team_id', $teamId)->where('name', $name)->first();
            if ($alias) {
                $payee = Payee::where('team_id', $teamId)->find($alias->payee_id);
                if ($payee) {
                    return $payee;
                }
                // Target was deleted — drop the dangling alias and fall through.
                $alias->delete();
            }
        }

        return Payee::findOrCreateByName($session, $name);
    }
}
