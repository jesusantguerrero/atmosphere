<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google_analytics' => [
        'measurement_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    'onesignal' => [
        'app_id' => env('ONESIGNAL_APP_ID'),
        'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),
    ],

    'google_auth' => [
        'client_ids' => array_filter([
            env('GOOGLE_AUTH_WEB_CLIENT_ID'),
            env('GOOGLE_AUTH_ANDROID_CLIENT_ID'),
            env('GOOGLE_AUTH_IOS_CLIENT_ID'),
        ]),
    ],

    'apple_auth' => [
        'bundle_id' => env('APPLE_AUTH_BUNDLE_ID', 'com.neatlancer.neathome'),
    ],

    'inbound_email' => [
        'domain' => env('INBOUND_EMAIL_DOMAIN', 'parse.loger.app'),
    ],

];
