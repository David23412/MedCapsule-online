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
    | Sync Configuration
    |--------------------------------------------------------------------------
    */
    'sync_enabled' => env('SSOT_SYNC_ENABLED', true),
    'legacy_write_warnings' => env('SSOT_LEGACY_WARNINGS', true),
    'conflict_detection' => env('SSOT_CONFLICT_DETECTION', true),
    
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
    | Observer Configuration
    |--------------------------------------------------------------------------
    */
    'observers' => [
        'enrollment' => [
            'enabled' => true,
            'sync_directions' => ['legacy_to_ssot', 'ssot_to_legacy'],
        ],
        'assignment_status' => [
            'enabled' => true,
            'sync_directions' => ['ssot_to_legacy'],
        ],
        'user_progress' => [
            'enabled' => true,
            'sync_directions' => ['legacy_to_ssot', 'ssot_to_legacy'],
        ],
        'mistake_mastery' => [
            'enabled' => true,
            'sync_directions' => ['legacy_to_ssot', 'ssot_to_legacy'],
        ],
        'video_progress' => [
            'enabled' => true,
            'sync_directions' => ['legacy_to_ssot', 'ssot_to_legacy'],
        ],
        'topic_lock' => [
            'enabled' => true,
            'sync_directions' => ['legacy_to_ssot', 'ssot_to_legacy'],
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