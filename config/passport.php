<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Passport will use when
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'web',

    'middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    |
    | Coolify hands the keys over with escaped line breaks ("\\n", or "\" left
    | at each line end), which OpenSSL rejects as "Invalid key supplied". Any
    | run of backslashes before "n" or before a line break becomes one break.
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY') ? preg_replace(['/\\\\+n/', '/\\\\+(?=\r?\n)/', '/\r/'], ["\n", '', ''], env('PASSPORT_PRIVATE_KEY')) : null,

    'public_key' => env('PASSPORT_PUBLIC_KEY') ? preg_replace(['/\\\\+n/', '/\\\\+(?=\r?\n)/', '/\r/'], ["\n", '', ''], env('PASSPORT_PUBLIC_KEY')) : null,

    /*
    |--------------------------------------------------------------------------
    | Passport Database Connection
    |--------------------------------------------------------------------------
    |
    | By default, Passport's models will utilize your application's default
    | database connection. If you wish to use a different connection you
    | may specify the configured name of the database connection here.
    |
    */

    'connection' => env('PASSPORT_CONNECTION'),

];
