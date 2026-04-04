<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains feature flags for safely rolling out new architectural
    | components without breaking existing functionality.
    |
    */

    // Event-Driven Grading Service - ENABLED for concurrent performance
    'event_driven_grading' => env('FEATURE_EVENT_DRIVEN_GRADING', true),
    'event_driven_grading_percentage' => env('FEATURE_EVENT_DRIVEN_GRADING_PERCENTAGE', 50), // 50% of users for testing

    // Smart Cache Manager - ENABLED for intelligent cache invalidation
    'smart_cache_manager' => env('FEATURE_SMART_CACHE_MANAGER', true),
    'smart_cache_manager_percentage' => env('FEATURE_SMART_CACHE_MANAGER_PERCENTAGE', 50), // 50% of operations for testing

    // Memory Efficient Quiz Service - ENABLED for large datasets
    'memory_efficient_quiz' => env('FEATURE_MEMORY_EFFICIENT_QUIZ', true),
    'memory_efficient_quiz_threshold' => env('FEATURE_MEMORY_EFFICIENT_QUIZ_THRESHOLD', 100), // Use for datasets > 100 questions (lowered for testing)

    // Memory Monitor Middleware
    'memory_monitor' => env('FEATURE_MEMORY_MONITOR', true), // Safe to enable by default
    'memory_monitor_routes' => [
        'quiz.grade',
        'quiz.start',
        'quiz.random',
        'quiz.grade-random',
        'add-question',
        'admin.delete.questions.bulk',
    ],

    // Architectural Health Check
    'health_check_enabled' => env('FEATURE_HEALTH_CHECK_ENABLED', true),
    'health_check_auto_fix' => env('FEATURE_HEALTH_CHECK_AUTO_FIX', false),

    // Rollout Strategy
    'rollout_strategy' => env('FEATURE_ROLLOUT_STRATEGY', 'user_id'), // 'user_id', 'percentage', 'whitelist'
    'rollout_whitelist_users' => array_filter(explode(',', env('FEATURE_ROLLOUT_WHITELIST_USERS', ''))),

    // Safety Settings
    'enable_fallback' => env('FEATURE_ENABLE_FALLBACK', true), // Always fallback to old services on error
    'log_feature_usage' => env('FEATURE_LOG_USAGE', true), // Log when new features are used
    'performance_comparison' => env('FEATURE_PERFORMANCE_COMPARISON', false), // Compare old vs new performance

    // Frontend design protection
    'design_protection_enabled' => env('DESIGN_PROTECTION_ENABLED', false),
    'design_protection_devtools_threshold' => (int) env('DESIGN_PROTECTION_DEVTOOLS_THRESHOLD', 160),
];
