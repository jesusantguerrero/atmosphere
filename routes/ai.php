<?php

use App\Http\Controllers\Mcp\McpOAuthMetadataController;
use App\Mcp\Servers\LogerServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController;

// Remote MCP server for Claude Code. Clients connect with OAuth (Passport):
// they register themselves, the user approves them on /oauth/authorize.
Mcp::web('/mcp', LogerServer::class)
    ->middleware(['auth:api', 'atmosphere.teamed', 'throttle:60,1'])
    ->name('mcp.loger');

// Same route names as Mcp::oauthRoutes() so its WWW-Authenticate header points here.
Route::get('/.well-known/oauth-protected-resource', [McpOAuthMetadataController::class, 'protectedResource'])
    ->name('mcp.oauth.protected-resource');
Route::get('/.well-known/oauth-protected-resource/{path}', [McpOAuthMetadataController::class, 'protectedResource'])
    ->where('path', '.*')
    ->name('mcp.oauth.protected-resource.nested');
Route::get('/.well-known/oauth-authorization-server', [McpOAuthMetadataController::class, 'authorizationServer'])
    ->name('mcp.oauth.authorization-server');
Route::get('/.well-known/oauth-authorization-server/{path}', [McpOAuthMetadataController::class, 'authorizationServer'])
    ->where('path', '.*')
    ->name('mcp.oauth.authorization-server.nested');
Route::post('/oauth/register', OAuthRegisterController::class)
    ->middleware('throttle:10,1')
    ->name('mcp.oauth.register');
