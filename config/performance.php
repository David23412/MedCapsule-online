<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Query Count Thresholds
    |--------------------------------------------------------------------------
    |
    | Maximum allowed database queries for each endpoint to prevent N+1 
    | query regressions. Tests will fail if these limits are exceeded.
    |
    */
    'query_thresholds' => [
        'courses.index' => 10,      // Course listing with enrollment data
        'dashboard.home' => 8,      // User dashboard with enrolled courses
        'course.show' => 6,         // Single course with topics
        'topic.show' => 4,          // Single topic with questions
        'user.profile' => 5,        // User profile with progress
        'api.courses' => 15,        // API courses with statistics
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Settings for performance monitoring and alerting
    |
    */
    'monitoring' => [
        'enabled' => true,
        'log_slow_queries' => true,
        'slow_query_threshold' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for integration tests
    |
    */
    'testing' => [
        'fail_on_threshold_exceeded' => true,
        'log_query_details' => false,
        'performance_buffer' => 2, // Allow 2 extra queries as buffer
    ],
];