<?php

use Illuminate\Support\Str;

return [
    'default' => env('CACHE_STORE', 'array'),

    'stores' => [
        'array' => [
            'driver' => 'array',
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'medcapsule_cache'),

    // PHASE 2 OPTIMIZATION: Enhanced TTL strategy with cache tags
    'ttl' => [
        // Core application data
        'quiz' => 1800,        // 30 minutes for quiz data
        'user' => 3600,        // 1 hour for user data
        'course' => 86400,     // 24 hours for course data
        'stats' => 300,        // 5 minutes for statistics
        'session' => 7200,     // 2 hours for session data
        
        // New optimized TTLs for Phase 2
        'question' => 43200,   // 12 hours for question data
        'topic' => 21600,      // 6 hours for topic data
        'progress' => 1800,    // 30 minutes for progress data
        'mistakes' => 3600,    // 1 hour for mistake data
        'notifications' => 600, // 10 minutes for notifications
        'evaluation' => 86400, // 24 hours for answer evaluation
        'medical_knowledge' => 604800, // 7 days for medical knowledge
        'text_processing' => 86400,    // 24 hours for text processing
    ],

    // PHASE 2: Cache tags for targeted invalidation
    'tags' => [
        'user_progress' => ['user', 'progress', 'topic'],
        'user_mistakes' => ['user', 'mistakes', 'topic'],
        'user_stats' => ['user', 'stats', 'mastery'],
        'topic_data' => ['topic', 'questions', 'progress'],
        'quiz_data' => ['quiz', 'questions', 'attempts'],
        'course_data' => ['course', 'topics', 'enrollment'],
        'notification_data' => ['user', 'notifications'],
        'evaluation_data' => ['question', 'evaluation', 'answer'],
        'medical_data' => ['medical', 'knowledge', 'domain'],
    ],

    // PHASE 2: Event-based invalidation settings
    'events' => [
        'CourseUpdated' => ['course_data', 'topic_data'],
        'TopicCompleted' => ['user_progress', 'topic_data', 'user_stats'],
        'QuestionDeleted' => ['quiz_data', 'topic_data', 'evaluation_data'],
        'UserProgressUpdated' => ['user_progress', 'user_stats'],
        'QuizAttemptCompleted' => ['user_progress', 'quiz_data', 'user_stats'],
        'NotificationCreated' => ['notification_data'],
        'PaymentCompleted' => ['course_data', 'user_progress'],
    ],
];
