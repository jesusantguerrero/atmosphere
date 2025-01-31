<?php

namespace App\Domains\Integration\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

class WhatsAppService
{
    private string $token;
    private string $phoneNumberId;
    private string $version = "v21.0";

    public function __construct()
    {
        $this->token =  config('integrations.whatsapp.token');
        $this->phoneNumberId =  config('integrations.whatsapp.phoneId');
    }

    public function send($messageBody)
    {

        $client = new Client();
        try {
            $response = $client->post("https://graph.facebook.com/{$this->version}/{$this->phoneNumberId}/messages", [
                'headers' => [
                    'Authorization' => "Bearer $this->token",
                    'Content-Type' => 'application/json',
                ],
                'json' => $messageBody,
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
    public function sendMessage($recipientPhone, $message)
    {

        $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $recipientPhone,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ]);
    }

    public function sendMessageFromTemplate($recipientPhone, $vars, $templateName = 'hello_world', $langCode = "en_US")
    {
        $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $recipientPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    "code" => $langCode
                ]
            ],
        ]);
    }
}
