<?php

namespace App\Domains\Integration\Services;

use App\Domains\Integration\Models\Integration;
use Exception;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Oauth2;
use Illuminate\Support\Facades\Log;

class GoogleService
{
    public static function getConfigPath()
    {
        return config('app.env') === 'local'
        ? base_path(config('integrations.google.credentials_path'))
        : config('integrations.google.credentials_path');
    }

    public static function setTokens($data, $user, $integrationId = null)
    {
        if (! $integrationId && $_GET['code']) {
            $client = new GoogleClient(['client_id' => config('integrations.google.client_id')]);
            $client->setApplicationName(config('app.name'));
            $client->setAuthConfig(self::getConfigPath());
            // Must match the redirect_uri used to build the auth URL in
            // requestAccessToken(); Google rejects the code exchange otherwise,
            // leaving the client tokenless and making the userinfo call below
            // fail with a confusing 401 "missing authentication credential".
            $client->setRedirectUri(config('app.url').'/services/accept-oauth');
            $client->setAccessType('offline');
            $userIdToken = $_GET['code'];
            $tokenResponse = $client->fetchAccessTokenWithAuthCode($userIdToken);

            if (! is_array($tokenResponse) || isset($tokenResponse['error'])) {
                $reason = is_array($tokenResponse)
                    ? ($tokenResponse['error_description'] ?? $tokenResponse['error'] ?? 'unknown error')
                    : 'no token returned';
                throw new Exception('Google rejected the authorization ('.$reason.'). Check that the redirect URI matches the one registered in Google Cloud.');
            }
            // Carry the freshly obtained token so the userinfo lookup is authed.
            $client->setAccessToken($tokenResponse);

            $integration = Integration::where([
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
                'name' => 'Google',
            ])->first();

            $oauth2 = new Oauth2($client);
            $userInfo = $oauth2->userinfo->get();

            if ($userInfo->email == $user->email) {
                $integration->token = json_encode($tokenResponse);
                // Incremental re-consent (e.g. adding the Calendar scope) often
                // returns no new refresh_token — keep the existing one instead of
                // overwriting it with nothing.
                if (! empty($tokenResponse['refresh_token'])) {
                    $integration->meta_data = json_encode($tokenResponse['refresh_token']);
                }
                $integration->save();
                session(['g_token', json_encode($tokenResponse)]);

                return;
            }
            throw new Exception('Error obtaining the token'.$userInfo->email);
        } elseif ($integrationId) {
            $integration = Integration::find($integrationId);
            $integration->token = json_encode($data->access_token);
            session(['g_token', json_encode($data->access_token)]);

            return;
        }
    }

    public static function getClient($integrationId)
    {
        $integration = Integration::find($integrationId);
        $client = new GoogleClient;
        $client->setAuthConfig(self::getConfigPath());

        if (! $accessToken = session('g_token') && $integration->token) {
            $accessToken = $integration->token;
        }

        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            if ($refreshToken = $integration->meta_data) {
                $tokenResponse = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                self::setTokens((object) [
                    'access_token' => $tokenResponse,
                    'refresh_token' => $refreshToken,
                ],
                    $integration->user,
                    $integrationId);
            } else {
                throw new Exception('Need authorize again');
            }
        }

        return $client;
    }

    public static function storeIntegration($data, $user)
    {
        Integration::updateOrCreate([
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
            'name' => $data->service_name,
            'automation_service_id' => $data->service_id,
        ], [
            'hash' => $user->email,
        ]);
    }

    /**
     * Best-effort revoke of the OAuth token on Google's side. Returns true if
     * Google confirmed the revoke, false otherwise. Failures are swallowed
     * because the user-facing disconnect should still proceed even if the
     * remote token was already invalid or unreachable.
     */
    public static function revokeToken(Integration $integration): bool
    {
        if (! $integration->token) {
            return false;
        }

        try {
            $tokenData = json_decode($integration->token, true);
            $accessToken = is_array($tokenData) ? ($tokenData['access_token'] ?? null) : null;

            if (! $accessToken) {
                return false;
            }

            $client = new GoogleClient;
            $client->setAuthConfig(self::getConfigPath());

            return (bool) $client->revokeToken($accessToken);
        } catch (Exception $e) {
            Log::warning('Failed to revoke Google token: '.$e->getMessage(), [
                'integration_id' => $integration->id,
            ]);

            return false;
        }
    }

    public static function requestAccessToken($data, $user)
    {
        $client = new GoogleClient([
            'client_id' => config('integrations.google.client_id'),
        ]);
        $client->addScope([
            Gmail::GMAIL_READONLY,
            'https://www.googleapis.com/auth/calendar.readonly',
            'https://www.googleapis.com/auth/userinfo.profile',
            'https://www.googleapis.com/auth/userinfo.email',
        ]);
        $client->setRedirectUri(config('app.url').'/services/accept-oauth');
        $client->setAccessType('offline');
        $client->setLoginHint($user->email);
        $client->setApprovalPrompt('auto');
        $client->setIncludeGrantedScopes(true);

        $authUrl = $client->createAuthUrl();
        if ($authUrl) {
            self::storeIntegration($data, $user);
        }

        return $authUrl;
    }
}
