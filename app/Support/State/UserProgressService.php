<?php

namespace App\Support\State;

use App\Models\User;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Single Source of Truth for User Progress State
 * 
 * Replaces split brain logic between:
 * - users.correct_answers_count (integer - cached)
 * - quiz_attempts.correct_answers (integer - per attempt)
 * - quiz_attempts.score (integer - same as correct_answers)
 * - Real-time calculation from quiz_attempts table
 */
class UserProgressService
{
    const CACHE_TTL = 300; // 5 minutes

    /**
     * Get definitive user progress statistics
     * 
     * @param int $userId
     * @return array
     */
    public function getUserProgress(int $userId): array
    {
        $cacheKey = "user_progress_ssot_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            // Source of truth: quiz_attempts table using 'score' field
            $stats = DB::table('quiz_attempts')
                ->where('user_id', $userId)
                ->selectRaw('
                    SUM(score) as total_correct_answers,
                    SUM(total_questions) as total_questions_attempted,
                    COUNT(*) as total_attempts,
                    AVG(percentage_grade) as average_percentage,
                    MAX(percentage_grade) as best_percentage,
                    SUM(duration_seconds) as total_study_time_seconds
                ')
                ->first();

            $totalCorrect = (int) ($stats->total_correct_answers ?? 0);
            $totalAttempted = (int) ($stats->total_questions_attempted ?? 0);
            $totalAttempts = (int) ($stats->total_attempts ?? 0);
            $averagePercentage = (float) ($stats->average_percentage ?? 0);
            $bestPercentage = (float) ($stats->best_percentage ?? 0);
            $totalStudyTime = (int) ($stats->total_study_time_seconds ?? 0);

            // Calculate accuracy
            $accuracy = $totalAttempted > 0 ? ($totalCorrect / $totalAttempted) * 100 : 0;

            // Get streak information
            $streakInfo = $this->calculateStudyStreak($userId);

            // Calculate XP based on consistent formula
            $xp = $this->calculateXP($totalCorrect, $totalAttempted, $streakInfo['streak_days']);

            return [
                'total_correct_answers' => $totalCorrect,
                'total_questions_attempted' => $totalAttempted,
                'total_attempts' => $totalAttempts,
                'accuracy_percentage' => round($accuracy, 2),
                'average_percentage' => round($averagePercentage, 2),
                'best_percentage' => round($bestPercentage, 2),
                'total_study_time_seconds' => $totalStudyTime,
                'study_streak_days' => $streakInfo['streak_days'],
                'last_study_date' => $streakInfo['last_study_date'],
                'xp' => $xp,
                'level' => $this->calculateLevel($xp),
                'computed_at' => now()
            ];
        });
    }

    /**
     * Get total correct answers (single source of truth)
     */
    public function getTotalCorrectAnswers(int $userId): int
    {
        return $this->getUserProgress($userId)['total_correct_answers'];
    }

    /**
     * Get user accuracy percentage
     */
    public function getAccuracyPercentage(int $userId): float
    {
        return $this->getUserProgress($userId)['accuracy_percentage'];
    }

    /**
     * Get user XP
     */
    public function getUserXP(int $userId): int
    {
        return $this->getUserProgress($userId)['xp'];
    }

    /**
     * Get user level
     */
    public function getUserLevel(int $userId): int
    {
        return $this->getUserProgress($userId)['level'];
    }

    /**
     * Calculate study streak from quiz attempts
     */
    public function calculateStudyStreak(int $userId): array
    {
        $cacheKey = "user_streak_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            // Get the user's latest quiz attempt
            $lastAttempt = QuizAttempt::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->first();
                
            if (!$lastAttempt) {
                return [
                    'streak_days' => 0,
                    'last_study_date' => null
                ];
            }
            
            $lastAttemptDate = $lastAttempt->created_at->startOfDay();
            $today = now()->startOfDay();
            $yesterday = now()->subDay()->startOfDay();
            
            // If last attempt was today or yesterday, calculate consecutive days
            if ($lastAttemptDate->equalTo($today) || $lastAttemptDate->equalTo($yesterday)) {
                $streak = $this->calculateConsecutiveDays($userId);
            } else {
                // Streak broken - reset to 0
                $streak = 0;
            }
            
            return [
                'streak_days' => max(0, $streak),
                'last_study_date' => $lastAttempt->created_at
            ];
        });
    }

    /**
     * Calculate consecutive study days
     */
    private function calculateConsecutiveDays(int $userId): int
    {
        $streak = 0;
        $currentDate = now()->startOfDay();
        
        while (true) {
            // Check if user had quiz activity on this day
            $hadActivity = QuizAttempt::where('user_id', $userId)
                ->whereDate('created_at', $currentDate)
                ->exists();
                
            if (!$hadActivity) {
                break; // Break the streak if no activity on this day
            }
            
            $streak++;
            $currentDate->subDay(); // Go back one day
            
            // Safety limit to prevent infinite loops
            if ($streak > 365) {
                break;
            }
        }
        
        return $streak;
    }

    /**
     * Calculate XP based on consistent formula
     */
    public function calculateXP(int $correctAnswers, int $totalQuestions, int $streakDays): int
    {
        // Base XP from correct answers (5 points per correct answer)
        $baseXP = $correctAnswers * 5;
        
        // Accuracy bonus (0-100% accuracy converted to 0-2 points per correct answer)
        $accuracyBonus = $totalQuestions > 0 
            ? floor(($correctAnswers * 100.0 / $totalQuestions) * 0.02 * $correctAnswers)
            : 0;
            
        // Streak bonus (each day is worth 10 points)
        $streakBonus = $streakDays * 10;
        
        return $baseXP + $accuracyBonus + $streakBonus;
    }

    /**
     * Calculate level from XP
     */
    public function calculateLevel(int $xp): int
    {
        // Every 1000 XP is a new level, starting at level 1
        return floor($xp / 1000) + 1;
    }

    /**
     * Get XP needed for next level
     */
    public function getXPForNextLevel(int $userId): int
    {
        $currentXP = $this->getUserXP($userId);
        $nextLevelTotalXP = ceil($currentXP / 1000) * 1000;
        return max(0, $nextLevelTotalXP - $currentXP);
    }

    /**
     * Update user progress after quiz attempt
     */
    public function updateProgressAfterQuiz(int $userId, int $correctAnswers, int $totalQuestions): array
    {
        // Clear cache to force recalculation
        $this->clearProgressCache($userId);
        
        // Get updated progress
        $progress = $this->getUserProgress($userId);
        
        logger()->info('User progress updated after quiz', [
            'user_id' => $userId,
            'quiz_correct' => $correctAnswers,
            'quiz_total' => $totalQuestions,
            'new_total_correct' => $progress['total_correct_answers'],
            'new_xp' => $progress['xp'],
            'new_level' => $progress['level']
        ]);
        
        return $progress;
    }

    /**
     * Sync cached user fields with computed values
     */
    public function syncUserCachedFields(int $userId): bool
    {
        $progress = $this->getUserProgress($userId);
        
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        
        $updates = [];
        
        // Check if cached values need updating
        if ($user->correct_answers_count !== $progress['total_correct_answers']) {
            $updates['correct_answers_count'] = $progress['total_correct_answers'];
        }
        
        if ($user->total_questions_attempted !== $progress['total_questions_attempted']) {
            $updates['total_questions_attempted'] = $progress['total_questions_attempted'];
        }
        
        if ($user->xp !== $progress['xp']) {
            $updates['xp'] = $progress['xp'];
        }
        
        if ($user->level !== $progress['level']) {
            $updates['level'] = $progress['level'];
        }
        
        if ($user->study_streak_days !== $progress['study_streak_days']) {
            $updates['study_streak_days'] = $progress['study_streak_days'];
        }
        
        if ($user->study_time_seconds !== $progress['total_study_time_seconds']) {
            $updates['study_time_seconds'] = $progress['total_study_time_seconds'];
        }
        
        if (!empty($updates)) {
            $user->update($updates);
            
            logger()->info('User cached fields synced', [
                'user_id' => $userId,
                'updates' => $updates,
                'synced_at' => now()
            ]);
            
            return true;
        }
        
        return false; // No sync needed
    }

    /**
     * Verify data consistency between cached and computed values
     */
    public function verifyDataConsistency(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return ['error' => 'User not found'];
        }
        
        $computed = $this->getUserProgress($userId);
        
        $inconsistencies = [];
        
        if ($user->correct_answers_count !== $computed['total_correct_answers']) {
            $inconsistencies['correct_answers_count'] = [
                'cached' => $user->correct_answers_count,
                'computed' => $computed['total_correct_answers'],
                'diff' => $computed['total_correct_answers'] - $user->correct_answers_count
            ];
        }
        
        if ($user->total_questions_attempted !== $computed['total_questions_attempted']) {
            $inconsistencies['total_questions_attempted'] = [
                'cached' => $user->total_questions_attempted,
                'computed' => $computed['total_questions_attempted'],
                'diff' => $computed['total_questions_attempted'] - $user->total_questions_attempted
            ];
        }
        
        if ($user->xp !== $computed['xp']) {
            $inconsistencies['xp'] = [
                'cached' => $user->xp,
                'computed' => $computed['xp'],
                'diff' => $computed['xp'] - $user->xp
            ];
        }
        
        return [
            'consistent' => empty($inconsistencies),
            'inconsistencies' => $inconsistencies,
            'checked_at' => now()
        ];
    }

    /**
     * Clear user progress cache
     */
    public function clearProgressCache(int $userId): void
    {
        Cache::forget("user_progress_ssot_{$userId}");
        Cache::forget("user_streak_{$userId}");
        
        // Clear related caches
        Cache::forget("user_profile_optimized_{$userId}_" . now()->format('Y-m-d-H'));
        Cache::forget("user_profile_optimized_{$userId}_" . now()->subHour()->format('Y-m-d-H'));
    }

    /**
     * Get progress comparison between users
     */
    public function compareUserProgress(array $userIds): array
    {
        $comparisons = [];
        
        foreach ($userIds as $userId) {
            $comparisons[$userId] = $this->getUserProgress($userId);
        }
        
        return $comparisons;
    }

    /**
     * Get leaderboard data
     */
    public function getLeaderboard(int $limit = 10): array
    {
        $cacheKey = "progress_leaderboard_{$limit}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            // Get top users by XP from quiz_attempts
            $topUsers = DB::table('quiz_attempts')
                ->select('user_id')
                ->selectRaw('SUM(score) as total_correct')
                ->selectRaw('SUM(total_questions) as total_attempted')
                ->selectRaw('COUNT(*) as total_attempts')
                ->groupBy('user_id')
                ->orderBy('total_correct', 'desc')
                ->limit($limit)
                ->get();
                
            $leaderboard = [];
            
            foreach ($topUsers as $userData) {
                $progress = $this->getUserProgress($userData->user_id);
                $user = User::find($userData->user_id);
                
                if ($user) {
                    $leaderboard[] = [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'total_correct_answers' => $progress['total_correct_answers'],
                        'xp' => $progress['xp'],
                        'level' => $progress['level'],
                        'accuracy_percentage' => $progress['accuracy_percentage'],
                        'study_streak_days' => $progress['study_streak_days']
                    ];
                }
            }
            
            return $leaderboard;
        });
    }
}