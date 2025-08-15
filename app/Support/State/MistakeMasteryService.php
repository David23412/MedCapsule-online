<?php

namespace App\Support\State;

use App\Models\Mistake;
use App\Models\User;
use App\Models\Question;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Single Source of Truth for Mistake Mastery State
 * 
 * Replaces split brain logic between:
 * - mistakes.mastered (boolean)
 * - mistakes.correct_streak (integer)
 * - Dynamic calculation based on streak thresholds
 */
class MistakeMasteryService
{
    const CACHE_TTL = 300; // 5 minutes
    
    // Mastery thresholds by mode
    const STREAK_THRESHOLD_QUIZ = 4;
    const STREAK_THRESHOLD_QUICK = 2;
    const STREAK_THRESHOLD_TUTOR = 3;

    /**
     * Get definitive mastery status for a mistake
     * 
     * @param Mistake $mistake
     * @param string $mode
     * @return array
     */
    public function getMasteryStatus(Mistake $mistake, string $mode = 'quiz'): array
    {
        $cacheKey = "mistake_mastery_{$mistake->id}_{$mode}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($mistake, $mode) {
            $threshold = $this->getStreakThreshold($mode);
            $currentStreak = $mistake->correct_streak ?? 0;
            $isMastered = $currentStreak >= $threshold;
            
            return [
                'is_mastered' => $isMastered,
                'current_streak' => $currentStreak,
                'required_streak' => $threshold,
                'remaining_correct' => max(0, $threshold - $currentStreak),
                'mode' => $mode,
                'last_attempt_date' => $mistake->last_attempt_date,
                'computed_at' => now()
            ];
        });
    }

    /**
     * Check if mistake is mastered (simple boolean)
     */
    public function isMastered(Mistake $mistake, string $mode = 'quiz'): bool
    {
        return $this->getMasteryStatus($mistake, $mode)['is_mastered'];
    }

    /**
     * Get streak threshold for mode
     */
    public function getStreakThreshold(string $mode): int
    {
        return match($mode) {
            'quick' => self::STREAK_THRESHOLD_QUICK,
            'tutor' => self::STREAK_THRESHOLD_TUTOR,
            'quiz' => self::STREAK_THRESHOLD_QUIZ,
            default => self::STREAK_THRESHOLD_QUIZ
        };
    }

    /**
     * Update mistake tracking after answer attempt
     */
    public function updateMistakeTracking(Mistake $mistake, bool $isCorrect, string $mode = 'quiz'): array
    {
        return DB::transaction(function () use ($mistake, $isCorrect, $mode) {
            // Update streak based on correctness
            if ($isCorrect) {
                $mistake->correct_streak = ($mistake->correct_streak ?? 0) + 1;
            } else {
                $mistake->correct_streak = 0; // Reset streak on incorrect answer
            }
            
            // Update last attempt date
            $mistake->last_attempt_date = now();
            
            // Update mode-specific counters
            if ($mode === 'quick') {
                $mistake->quick_correct_count = ($mistake->quick_correct_count ?? 0) + ($isCorrect ? 1 : 0);
            } elseif ($mode === 'quiz') {
                $mistake->quiz_correct_count = ($mistake->quiz_correct_count ?? 0) + ($isCorrect ? 1 : 0);
            }
            
            $mistake->save();
            
            // Clear cache
            $this->clearMasteryCache($mistake);
            
            // Get updated mastery status
            $masteryStatus = $this->getMasteryStatus($mistake, $mode);
            
            logger()->info('Mistake tracking updated', [
                'mistake_id' => $mistake->id,
                'user_id' => $mistake->user_id,
                'question_id' => $mistake->question_id,
                'is_correct' => $isCorrect,
                'mode' => $mode,
                'new_streak' => $mistake->correct_streak,
                'is_mastered' => $masteryStatus['is_mastered'],
                'updated_at' => now()
            ]);
            
            return $masteryStatus;
        });
    }

    /**
     * Get user's mastered mistakes count
     */
    public function getUserMasteredCount(int $userId, string $mode = 'quiz'): int
    {
        $cacheKey = "user_mastered_mistakes_{$userId}_{$mode}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $mode) {
            $threshold = $this->getStreakThreshold($mode);
            
            return Mistake::where('user_id', $userId)
                ->where('correct_streak', '>=', $threshold)
                ->count();
        });
    }

    /**
     * Get user's unmastered mistakes
     */
    public function getUserUnmasteredMistakes(int $userId, string $mode = 'quiz', int $limit = null): array
    {
        $cacheKey = "user_unmastered_mistakes_{$userId}_{$mode}_" . ($limit ?? 'all');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $mode, $limit) {
            $threshold = $this->getStreakThreshold($mode);
            
            $query = Mistake::where('user_id', $userId)
                ->where('correct_streak', '<', $threshold)
                ->where('skipped', false)
                ->with(['question'])
                ->orderBy('last_attempt_date', 'desc');
                
            if ($limit) {
                $query->limit($limit);
            }
            
            $mistakes = $query->get();
            
            return $mistakes->map(function ($mistake) use ($mode) {
                $masteryStatus = $this->getMasteryStatus($mistake, $mode);
                
                return [
                    'mistake_id' => $mistake->id,
                    'question_id' => $mistake->question_id,
                    'question_text' => $mistake->question_text,
                    'correct_answer' => $mistake->correct_answer,
                    'submitted_answer' => $mistake->submitted_answer,
                    'mastery_status' => $masteryStatus,
                    'last_attempt_date' => $mistake->last_attempt_date
                ];
            })->toArray();
        });
    }

    /**
     * Get mistakes ready for review (not mastered, not recently attempted)
     */
    public function getMistakesForReview(int $userId, string $mode = 'quiz', int $limit = 20): array
    {
        $cacheKey = "mistakes_for_review_{$userId}_{$mode}_{$limit}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $mode, $limit) {
            $threshold = $this->getStreakThreshold($mode);
            $reviewCutoff = now()->subHours(24); // Don't review mistakes attempted in last 24h
            
            $mistakes = Mistake::where('user_id', $userId)
                ->where('correct_streak', '<', $threshold)
                ->where('skipped', false)
                ->where(function ($query) use ($reviewCutoff) {
                    $query->where('last_attempt_date', '<', $reviewCutoff)
                          ->orWhereNull('last_attempt_date');
                })
                ->with(['question.topic.course'])
                ->orderBy('last_attempt_date', 'asc')
                ->limit($limit)
                ->get();
                
            return $mistakes->map(function ($mistake) use ($mode) {
                $masteryStatus = $this->getMasteryStatus($mistake, $mode);
                
                return [
                    'mistake_id' => $mistake->id,
                    'question_id' => $mistake->question_id,
                    'question' => $mistake->question,
                    'mastery_status' => $masteryStatus,
                    'topic_name' => $mistake->question->topic->name ?? 'Unknown',
                    'course_name' => $mistake->question->topic->course->name ?? 'Unknown',
                    'priority_score' => $this->calculatePriorityScore($mistake, $masteryStatus)
                ];
            })->sortByDesc('priority_score')->values()->toArray();
        });
    }

    /**
     * Calculate priority score for mistake review
     */
    private function calculatePriorityScore(Mistake $mistake, array $masteryStatus): float
    {
        $score = 0;
        
        // Higher priority for mistakes with more attempts but still not mastered
        $score += ($mistake->quiz_correct_count ?? 0) * 0.5;
        $score += ($mistake->quick_correct_count ?? 0) * 0.3;
        
        // Higher priority for older mistakes
        if ($mistake->last_attempt_date) {
            $daysSinceAttempt = now()->diffInDays($mistake->last_attempt_date);
            $score += min($daysSinceAttempt * 0.1, 5); // Cap at 5 points
        } else {
            $score += 10; // Never attempted gets highest priority
        }
        
        // Lower priority for mistakes close to mastery
        $remainingCorrect = $masteryStatus['remaining_correct'];
        $score -= $remainingCorrect * 2;
        
        return max(0, $score);
    }

    /**
     * Mark mistake as skipped
     */
    public function skipMistake(Mistake $mistake): bool
    {
        $mistake->update(['skipped' => true]);
        $this->clearMasteryCache($mistake);
        
        logger()->info('Mistake marked as skipped', [
            'mistake_id' => $mistake->id,
            'user_id' => $mistake->user_id,
            'question_id' => $mistake->question_id,
            'skipped_at' => now()
        ]);
        
        return true;
    }

    /**
     * Unskip mistake
     */
    public function unskipMistake(Mistake $mistake): bool
    {
        $mistake->update(['skipped' => false]);
        $this->clearMasteryCache($mistake);
        
        logger()->info('Mistake unskipped', [
            'mistake_id' => $mistake->id,
            'user_id' => $mistake->user_id,
            'question_id' => $mistake->question_id,
            'unskipped_at' => now()
        ]);
        
        return true;
    }

    /**
     * Get mastery statistics for user
     */
    public function getUserMasteryStats(int $userId): array
    {
        $cacheKey = "user_mastery_stats_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $totalMistakes = Mistake::where('user_id', $userId)->count();
            $skippedMistakes = Mistake::where('user_id', $userId)->where('skipped', true)->count();
            
            $stats = [];
            foreach (['quiz', 'quick', 'tutor'] as $mode) {
                $threshold = $this->getStreakThreshold($mode);
                $masteredCount = Mistake::where('user_id', $userId)
                    ->where('correct_streak', '>=', $threshold)
                    ->count();
                    
                $stats[$mode] = [
                    'mastered_count' => $masteredCount,
                    'unmastered_count' => $totalMistakes - $masteredCount - $skippedMistakes,
                    'mastery_percentage' => $totalMistakes > 0 ? ($masteredCount / $totalMistakes) * 100 : 0
                ];
            }
            
            return [
                'total_mistakes' => $totalMistakes,
                'skipped_mistakes' => $skippedMistakes,
                'active_mistakes' => $totalMistakes - $skippedMistakes,
                'by_mode' => $stats,
                'computed_at' => now()
            ];
        });
    }

    /**
     * Sync legacy mastered field with computed mastery
     */
    public function syncLegacyMasteredField(Mistake $mistake, string $mode = 'quiz'): bool
    {
        $masteryStatus = $this->getMasteryStatus($mistake, $mode);
        $computedMastered = $masteryStatus['is_mastered'];
        
        if ($mistake->mastered !== $computedMastered) {
            $oldValue = $mistake->mastered;
            $mistake->update(['mastered' => $computedMastered]);
            
            logger()->warning('Legacy mastered field synced with computed value', [
                'mistake_id' => $mistake->id,
                'user_id' => $mistake->user_id,
                'question_id' => $mistake->question_id,
                'old_mastered' => $oldValue,
                'new_mastered' => $computedMastered,
                'current_streak' => $mistake->correct_streak,
                'mode' => $mode,
                'synced_at' => now()
            ]);
            
            return true;
        }
        
        return false; // No sync needed
    }

    /**
     * Verify data consistency between legacy and computed mastery
     */
    public function verifyMasteryConsistency(int $userId, string $mode = 'quiz'): array
    {
        $mistakes = Mistake::where('user_id', $userId)->get();
        $inconsistencies = [];
        
        foreach ($mistakes as $mistake) {
            $masteryStatus = $this->getMasteryStatus($mistake, $mode);
            $computedMastered = $masteryStatus['is_mastered'];
            
            if ($mistake->mastered !== $computedMastered) {
                $inconsistencies[] = [
                    'mistake_id' => $mistake->id,
                    'question_id' => $mistake->question_id,
                    'legacy_mastered' => $mistake->mastered,
                    'computed_mastered' => $computedMastered,
                    'current_streak' => $mistake->correct_streak,
                    'required_streak' => $masteryStatus['required_streak']
                ];
            }
        }
        
        return [
            'consistent' => empty($inconsistencies),
            'total_checked' => $mistakes->count(),
            'inconsistencies_count' => count($inconsistencies),
            'inconsistencies' => $inconsistencies,
            'checked_at' => now()
        ];
    }

    /**
     * Clear mastery cache for mistake
     */
    public function clearMasteryCache(Mistake $mistake): void
    {
        foreach (['quiz', 'quick', 'tutor'] as $mode) {
            Cache::forget("mistake_mastery_{$mistake->id}_{$mode}");
        }
        
        // Clear user-level caches
        Cache::forget("user_mastered_mistakes_{$mistake->user_id}_quiz");
        Cache::forget("user_mastered_mistakes_{$mistake->user_id}_quick");
        Cache::forget("user_mastered_mistakes_{$mistake->user_id}_tutor");
        Cache::forget("user_mastery_stats_{$mistake->user_id}");
        
        // Clear review caches
        foreach (['quiz', 'quick', 'tutor'] as $mode) {
            for ($limit = 10; $limit <= 50; $limit += 10) {
                Cache::forget("mistakes_for_review_{$mistake->user_id}_{$mode}_{$limit}");
                Cache::forget("user_unmastered_mistakes_{$mistake->user_id}_{$mode}_{$limit}");
            }
            Cache::forget("user_unmastered_mistakes_{$mistake->user_id}_{$mode}_all");
        }
    }

    /**
     * Bulk mastery check for multiple mistakes
     */
    public function getBulkMasteryStatus(array $mistakeIds, string $mode = 'quiz'): array
    {
        $results = [];
        $mistakes = Mistake::whereIn('id', $mistakeIds)->get()->keyBy('id');
        
        foreach ($mistakeIds as $mistakeId) {
            if (isset($mistakes[$mistakeId])) {
                $results[$mistakeId] = $this->getMasteryStatus($mistakes[$mistakeId], $mode);
            }
        }
        
        return $results;
    }
}