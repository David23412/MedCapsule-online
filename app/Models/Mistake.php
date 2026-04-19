<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\Core\UltraConsolidatedCacheService;
use App\Services\Learning\AdaptiveMistakeScheduler;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Mistake extends Model
{
    use HasFactory;

    protected static array $columnExistenceCache = [];

    protected $fillable = [
        'user_id',
        'question_id',
        'submitted_answer',
        'correct_streak',
        'last_attempt_date',
        'quick_correct_count',
        'quiz_correct_count',
        'review_count',
        'lapse_count',
        'interval_days',
        'ease_factor',
        'difficulty',
        'difficulty_baseline',
        'stability',
        'retrievability',
        'skip_count',
        'consecutive_skip_count',
        'last_skipped_at',
        'skip_until',
        'last_response_seconds',
        'last_review_rating',
        'last_review_confidence',
        'hesitation_score',
        'learning_state',
        'same_day_relearn_step',
        'same_day_relearn_count',
        'next_review_at',
        'last_reviewed_at',
        'mastered',
        'skipped'
    ];

    protected $attributes = [
        'correct_streak' => 0,
        'submitted_answer' => '',  // Default empty string for submitted_answer
        'quick_correct_count' => 0,
        'quiz_correct_count' => 0,
        'review_count' => 0,
        'lapse_count' => 0,
        'interval_days' => 0,
        'ease_factor' => 2.3,
        'difficulty' => 0.45,
        'difficulty_baseline' => null,
        'stability' => 0.5,
        'retrievability' => 0.9,
        'skip_count' => 0,
        'consecutive_skip_count' => 0,
        'learning_state' => null,
        'same_day_relearn_step' => 0,
        'same_day_relearn_count' => 0,
        'mastered' => false,
        'skipped' => false
    ];

    protected $casts = [
        'last_attempt_date' => 'datetime',
        'next_review_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
        'correct_streak' => 'integer',
        'quick_correct_count' => 'integer',
        'quiz_correct_count' => 'integer',
        'review_count' => 'integer',
        'lapse_count' => 'integer',
        'interval_days' => 'integer',
        'ease_factor' => 'float',
        'difficulty' => 'float',
        'difficulty_baseline' => 'float',
        'stability' => 'float',
        'retrievability' => 'float',
        'skip_count' => 'integer',
        'consecutive_skip_count' => 'integer',
        'last_skipped_at' => 'datetime',
        'skip_until' => 'datetime',
        'last_response_seconds' => 'integer',
        'last_review_rating' => 'integer',
        'hesitation_score' => 'float',
        'same_day_relearn_step' => 'integer',
        'same_day_relearn_count' => 'integer',
        'mastered' => 'boolean',
        'skipped' => 'boolean'
    ];

    public $timestamps = true;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($mistake) {
            // Ensure the question exists before creating a mistake entry
            if (!Question::where('id', $mistake->question_id)->exists()) {
                Log::warning('⚠️ Attempting to create a mistake with an invalid question_id', [
                    'question_id' => $mistake->question_id,
                    'user_id' => $mistake->user_id
                ]);
                return false;
            }

            // Initialize tracking fields
            $mistake->correct_streak = 0;
            $mistake->last_attempt_date = now();
            $mistake->setColumnValue('next_review_at', $mistake->next_review_at ?: now());
            $mistake->setColumnValue('last_reviewed_at', $mistake->last_reviewed_at ?: now());
            $mistake->setColumnValue('review_count', $mistake->review_count ?? 0);
            $mistake->setColumnValue('lapse_count', $mistake->lapse_count ?? 0);
            $mistake->setColumnValue('interval_days', $mistake->interval_days ?? 0);
            $mistake->setColumnValue('ease_factor', $mistake->ease_factor ?? 2.3);
            $mistake->setColumnValue('difficulty', $mistake->difficulty ?? 0.45);
            $mistake->setColumnValue('difficulty_baseline', $mistake->difficulty_baseline ?? null);
            $mistake->setColumnValue('stability', $mistake->stability ?? 0.5);
            $mistake->setColumnValue('retrievability', $mistake->retrievability ?? 0.9);
            $mistake->setColumnValue('skip_count', $mistake->skip_count ?? 0);
            $mistake->setColumnValue('consecutive_skip_count', $mistake->consecutive_skip_count ?? 0);
            $mistake->setColumnValue('learning_state', $mistake->learning_state ?? null);
            $mistake->setColumnValue('same_day_relearn_step', $mistake->same_day_relearn_step ?? 0);
            $mistake->setColumnValue('same_day_relearn_count', $mistake->same_day_relearn_count ?? 0);
        });

        static::updating(function ($mistake) {
            if (!Question::where('id', $mistake->question_id)->exists()) {
                Log::warning('⚠️ Attempting to update a mistake with an invalid question_id', [
                    'question_id' => $mistake->question_id,
                    'user_id' => $mistake->user_id
                ]);
                return false;
            }
        });

        static::created(function ($mistake) {
            $mistake->clearCaches();
        });

        static::updated(function ($mistake) {
            $mistake->clearCaches();
        });

        static::deleted(function ($mistake) {
            $mistake->clearCaches();
        });
    }

    /**
     * Get the question associated with the mistake.
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the user who made the mistake.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentRetrievability(?Carbon $asOf = null): float
    {
        $asOf ??= now();
        $lastReviewedAt = $this->last_reviewed_at instanceof Carbon
            ? $this->last_reviewed_at
            : ($this->last_attempt_date instanceof Carbon ? $this->last_attempt_date : null);

        if (!$lastReviewedAt && $this->last_reviewed_at) {
            $lastReviewedAt = Carbon::parse($this->last_reviewed_at);
        } elseif (!$lastReviewedAt && $this->last_attempt_date) {
            $lastReviewedAt = Carbon::parse($this->last_attempt_date);
        }

        $retrievabilitySeed = max(0.01, min(0.99, (float) ($this->retrievability ?? 0.9)));

        if (!$lastReviewedAt || !static::hasColumn('stability')) {
            return round($retrievabilitySeed, 4);
        }

        $stability = max(0.15, (float) ($this->stability ?? 0.5));
        $elapsedDays = max(0.0, $lastReviewedAt->diffInSeconds($asOf) / 86400);
        $retrievability = $retrievabilitySeed / (1.0 + ($elapsedDays / (9.0 * $stability)));

        return round(max(0.01, min(0.99, $retrievability)), 4);
    }

    /**
     * Check if the mistake is considered mastered based on mode
     */
    public function isMastered($mode = 'classic'): bool
    {
        $normalizedMode = $this->normalizeReviewMode((string) $mode);

        if ($normalizedMode === 'spaced') {
            return false;
        }

        $requiredStreak = 2; // Default for 'practice' or 'easy'
        
        if ($normalizedMode === 'hard' || $normalizedMode === 'quiz' || $normalizedMode === 'classic') {
            $requiredStreak = 3;
        }

        return (bool) $this->mastered || $this->correct_streak >= $requiredStreak;
    }

    /**
     * Update mistake tracking based on answer correctness and mode
     */
    public function updateTracking($isCorrect, $mode = 'quiz')
    {
        $this->applyReviewOutcome((bool) $isCorrect, (string) $mode);
        $this->save();
        
    }

    public function applyReviewOutcome(bool $isCorrect, string $mode = 'quiz'): void
    {
        $rating = $isCorrect ? 4 : 1;
        $this->applyRatedReview($rating, $mode);
    }

    public function applyRatedReview(
        int $rating,
        string $mode = 'quiz',
        ?int $responseSeconds = null,
        ?string $confidence = null,
        array $interactionSignals = []
    ): void
    {
        app(AdaptiveMistakeScheduler::class)->applyReview(
            $this,
            $rating,
            $this->normalizeReviewMode($mode),
            $responseSeconds,
            $confidence,
            $interactionSignals
        );
    }

    public function markFailedAttempt(?string $submittedAnswer = null): void
    {
        app(AdaptiveMistakeScheduler::class)->initializeFromFailure($this, $submittedAnswer);
    }

    public function applySkip(string $mode = 'quiz'): void
    {
        app(AdaptiveMistakeScheduler::class)->applySkip($this, $this->normalizeReviewMode($mode));
    }

    public function getFocusConceptNameAttribute(): ?string
    {
        $question = $this->relationLoaded('question') ? $this->question : null;

        if ($question && !$question->relationLoaded('topic')) {
            $question->loadMissing('topic');
        }

        $concept = trim((string) ($question?->primary_concept_name ?? $question?->concept ?? ''));

        if ($concept === '' && $question?->relationLoaded('topic')) {
            $concept = trim((string) ($question->topic?->name ?? ''));
        }

        if ($concept !== '') {
            return $concept;
        }

        return null;
    }

    public function getFocusConceptSlugAttribute(): ?string
    {
        $concept = $this->focus_concept_name;

        if ($concept === null || $concept === '') {
            return null;
        }

        return Str::slug($concept);
    }

    public static function recordFailure(int $userId, Question $question, ?string $submittedAnswer = null): self
    {
        return DB::transaction(function () use ($userId, $question, $submittedAnswer) {
            $mistake = static::firstOrNew([
                'user_id' => $userId,
                'question_id' => $question->id,
            ]);

            $mistake->submitted_answer = $submittedAnswer !== null && trim($submittedAnswer) !== ''
                ? $submittedAnswer
                : 'unanswered';

            $isNew = !$mistake->exists;
            if ($isNew) {
                // Must persist first so $mistake->id is available for event recording
                $mistake->save();
            }

            $mistake->setRelation('question', $question);
            $mistake->markFailedAttempt($mistake->submitted_answer);
            $mistake->save();

            return $mistake;
        });
    }

    /**
     * Scope a query to only include mistakes that aren't mastered yet.
     */
    public function scopeNotMastered($query, $mode = 'quiz')
    {
        $normalizedMode = $this->normalizeReviewMode((string) $mode);

        if ($normalizedMode === 'spaced') {
            return $query;
        }

        $requiredStreak = $this->resolveRequiredStreak($query, $normalizedMode);

        return $query
            ->where('mastered', false)
            ->where('correct_streak', '<', $requiredStreak);
    }

    /**
     * Scope a query to only include mastered mistakes.
     */
    public function scopeMastered($query, $mode = 'quiz')
    {
        $normalizedMode = $this->normalizeReviewMode((string) $mode);

        if ($normalizedMode === 'spaced') {
            return $query->whereRaw('1 = 0');
        }

        $requiredStreak = $this->resolveRequiredStreak($query, $normalizedMode);

        return $query->where(function ($innerQuery) use ($requiredStreak) {
            $innerQuery
                ->where('mastered', true)
                ->orWhere('correct_streak', '>=', $requiredStreak);
        });
    }

    /**
     * Resolve the required streak for mastery scopes.
     * Uses in-memory User model when available to avoid extra DB queries.
     */
    private function resolveRequiredStreak($query, string $normalizedMode): int
    {
        if ($normalizedMode === 'quick') {
            return 2;
        }

        $userId = $this->extractUserIdFromQuery($query);
        $difficulty = 'easy';
        if ($userId) {
            // Use the cached User instance if already loaded in memory (e.g. auth()->user()),
            // otherwise fall back to a lightweight value() query (no model hydration).
            $user = auth()->check() && auth()->id() === $userId ? auth()->user() : null;
            $difficulty = $user?->mistake_mastery_mode
                ?? \App\Models\User::where('id', $userId)->value('mistake_mastery_mode')
                ?? 'easy';
        }

        return (new \App\Services\Learning\UserMistakeMasteryService())->requiredStreakForDifficulty($difficulty);
    }

    private function extractUserIdFromQuery($query): ?int
    {
        $baseQuery = $query->getQuery();
        if (isset($baseQuery->wheres)) {
            foreach ($baseQuery->wheres as $where) {
                // If it's a basic where and column is user_id or mistakes.user_id
                if (isset($where['column']) && ($where['column'] === 'user_id' || $where['column'] === 'mistakes.user_id') && isset($where['value'])) {
                    return (int) $where['value'];
                }
            }
        }
        return null;
    }

    public function scopeDueForReview($query, $mode = 'quiz')
    {
        $query = $query
            ->notMastered($mode);

        if (static::hasColumn('skip_until')) {
            $query->where(function ($innerQuery) {
                $innerQuery
                    ->where('skipped', false)
                    ->orWhere(function ($skipQuery) {
                        $skipQuery
                            ->where('skipped', true)
                            ->whereNotNull('skip_until')
                            ->where('skip_until', '<=', now());
                    });
            });
        } else {
            $query->where('skipped', false);
        }

        if (!static::hasColumn('next_review_at')) {
            return $query;
        }

        return $query->where(function ($innerQuery) {
            $innerQuery
                ->whereNull('next_review_at')
                ->orWhere('next_review_at', '<=', now());
        });
    }

    public static function hasColumn(string $column): bool
    {
        $column = static::normalizeColumnName($column);

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

    public static function qualifiedColumns(array $columns): array
    {
        $table = (new static())->getTable();

        return array_map(
            fn (string $column) => str_contains($column, '.') ? $column : $table . '.' . $column,
            static::availableColumns($columns)
        );
    }

    /**
     * 💀 LETHAL CACHE CLEARING - CLEAR ALL MISTAKE CACHES 💀
     */
    protected function clearCaches()
    {
        try {
            $cacheKeys = $this->getMistakeCacheKeys();

            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }
            
            // Advance cache version to invalidate primary learning list safely without database scans
            $versionKey = "mistakes_cache_version_{$this->user_id}";
            $currentVersion = Cache::get($versionKey, 1);
            Cache::put($versionKey, $currentVersion + 1, 3600);
            
        } catch (\Exception $e) {
            Log::error("❌ Error clearing mistake caches", [
                'user_id' => $this->user_id,
                'question_id' => $this->question_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Build the targeted mistake cache keys that should be invalidated.
     */
    protected function getMistakeCacheKeys(): array
    {
        $cacheKeys = [
            "mistake_{$this->user_id}_{$this->question_id}_progress",
            "mistake_courses_{$this->user_id}",
            "mistakes_user_{$this->user_id}",
            "mistakes_count_{$this->user_id}",
            "performance_tracking_coach_{$this->user_id}",
            UltraConsolidatedCacheService::generateCacheKey('mistakes_full', [
                'user_id' => $this->user_id,
                'course_filter' => 'all'
            ]),
            UltraConsolidatedCacheService::generateCacheKey('mistakes_full', [
                'user_id' => $this->user_id,
                'course_filter' => 'skipped'
            ]),
        ];

        $courseId = Question::query()
            ->join('topics', 'questions.topic_id', '=', 'topics.id')
            ->where('questions.id', $this->question_id)
            ->value('topics.course_id');

        if ($courseId !== null) {
            $cacheKeys[] = UltraConsolidatedCacheService::generateCacheKey('mistakes_full', [
                'user_id' => $this->user_id,
                'course_filter' => $courseId
            ]);
        }

        return array_values(array_unique($cacheKeys));
    }

    public function normalizeReviewMode(string $mode = 'classic'): string
    {
        $cleaned = strtolower(trim($mode));
        
        return match ($cleaned) {
            'easy', 'practice', 'simple' => 'easy',
            'hard', 'quiz', 'classic', 'tough' => 'hard',
            'spaced', 'adaptive', 'intelligent' => 'spaced',
            'quick', 'lightning' => 'quick',
            default => 'hard', // Default back to hard if unknown
        };
    }

    private static function normalizeColumnName(string $column): string
    {
        if (!str_contains($column, '.')) {
            return $column;
        }

        $segments = explode('.', $column);

        return (string) end($segments);
    }

    private function setColumnValue(string $column, mixed $value): void
    {
        if (static::hasColumn($column)) {
            $this->setAttribute($column, $value);
        }
    }

    public function setAdaptiveColumnValue(string $column, mixed $value): void
    {
        $this->setColumnValue($column, $value);
    }
}
