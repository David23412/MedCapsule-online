<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Set trusted proxy IP addresses. You can set it to '*' to trust all
    | proxies, but this is not recommended for production.
    |
    */
    'proxies' => env('TRUSTED_PROXIES', '*'),

    /*
    |--------------------------------------------------------------------------
    | Trusted Headers
    |--------------------------------------------------------------------------
    |
    | Which headers to use to detect proxies.
    |
    */
    'headers' => \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                 \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                 \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                 \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
                 \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB,

    /*
    |--------------------------------------------------------------------------
    | Trusted Hosts
    |--------------------------------------------------------------------------
    |
    | Set trusted host patterns for ngrok and other development tools.
    |
    */
    'hosts' => [
        env('APP_URL'),
        '*.ngrok-free.dev',
        '*.ngrok-free.app',
        '*.ngrok.io',
        'localhost',
        '127.0.0.1',
        '*.medcapsule.com',
    ],
];
