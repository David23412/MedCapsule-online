<?php

/**
 * OPcache Configuration for MedCapsule
 * This file provides OPcache optimization settings
 * Copy relevant settings to your php.ini file
 */

return [
    /*
    |--------------------------------------------------------------------------
    | OPcache Status and Monitoring
    |--------------------------------------------------------------------------
    |
    | These settings help monitor and optimize OPcache performance
    |
    */

    'status' => [
        'enabled' => extension_loaded('Zend OPcache') && ini_get('opcache.enable'),
        'cli_enabled' => extension_loaded('Zend OPcache') && ini_get('opcache.enable_cli'),
        'memory_usage' => function_exists('opcache_get_status') ? opcache_get_status(false) : null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Recommended Production Settings
    |--------------------------------------------------------------------------
    |
    | These are the golden standard settings for production environments
    | Copy these to your php.ini file
    |
    */

    'production_settings' => [
        'opcache.enable' => 1,
        'opcache.enable_cli' => 1, // CRITICAL: Enable for CLI performance
        'opcache.memory_consumption' => 128,
        'opcache.interned_strings_buffer' => 8,
        'opcache.max_accelerated_files' => 10000,
        'opcache.revalidate_freq' => 60,
        'opcache.fast_shutdown' => 1,
        'opcache.enable_file_override' => 1,
        'opcache.validate_timestamps' => 0, // Disable in production
        'opcache.huge_code_pages' => 1,
        'opcache.consistency_checks' => 0,
        'opcache.dups_fix' => 0,
        'opcache.max_wasted_percentage' => 5,
        'opcache.jit' => 'disable',
        'opcache.jit_buffer_size' => '64M',
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | These settings are optimized for development with hot reloading
    |
    */

    'development_settings' => [
        'opcache.enable' => 1,
        'opcache.enable_cli' => 0, // Keep disabled for development
        'opcache.memory_consumption' => 64,
        'opcache.interned_strings_buffer' => 4,
        'opcache.max_accelerated_files' => 5000,
        'opcache.revalidate_freq' => 2,
        'opcache.fast_shutdown' => 1,
        'opcache.enable_file_override' => 1,
        'opcache.validate_timestamps' => 1,
        'opcache.huge_code_pages' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Benchmarks
    |--------------------------------------------------------------------------
    |
    | Expected performance improvements with OPcache enabled
    |
    */

    'performance_benchmarks' => [
        'without_opcache' => [
            'response_time' => '200-300ms',
            'memory_usage' => '150MB',
            'cpu_usage' => 'High'
        ],
        'with_opcache' => [
            'response_time' => '50-100ms',
            'memory_usage' => '100MB',
            'cpu_usage' => 'Low'
        ],
        'improvement' => [
            'response_time' => '50-75% faster',
            'memory_usage' => '33% less',
            'cpu_usage' => '60% reduction'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | OPcache Management Commands
    |--------------------------------------------------------------------------
    |
    | Artisan commands for OPcache management
    |
    */

    'commands' => [
        'status' => 'opcache:status',
        'clear' => 'opcache:clear',
        'compile' => 'opcache:compile',
        'reset' => 'opcache:reset'
    ]
];
