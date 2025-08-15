<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */
    
    // Cache settings
    'cache_ttl' => env('CACHE_TTL', 3600), // 1 hour default
    'query_cache_ttl' => env('QUERY_CACHE_TTL', 1800), // 30 minutes
    'user_cache_ttl' => env('USER_CACHE_TTL', 900), // 15 minutes
    
    // Memory management
    'memory_limit' => env('MEMORY_LIMIT', '256M'),
    'max_execution_time' => env('MAX_EXECUTION_TIME', 60),
    
    // Database optimization
    'query_log_enabled' => env('QUERY_LOG_ENABLED', false),
    'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000), // milliseconds
    
    // Analytics & Reporting
    'analytics' => [
        'track_user_activity' => env('ANALYTICS_TRACK_ACTIVITY', true),
        'track_quiz_performance' => env('ANALYTICS_TRACK_PERFORMANCE', true),
        'track_learning_paths' => env('ANALYTICS_TRACK_LEARNING_PATHS', true),
        'generate_reports' => env('ANALYTICS_GENERATE_REPORTS', true),
        'report_frequency' => env('ANALYTICS_REPORT_FREQUENCY', 'weekly'),
        'admin_dashboard_refresh_minutes' => env('ADMIN_DASHBOARD_REFRESH', 5),
        'activity_log_retention_days' => env('ANALYTICS_LOG_RETENTION', 90),
        'performance_data_retention_days' => env('ANALYTICS_PERFORMANCE_RETENTION', 365),
    ],
    
    // Maintenance & Operations
    'maintenance' => [
        'cleanup_expired_sessions' => env('CLEANUP_SESSIONS', true),
        'cleanup_old_logs' => env('CLEANUP_LOGS', true),
        'optimize_database' => env('OPTIMIZE_DATABASE', true),
        'generate_sitemaps' => env('GENERATE_SITEMAPS', true),
        'auto_backup' => env('AUTO_BACKUP', true),
        'backup_frequency' => env('BACKUP_FREQUENCY', 'daily'),
        'backup_retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'health_checks' => env('HEALTH_CHECKS', true),
        'performance_monitoring' => env('PERFORMANCE_MONITORING', true),
        'error_reporting' => env('ERROR_REPORTING', true),
    ],
];