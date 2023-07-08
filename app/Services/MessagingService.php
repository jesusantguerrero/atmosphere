<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\WebPushConfig;
use Kreait\Laravel\Firebase\Facades\Firebase;

class MessagingService {
    private $messaging;

    public function __construct() {
        $this->messaging = Firebase::messaging();


    }

    public function occurrenceType($messageData, $deviceId) {
        $message = CloudMessage::withTarget('token', $deviceId)
        ->withNotification(Notification::fromArray([
            'title' => 'test',
            'body' => 'test',
            'image' => 'http://lorempixel.com/400/200/',
        ]))
        ->withData($messageData);

        $this->messaging->send($message);

        $config = WebPushConfig::fromArray([
            'notification' => [
                'title' => '$GOOG up 1.43% on the day',
                'body' => '$GOOG gained 11.80 points to close at 835.67, up 1.43% on the day.',
                'icon' => 'https://my-server/icon.png',
            ],
            'fcm_options' => [
                'link' => 'https://my-server/some-page',
            ],
        ]);
        
        $message = $message->withWebPushConfig($config);
        $this->messaging->send($message);
        echo "sent";
    }

    public function newsLetter($messageData) {
        $message = CloudMessage::withTarget('topic', 'occurrence')
        ->withNotification(Notification::fromArray([
            'title' => 'test',
            'body' => 'test',
            'image' => 'http://lorempixel.com/400/200/',
        ]))
        ->withData($messageData);

        $this->messaging->send($message);
    }
}
