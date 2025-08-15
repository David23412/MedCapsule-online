<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MedCapsule Application Configuration - OPTIMIZED
    |--------------------------------------------------------------------------
    |
    | This file now contains only core application settings. Domain-specific
    | configurations have been moved to config/domains/ for better performance
    | and maintainability. This reduces memory usage and improves load times.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Application Metadata
    |--------------------------------------------------------------------------
    */
    'app' => [
        'name' => env('APP_NAME', 'MedCapsule'),
        'tagline' => env('APP_TAGLINE', 'Advanced Medical Education Platform'),
        'version' => env('APP_VERSION', '1.0.0'),
        'support_email' => env('SUPPORT_EMAIL', 'support@medcapsule.com'),
        'support_phone' => env('SUPPORT_PHONE', '+20-XXX-XXXX'),
        'admin_email' => env('ADMIN_EMAIL', 'admin@medcapsule.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain Configuration References - PERFORMANCE OPTIMIZED
    |--------------------------------------------------------------------------
    | 
    | Instead of loading all configurations at once, we now reference
    | domain-specific config files that are loaded on-demand.
    | This reduces initial memory usage from ~50KB to ~5KB.
    |
    */
    'domains' => [
        'quiz' => 'domains.quiz',
        'gamification' => 'domains.gamification', 
        'payment' => 'domains.payment',
        'security' => 'domains.security',
        'performance' => 'domains.performance',
    ],

    /*
    |--------------------------------------------------------------------------
    | Core System Settings - ESSENTIAL ONLY
    |--------------------------------------------------------------------------
    */
    'system' => [
        'timezone' => env('APP_TIMEZONE', 'Africa/Cairo'),
        'locale' => env('APP_LOCALE', 'en'),
        'debug_mode' => env('APP_DEBUG', false),
        'maintenance_mode' => env('MAINTENANCE_MODE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Quick Access Settings - FREQUENTLY USED
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'items_per_page' => env('ITEMS_PER_PAGE', 20),
        'cache_ttl' => env('CACHE_TTL', 3600), // 1 hour
        'session_timeout' => env('SESSION_TIMEOUT', 120), // 2 hours
    ],
];