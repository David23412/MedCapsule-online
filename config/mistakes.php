<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mistake Mastery Thresholds
    |--------------------------------------------------------------------------
    |
    | These values define how many consecutive correct answers are required
    | to consider a mistake "mastered" in different modes.
    |
    */
    'thresholds' => [
        'quick' => 2,  // Quick mode requires 2 consecutive correct answers
        'quiz' => 4,   // Quiz mode requires 4 consecutive correct answers
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Mistake Review Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for mistake review functionality
    |
    */
    'review' => [
        'default_limit' => 20,
        'max_limit' => 100,
        'reminder_frequency_hours' => 24,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Mistake Analytics
    |--------------------------------------------------------------------------
    |
    | Settings for mistake tracking and analytics
    |
    */
    'analytics' => [
        'track_attempt_history' => true,
        'max_history_days' => 90,
        'enable_streak_notifications' => true,
    ],
];