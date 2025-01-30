<?php

namespace App\Domains\Integration\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

class WhatsAppService
{
    public function sendMessage($recipientPhone, $message)
    {
        $token =  config('integrations.whatsapp.token');
        $phoneNumberId =  config('integrations.whatsapp.phoneId');
        $version = "v21.0";

        $client = new Client();
        try {
            $response = $client->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'headers' => [
                    'Authorization' => "Bearer $token",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipientPhone,
                    'type' => 'template',
                    'template' => [
                        'name' => 'hello_world',
                        'language' => [
                            "code" => "en_US"
                        ]
                    ],
                ],
            ]);

            return response()->json(json_decode($response->getBody(), true));
        } catch (RequestException $e) {
            $errorResponse = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response from server';
            Log::alert($errorResponse);
            throw new Exception($errorResponse);
        } catch (Exception $e) {
            Log::alert($e->getMessage());
            throw new Exception($e->getMessage());
        }
    }
}
