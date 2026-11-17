<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'hive' => [
        'base_url' => env('HIVE_BASE_URL', 'https://hub.hive.test'),
        'client_id' => env('HIVE_CLIENT_ID', ''),
        'client_secret' => env('HIVE_CLIENT_SECRET', ''),
        'redirect' => env('HIVE_REDIRECT_URI', env('APP_URL').'/auth/callback'),
        'verify_ssl' => env('HIVE_VERIFY_SSL', true),
        'ca_bundle' => env('HIVE_CA_BUNDLE'),
    ],

];
