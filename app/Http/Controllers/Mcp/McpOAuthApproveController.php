<?php

namespace App\Http\Controllers\Mcp;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Approves the consent screen like Passport does, but shows Loger's own
 * "connected" page before handing the code to the app's localhost callback,
 * so the last thing the user sees from Loger is a confirmation.
 */
class McpOAuthApproveController extends ApproveAuthorizationController
{
    public function approve(Request $request, ResponseInterface $psrResponse): Response
    {
        $authRequest = $this->getAuthRequestFromSession($request);
        $authRequest->setAuthorizationApproved(true);

        $response = $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, $psrResponse)
        ), $authRequest->getGrantTypeId() === 'implicit');

        $redirectUrl = $response->headers->get('Location');

        if (! $response->isRedirection() || ! $redirectUrl || str_contains($redirectUrl, 'error=')) {
            return $response;
        }

        return Inertia::render('Auth/OAuthConnected', [
            'client' => ['name' => $authRequest->getClient()->getName()],
            'redirectUrl' => $redirectUrl,
        ])->toResponse($request);
    }
}
