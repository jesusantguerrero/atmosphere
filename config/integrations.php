<?php

return [
    'google' => [
        'credentials_path' => env('GOOGLE_CREDENTIALS_PATH'),
        'client_id' => env('GOOGLE_CLIENT_ID'),
    ],
    'whatsapp' => [
        'token' => env("WA_TOKEN"),
        'phoneId' => env("WA_PHONE_NUMBER_ID"),
        'webhook' => [
            'verifyToken' => env("WA_WEBHOOK_VERIFY_TOKEN")
        ]
    ],
    'telegram' => [
        'token' => env("TELEGRAM_BOT_TOKEN"),
        'chat_id' => env("TELEGRAM_CHAT_ID"),
    ],
];
