<?php

namespace App\Domains\Integration\Services;

use Exception;
use Google\Service\Gmail;
use Google\Service\Oauth2;
use Google\Client as GoogleClient;
use App\Domains\Integration\Models\Integration;

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
            $client->setAccessType('offline');
            $userIdToken = $_GET['code'];
            $tokenResponse = $client->fetchAccessTokenWithAuthCode($userIdToken);

            $integration = Integration::where([
                'user_id' => $user->id,
                'team_id' => $user->current_team_id,
                'name' => 'Google',
            ])->first();

            $oauth2 = new Oauth2($client);
            $userInfo = $oauth2->userinfo->get();

            if ($userInfo->email == $user->email) {
                $integration->token = json_encode($tokenResponse);
                if ($tokenResponse['refresh_token']) {
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
        $client = new GoogleClient();
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

    public static function requestAccessToken($data, $user)
    {
        $client = new GoogleClient([
            'client_id' => config('integrations.google.client_id'),
        ]);
        $client->addScope([
            Gmail::GMAIL_READONLY,
            "https://www.googleapis.com/auth/userinfo.profile",
            "https://www.googleapis.com/auth/userinfo.email"
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
