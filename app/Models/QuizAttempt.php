<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Support\Facades\Validator;

class QuizAttempt extends Model
{
    use HasFactory;

    protected static array $columnExistenceCache = [];

    /**
     * When true, suppress per-attempt cache invalidation and rely on caller-level invalidation.
     * Helps avoid invalidation fan-out during batch attempt creation (e.g. multi-source quiz).
     */
    protected static bool $deferProgressInvalidation = false;

    protected $fillable = [
        'user_id',
        'topic_id',
        'quiz_type',
        'study_mode',
        'session_type',
        'source',
        'score',
        'total_questions',
        'percentage_grade',
        'duration_seconds',
        'question_data',     // JSON array of {question_id, user_answer, is_correct} for potential review
        'assignment_id',     // Link to assignment if this is an assignment quiz
        'attempt_id'         // Groups multiple sources from same quiz attempt
    ];

    protected $casts = [
        'percentage_grade' => 'float',
        'score' => 'integer',
        'total_questions' => 'integer',
        'duration_seconds' => 'integer',
        'question_data' => 'array',
        'topic_id' => 'integer',
        'assignment_id' => 'integer',
        'attempt_id' => 'integer',
        'session_type' => 'string',
    ];

    protected $attributes = [
        'study_mode' => null,
        'source' => null
    ];

    /**
     * Validation rules for quiz attempts
     */
    public static $rules = [
        'user_id' => 'required|exists:users,id',
        'topic_id' => 'nullable|exists:topics,id',
        'quiz_type' => 'required|in:normal,random,tutor,mistake_review,assignment',
        'study_mode' => 'nullable|in:quiz,tutor,random,mistake_review,assignment',
        'session_type' => 'nullable|string|max:32',
        'source' => 'nullable|string',
        'score' => 'required|integer|min:0',
        'total_questions' => 'required|integer|min:1',
        'percentage_grade' => 'required|numeric|min:0|max:100',
        'duration_seconds' => 'required|integer|min:0',
        'question_data' => 'nullable|array',
        'assignment_id' => 'nullable|exists:assignments,id',
        'attempt_id' => 'nullable|integer|min:1'
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // PHASE 2: Dispatch QuizAttemptCompleted event when quiz attempt is completed
        static::created(function ($quizAttempt) {
            $percentage = ($quizAttempt->score / $quizAttempt->total_questions) * 100;
            
            event(new \App\Events\QuizAttemptCompleted(
                $quizAttempt,
                $quizAttempt->score,
                $quizAttempt->total_questions,
                $percentage
            ));
            
            // CRITICAL FIX: Clear profile cache when new quiz attempt is created
            try {
                $cacheKey = "user_profile_optimized_{$quizAttempt->user_id}_" . now()->format('Y-m-d-H');
                \Illuminate\Support\Facades\Cache::forget($cacheKey);

                // Also clear the previous hour's cache to ensure immediate updates
                $previousHourKey = "user_profile_optimized_{$quizAttempt->user_id}_" . now()->subHour()->format('Y-m-d-H');
                \Illuminate\Support\Facades\Cache::forget($previousHourKey);

                // ULTRA-FAST: Clear performance chart cache immediately for real-time updates
                $performanceCacheKey = "performance_data_optimized_{$quizAttempt->user_id}";
                \Illuminate\Support\Facades\Cache::forget($performanceCacheKey);
                \Illuminate\Support\Facades\Cache::forget("performance_tracking_coach_{$quizAttempt->user_id}");
                
                // 🔥 CRITICAL: Synchronously clear topic progress cache to avoid stale reads during grading
                $studyMode = $quizAttempt->study_mode ?: 'quiz';
                $progressKey = "user_{$quizAttempt->user_id}_topic_{$quizAttempt->topic_id}_progress_{$studyMode}";
                \Illuminate\Support\Facades\Cache::forget($progressKey);
                
                // Clear the Ultra Cache version too (hashed)
                $ultraKey = "ultra_cache:" . md5($progressKey);
                \Illuminate\Support\Facades\Cache::forget($ultraKey);
                
                \Log::info('Profile cache cleared after quiz attempt', [
                    'user_id' => $quizAttempt->user_id,
                    'quiz_type' => $quizAttempt->quiz_type,
                    'study_mode' => $quizAttempt->study_mode,
                    'duration_seconds' => $quizAttempt->duration_seconds
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to clear profile cache', [
                    'error' => $e->getMessage(),
                    'user_id' => $quizAttempt->user_id
                ]);
            }
        });

        // PHASE 2: Dispatch UserProgressUpdated event when progress changes (only for regular quizzes)
        static::saved(function ($quizAttempt) {
            if ($quizAttempt->score !== null && 
                $quizAttempt->total_questions !== null && 
                $quizAttempt->topic_id !== null && 
                $quizAttempt->quiz_type !== 'random') {
                
                $percentage = ($quizAttempt->score / $quizAttempt->total_questions) * 100;
                
                event(new \App\Events\UserProgressUpdated(
                    $quizAttempt->user_id,
                    $quizAttempt->topic_id,
                    $percentage,
                    $quizAttempt->study_mode ?? 'quiz'
                ));
            }
        });

        static::creating(function ($model) {
            // CRITICAL FIX: Ensure assignment_id is nullable
            if (empty($model->assignment_id)) {
                $model->assignment_id = null;
            }
            
            // CRITICAL FIX: Ensure study_mode is set for tutor mode
            if ($model->study_mode === 'tutor' && $model->quiz_type === null) {
                $model->quiz_type = 'normal'; // Tutor mode uses normal quiz_type
            }
            
            // CRITICAL FIX: Ensure source is set for tutor mode
            if ($model->source === null && $model->study_mode === 'tutor') {
                $model->source = 'General';
            }
            
            // CRITICAL FIX: Ensure correct quiz_type for tutor mode
            if ($model->study_mode === 'tutor' && $model->quiz_type !== 'normal') {
                $model->quiz_type = 'normal';
            }
            
            // Validate data before saving with error handling
            try {
                $validator = Validator::make(
                    $model->toArray(),
                    static::$rules
                );

                if ($validator->fails()) {
                    \Log::error('QuizAttempt validation failed', [
                        'errors' => $validator->errors()->toArray(),
                        'data' => $model->toArray()
                    ]);
                    throw new \Illuminate\Validation\ValidationException($validator);
                }
            } catch (\Exception $e) {
                \Log::error('QuizAttempt validation error', [
                    'error' => $e->getMessage(),
                    'data' => $model->toArray()
                ]);                throw $e;
            }

            // Ensure percentage_grade is calculated correctly
            if ($model->total_questions > 0) {
                $model->percentage_grade = round(($model->score / $model->total_questions) * 100, 2);
            }

            // Ensure question_data is properly structured
            if (!is_array($model->question_data)) {
                $model->question_data = [];
            }

            // Add timestamp to question_data for better tracking
            $questionData = $model->question_data;
            foreach ($questionData as $key => $data) {
                if (is_array($data)) {
                    $questionData[$key]['answered_at'] = now()->toIso8601String();                }
            }
            $model->question_data = $questionData;
            
            \Log::info('QuizAttempt being created', [
                'user_id' => $model->user_id,
                'topic_id' => $model->topic_id,
                'quiz_type' => $model->quiz_type,
                'study_mode' => $model->study_mode,
                'source' => $model->source,
                'score' => $model->score,
                'total_questions' => $model->total_questions
            ]);
        });

        static::updating(function ($model) {
            // Prevent updates to critical fields after creation
            $original = $model->getOriginal();
            $immutableFields = ['user_id', 'topic_id', 'quiz_type', 'question_data'];
            
            foreach ($immutableFields as $field) {
                if ($model->isDirty($field)) {
                    throw new \Exception("Cannot modify $field after quiz attempt creation");
                }
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class)->withDefault(); // withDefault() handles null topic_id
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class)->withDefault();
    }

    /**
     * Get commonly missed questions for a user in a topic with error handling
     */
    public static function getCommonMistakes($userId, $topicId, $limit = 10)
    {
        try {
            return DB::table('mistakes')
                ->select([
                    'mistakes.question_id',
                    'questions.question',
                    'questions.correct_answer',
                    'questions.explanation',
                    'questions.topic_id',
                    DB::raw('COUNT(*) as mistake_count')
                ])
                ->join('questions', 'mistakes.question_id', '=', 'questions.id')
                ->where('mistakes.user_id', $userId)
                ->where('questions.topic_id', $topicId)
                ->groupBy('mistakes.question_id', 'questions.question', 'questions.correct_answer', 'questions.explanation', 'questions.topic_id')
                ->orderBy('mistake_count', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            \Log::error('Error getting common mistakes', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Get question data with proper JSON handling
     */
    public function getQuestionDataAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        return [];
    }

    /**
     * Set question data with proper JSON handling
     */
    public function setQuestionDataAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['question_data'] = json_encode($value);
        } else {
            $this->attributes['question_data'] = $value;
        }
    }

    /**
     * Clean up old quiz attempts to prevent database bloat
     */
    public static function cleanupOldAttempts($daysOld = 90)
    {
        try {
            $cutoffDate = now()->subDays($daysOld);
            $deletedCount = self::where('created_at', '<', $cutoffDate)->delete();
            
            \Log::info('Cleaned up old quiz attempts', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString()
            ]);
            
            return $deletedCount;
        } catch (\Exception $e) {
            \Log::error('Error cleaning up old quiz attempts', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get topic progress for a user based on TOTAL QUESTIONS ANSWERED CORRECTLY across ALL SOURCES
     * EFFICIENT IMPLEMENTATION: Total Questions in Topic / Correct Questions in Topic × 100
     * CACHED for 5 minutes for performance
     */
    public static function getTopicProgress($userId, $topicId, $studyMode = 'quiz')
    {
        // CRITICAL: Tutor mode uses separate method - no overall progress calculation
        if ($studyMode === 'tutor') {
            return self::getTutorSourceProgress($userId, $topicId);
        }
        
        // Cache key for this specific user/topic/mode combination
        // 🔥 STANDARDIZED: matches UltraConsolidatedCacheService pattern
        $cacheKey = "user_{$userId}_topic_{$topicId}_progress_{$studyMode}";
        
        // 🔥 REDUCED CACHE: 10 seconds for instant updates
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 10, function () use ($userId, $topicId, $studyMode) {
            return self::calculateTopicProgress($userId, $topicId, $studyMode);
        });
    }

    /**
     * Calculate topic progress (internal method)
     * LATEST ATTEMPT LOGIC: Use the most recent attempt for each source to determine progress
     */
    private static function calculateTopicProgress($userId, $topicId, $studyMode)
    {
        try {
            // STEP 1: Get total questions available in this topic by source (OPTIMIZED: Single query)
            $totalQuestionsBySource = \App\Models\Question::where('topic_id', $topicId)
                ->selectRaw('source, COUNT(*) as total')
                ->groupBy('source')
                ->pluck('total', 'source')
                ->toArray();

            if (empty($totalQuestionsBySource)) {
                return [
                    'overall' => [
                        'score' => 0,
                        'total' => 0,
                        'percentage' => 0
                    ],
                    'sources' => []
                ];
            }

            // STEP 2: Get latest source attempts in ONE query (hot-path optimization)
            $latestAttemptsBySource = self::getLatestAttemptsBySource($userId, $topicId, $studyMode);

            // STEP 3: Calculate progress based on LATEST attempts only
            $sourceProgress = [];
            $totalCorrectQuestions = 0;
            $totalQuestionsInTopic = array_sum($totalQuestionsBySource);

            foreach ($totalQuestionsBySource as $source => $totalQuestions) {
                $latestAttempt = $latestAttemptsBySource[$source] ?? null;

                if ($latestAttempt) {
                    // Use the latest attempt's score and total for this source
                    $correctCount = (int) ($latestAttempt['score'] ?? 0);
                    $attemptedCount = (int) ($latestAttempt['total_questions'] ?? 0);
                    $percentage = $attemptedCount > 0 ? ($correctCount / $attemptedCount) * 100 : 0;
                } else {
                    // No attempts for this source yet
                    $correctCount = 0;
                    $attemptedCount = 0;
                    $percentage = 0;
                }

                $sourceProgress[$source] = [
                    'score' => $correctCount,
                    'total' => $attemptedCount,
                    'percentage' => round($percentage, 2)
                ];

                $totalCorrectQuestions += $correctCount;
            }

            // STEP 4: Calculate overall percentage based on latest attempts
            $overallPercentage = $totalQuestionsInTopic > 0
                ? ($totalCorrectQuestions / $totalQuestionsInTopic) * 100
                : 0;

            return [
                'overall' => [
                    'score' => $totalCorrectQuestions,
                    'total' => $totalQuestionsInTopic,
                    'percentage' => round($overallPercentage, 2)
                ],
                'sources' => $sourceProgress
            ];
        } catch (\Exception $e) {
            \Log::error('Error calculating topic progress', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'study_mode' => $studyMode,
                'error' => $e->getMessage()
            ]);

            return [
                'overall' => [
                    'score' => 0,
                    'total' => 0,
                    'percentage' => 0
                ],
                'sources' => []
            ];
        }
    }

    /**
     * Get latest attempt row per source for a user/topic/mode in a single query.
     *
     * @param int $userId
     * @param int $topicId
     * @param string $studyMode
     * @return array<string, array{source:string,score:int,total_questions:int}>
     */
    private static function getLatestAttemptsBySource(int $userId, int $topicId, string $studyMode): array
    {
        $latestPerSource = self::query()
            ->selectRaw('source, MAX(id) as latest_id')
            ->where('user_id', $userId)
            ->where('topic_id', $topicId)
            ->where('study_mode', $studyMode)
            ->whereNotNull('source')
            ->groupBy('source');

        return self::query()
            ->from('quiz_attempts as qa')
            ->joinSub($latestPerSource, 'latest_per_source', function ($join) {
                $join->on('qa.id', '=', 'latest_per_source.latest_id');
            })
            ->get(['qa.source', 'qa.score', 'qa.total_questions'])
            ->keyBy('source')
            ->toArray();
    }

    /**
     * Get tutor mode source progress - ONLY LATEST ATTEMPT PER SOURCE
     * Does NOT calculate overall progress - tutor mode is source-specific only
     */
    private static function getTutorSourceProgress($userId, $topicId)
    {
        try {
            // Get total questions available in this topic by source
            $totalQuestionsBySource = \App\Models\Question::where('topic_id', $topicId)
                ->selectRaw('source, COUNT(*) as total')
                ->groupBy('source')
                ->pluck('total', 'source')
                ->toArray();

            if (empty($totalQuestionsBySource)) {
                return [
                    'overall' => ['score' => 0, 'total' => 0, 'percentage' => 0],
                    'sources' => []
                ];
            }

            // 🔥 FIX: Get ONLY the LATEST attempt for each source in one query
            $latestAttemptsBySource = self::getLatestAttemptsBySource($userId, $topicId, 'tutor');

            // Calculate progress for each source - USING LATEST ATTEMPT ONLY
            $sourceProgress = [];
            
            foreach ($totalQuestionsBySource as $source => $totalQuestions) {
                $latestAttempt = $latestAttemptsBySource[$source] ?? null;

                if ($latestAttempt) {
                    // Use the latest attempt's score and total for this source
                    $correctCount = (int) ($latestAttempt['score'] ?? 0);
                    $attemptedCount = (int) ($latestAttempt['total_questions'] ?? 0);
                    $percentage = $attemptedCount > 0 ? ($correctCount / $attemptedCount) * 100 : 0;
                } else {
                    // No attempts for this source yet
                    $correctCount = 0;
                    $attemptedCount = 0;
                    $percentage = 0;
                }
                
                $sourceProgress[$source] = [
                    'score' => $correctCount,
                    'total' => $attemptedCount,
                    'percentage' => round($percentage, 2)
                ];
            }

            // TUTOR MODE: No overall progress calculation - return empty overall
            return [
                'overall' => [
                    'score' => 0,
                    'total' => 0,
                    'percentage' => 0
                ],
                'sources' => $sourceProgress
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error calculating tutor source progress', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'overall' => ['score' => 0, 'total' => 0, 'percentage' => 0],
                'sources' => []
            ];
        }
    }

    /**
     * Generate a simple sequential attempt ID for grouping multiple sources
     * Format: Simple incrementing number (1, 2, 3, etc.)
     */
    public static function generateAttemptId($userId, $topicId, $timestamp = null)
    {
        $counterKey = "quiz_attempt_seq:user:{$userId}";

        try {
            $redis = Redis::connection();

            // Seed counter once from DB if key does not exist.
            // IMPORTANT: Avoid evaluating DB max() eagerly on every call.
            if (!$redis->exists($counterKey)) {
                $seed = (int) (self::where('user_id', $userId)->max('attempt_id') ?? 0);
                if ($redis->setnx($counterKey, $seed)) {
                    $redis->expire($counterKey, 2592000); // 30 days
                }
            }

            return (int) $redis->incr($counterKey);
        } catch (\Throwable $e) {
            // Safe fallback if Redis is unavailable
            $maxId = self::where('user_id', $userId)->max('attempt_id') ?? 0;
            return $maxId + 1;
        }
    }

    public static function setProgressInvalidationDeferred(bool $defer): void
    {
        self::$deferProgressInvalidation = $defer;
    }

    public static function hasColumn(string $column): bool
    {
        $column = str_contains($column, '.') ? (string) substr($column, strrpos($column, '.') + 1) : $column;

        if (!array_key_exists($column, static::$columnExistenceCache)) {
            $instance = new static();
            $table = $instance->getTable();

            static::$columnExistenceCache[$column] = Schema::hasTable($table)
                && Schema::hasColumn($table, $column);
        }

        return static::$columnExistenceCache[$column];
    }

    public static function availableColumns(array $columns): array
    {
        return array_values(array_filter($columns, fn (string $column) => static::hasColumn($column)));
    }

    public static function filterPersistableAttributes(array $attributes): array
    {
        $availableColumns = array_flip(static::availableColumns(array_keys($attributes)));

        return array_intersect_key($attributes, $availableColumns);
    }

    /**
     * Create a new quiz attempt with progress tracking
     */
    public static function createWithProgress($data, bool $includeProgress = true)
    {
        try {
            // Generate attempt_id if not provided (for grouping multiple sources)
            if (!isset($data['attempt_id']) || empty($data['attempt_id'])) {
                $data['attempt_id'] = self::generateAttemptId(
                    $data['user_id'],
                    $data['topic_id'] ?? null
                );
            }

            // Calculate percentage for this attempt
            $percentage = $data['total_questions'] > 0
                ? round(($data['score'] / $data['total_questions']) * 100, 2)
                : 0;

            // Ensure question_data is an array of objects with required fields
            $questionData = array_map(function($q) {
                return [
                    'question_id' => $q['question_id'],
                    'user_answer' => $q['user_answer'] ?? null,
                    'is_correct' => $q['is_correct'] ?? false
                ];
            }, $data['question_data'] ?? []);

            // Create attempt with calculated data
            $attempt = self::create(self::filterPersistableAttributes(array_merge($data, [
                'percentage_grade' => $percentage,
                'question_data' => $questionData
            ])));

            // Clear relevant caches unless deferred by caller (batch mode)
            if (!self::$deferProgressInvalidation) {
                try {
                    $cacheService = app(UltraConsolidatedCacheService::class);
                    $cacheService->smartInvalidate('user_progress', $data['user_id'], [
                        'topic_id' => $data['topic_id']
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to clear cache after quiz attempt creation', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Return both the attempt and updated progress
            return [
                'attempt' => $attempt,
                'progress' => $includeProgress
                    ? self::getTopicProgress($data['user_id'], $data['topic_id'], $data['study_mode'] ?? 'quiz')
                    : null,
            ];
        } catch (\Exception $e) {
            \Log::error('Error creating quiz attempt with progress', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create multiple quiz attempts for different sources with the same attempt_id
     * This ensures they get grouped together on the chart
     */
    public static function createMultipleSourceAttempts($baseData, $sourceAttempts)
    {
        try {
            // Generate a single attempt_id for all sources in this quiz
            $attemptId = self::generateAttemptId(
                $baseData['user_id'],
                $baseData['topic_id'] ?? null
            );

            $createdAttempts = [];
            foreach ($sourceAttempts as $sourceData) {
                // Merge base data with source-specific data
                $attemptData = array_merge($baseData, $sourceData, [
                    'attempt_id' => $attemptId
                ]);

                $attempt = self::createWithProgress($attemptData);
                $createdAttempts[] = $attempt['attempt'];
            }

            \Log::info('Created multiple source attempts with shared attempt_id', [
                'attempt_id' => $attemptId,
                'user_id' => $baseData['user_id'],
                'topic_id' => $baseData['topic_id'],
                'sources_count' => count($sourceAttempts)
            ]);

            return [
                'attempts' => $createdAttempts,
                'attempt_id' => $attemptId
            ];
        } catch (\Exception $e) {
            \Log::error('Error creating multiple source attempts', [
                'error' => $e->getMessage(),
                'user_id' => $baseData['user_id'],
                'sources_count' => count($sourceAttempts)
            ]);
            throw $e;
        }
    }

    /**
     * Get assignment-specific topic progress for a user with error handling
     */
    public static function getAssignmentTopicProgress($userId, $topicId, $assignmentId = null, $studyMode = 'assignment')
    {
        try {
            $query = self::where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->where('study_mode', $studyMode);
            
            // CRITICAL FIX: Filter by assignment_id if provided
            if ($assignmentId) {
                $query->where('assignment_id', $assignmentId);
            }
            
            $attempts = $query->get();

            if ($attempts->isEmpty()) {
                return [
                    'overall' => [
                        'score' => 0,                        'total' => 0,
                        'percentage' => 0
                    ],
                    'sources' => []
                ];
            }

            // Get the actual number of questions available in each source
            $sourceQuestionCounts = \App\Models\Question::where('topic_id', $topicId)
                ->whereNotNull('source')
                ->selectRaw('source, COUNT(*) as count')                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray();

            $totalScore = $attempts->sum('score');
            $totalQuestions = $attempts->sum('total_questions');
            $overallPercentage = $totalQuestions > 0 ? ($totalScore / $totalQuestions) * 100 : 0;

            // Group by source and calculate correct answers per source
            $sourceProgress = [];
            foreach ($attempts as $attempt) {
                $source = $attempt->source ?: 'General';
                if (!isset($sourceProgress[$source])) {
                    $sourceProgress[$source] = [
                        'score' => 0,
                        'total' => 0,
                        'percentage' => 0
                    ];
                }
                $sourceProgress[$source]['score'] += $attempt->score;
                $sourceProgress[$source]['total'] += $attempt->total_questions;
            }

            // Calculate percentages for each source based on available questions
            foreach ($sourceProgress as $source => $data) {
                $availableQuestions = $sourceQuestionCounts[$source] ?? 0;
                $completedQuestions = min($data['score'], $availableQuestions); // Cap at available questions
                
                $sourceProgress[$source]['percentage'] = $availableQuestions > 0 
                    ? ($completedQuestions / $availableQuestions) * 100 
                    : 0;
            }

            return [
                'overall' => [
                    'score' => $totalScore,
                    'total' => $totalQuestions,
                    'percentage' => $overallPercentage
                ],
                'sources' => $sourceProgress
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting assignment topic progress', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'assignment_id' => $assignmentId,
                'study_mode' => $studyMode,
                'error' => $e->getMessage()
            ]);
            
            return [
                'overall' => [
                    'score' => 0,
                    'total' => 0,
                    'percentage' => 0
                ],
                'sources' => []
            ];
        }
    }

    // Format duration as human-readable string
    // 💀 N+1 WARNING: This accessor accesses 'duration_seconds' - consider eager loading
    public function getDurationAttribute(): string
    {
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        
        if ($minutes > 0) {
            return sprintf("%dm %ds", $minutes, $seconds);
        }
        
        return sprintf("%ds", $seconds);
    }

    /**
     * 🔥 CLEAR PROGRESS CACHE - Call after quiz/tutor completion for instant updates
     */
    public static function clearProgressCache($userId, $topicId, $studyMode = null)
    {
        if ($studyMode) {
            // Clear specific mode cache
            $cacheKey = "topic_progress_{$userId}_{$topicId}_{$studyMode}";
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            if (config('app.debug')) {
                \Log::debug("Progress cache cleared", [
                    'user_id' => $userId,
                    'topic_id' => $topicId,
                    'study_mode' => $studyMode,
                    'cache_key' => $cacheKey
                ]);
            }
        } else {
            // Clear both quiz and tutor caches
            \Illuminate\Support\Facades\Cache::forget("topic_progress_{$userId}_{$topicId}_quiz");
            \Illuminate\Support\Facades\Cache::forget("topic_progress_{$userId}_{$topicId}_tutor");
            if (config('app.debug')) {
                \Log::debug("All progress caches cleared", [
                    'user_id' => $userId,
                    'topic_id' => $topicId
                ]);
            }
        }
    }
}
