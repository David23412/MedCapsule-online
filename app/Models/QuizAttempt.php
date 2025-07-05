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
        'correct_answers'    // Number of correct answers in the quiz
    ];

    protected $casts = [
        'percentage_grade' => 'float',
        'score' => 'integer',
        'total_questions' => 'integer',
        'duration_seconds' => 'integer',
        'question_data' => 'array',
        'correct_answers' => 'integer',
        'topic_id' => 'integer'
    ];

    protected $attributes = [
        'study_mode' => 'normal',
        'source' => 'all'
    ];

    /**
     * Validation rules for quiz attempts
     */
    public static $rules = [
        'user_id' => 'required|exists:users,id',
        'topic_id' => 'nullable|exists:topics,id',
        'quiz_type' => 'required|in:normal,random,tutor',
        'study_mode' => 'required|in:normal,quiz,tutor,random',
        'source' => 'nullable|string',
        'score' => 'required|integer|min:0',
        'total_questions' => 'required|integer|min:1',
        'percentage_grade' => 'required|numeric|min:0|max:100',
        'duration_seconds' => 'required|integer|min:0',
        'question_data' => 'required|array',
        'question_data.*.question_id' => 'required|exists:questions,id',
        'question_data.*.user_answer' => 'nullable|string',
        'question_data.*.is_correct' => 'required|boolean'
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Validate data before saving
            $validator = Validator::make(
                $model->toArray(),
                static::$rules
            );

            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
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
            foreach ($model->question_data as &$data) {
                $data['answered_at'] = now()->toIso8601String();
            }
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

    /**
     * Get commonly missed questions for a user in a topic with error handling
     */
    public static function getCommonMistakes($userId, $topicId, $limit = 10)
    {
        try {
            // Get incorrect answers from question_data of recent attempts
            $recentAttempts = self::where([
                'user_id' => $userId,
                'topic_id' => $topicId
            ])
            ->orderBy('created_at', 'desc')
            ->limit(50) // Look at last 50 attempts max
            ->get();

            if ($recentAttempts->isEmpty()) {
                return collect([]);
            }

            $questionData = $recentAttempts->pluck('question_data')
                ->filter()
                ->flatten(1)
                ->filter(function ($data) {
                    return isset($data['is_correct']) && !$data['is_correct'] && isset($data['question_id']);
                });

            if ($questionData->isEmpty()) {
                return collect([]);
            }

            // Count frequency of each missed question
            $questionCounts = array_count_values($questionData->pluck('question_id')->toArray());
            arsort($questionCounts);

            // Get the most commonly missed question IDs
            $commonMistakeIds = array_slice($questionCounts, 0, $limit, true);

            return Question::whereIn('id', array_keys($commonMistakeIds))
                ->with(['topic.course']) // Eager load relationships
                ->get()
                ->sortBy(function ($question) use ($commonMistakeIds) {
                    return array_search($question->id, array_keys($commonMistakeIds));
                });
        } catch (\Exception $e) {
            \Log::error('Error in getCommonMistakes', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'topic_id' => $topicId
            ]);
            return collect([]);
        }
    }

    /**
     * Safely get question data with error handling
     */
    public function getQuestionDataAttribute($value)
    {
        try {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            }
            return is_array($value) ? $value : [];
        } catch (\Exception $e) {
            \Log::error('Error decoding question_data', [
                'error' => $e->getMessage(),
                'quiz_attempt_id' => $this->id
            ]);
            return [];
        }
    }

    /**
     * Clean up old quiz attempts to prevent database bloat
     */
    public static function cleanupOldAttempts($daysOld = 90)
    {
        try {
            $cutoffDate = now()->subDays($daysOld);
            return self::where('created_at', '<', $cutoffDate)->delete();
        } catch (\Exception $e) {
            \Log::error('Error cleaning up old quiz attempts', [
                'error' => $e->getMessage(),
                'days_old' => $daysOld
            ]);
            return 0;
        }
    }

    /**
     * Get both source-specific and overall progress for a user in a topic
     */
    public static function getTopicProgress($userId, $topicId, $studyMode = 'quiz')
    {
        // Get total questions in topic
        $totalTopicQuestions = Question::where('topic_id', $topicId)->count();
        
        // Get all sources for this topic
        $sources = Question::where('topic_id', $topicId)
            ->distinct('source')
            ->pluck('source')
            ->filter()
            ->values();

        // Get the latest attempt for each source
        $sourceProgress = [];
        $totalScore = 0;
        $totalAttempted = 0;

        foreach ($sources as $source) {
            $latestAttempt = self::where([
                'user_id' => $userId,
                'topic_id' => $topicId,
                'study_mode' => $studyMode,
                'source' => $source
            ])
            ->orderBy('created_at', 'desc')
            ->first();

            if ($latestAttempt) {
                $totalSourceQuestions = Question::where('topic_id', $topicId)
                    ->where('source', $source)
                    ->count();

            $sourceProgress[$source] = [
                    'score' => $latestAttempt->score,
                    'total' => $latestAttempt->total_questions,
                    'total_available' => $totalSourceQuestions,
                    'percentage' => $latestAttempt->percentage_grade
            ];

                $totalScore += $latestAttempt->score;
                $totalAttempted += $latestAttempt->total_questions;
            }
        }

        // Calculate overall progress
        $overallPercentage = $totalTopicQuestions > 0 
            ? round(($totalScore / $totalTopicQuestions) * 100, 2)
            : 0;

        return [
            'overall' => [
                'score' => $totalScore,
                'total' => $totalTopicQuestions,
                'percentage' => $overallPercentage
            ],
            'sources' => $sourceProgress
        ];
    }

    /**
     * Create a new quiz attempt with progress tracking
     */
    public static function createWithProgress($data)
    {
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
        $attempt = self::create(array_merge($data, [
            'percentage_grade' => $percentage,
            'question_data' => $questionData
        ]));

        // Clear relevant caches
        app(CacheService::class)->clearUserTopicProgressCache($data['user_id'], $data['topic_id']);

        // Return both the attempt and updated progress
        return [
            'attempt' => $attempt,
            'progress' => self::getTopicProgress($data['user_id'], $data['topic_id'], $data['study_mode'] ?? 'quiz')
        ];
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