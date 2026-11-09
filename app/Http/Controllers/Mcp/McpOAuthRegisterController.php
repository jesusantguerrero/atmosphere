<?php

namespace App\Http\Controllers\Mcp;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController;

/**
 * Dynamic client registration (RFC 7591) from laravel/mcp, answering with
 * Loger's scopes instead of the package's single `mcp:use`, so Claude Code
 * asks for read and write access on the consent screen.
 */
class McpOAuthRegisterController extends OAuthRegisterController
{
    public function __invoke(Request $request): JsonResponse
    {
        $response = parent::__invoke($request);

        if ($response->getStatusCode() === 201) {
            $response->setData([
                ...$response->getData(true),
                'scope' => implode(' ', McpOAuthMetadataController::SCOPES),
            ]);
        }

        return $response;
    }
}
