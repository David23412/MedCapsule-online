<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Services\CacheService;
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
        'question_data' => 'required|array',
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
            if ($quizAttempt->score !== null && $quizAttempt->total_questions !== null) {
                $percentage = ($quizAttempt->score / $quizAttempt->total_questions) * 100;
                
                event(new \App\Events\QuizAttemptCompleted(
                    $quizAttempt,
                    $quizAttempt->score,
                    $quizAttempt->total_questions,
                    $percentage
                ));
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
            if ($model->study_mode === null && $model->quiz_type === 'tutor') {
                $model->study_mode = 'tutor';
            }
            
            // CRITICAL FIX: Ensure source is set for tutor mode
            if ($model->source === null && $model->study_mode === 'tutor') {
                $model->source = 'General';
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
                ]);
                throw $e;
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
                    $questionData[$key]['answered_at'] = now()->toIso8601String();
                }
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
     * Get topic progress for a user with error handling
     */
    public static function getTopicProgress($userId, $topicId, $studyMode = 'quiz')
    {
        try {
            $attempts = self::where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->where('study_mode', $studyMode)
                ->get();

            if ($attempts->isEmpty()) {
                return [
                    'overall' => [
                        'score' => 0,
                        'total' => 0,
                        'percentage' => 0
                    ],
                    'sources' => []
                ];
            }

            // Get the actual number of questions available in each source
            $sourceQuestionCounts = \App\Models\Question::where('topic_id', $topicId)
                ->whereNotNull('source')
                ->selectRaw('source, COUNT(*) as count')
                ->groupBy('source')
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
            \Log::error('Error getting topic progress', [
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
     * Create a new quiz attempt with progress tracking
     */
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
                app(CacheService::class)->clearUserTopicProgressCache($data['user_id'], $data['topic_id']);
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
                        'score' => 0,
                        'total' => 0,
                        'percentage' => 0
                    ],
                    'sources' => []
                ];
            }

            // Get the actual number of questions available in each source
            $sourceQuestionCounts = \App\Models\Question::where('topic_id', $topicId)
                ->whereNotNull('source')
                ->selectRaw('source, COUNT(*) as count')
                ->groupBy('source')
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