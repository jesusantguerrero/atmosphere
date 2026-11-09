<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Token;

/**
 * Apps the user authorized through OAuth (Claude Code via MCP), shown on Settings → Security.
 */
class ConnectedAppController extends Controller
{
    /**
     * @return list<array{client_id: string, name: string, scopes: list<string>, connected_at: string|null, expires_at: string|null}>
     */
    public static function forUser(User $user): array
    {
        return $user->oauthTokens()
            ->with('client:id,name')
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->get()
            ->groupBy('client_id')
            ->map(fn ($tokens) => [
                'client_id' => (string) $tokens->first()->client_id,
                'name' => $tokens->first()->client?->name ?? 'App',
                'scopes' => $tokens->pluck('scopes')->flatten()->unique()->values()->all(),
                'connected_at' => $tokens->min('created_at')?->toIso8601String(),
                'expires_at' => $tokens->max('expires_at')?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * Revoke every token (and its refresh token) the user granted to one app.
     */
    public function destroy(Request $request, string $client): RedirectResponse
    {
        $tokens = $request->user()->oauthTokens()
            ->where('client_id', $client)
            ->where('revoked', false)
            ->get();

        abort_if($tokens->isEmpty(), 404);

        $tokens->each(function (Token $token) {
            $token->revoke();
            $token->refreshToken?->revoke();
        });

        return back(303);
    }
}
