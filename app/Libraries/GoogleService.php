<?php
namespace App\Libraries;

use App\Jobs\ProcessGmail;
use App\Models\Integrations\Automation;
use App\Models\Integrations\Integration;
use App\Models\User;
use Exception;
use Google_Client;

class GoogleService
{

    public static function getTokens($userId) {
        return User::find($userId);
    }

    public static function setTokens($data, $userId, $integrationId = null) {
        $client = new Google_Client();
        $client->setAuthConfig(base_path(env("GOOGLE_CREDENTIALS_PATH")));
        $client->setRedirectUri(config('app.url'));
        if ($data->code) {
            $tokenResponse = $client->fetchAccessTokenWithAuthCode($data->code);
            session(['g_token', json_encode($tokenResponse)]);
        } else {
            $tokenResponse = $data;
        }
        if (isset($tokenResponse["error"])) {
            throw new Exception($tokenResponse["error_description"]);
        }
        $user = user::find($userId);
        $integration = new Integration();
        $integration->team_id = $user->current_team_id;
        $integration->user_id = $user->id;
        $integration->name = $data->service_name;
        $integration->automation_service_id = $data->service_id;
        $integration->token = encrypt($tokenResponse['refresh_token']);
        $integration->hash = $data->user;
        $integration->save();
        return $tokenResponse;
    }

    public static function getClient($integrationId) {
        $integration = Integration::find($integrationId);
        $client = new Google_Client();
        $client->setAuthConfig(env("GOOGLE_CREDENTIALS_PATH"));
        if (!$accessToken = session('g_token')) {
            $accessToken = $client->fetchAccessTokenWithRefreshToken(decrypt($integration->token));
        }

        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            if ($refreshToken = $client->refreshToken(decrypt($integration->token))) {
                $accessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                self::setTokens((object) [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken
                ], $integration->user_id, $integrationId);
                $client->setAccessToken($accessToken);
            }
        }

        return $client;
    }

    public static function entryFromGmail($automationId, $afterResponse = null) {
       $automation = Automation::find($automationId);
       echo "$automation->name $automation->id \n";
       $method = $afterResponse ? "dispatchAfterResponse" : "dispatch";
       ProcessGmail::$method($automation);
       return true;
    }
}
