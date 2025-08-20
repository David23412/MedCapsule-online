<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Support\Facades\Validator;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'topic_id',
        'quiz_type',
        'study_mode',
        'source',
        'score',
        'total_questions',
        'percentage_grade',
        'duration_seconds',
        'question_data',     // JSON array of {question_id, user_answer, is_correct} for potential review
        'correct_answers',   // Number of correct answers in the quiz
        'assignment_id'      // Link to assignment if this is an assignment quiz
    ];

    protected $casts = [
        'percentage_grade' => 'float',
        'score' => 'integer',
        'total_questions' => 'integer',
        'duration_seconds' => 'integer',
        'question_data' => 'array',
        'correct_answers' => 'integer',  // Simple count of correct answers
        'topic_id' => 'integer',
        'assignment_id' => 'integer'
    ];

    protected $attributes = [
        'study_mode' => null,
        'source' => null,
        'correct_answers' => 0
    ];

    /**
     * Validation rules for quiz attempts
     */
    public static $rules = [
        'user_id' => 'required|exists:users,id',
        'topic_id' => 'nullable|exists:topics,id',
        'quiz_type' => 'required|in:normal,random,tutor,mistake_review,assignment',
        'study_mode' => 'nullable|in:quiz,tutor,random,mistake_review,assignment',
        'source' => 'nullable|string',
        'score' => 'required|integer|min:0',
        'total_questions' => 'required|integer|min:1',
        'percentage_grade' => 'required|numeric|min:0|max:100',
        'duration_seconds' => 'required|integer|min:0',
        'question_data' => 'nullable|array',
        'correct_answers' => 'nullable|integer|min:0',
        'assignment_id' => 'nullable|exists:assignments,id'
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
            // CRITICAL FIX: Ensure correct_answers is set if not provided
            if (!isset($model->correct_answers) || $model->correct_answers === null) {
                $model->correct_answers = $model->score ?? 0;
            }
            
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
                'total_questions' => $model->total_questions,
                'correct_answers' => $model->correct_answers
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
        $cacheKey = "topic_progress_{$userId}_{$topicId}_{$studyMode}";
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($userId, $topicId, $studyMode) {
            return self::calculateTopicProgress($userId, $topicId, $studyMode);
        });
    }

    /**
     * Calculate topic progress (internal method)
     * OPTIMIZED LOGIC: Count unique questions answered correctly out of total available questions by source
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

            // STEP 2: Get quiz attempts and extract unique correct questions (OPTIMIZED: Minimal data fetch)
            $attempts = self::where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->where('study_mode', $studyMode)
                ->select(['source', 'question_data'])
                ->get();

            // STEP 3: Get question ID to source mapping for accurate source assignment
            $questionSourceMap = \App\Models\Question::where('topic_id', $topicId)
                ->pluck('source', 'id')
                ->toArray();

            // STEP 4: Process correct questions and attempted questions by their ACTUAL source (CRITICAL FIX)
            $correctQuestionsBySource = [];
            $attemptedQuestionsBySource = [];
            
            foreach ($attempts as $attempt) {
                // Extract question IDs and map to their actual sources
                if (is_array($attempt->question_data)) {
                    foreach ($attempt->question_data as $questionData) {
                        if (!empty($questionData['question_id'])) {
                            
                            $questionId = $questionData['question_id'];
                            $actualSource = $questionSourceMap[$questionId] ?? 'General';
                            
                            // Track all attempted questions
                            if (!isset($attemptedQuestionsBySource[$actualSource])) {
                                $attemptedQuestionsBySource[$actualSource] = [];
                            }
                            $attemptedQuestionsBySource[$actualSource][$questionId] = true;
                            
                            // Track correct questions
                            if (!empty($questionData['is_correct'])) {
                                if (!isset($correctQuestionsBySource[$actualSource])) {
                                    $correctQuestionsBySource[$actualSource] = [];
                                }
                                $correctQuestionsBySource[$actualSource][$questionId] = true;
                            }
                        }
                    }
                }
            }

            // STEP 5: Calculate progress for each source (OPTIMIZED: Direct count)
            $sourceProgress = [];
            $totalCorrectQuestions = 0;
            $totalQuestionsInTopic = array_sum($totalQuestionsBySource);

            foreach ($totalQuestionsBySource as $source => $totalQuestions) {
                // Count unique correct questions (OPTIMIZED: array_keys count)
                $correctCount = isset($correctQuestionsBySource[$source]) 
                    ? count($correctQuestionsBySource[$source]) 
                    : 0;
                
                // Count unique attempted questions
                $attemptedCount = isset($attemptedQuestionsBySource[$source])
                    ? count($attemptedQuestionsBySource[$source])
                    : 0;
                
                $percentage = $attemptedCount > 0 ? ($correctCount / $attemptedCount) * 100 : 0;
                
                $sourceProgress[$source] = [
                    'score' => $correctCount,
                    'total' => $attemptedCount, // FIXED: Use attempted count, not available count
                    'percentage' => round($percentage, 2)
                ];
                
                $totalCorrectQuestions += $correctCount;
            }

            // STEP 6: Calculate overall percentage (OPTIMIZED: Pre-calculated total)
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
     * Get tutor mode source progress - ONLY for source modal updates
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

            // Get tutor mode attempts only
            $attempts = self::where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->where('study_mode', 'tutor')
                ->select(['source', 'question_data'])
                ->get();

            // Get question ID to source mapping
            $questionSourceMap = \App\Models\Question::where('topic_id', $topicId)
                ->pluck('source', 'id')
                ->toArray();

            // Process ALL questions by their actual source (correct and incorrect)
            $correctQuestionsBySource = [];
            $attemptedQuestionsBySource = [];
            
            foreach ($attempts as $attempt) {
                if (is_array($attempt->question_data)) {
                    foreach ($attempt->question_data as $questionData) {
                        if (!empty($questionData['question_id'])) {
                            
                            $questionId = $questionData['question_id'];
                            $actualSource = $questionSourceMap[$questionId] ?? 'General';
                            
                            // Track all attempted questions
                            if (!isset($attemptedQuestionsBySource[$actualSource])) {
                                $attemptedQuestionsBySource[$actualSource] = [];
                            }
                            $attemptedQuestionsBySource[$actualSource][$questionId] = true;
                            
                            // Track correct questions
                            if (!empty($questionData['is_correct'])) {
                                if (!isset($correctQuestionsBySource[$actualSource])) {
                                    $correctQuestionsBySource[$actualSource] = [];
                                }
                                $correctQuestionsBySource[$actualSource][$questionId] = true;
                            }
                        }
                    }
                }
            }

            // Calculate progress for each source - TUTOR MODE ONLY
            $sourceProgress = [];
            
            foreach ($totalQuestionsBySource as $source => $totalQuestions) {
                $correctCount = isset($correctQuestionsBySource[$source]) 
                    ? count($correctQuestionsBySource[$source]) 
                    : 0;
                
                $attemptedCount = isset($attemptedQuestionsBySource[$source])
                    ? count($attemptedQuestionsBySource[$source])
                    : 0;
                
                $percentage = $attemptedCount > 0 ? ($correctCount / $attemptedCount) * 100 : 0;
                
                $sourceProgress[$source] = [
                    'score' => $correctCount,
                    'total' => $attemptedCount, // FIXED: Use attempted count, not available count
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
     * Create a new quiz attempt with progress tracking     */
    public static function createWithProgress($data)
    {
        try {
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

            // CRITICAL FIX: Ensure correct_answers is set
            if (!isset($data['correct_answers'])) {
                $data['correct_answers'] = $data['score'] ?? 0;
            }

            // Create attempt with calculated data
            $attempt = self::create(array_merge($data, [
                'percentage_grade' => $percentage,
                'question_data' => $questionData
            ]));

            // Clear relevant caches
            try {
                app(UltraConsolidatedCacheService::class)->clearUserTopicProgressCache($data['user_id'], $data['topic_id']);
            } catch (\Exception $e) {
                \Log::warning('Failed to clear cache after quiz attempt creation', [
                    'error' => $e->getMessage()
                ]);
            }

            // Return both the attempt and updated progress
            return [
                'attempt' => $attempt,
                'progress' => self::getTopicProgress($data['user_id'], $data['topic_id'], $data['study_mode'] ?? 'quiz')
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
} 