<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MedCapsule Application Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all the configuration options for the MedCapsule
    | medical education platform. This centralizes settings for easier
    | maintenance and environment-specific configurations.
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
    | Quiz & Learning Configuration
    |--------------------------------------------------------------------------
    */
    'quiz' => [
        // Default number of questions per quiz
        'default_questions_per_quiz' => env('QUIZ_DEFAULT_QUESTIONS', 10),
        
        // Maximum questions per quiz
        'max_questions_per_quiz' => env('QUIZ_MAX_QUESTIONS', 50),
        
        // Minimum questions per quiz
        'min_questions_per_quiz' => env('QUIZ_MIN_QUESTIONS', 5),
        
        // Quiz timeout in minutes (0 = no timeout)
        'quiz_timeout_minutes' => env('QUIZ_TIMEOUT_MINUTES', 30),
        
        // Enable/disable tutor mode
        'enable_tutor_mode' => env('QUIZ_ENABLE_TUTOR_MODE', true),
        
        // Enable/disable random quiz mode
        'enable_random_mode' => env('QUIZ_ENABLE_RANDOM_MODE', true),
        
        // Default similarity threshold for written answers
        'written_answer_similarity_threshold' => env('QUIZ_SIMILARITY_THRESHOLD', 0.8),
        
        // Performance tracking
        'track_performance' => env('QUIZ_TRACK_PERFORMANCE', true),
        'performance_cache_minutes' => env('QUIZ_PERFORMANCE_CACHE', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gamification & XP System
    |--------------------------------------------------------------------------
    */
    'gamification' => [
        // XP per correct answer
        'xp_per_correct_answer' => env('XP_PER_CORRECT_ANSWER', 10),
        
        // XP required per level
        'xp_per_level' => env('XP_PER_LEVEL', 1000),
        
        // Maximum level cap
        'max_level' => env('MAX_LEVEL', 100),
        
        // Streak bonuses
        'streak_bonus_multiplier' => env('STREAK_BONUS_MULTIPLIER', 1.5),
        'max_streak_bonus' => env('MAX_STREAK_BONUS', 50),
        
        // Achievement system
        'enable_achievements' => env('ENABLE_ACHIEVEMENTS', true),
        'enable_leaderboards' => env('ENABLE_LEADERBOARDS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    */
    'payment' => [
        // Default currency
        'default_currency' => env('PAYMENT_CURRENCY', 'EGP'),
        
        // Payment methods
        'payment_methods' => [
            'vodafone_cash' => [
                'enabled' => env('PAYMENT_VODAFONE_ENABLED', true),
                'number' => env('PAYMENT_VODAFONE_NUMBER', '01012345678'),
                'name' => env('PAYMENT_VODAFONE_NAME', 'MedCapsule Account'),
            ],
            'fawry' => [
                'enabled' => env('PAYMENT_FAWRY_ENABLED', true),
                'number' => env('PAYMENT_FAWRY_NUMBER', 'FAWRY-ACCOUNT'),
                'reference' => env('PAYMENT_FAWRY_REFERENCE', 'MEDCAPSULE'),
            ],
            'instapay' => [
                'enabled' => env('PAYMENT_INSTAPAY_ENABLED', true),
                'username' => env('PAYMENT_INSTAPAY_USERNAME', '@medcapsule'),
            ],
        ],
        
        // Payment verification
        'auto_verification' => [
            'enabled' => env('PAYMENT_AUTO_VERIFY', false),
            'transaction_patterns' => [
                'vodafone_cash' => '/^VC\d{10,}$/',
                'fawry' => '/^FW\d{8,}$/',
                'instapay' => '/^IP\d{10,}$/',
            ],
        ],
        
        // Payment expiry
        'reference_expiry_hours' => env('PAYMENT_EXPIRY_HOURS', 24),
        'cleanup_expired_payments' => env('PAYMENT_CLEANUP_EXPIRED', true),
        
        // Notifications
        'notify_admin_on_payment' => env('PAYMENT_NOTIFY_ADMIN', true),
        'notify_user_on_verification' => env('PAYMENT_NOTIFY_USER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    'security' => [
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
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_document_types' => ['pdf', 'doc', 'docx'],
        
        // CSRF protection
        'csrf_except_routes' => [
            'api/*',
            'webhooks/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance & Caching
    |--------------------------------------------------------------------------
    */
    'performance' => [
        // Cache durations (in minutes)
        'cache_durations' => [
            'courses' => env('CACHE_COURSES_MINUTES', 60),
            'topics' => env('CACHE_TOPICS_MINUTES', 30),
            'questions' => env('CACHE_QUESTIONS_MINUTES', 15),
            'user_progress' => env('CACHE_USER_PROGRESS_MINUTES', 10),
            'leaderboards' => env('CACHE_LEADERBOARDS_MINUTES', 30),
            'statistics' => env('CACHE_STATISTICS_MINUTES', 60),
        ],
        
        // Database optimization
        'eager_load_relationships' => env('DB_EAGER_LOAD', true),
        'query_logging' => env('DB_QUERY_LOGGING', false),
        'slow_query_threshold_ms' => env('DB_SLOW_QUERY_THRESHOLD', 1000),
        
        // Response optimization
        'enable_compression' => env('RESPONSE_COMPRESSION', true),
        'minify_assets' => env('ASSETS_MINIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        // Channels
        'channels' => [
            'database' => env('NOTIFICATIONS_DATABASE', true),
            'email' => env('NOTIFICATIONS_EMAIL', true),
            'sms' => env('NOTIFICATIONS_SMS', false),
            'push' => env('NOTIFICATIONS_PUSH', false),
        ],
        
        // Notification types
        'types' => [
            'level_up' => env('NOTIFY_LEVEL_UP', true),
            'course_completion' => env('NOTIFY_COURSE_COMPLETION', true),
            'streak_milestone' => env('NOTIFY_STREAK_MILESTONE', true),
            'payment_verified' => env('NOTIFY_PAYMENT_VERIFIED', true),
            'course_welcome' => env('NOTIFY_COURSE_WELCOME', true),
            'mistake_correction' => env('NOTIFY_MISTAKE_CORRECTION', false),
        ],
        
        // Cleanup settings
        'cleanup_read_after_days' => env('NOTIFICATIONS_CLEANUP_DAYS', 30),
        'max_notifications_per_user' => env('NOTIFICATIONS_MAX_PER_USER', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Management
    |--------------------------------------------------------------------------
    */
    'content' => [
        // Image processing
        'image_optimization' => env('IMAGE_OPTIMIZATION', true),
        'image_max_width' => env('IMAGE_MAX_WIDTH', 1920),
        'image_max_height' => env('IMAGE_MAX_HEIGHT', 1080),
        'image_quality' => env('IMAGE_QUALITY', 85),
        
        // Course settings
        'max_topics_per_course' => env('MAX_TOPICS_PER_COURSE', 50),
        'max_questions_per_topic' => env('MAX_QUESTIONS_PER_TOPIC', 100),
        'require_course_images' => env('REQUIRE_COURSE_IMAGES', false),
        
        // Question validation
        'require_explanations' => env('REQUIRE_EXPLANATIONS', true),
        'min_options_per_mcq' => env('MIN_MCQ_OPTIONS', 2),
        'max_options_per_mcq' => env('MAX_MCQ_OPTIONS', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Reporting
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        // Data collection
        'track_user_activity' => env('ANALYTICS_TRACK_ACTIVITY', true),
        'track_quiz_performance' => env('ANALYTICS_TRACK_PERFORMANCE', true),
        'track_learning_paths' => env('ANALYTICS_TRACK_LEARNING_PATHS', true),
        
        // Reporting
        'generate_reports' => env('ANALYTICS_GENERATE_REPORTS', true),
        'report_frequency' => env('ANALYTICS_REPORT_FREQUENCY', 'weekly'),
        'admin_dashboard_refresh_minutes' => env('ADMIN_DASHBOARD_REFRESH', 5),
        
        // Data retention
        'activity_log_retention_days' => env('ANALYTICS_LOG_RETENTION', 90),
        'performance_data_retention_days' => env('ANALYTICS_PERFORMANCE_RETENTION', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Experience
    |--------------------------------------------------------------------------
    */
    'ux' => [
        // Interface preferences
        'default_theme' => env('DEFAULT_THEME', 'light'),
        'enable_dark_mode' => env('ENABLE_DARK_MODE', true),
        'items_per_page' => env('ITEMS_PER_PAGE', 20),
        
        // Accessibility
        'enable_accessibility_features' => env('ENABLE_ACCESSIBILITY', true),
        'high_contrast_mode' => env('HIGH_CONTRAST_MODE', false),
        'keyboard_navigation' => env('KEYBOARD_NAVIGATION', true),
        
        // Mobile optimization
        'mobile_optimized' => env('MOBILE_OPTIMIZED', true),
        'progressive_web_app' => env('ENABLE_PWA', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        // External APIs
        'medical_terminology_api' => [
            'enabled' => env('MEDICAL_API_ENABLED', false),
            'url' => env('MEDICAL_API_URL'),
            'key' => env('MEDICAL_API_KEY'),
            'timeout' => env('MEDICAL_API_TIMEOUT', 30),
        ],
        
        // Social features
        'social_sharing' => env('SOCIAL_SHARING_ENABLED', true),
        'study_groups' => env('STUDY_GROUPS_ENABLED', false),
        'peer_discussions' => env('PEER_DISCUSSIONS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance & Operations
    |--------------------------------------------------------------------------
    */
    'maintenance' => [
        // Scheduled tasks
        'cleanup_expired_sessions' => env('CLEANUP_SESSIONS', true),
        'cleanup_old_logs' => env('CLEANUP_LOGS', true),
        'optimize_database' => env('OPTIMIZE_DATABASE', true),
        'generate_sitemaps' => env('GENERATE_SITEMAPS', true),
        
        // Backup settings
        'auto_backup' => env('AUTO_BACKUP', true),
        'backup_frequency' => env('BACKUP_FREQUENCY', 'daily'),
        'backup_retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        
        // Health monitoring
        'health_checks' => env('HEALTH_CHECKS', true),
        'performance_monitoring' => env('PERFORMANCE_MONITORING', true),
        'error_reporting' => env('ERROR_REPORTING', true),
    ],
];