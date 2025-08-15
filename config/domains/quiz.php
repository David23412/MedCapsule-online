<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quiz & Learning Configuration
    |--------------------------------------------------------------------------
    */
    
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
];