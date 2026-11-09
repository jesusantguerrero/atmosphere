<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * The MCP server only accepts tokens created for it (`php artisan loger:mcp-token`).
 * Mobile and API tokens carry the `*` ability, so they are rejected on purpose:
 * revoking the Claude token never signs the phone out, and the reverse holds too.
 */
class EnsureMcpToken
{
    public const ABILITY = 'mcp';

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        abort_unless($token instanceof PersonalAccessToken && in_array(self::ABILITY, $token->abilities ?? [], true), 403, 'This token cannot use the Loger MCP server.');

        return $next($request);
    }
}
