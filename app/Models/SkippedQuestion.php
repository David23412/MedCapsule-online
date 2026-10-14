<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SkippedQuestion extends Model
{
    use HasFactory;

    // Disable timestamps since the table doesn't have them
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'question_id',
        'skipped_at',
        'session_type'
    ];

    protected $casts = [
        'skipped_at' => 'datetime'
    ];

    // Session type constants
    const SESSION_LEARN_MISTAKES = 'learn_mistakes';
    const SESSION_QUIZ = 'quiz';
    const SESSION_REVIEW = 'review';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Query Scopes
     */
    public function scopeForUser($query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForQuestion($query, int $questionId): Builder
    {
        return $query->where('question_id', $questionId);
    }

    public function scopeForSession($query, string $sessionType): Builder
    {
        return $query->where('session_type', $sessionType);
    }

    public function scopeRecent($query, int $days = 7): Builder
    {
        return $query->where('skipped_at', '>=', now()->subDays($days));
    }

    public function scopeByTopic($query, int $topicId): Builder
    {
        return $query->whereHas('question', function($q) use ($topicId) {
            $q->where('topic_id', $topicId);
        });
    }

    /**
     * Static Methods for Business Logic
     */
    public static function markAsSkipped(int $userId, int $questionId, string $sessionType = self::SESSION_LEARN_MISTAKES): bool
    {
        try {
            self::updateOrCreate(
                [
                    'user_id' => $userId,
                    'question_id' => $questionId,
                    'session_type' => $sessionType
                ],
                [
                    'skipped_at' => now()
                ]
            );
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to mark question as skipped', [
                'user_id' => $userId,
                'question_id' => $questionId,
                'session_type' => $sessionType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public static function isSkipped(int $userId, int $questionId, string $sessionType = self::SESSION_LEARN_MISTAKES): bool
    {
        return self::where('user_id', $userId)
                  ->where('question_id', $questionId)
                  ->where('session_type', $sessionType)
                  ->exists();
    }

    public static function getSkippedQuestions(int $userId, ?string $sessionType = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::with(['question', 'question.topic'])
                    ->where('user_id', $userId);

        if ($sessionType) {
            $query->where('session_type', $sessionType);
        }

        return $query->orderBy('skipped_at', 'desc')->get();
    }

    public static function getSkippedQuestionsForTopic(int $userId, int $topicId, ?string $sessionType = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::with(['question'])
                    ->where('user_id', $userId)
                    ->whereHas('question', function($q) use ($topicId) {
                        $q->where('topic_id', $topicId);
                    });

        if ($sessionType) {
            $query->where('session_type', $sessionType);
        }

        return $query->orderBy('skipped_at', 'desc')->get();
    }

    public static function removeSkip(int $userId, int $questionId, string $sessionType = self::SESSION_LEARN_MISTAKES): bool
    {
        return self::where('user_id', $userId)
                  ->where('question_id', $questionId)
                  ->where('session_type', $sessionType)
                  ->delete() > 0;
    }

    public static function clearAllSkips(int $userId, ?string $sessionType = null): int
    {
        $query = self::where('user_id', $userId);

        if ($sessionType) {
            $query->where('session_type', $sessionType);
        }

        return $query->delete();
    }

    public static function clearTopicSkips(int $userId, int $topicId, ?string $sessionType = null): int
    {
        $query = self::where('user_id', $userId)
                    ->whereHas('question', function($q) use ($topicId) {
                        $q->where('topic_id', $topicId);
                    });

        if ($sessionType) {
            $query->where('session_type', $sessionType);
        }

        return $query->delete();
    }

    /**
     * Statistics and Analytics
     */
    public static function getSkipStatistics(int $userId): array
    {
        $baseQuery = self::where('user_id', $userId);

        return [
            'total_skips' => (clone $baseQuery)->count(),
            'recent_skips' => (clone $baseQuery)->where('skipped_at', '>=', now()->subDays(7))->count(),
            'skips_by_session' => [
                self::SESSION_LEARN_MISTAKES => (clone $baseQuery)->where('session_type', self::SESSION_LEARN_MISTAKES)->count(),
                self::SESSION_QUIZ => (clone $baseQuery)->where('session_type', self::SESSION_QUIZ)->count(),
                self::SESSION_REVIEW => (clone $baseQuery)->where('session_type', self::SESSION_REVIEW)->count(),
            ],
            'most_skipped_topics' => self::getMostSkippedTopics($userId, 5),
            'skip_frequency' => self::getSkipFrequency($userId)
        ];
    }

    public static function getMostSkippedTopics(int $userId, int $limit = 10): array
    {
        return self::select('questions.topic_id', 'topics.name as topic_name', \DB::raw('COUNT(*) as skip_count'))
                  ->join('questions', 'skipped_questions.question_id', '=', 'questions.id')
                  ->join('topics', 'questions.topic_id', '=', 'topics.id')
                  ->where('skipped_questions.user_id', $userId)
                  ->groupBy('questions.topic_id', 'topics.name')
                  ->orderBy('skip_count', 'desc')
                  ->limit($limit)
                  ->get()
                  ->toArray();
    }

    public static function getSkipFrequency(int $userId): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = self::where('user_id', $userId)
                        ->whereDate('skipped_at', $date)
                        ->count();
            $days[$date] = $count;
        }
        return $days;
    }

    /**
     * Cleanup Methods
     */
    public static function cleanupOldSkips(int $daysOld = 30): int
    {
        return self::where('skipped_at', '<', now()->subDays($daysOld))->delete();
    }

    /**
     * Validation Methods
     */
    public static function getValidSessionTypes(): array
    {
        return [
            self::SESSION_LEARN_MISTAKES,
            self::SESSION_QUIZ,
            self::SESSION_REVIEW
        ];
    }

    public static function isValidSessionType(string $sessionType): bool
    {
        return in_array($sessionType, self::getValidSessionTypes());
    }
}