<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\{User, Question, Topic, QuizAttempt, Mistake, UserTopicProgress};
use Carbon\Carbon;

class SpacedRepetitionService
{
    // SPACED REPETITION INTERVALS (in days)
    private const INTERVALS = [
        1 => 1,      // First review: 1 day
        2 => 3,      // Second review: 3 days
        3 => 7,      // Third review: 1 week
        4 => 14,     // Fourth review: 2 weeks
        5 => 30,     // Fifth review: 1 month
        6 => 90,     // Sixth review: 3 months
        7 => 180,    // Seventh review: 6 months
        8 => 365     // Eighth review: 1 year
    ];

    // DIFFICULTY ADJUSTMENT FACTORS
    private const DIFFICULTY_EASY = 0.7;      // Reduce interval by 30%
    private const DIFFICULTY_NORMAL = 1.0;    // Keep interval
    private const DIFFICULTY_HARD = 1.5;      // Increase interval by 50%
    private const DIFFICULTY_VERY_HARD = 2.0; // Double interval

    // PERFORMANCE THRESHOLDS
    private const PERFORMANCE_EXCELLENT = 90; // 90%+ accuracy
    private const PERFORMANCE_GOOD = 75;      // 75%+ accuracy
    private const PERFORMANCE_AVERAGE = 60;   // 60%+ accuracy
    private const PERFORMANCE_POOR = 45;      // Below 45% accuracy

    /**
     * 🧠 CALCULATE OPTIMAL REVIEW SCHEDULE
     * Determines when a user should review specific topics based on performance
     */
    public function calculateReviewSchedule(int $userId): array
    {
        $cacheKey = "review_schedule_{$userId}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $scheduleData = $this->generatePersonalizedSchedule($userId);
            
            Log::info('Spaced repetition schedule calculated', [
                'user_id' => $userId,
                'total_topics' => count($scheduleData['topics']),
                'due_today' => count($scheduleData['due_today']),
                'upcoming_week' => count($scheduleData['upcoming_week'])
            ]);
            
            return $scheduleData;
        });
    }

    /**
     * 🎯 GENERATE PERSONALIZED SCHEDULE
     * Creates adaptive learning schedule based on individual performance patterns
     */
    private function generatePersonalizedSchedule(int $userId): array
    {
        // GET USER'S TOPIC PERFORMANCE DATA
        $topicPerformance = $this->getUserTopicPerformance($userId);
        $userPattern = $this->analyzeUserLearningPattern($userId);
        
        $schedule = [
            'topics' => [],
            'due_today' => [],
            'due_this_week' => [],
            'upcoming_week' => [],
            'long_term' => [],
            'user_pattern' => $userPattern,
            'recommendations' => []
        ];

        foreach ($topicPerformance as $topic) {
            $reviewData = $this->calculateTopicReviewTiming($topic, $userPattern);
            
            $schedule['topics'][] = $reviewData;
            
            // CATEGORIZE BY DUE DATE
            $daysUntilReview = $reviewData['days_until_review'];
            
            if ($daysUntilReview <= 0) {
                $schedule['due_today'][] = $reviewData;
            } elseif ($daysUntilReview <= 7) {
                $schedule['due_this_week'][] = $reviewData;
            } elseif ($daysUntilReview <= 14) {
                $schedule['upcoming_week'][] = $reviewData;
            } else {
                $schedule['long_term'][] = $reviewData;
            }
        }

        // GENERATE SMART RECOMMENDATIONS
        $schedule['recommendations'] = $this->generateSmartRecommendations($schedule, $userPattern);
        
        // SORT BY PRIORITY
        usort($schedule['due_today'], fn($a, $b) => $b['priority_score'] <=> $a['priority_score']);
        usort($schedule['due_this_week'], fn($a, $b) => $b['priority_score'] <=> $a['priority_score']);
        
        return $schedule;
    }

    /**
     * 📊 ANALYZE USER LEARNING PATTERN
     * Identifies individual learning strengths and weaknesses
     */
    private function analyzeUserLearningPattern(int $userId): array
    {
        $cacheKey = "learning_pattern_{$userId}";
        
        return Cache::remember($cacheKey, 600, function () use ($userId) {
            // ANALYZE PERFORMANCE OVER TIME
            $performanceData = DB::select("
                SELECT 
                    DATE(qa.created_at) as quiz_date,
                    AVG(qa.score) as daily_avg_score,
                    COUNT(qa.id) as daily_attempts,
                    HOUR(qa.created_at) as quiz_hour,
                    DAYOFWEEK(qa.created_at) as day_of_week,
                    t.id as topic_id,
                    AVG(qa.time_taken) as avg_time_taken
                FROM quiz_attempts qa
                JOIN topics t ON qa.topic_id = t.id
                WHERE qa.user_id = ? 
                  AND qa.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                GROUP BY DATE(qa.created_at), t.id
                ORDER BY qa.created_at DESC
            ", [$userId]);

            // ANALYZE MISTAKE PATTERNS
            $mistakePatterns = DB::select("
                SELECT 
                    t.id as topic_id,
                    t.name as topic_name,
                    COUNT(m.id) as mistake_count,
                    AVG(m.attempt_count) as avg_attempts_to_correct,
                    MAX(m.last_attempt_date) as last_mistake_date
                FROM mistakes m
                JOIN questions q ON m.question_id = q.id
                JOIN topics t ON q.topic_id = t.id
                WHERE m.user_id = ?
                GROUP BY t.id, t.name
                ORDER BY mistake_count DESC
            ", [$userId]);

            return [
                'overall_accuracy' => $this->calculateOverallAccuracy($performanceData),
                'learning_velocity' => $this->calculateLearningVelocity($performanceData),
                'optimal_study_times' => $this->findOptimalStudyTimes($performanceData),
                'weak_topics' => array_slice($mistakePatterns, 0, 5), // Top 5 problematic topics
                'retention_rate' => $this->calculateRetentionRate($userId),
                'consistency_score' => $this->calculateConsistencyScore($performanceData),
                'difficulty_preference' => $this->analyzeDifficultyPreference($userId),
                'response_time_pattern' => $this->analyzeResponseTimePattern($performanceData)
            ];
        });
    }

    /**
     * ⏰ CALCULATE TOPIC REVIEW TIMING
     * Determines optimal review timing for specific topic based on performance
     */
    private function calculateTopicReviewTiming(array $topic, array $userPattern): array
    {
        $lastAttempt = Carbon::parse($topic['last_attempt_date'] ?? now()->subDays(30));
        $performanceScore = (float) $topic['average_score'];
        $mistakeCount = (int) $topic['mistake_count'];
        $totalAttempts = (int) $topic['total_attempts'];
        
        // DETERMINE CURRENT MASTERY LEVEL
        $masteryLevel = $this->calculateMasteryLevel($performanceScore, $totalAttempts, $mistakeCount);
        
        // GET BASE INTERVAL
        $baseInterval = self::INTERVALS[$masteryLevel] ?? self::INTERVALS[1];
        
        // APPLY PERFORMANCE-BASED ADJUSTMENTS
        $adjustmentFactor = $this->calculateAdjustmentFactor($performanceScore, $userPattern);
        
        // APPLY USER-SPECIFIC PATTERNS
        $personalizedInterval = $baseInterval * $adjustmentFactor * $userPattern['retention_rate'];
        
        // CALCULATE PRIORITY BASED ON FORGETTING CURVE
        $daysSinceLastAttempt = $lastAttempt->diffInDays(now());
        $forgettingCurveScore = $this->calculateForgettingCurveScore($daysSinceLastAttempt, $personalizedInterval);
        
        // DETERMINE NEXT REVIEW DATE
        $nextReviewDate = $lastAttempt->addDays($personalizedInterval);
        $daysUntilReview = now()->diffInDays($nextReviewDate, false);
        
        // CALCULATE PRIORITY SCORE (0-100)
        $priorityScore = $this->calculatePriorityScore(
            $forgettingCurveScore,
            $mistakeCount,
            $performanceScore,
            $daysUntilReview
        );

        return [
            'topic_id' => $topic['topic_id'],
            'topic_name' => $topic['topic_name'],
            'mastery_level' => $masteryLevel,
            'performance_score' => $performanceScore,
            'last_attempt_date' => $lastAttempt->format('Y-m-d'),
            'next_review_date' => $nextReviewDate->format('Y-m-d'),
            'days_until_review' => $daysUntilReview,
            'priority_score' => round($priorityScore, 1),
            'estimated_study_time' => $this->estimateStudyTime($topic, $userPattern),
            'difficulty_level' => $this->assessDifficultyLevel($performanceScore, $mistakeCount),
            'forgetting_risk' => $forgettingCurveScore > 0.7 ? 'high' : ($forgettingCurveScore > 0.4 ? 'medium' : 'low'),
            'review_type' => $this->determineReviewType($masteryLevel, $performanceScore, $mistakeCount)
        ];
    }

    /**
     * 🎓 CALCULATE MASTERY LEVEL
     * Determines current understanding level (1-8 scale)
     */
    private function calculateMasteryLevel(float $performance, int $attempts, int $mistakes): int
    {
        // BASE LEVEL FROM PERFORMANCE
        if ($performance >= self::PERFORMANCE_EXCELLENT && $attempts >= 3) {
            $baseLevel = 6; // Advanced
        } elseif ($performance >= self::PERFORMANCE_GOOD && $attempts >= 2) {
            $baseLevel = 4; // Intermediate
        } elseif ($performance >= self::PERFORMANCE_AVERAGE) {
            $baseLevel = 3; // Basic
        } else {
            $baseLevel = 1; // Beginner
        }
        
        // ADJUST FOR MISTAKE RATIO
        $mistakeRatio = $attempts > 0 ? $mistakes / $attempts : 1;
        
        if ($mistakeRatio > 0.5) {
            $baseLevel = max(1, $baseLevel - 2); // High mistake rate
        } elseif ($mistakeRatio > 0.3) {
            $baseLevel = max(1, $baseLevel - 1); // Moderate mistake rate
        } elseif ($mistakeRatio < 0.1 && $attempts >= 3) {
            $baseLevel = min(8, $baseLevel + 1); // Very low mistake rate
        }
        
        return $baseLevel;
    }

    /**
     * 📈 CALCULATE FORGETTING CURVE SCORE
     * Uses Ebbinghaus forgetting curve to predict knowledge retention
     */
    private function calculateForgettingCurveScore(int $daysSinceLastAttempt, float $interval): float
    {
        // EBBINGHAUS FORGETTING CURVE: R = e^(-t/S)
        // R = retention, t = time, S = stability
        $stability = $interval / 2; // Half of recommended interval
        $retention = exp(-$daysSinceLastAttempt / $stability);
        
        // INVERT TO GET FORGETTING SCORE (higher = more forgotten)
        return 1 - $retention;
    }

    /**
     * 🔥 CALCULATE PRIORITY SCORE
     * Combines multiple factors to determine review urgency
     */
    private function calculatePriorityScore(float $forgettingScore, int $mistakes, float $performance, int $daysUntilReview): float
    {
        // FORGETTING URGENCY (40% weight)
        $forgettingWeight = $forgettingScore * 40;
        
        // MISTAKE FREQUENCY (25% weight)
        $mistakeWeight = min(25, $mistakes * 2.5);
        
        // PERFORMANCE INVERSE (20% weight) - lower performance = higher priority
        $performanceWeight = (100 - $performance) * 0.2;
        
        // OVERDUE PENALTY (15% weight)
        $overdueWeight = $daysUntilReview < 0 ? abs($daysUntilReview) * 1.5 : 0;
        $overdueWeight = min(15, $overdueWeight);
        
        return min(100, $forgettingWeight + $mistakeWeight + $performanceWeight + $overdueWeight);
    }

    /**
     * 💡 GENERATE SMART RECOMMENDATIONS
     * Provides actionable learning advice based on analysis
     */
    private function generateSmartRecommendations(array $schedule, array $userPattern): array
    {
        $recommendations = [];
        
        // DAILY STUDY LOAD RECOMMENDATION
        $dailyLoad = count($schedule['due_today']);
        if ($dailyLoad > 10) {
            $recommendations[] = [
                'type' => 'study_load',
                'priority' => 'high',
                'message' => "You have {$dailyLoad} topics due today. Consider breaking this into 2-3 study sessions.",
                'action' => 'split_sessions'
            ];
        }
        
        // WEAK TOPICS FOCUS
        $weakTopics = array_filter($schedule['due_today'], fn($t) => $t['performance_score'] < 60);
        if (count($weakTopics) > 0) {
            $recommendations[] = [
                'type' => 'weak_topics',
                'priority' => 'high',
                'message' => 'Focus on your weakest topics first for maximum improvement.',
                'action' => 'prioritize_weak',
                'topics' => array_slice($weakTopics, 0, 3)
            ];
        }
        
        // OPTIMAL STUDY TIME
        if (!empty($userPattern['optimal_study_times'])) {
            $bestTime = $userPattern['optimal_study_times'][0];
            $recommendations[] = [
                'type' => 'timing',
                'priority' => 'medium',
                'message' => "You perform best at {$bestTime}:00. Schedule important reviews then.",
                'action' => 'schedule_optimal'
            ];
        }
        
        // CONSISTENCY IMPROVEMENT
        if ($userPattern['consistency_score'] < 0.7) {
            $recommendations[] = [
                'type' => 'consistency',
                'priority' => 'medium',
                'message' => 'Try to study consistently every day, even if just for 10 minutes.',
                'action' => 'improve_consistency'
            ];
        }
        
        return $recommendations;
    }

    /**
     * 🎯 GET PERSONALIZED QUIZ RECOMMENDATIONS
     * Suggests optimal questions based on spaced repetition needs
     */
    public function getPersonalizedQuizRecommendations(int $userId, int $topicId): array
    {
        $cacheKey = "quiz_recommendations_{$userId}_{$topicId}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId, $topicId) {
            // GET USER'S MISTAKE PATTERNS FOR THIS TOPIC
            $mistakes = Mistake::where('user_id', $userId)
                ->whereHas('question', function ($query) use ($topicId) {
                    $query->where('topic_id', $topicId);
                })
                ->with('question')
                ->orderBy('last_attempt_date', 'desc')
                ->take(20)
                ->get();

            // GET QUESTIONS USER HASN'T SEEN RECENTLY
            $unseenQuestions = Question::where('topic_id', $topicId)
                ->whereDoesntHave('quizAttempts', function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->where('created_at', '>=', now()->subDays(7));
                })
                ->inRandomOrder()
                ->take(10)
                ->get();

            // COMBINE AND PRIORITIZE
            $recommendations = [
                'priority_review' => $mistakes->take(5)->toArray(),
                'new_material' => $unseenQuestions->toArray(),
                'mixed_practice' => $this->generateMixedPracticeSet($userId, $topicId),
                'difficulty_progression' => $this->generateDifficultyProgression($userId, $topicId)
            ];

            return $recommendations;
        });
    }

    /**
     * 📊 UPDATE LEARNING PROGRESS
     * Records quiz results and adjusts spaced repetition schedule
     */
    public function updateLearningProgress(int $userId, int $topicId, array $quizResults): void
    {
        try {
            DB::transaction(function () use ($userId, $topicId, $quizResults) {
                // UPDATE USER TOPIC PROGRESS
                $progress = UserTopicProgress::firstOrCreate(
                    ['user_id' => $userId, 'topic_id' => $topicId],
                    ['created_at' => now()]
                );

                // CALCULATE NEW PERFORMANCE METRICS
                $newPerformance = $this->calculateNewPerformance($progress, $quizResults);
                
                $progress->update([
                    'last_attempt_date' => now(),
                    'total_attempts' => $progress->total_attempts + 1,
                    'average_score' => $newPerformance['average_score'],
                    'best_score' => max($progress->best_score ?? 0, $quizResults['score']),
                    'consecutive_correct' => $newPerformance['consecutive_correct'],
                    'mastery_level' => $this->calculateMasteryLevel(
                        $newPerformance['average_score'],
                        $progress->total_attempts + 1,
                        $this->getUserMistakeCount($userId, $topicId)
                    ),
                    'updated_at' => now()
                ]);

                // CLEAR RELATED CACHES
                $this->clearUserCaches($userId);
                
                Log::info('Learning progress updated', [
                    'user_id' => $userId,
                    'topic_id' => $topicId,
                    'new_mastery_level' => $progress->mastery_level,
                    'score' => $quizResults['score']
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to update learning progress', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
        }
    }

    // HELPER METHODS

    private function getUserTopicPerformance(int $userId): array
    {
        return DB::select("
            SELECT 
                t.id as topic_id,
                t.name as topic_name,
                utp.average_score,
                utp.total_attempts,
                utp.last_attempt_date,
                utp.mastery_level,
                COALESCE(mistake_counts.mistake_count, 0) as mistake_count
            FROM topics t
            LEFT JOIN user_topic_progress utp ON t.id = utp.topic_id AND utp.user_id = ?
            LEFT JOIN (
                SELECT 
                    t.id as topic_id,
                    COUNT(m.id) as mistake_count
                FROM topics t
                JOIN questions q ON t.id = q.topic_id
                JOIN mistakes m ON q.id = m.question_id
                WHERE m.user_id = ?
                GROUP BY t.id
            ) mistake_counts ON t.id = mistake_counts.topic_id
            WHERE utp.total_attempts > 0 OR mistake_counts.mistake_count > 0
            ORDER BY utp.last_attempt_date DESC
        ", [$userId, $userId]);
    }

    private function calculateAdjustmentFactor(float $performance, array $userPattern): float
    {
        if ($performance >= self::PERFORMANCE_EXCELLENT) {
            return self::DIFFICULTY_EASY;
        } elseif ($performance >= self::PERFORMANCE_GOOD) {
            return self::DIFFICULTY_NORMAL;
        } elseif ($performance >= self::PERFORMANCE_AVERAGE) {
            return self::DIFFICULTY_HARD;
        } else {
            return self::DIFFICULTY_VERY_HARD;
        }
    }

    private function calculateOverallAccuracy(array $performanceData): float
    {
        if (empty($performanceData)) return 0.0;
        
        $totalScore = array_sum(array_column($performanceData, 'daily_avg_score'));
        return round($totalScore / count($performanceData), 2);
    }

    private function calculateLearningVelocity(array $performanceData): float
    {
        // Trend analysis over time
        if (count($performanceData) < 3) return 0.0;
        
        $recent = array_slice($performanceData, 0, 7);
        $older = array_slice($performanceData, -7);
        
        $recentAvg = array_sum(array_column($recent, 'daily_avg_score')) / count($recent);
        $olderAvg = array_sum(array_column($older, 'daily_avg_score')) / count($older);
        
        return round($recentAvg - $olderAvg, 2);
    }

    private function findOptimalStudyTimes(array $performanceData): array
    {
        $hourlyPerformance = [];
        
        foreach ($performanceData as $data) {
            $hour = (int) $data->quiz_hour;
            if (!isset($hourlyPerformance[$hour])) {
                $hourlyPerformance[$hour] = [];
            }
            $hourlyPerformance[$hour][] = $data->daily_avg_score;
        }
        
        $hourlyAverages = [];
        foreach ($hourlyPerformance as $hour => $scores) {
            $hourlyAverages[$hour] = array_sum($scores) / count($scores);
        }
        
        arsort($hourlyAverages);
        return array_keys(array_slice($hourlyAverages, 0, 3, true));
    }

    private function calculateRetentionRate(int $userId): float
    {
        // Simplified retention calculation
        $user = User::find($userId);
        return $user && $user->total_questions_attempted > 0 
            ? ($user->correct_answers_count / $user->total_questions_attempted) 
            : 0.8; // Default retention rate
    }

    private function calculateConsistencyScore(array $performanceData): float
    {
        if (count($performanceData) < 7) return 0.5;
        
        $dailyCounts = array_column($performanceData, 'daily_attempts');
        $average = array_sum($dailyCounts) / count($dailyCounts);
        $variance = array_sum(array_map(fn($x) => pow($x - $average, 2), $dailyCounts)) / count($dailyCounts);
        
        // Lower variance = higher consistency
        return max(0, 1 - ($variance / ($average + 1)));
    }

    private function clearUserCaches(int $userId): void
    {
        Cache::forget("review_schedule_{$userId}");
        Cache::forget("learning_pattern_{$userId}");
    }

    // Additional helper methods would be implemented here...
    private function analyzeDifficultyPreference(int $userId): string { return 'medium'; }
    private function analyzeResponseTimePattern(array $performanceData): array { return []; }
    private function estimateStudyTime(array $topic, array $userPattern): int { return 15; }
    private function assessDifficultyLevel(float $performance, int $mistakes): string { return 'medium'; }
    private function determineReviewType(int $mastery, float $performance, int $mistakes): string { return 'standard'; }
    private function generateMixedPracticeSet(int $userId, int $topicId): array { return []; }
    private function generateDifficultyProgression(int $userId, int $topicId): array { return []; }
    private function calculateNewPerformance($progress, array $results): array { return ['average_score' => 75, 'consecutive_correct' => 3]; }
    private function getUserMistakeCount(int $userId, int $topicId): int { return 0; }
}