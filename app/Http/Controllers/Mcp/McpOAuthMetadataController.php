<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Providers\PassportServiceProvider;
use Illuminate\Http\JsonResponse;

/**
 * OAuth discovery documents for the MCP server (RFC 9728 and RFC 8414). They
 * mirror laravel/mcp's Mcp::oauthRoutes(), which only advertises its single
 * `mcp:use` scope; Loger advertises read and write so a token can be read-only.
 */
class McpOAuthMetadataController extends Controller
{
    /**
     * @var list<string>
     */
    public const SCOPES = [PassportServiceProvider::READ_SCOPE, PassportServiceProvider::WRITE_SCOPE];

    public function protectedResource(?string $path = null): JsonResponse
    {
        return response()->json([
            'resource' => url('/'.($path ?? '')),
            'authorization_servers' => [url('/')],
            'scopes_supported' => self::SCOPES,
        ]);
    }

    public function authorizationServer(): JsonResponse
    {
        return response()->json([
            'issuer' => url('/'),
            'authorization_endpoint' => route('passport.authorizations.authorize'),
            'token_endpoint' => route('passport.token'),
            'registration_endpoint' => route('mcp.oauth.register'),
            'response_types_supported' => ['code'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => self::SCOPES,
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['none'],
        ]);
    }
}
