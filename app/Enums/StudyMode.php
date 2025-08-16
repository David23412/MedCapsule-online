<?php

namespace App\Enums;

/**
 * Study Mode Constants for Type Safety
 * Replaces magic strings throughout the application
 */
class StudyMode
{
    const QUIZ = 'quiz';
    const TUTOR = 'tutor';
    const RANDOM = 'random';
    const ASSIGNMENT = 'assignment';
    const MISTAKE_REVIEW = 'mistake_review';
    
    /**
     * Get all valid study modes
     */
    public static function all(): array
    {
        return [
            self::QUIZ,
            self::TUTOR,
            self::RANDOM,
            self::ASSIGNMENT,
            self::MISTAKE_REVIEW
        ];
    }
    
    /**
     * Check if mode is valid
     */
    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::all());
    }
}