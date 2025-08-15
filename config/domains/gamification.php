<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gamification & XP System
    |--------------------------------------------------------------------------
    */
    
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
];