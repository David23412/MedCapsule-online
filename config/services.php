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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paymob' => [
        'enabled' => env('PAYMOB_ENABLED', false),
        'mode' => env('PAYMOB_MODE', 'test'),
        'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
        'public_key' => env('PAYMOB_PUBLIC_KEY'),
        'secret_key' => env('PAYMOB_SECRET_KEY'),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
        'card_integration_id' => env('PAYMOB_CARD_INTEGRATION_ID'),
        'wallet_integration_id' => env('PAYMOB_WALLET_INTEGRATION_ID'),
        'iframe_id' => env('PAYMOB_IFRAME_ID'),
    ],

];
