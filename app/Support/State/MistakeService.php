<?php

namespace App\Support\State;

use App\Models\Mistake;

enum MistakeMode: string
{
    case QUICK = 'quick';
    case QUIZ = 'quiz';
}

final class MistakeService
{
    /**
     * Get mastery thresholds from config
     * 
     * @return array
     */
    private static function getThresholds(): array
    {
        return config('mistakes.thresholds', [
            'quick' => 2,
            'quiz' => 4
        ]);
    }
    
    /**
     * Single source of truth for mistake mastery
     * Based on correct_streak and mode-specific thresholds
     * 
     * @param Mistake $mistake
     * @param MistakeMode $mode
     * @return bool
     */
    public static function isMasteredForMode(Mistake $mistake, MistakeMode $mode): bool
    {
        $thresholds = self::getThresholds();
        $requiredStreak = $thresholds[$mode->value] ?? 4;
        
        return $mistake->correct_streak >= $requiredStreak;
    }
    
    /**
     * Check if mistake is mastered for quiz mode (default)
     * 
     * @param Mistake $mistake
     * @return bool
     */
    public static function isMastered(Mistake $mistake): bool
    {
        return self::isMasteredForMode($mistake, MistakeMode::QUIZ);
    }
    
    /**
     * Update mistake tracking after answer attempt
     * 
     * @param Mistake $mistake
     * @param bool $isCorrect
     * @param MistakeMode $mode
     * @return void
     */
    public static function updateTracking(Mistake $mistake, bool $isCorrect, MistakeMode $mode = MistakeMode::QUIZ): void
    {
        // Update last attempt date
        $mistake->last_attempt_date = now();
        
        if ($isCorrect) {
            $mistake->correct_streak++;
            
            // Update mode-specific counters for analytics
            if ($mode === MistakeMode::QUICK) {
                $mistake->quick_correct_count++;
            } else {
                $mistake->quiz_correct_count++;
            }
        } else {
            // Reset streak on incorrect answer
            $mistake->correct_streak = 0;
        }
        
        // Keep legacy mastered field in sync during Phase A
        $mistake->mastered = self::isMasteredForMode($mistake, $mode);
        
        $mistake->save();
        
        \Log::info('Updated mistake tracking', [
            'question_id' => $mistake->question_id,
            'user_id' => $mistake->user_id,
            'correct_streak' => $mistake->correct_streak,
            'is_mastered' => self::isMasteredForMode($mistake, $mode),
            'mode' => $mode->value
        ]);
    }
    
    /**
     * Get mistakes that are not mastered for a specific mode
     * 
     * @param int $userId
     * @param MistakeMode $mode
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getNotMasteredForUser(int $userId, MistakeMode $mode = MistakeMode::QUIZ): \Illuminate\Database\Eloquent\Collection
    {
        $thresholds = self::getThresholds();
        $requiredStreak = $thresholds[$mode->value] ?? 4;
        
        return Mistake::where('user_id', $userId)
            ->where('correct_streak', '<', $requiredStreak)
            ->where('skipped', false)
            ->get();
    }
    
    /**
     * Get mastered mistakes for a specific mode
     * 
     * @param int $userId
     * @param MistakeMode $mode
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getMasteredForUser(int $userId, MistakeMode $mode = MistakeMode::QUIZ): \Illuminate\Database\Eloquent\Collection
    {
        $thresholds = self::getThresholds();
        $requiredStreak = $thresholds[$mode->value] ?? 4;
        
        return Mistake::where('user_id', $userId)
            ->where('correct_streak', '>=', $requiredStreak)
            ->get();
    }
    
    /**
     * Get mistake statistics for user
     * 
     * @param int $userId
     * @return array
     */
    public static function getStatisticsForUser(int $userId): array
    {
        $allMistakes = Mistake::where('user_id', $userId)->get();
        
        $quizMastered = $allMistakes->filter(fn($m) => self::isMasteredForMode($m, MistakeMode::QUIZ))->count();
        $quickMastered = $allMistakes->filter(fn($m) => self::isMasteredForMode($m, MistakeMode::QUICK))->count();
        
        return [
            'total_mistakes' => $allMistakes->count(),
            'quiz_mastered' => $quizMastered,
            'quiz_not_mastered' => $allMistakes->count() - $quizMastered,
            'quick_mastered' => $quickMastered,
            'quick_not_mastered' => $allMistakes->count() - $quickMastered,
            'skipped' => $allMistakes->where('skipped', true)->count(),
            'average_streak' => $allMistakes->avg('correct_streak') ?? 0,
        ];
    }
    
    /**
     * Reset mistake progress (for testing or admin actions)
     * 
     * @param Mistake $mistake
     * @return void
     */
    public static function resetProgress(Mistake $mistake): void
    {
        $mistake->update([
            'correct_streak' => 0,
            'quick_correct_count' => 0,
            'quiz_correct_count' => 0,
            'mastered' => false, // Keep during Phase A
            'last_attempt_date' => null,
        ]);
        
        \Log::info('Reset mistake progress', [
            'question_id' => $mistake->question_id,
            'user_id' => $mistake->user_id,
        ]);
    }
    
    /**
     * Mark mistake as skipped
     * 
     * @param Mistake $mistake
     * @return void
     */
    public static function markAsSkipped(Mistake $mistake): void
    {
        $mistake->update([
            'skipped' => true,
            'last_attempt_date' => now(),
        ]);
        
        \Log::info('Marked mistake as skipped', [
            'question_id' => $mistake->question_id,
            'user_id' => $mistake->user_id,
        ]);
    }
}