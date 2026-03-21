<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [
        env('APP_URL', 'http://localhost:8000'),
        'https://8b4695eeacde.ngrok-free.app',
        'https://apperceptive-nonvehemently-teresita.ngrok-free.dev',
        'https://*.ngrok-free.app',
        'https://*.ngrok-free.dev',
    ],
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.ngrok-free\.app$/',
        '/^https:\/\/.*\.ngrok-free\.dev$/',
        '/^https:\/\/.*\.ngrok\.io$/'
    ],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'X-CSRF-TOKEN'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
]; 
