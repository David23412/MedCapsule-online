<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\Core\UltraConsolidatedCacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Question extends Model
{
    use HasFactory;

    // Question types constants
    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_WRITTEN = 'written';
    
    protected $fillable = [
        'topic_id',
        'question', // Actual database field name
        'source',
        'option_a', // Actual database field names
        'option_b',
        'option_c',
        'option_d',
        'option_e',
        'correct_answer',
        'question_type',
        'explanation',
        'image_url',
        'extra_topic',
        'concept',
        'alternative_answers',
        'exam_appearances_count',
    ];

    protected $casts = [
        'topic_id' => 'integer',
        'alternative_answers' => 'string',
        'exam_appearances_count' => 'integer'
    ];

    // Remove the problematic accessors that were trying to map to a different schema
    // The database already has the correct field names, so we don't need accessors

    // Define the relationship: A Question belongs to a Topic
    public function topic(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Boot method for Question model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($question) {
            $question->clearCourseSearchCache();
            // Clear question count cache for current topic
            Cache::forget("topic:{$question->topic_id}:questions_count");
            
            // If topic was changed, clear old topic cache too
            if ($question->wasChanged('topic_id')) {
                Cache::forget("topic:{$question->getOriginal('topic_id')}:questions_count");
            }
        });

        static::deleted(function ($question) {
            $question->clearCourseSearchCache();
            // Clear cache for current topic
            Cache::forget("topic:{$question->topic_id}:questions_count");
        });
    }

    private function clearCourseSearchCache(): void
    {
        $topicIds = array_filter([$this->topic_id, $this->getOriginal('topic_id')]);
        foreach (DB::table('topics')->whereIn('id', $topicIds)->distinct()->pluck('course_id') as $courseId) {
            Cache::forget("course_{$courseId}_questions_search_payload_v10");
            Cache::forget("course_{$courseId}_questions_search_payload_v11");
        }
    }

    // Define topics relationship: Consolidates main topic and extra_topic string into a single collection
    // Cached per-instance to prevent repeated DB queries when accessed multiple times
    public function getTopicsAttribute()
    {
        if (isset($this->topics_cached)) {
            return $this->topics_cached;
        }

        $topicIds = collect([$this->topic_id]);
        
        if (!empty($this->extra_topic)) {
            $extraIds = explode(',', $this->extra_topic);
            foreach ($extraIds as $id) {
                $topicIds->push((int) trim($id));
            }
        }
        
        $this->topics_cached = Topic::whereIn('id', $topicIds->unique()->filter())->get();
        return $this->topics_cached;
    }

    /**
     * ✅ ZERO N+1: Enrich an entire question collection with badge data in ONE query.
     * Call this in the controller before passing questions to a Blade view.
     *
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $questions
     * @param int $currentTopicId  The topic the student is currently viewing (its name badge is hidden).
     */
    public static function enrichWithBadges($questions, int $currentTopicId): void
    {
        // 1. Collect every extra topic ID AND main topic IDs (for cross-linked questions)
        $allExtraIds = collect();
        $allMainTopicIds = collect();
        foreach ($questions as $q) {
            $raw = is_array($q) ? ($q['extra_topic'] ?? null) : ($q->extra_topic ?? null);
            if (!empty($raw)) {
                foreach (explode(',', $raw) as $id) {
                    $allExtraIds->push((int) trim($id));
                }
            }
            // Also collect main topic IDs so we can resolve names for cross-linked questions
            $mainId = (int) (is_array($q) ? ($q['topic_id'] ?? 0) : ($q->topic_id ?? 0));
            if ($mainId > 0 && $mainId !== $currentTopicId) {
                $allMainTopicIds->push($mainId);
            }
        }

        // 2. Single query to resolve all needed topic names (extra + main for cross-linked)
        $allNeededIds = $allExtraIds->merge($allMainTopicIds)->unique()->filter()->values();
        $topicNames = [];
        if ($allNeededIds->isNotEmpty()) {
            $topicNames = Topic::whereIn('id', $allNeededIds)
                ->pluck('name', 'id')
                ->all();
        }

        // 3. Assign the pre-computed badge array to each question (no further DB hits)
        foreach ($questions as $q) {
            $qObj = is_array($q) ? $q : $q;
            $badges = [];

            $count = (int) (is_array($qObj) ? ($qObj['exam_appearances_count'] ?? 0) : ($qObj->exam_appearances_count ?? 0));
            if ($count >= 3) {
                $badges[] = '🔥 HIGH YIELD';
            } elseif ($count >= 2) {
                $badges[] = '📈 REPEATED';
            }

            $mainTopicId = (int) (is_array($qObj) ? ($qObj['topic_id'] ?? 0) : ($qObj->topic_id ?? 0));

            // When viewing a question from a linked topic (not its home topic),
            // show the main topic name as a badge so users see the exam origin
            if ($mainTopicId !== $currentTopicId && $mainTopicId > 0 && isset($topicNames[$mainTopicId])) {
                $name = strtoupper($topicNames[$mainTopicId]);
                if (!in_array($name, $badges, true)) {
                    $badges[] = $name;
                }
            }

            $raw = is_array($qObj) ? ($qObj['extra_topic'] ?? null) : ($qObj->extra_topic ?? null);
            if (!empty($raw)) {
                foreach (explode(',', $raw) as $idStr) {
                    $id = (int) trim($idStr);
                    // Skip the question's own main topic and the currently-viewed topic
                    if ($id === $mainTopicId || $id === $currentTopicId) {
                        continue;
                    }
                    if (isset($topicNames[$id])) {
                        $name = strtoupper($topicNames[$id]);
                        if (!in_array($name, $badges, true)) {
                            $badges[] = $name;
                        }
                    }
                }
            }

            // Stamp as a plain attribute so Blade reads it without triggering accessors
            if (!is_array($qObj)) {
                if ($qObj instanceof \Illuminate\Database\Eloquent\Model) {
                    $qObj->setRelation('_badges_resolved', collect());
                }
                $qObj->enhanced_source_badges_computed = $badges;
            }
        }
    }

    // Check if question belongs to an exam topic
    public function isFromExam(): bool
    {
        return $this->exam_appearances_count > 0;
    }

    // Get exam frequency level
    public function getExamFrequencyLevelAttribute(): string
    {
        if ($this->exam_appearances_count >= 5) return 'Very High';
        if ($this->exam_appearances_count >= 3) return 'High';
        if ($this->exam_appearances_count >= 2) return 'Medium';
        if ($this->exam_appearances_count >= 1) return 'Low';
        return 'None';
    }

    // Get exam frequency score (numeric)
    public function getExamFrequencyScoreAttribute(): int
    {
        return $this->exam_appearances_count;
    }

    /**
     * Returns badges. If enrichWithBadges() was called on the collection this is free.
     * Falls back to the N+1 path only when called individually (e.g. Tinker).
     */
    public function getEnhancedSourceBadgesAttribute(): array
    {
        // Fast path: already computed by enrichWithBadges()
        if (isset($this->enhanced_source_badges_computed)) {
            return $this->enhanced_source_badges_computed;
        }

        // Slow fallback (single question, e.g. Tinker)
        $badges = [];
        if ($this->exam_appearances_count >= 3) {
            $badges[] = '🔥 HIGH YIELD';
        } elseif ($this->exam_appearances_count >= 2) {
            $badges[] = '📈 REPEATED';
        }
        // Show ALL linked topic names (including main topic for cross-linked context).
        // The Blade template filters out the currently-viewed topic by name, so the
        // exam origin badge (e.g. "FINAL EXAM 2022") will appear when viewing from
        // a linked lecture topic, and will be hidden when viewing from the exam itself.
        foreach ($this->topics as $topic) {
            if ($topic->id === $this->topic_id && empty($this->extra_topic)) {
                // Only skip main topic if the question has no cross-links
                continue;
            }
            $name = strtoupper($topic->name);
            if (!in_array($name, $badges, true)) {
                $badges[] = $name;
            }
        }
        return $badges;
    }

    // source_badges kept for backward compat but only used in single-question context
    public function getSourceBadgesAttribute(): array
    {
        $badges = [];
        foreach ($this->topics as $topic) {
            if ($topic->id === $this->topic_id) continue;
            $name = strtoupper($topic->name);
            if (!in_array($name, $badges, true)) $badges[] = $name;
        }
        return $badges;
    }

    // Get exam origin text for trust signals
    // Get source text for under the question (excluding the current owner topic)
    public function getExamOriginTextAttribute(): string
    {
        $extraTopics = $this->topics->filter(fn($t) => $t->id != $this->topic_id);

        if ($extraTopics->isEmpty()) {
            return '';
        }
        
        $origins = $extraTopics->map(fn($topic) => $topic->name)->toArray();
        
        return 'Source: ' . implode(', ', $origins);
    }

    // Calculate and update recurrence count based on extra_topic string
    public function updateExamFrequencyScore(): void
    {
        if (empty($this->extra_topic)) {
            $this->update(['exam_appearances_count' => 0]);
            return;
        }

        $extraIds = array_filter(explode(',', $this->extra_topic));
        $this->update([
            'exam_appearances_count' => count($extraIds)
        ]);
    }

    /**
     * Scope for questions that have at least one link.
     */
    public function scopeFromExam($query)
    {
        return $query->whereNotNull('extra_topic')->where('extra_topic', '!=', '');
    }

    /**
     * Scope for high-yield questions (appeared in 3+ exams).
     */
    public function scopeHighYield($query)
    {
        return $query->where('exam_appearances_count', '>=', 3);
    }

    /**
     * Get all mistakes associated with this question.
     */
    public function mistakes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Mistake::class);
    }

    public function getConceptsAttribute(): Collection
    {
        $concept = trim((string) ($this->attributes['concept'] ?? ''));
        if ($concept === '') {
            return collect();
        }

        return collect([
            (object) [
                'id' => abs(crc32(Str::slug($concept))),
                'name' => $concept,
                'slug' => Str::slug($concept),
            ],
        ]);
    }

    public function assignConceptTags(array $conceptNames): void
    {
        $concept = collect($conceptNames)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn (string $name) => Str::lower($name))
            ->first();

        $this->forceFill([
            'concept' => $concept ?: null,
        ]);
    }

    public function getPrimaryConceptNameAttribute(): ?string
    {
        $concept = trim((string) ($this->attributes['concept'] ?? ''));
        return $concept !== '' ? $concept : null;
    }

    public function getPrimaryConceptSlugAttribute(): ?string
    {
        $concept = $this->primary_concept_name;

        if ($concept === null || $concept === '') {
            return null;
        }

        return Str::slug($concept);
    }

    public function getConceptTagsAttribute(): array
    {
        $concept = $this->primary_concept_name;

        if ($concept === null || $concept === '') {
            return [];
        }

        return [[
            'name' => $concept,
            'slug' => Str::slug($concept),
        ]];
    }

    /**
     * Get all quiz attempts that reference this question.
     * Since quiz_attempts doesn't have a question_id column,
     * we need to query through the question_data JSON field.
     */
    public function quizAttempts()
    {
        // This is a complex relationship since quiz_attempts stores question data in JSON
        // For now, return an empty collection to prevent errors
        // In a real implementation, you'd need to query the JSON field
        return collect();
    }

    /**
     * Get the detailed explanation for this question.
     */
    public function detailedExplanation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(QuestionExplanation::class);
    }

    /**
     * Get the success rate for this question.
     * Returns 0 – placeholder for future implementation.
     */
    public function getSuccessRateAttribute(): float
    {
        return 0;
    }

    /**
     * Check if the submitted answer is correct for this question.
     *
     * @param string $submittedAnswer The answer submitted by the user.
     * @return bool True if correct, false otherwise.
     */
    public function isAnswerCorrect(string $submittedAnswer): bool
    {
        if ($this->question_type === self::TYPE_MULTIPLE_CHOICE) {
            return strtoupper(trim($submittedAnswer)) === strtoupper(trim($this->correct_answer));
        } elseif ($this->question_type === self::TYPE_WRITTEN) {
            try {
                $safeSubmittedAnswer = trim((string)$submittedAnswer);
                if ($safeSubmittedAnswer === '') {
                    return false;
                }

                return (bool) app(\App\Services\WrittenAnswerEvaluationService::class)
                    ->isCorrect($this, $safeSubmittedAnswer);
            } catch (\Throwable $e) {
                Log::error('Error checking written answer', [
                    'question_id' => $this->id,
                    'error' => $e->getMessage()
                ]);

                return false;
            }
        }
        
        return false;
    }
    /**
     * Get questions that the user has made mistakes on.
     * Now uses user-specific mistake data instead of question-level metrics.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getMistakesForUser(int $userId)
    {
        return static::whereHas('mistakes', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get();
    }

    /**
     * Get the total number of questions in the system.
     * Returns the count of all questions.
     *
     * @return int
     */
    public static function getTotalQuestionsCount(): int
    {
        return static::count();
    }
}
