<?php

namespace App\Domains\Integration\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;

class TelegramService
{
    private string $token;

    public function __construct()
    {
        $this->token =  config('integrations.telegram.token');
    }

    public function send($messageBody, $recipientPhone, $config = [])
    {

        $client = new Client();
        try {
            $messageData = [
                'chat_id' => $recipientPhone,
                'text'    => $messageBody,
                ...$config
            ];
            $response = $client->post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                "headers" => [
                    'Content-Type' => 'application/json',
                ],
                "json" => $messageData
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
        $this->send($message, $recipientPhone);
    }

    public function sendQuiz($recipientPhone)
    {
        $this->send('Choose an option:', $recipientPhone, [
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => 'Option 1', 'callback_data' => '1'],
                            ['text' => 'Option 2', 'callback_data' => '2']
                        ]
                    ]
                ])
        ]);
    }
}
