<?php

use App\Support\TrustedNetworkConfiguration;

$configuredProxies = TrustedNetworkConfiguration::trustedProxies(
    env('TRUSTED_PROXIES')
);

$configuredHosts = TrustedNetworkConfiguration::trustedHosts(
    env('TRUSTED_HOSTS', ''),
    (string) env('APP_URL', ''),
    filter_var(env('ENABLE_NGROK_COMPAT', false), FILTER_VALIDATE_BOOL)
);

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
    'proxies' => $configuredProxies,

    /*
    |--------------------------------------------------------------------------
    | Trusted Headers
    |--------------------------------------------------------------------------
    |
    | Which headers to use to detect proxies.
    |
    */
    'headers' => \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
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
    'hosts' => TrustedNetworkConfiguration::hostPatterns($configuredHosts),
];
