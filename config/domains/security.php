<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    
    // Rate limiting
    'rate_limits' => [
        'quiz_attempts' => env('RATE_LIMIT_QUIZ_ATTEMPTS', '10,60'), // 10 per minute
        'payment_submissions' => env('RATE_LIMIT_PAYMENTS', '5,60'), // 5 per minute
        'password_resets' => env('RATE_LIMIT_PASSWORD_RESETS', '3,60'), // 3 per minute
        'login_attempts' => env('RATE_LIMIT_LOGIN', '5,60'), // 5 per minute
    ],
    
    // Session management
    'session_timeout_minutes' => env('SESSION_TIMEOUT', 120),
    'concurrent_sessions_limit' => env('CONCURRENT_SESSIONS_LIMIT', 3),
    
    // File uploads
    'upload_max_size_mb' => env('UPLOAD_MAX_SIZE_MB', 10),
    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
    
    // API security
    'api_rate_limit' => env('API_RATE_LIMIT', '100,60'), // 100 per minute
    'require_api_key' => env('REQUIRE_API_KEY', false),
];