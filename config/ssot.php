<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Single Source of Truth (SSOT) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the SSOT refactor implementation including dual-write
    | synchronization, legacy field deprecation, and migration settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration - DUAL-WRITE DISABLED (SSOT ONLY MODE)
    |--------------------------------------------------------------------------
    */
    'sync_enabled' => false,
    'legacy_write_warnings' => false,
    'conflict_detection' => false,
    
    /*
    |--------------------------------------------------------------------------
    | Deprecation Timeline
    |--------------------------------------------------------------------------
    |
    | Target dates for removing legacy fields after full migration
    |
    */
    'deprecation_dates' => [
        'course_user.is_enrolled' => '2025-09-15',
        'video_progress.completed' => '2025-09-01',
        'topics.is_locked' => '2025-09-10',
        'mistakes.mastered' => '2025-09-05',
        'quiz_attempts.correct_answers' => '2025-08-30',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Observer Configuration - ALL DISABLED (SSOT ONLY MODE)
    |--------------------------------------------------------------------------
    */
    'observers' => [
        'enrollment' => [
            'enabled' => false,
            'sync_directions' => [],
        ],
        'assignment_status' => [
            'enabled' => false,
            'sync_directions' => [],
        ],
        'user_progress' => [
            'enabled' => false,
            'sync_directions' => [],
        ],
        'mistake_mastery' => [
            'enabled' => false,
            'sync_directions' => [],
        ],
        'video_progress' => [
            'enabled' => false,
            'sync_directions' => [],
        ],
        'topic_lock' => [
            'enabled' => false,
            'sync_directions' => [],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'channel' => env('SSOT_LOG_CHANNEL', 'single'),
        'level' => env('SSOT_LOG_LEVEL', 'info'),
        'include_stack_trace' => env('SSOT_LOG_STACK_TRACE', false),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'cache_sync_operations' => true,
        'batch_sync_threshold' => 100,
        'max_recursion_depth' => 3,
    ],
];