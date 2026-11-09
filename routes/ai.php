<?php

use App\Mcp\Servers\LogerServer;
use Laravel\Mcp\Facades\Mcp;

// Remote MCP server for Claude Code. Tokens come from `php artisan loger:mcp-token`.
Mcp::web('/mcp', LogerServer::class)
    ->middleware(['auth:sanctum', 'mcp.token', 'atmosphere.teamed', 'throttle:60,1'])
    ->name('mcp.loger');
