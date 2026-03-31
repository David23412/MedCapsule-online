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
    
    // Standardized default threshold for written answer similarity
    const DEFAULT_SIMILARITY_THRESHOLD = 0.8; // 80% similarity required by default

    protected $fillable = [
        'topic_id',
        'question', // Actual database field name
        'explanation',
        'question_type',
        'option_a', // Actual database field names
        'option_b',
        'option_c', 
        'option_d',
        'correct_answer',
        'image_url',
        'source',
        'concept',
        'alternative_answers',
        'exam_appearances_count',
        'extra_topic'
    ];

    protected $casts = [
        'topic_id' => 'integer',
        'alternative_answers' => 'string',
        'exam_appearances_count' => 'integer'
    ];

    // Remove the problematic accessors that were trying to map to a different schema
    // The database already has the correct field names, so we don't need accessors

    // Define the relationship: A Question belongs to a Topic
    public function topic()
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
            // Clear question count cache for current topic
            Cache::forget("topic:{$question->topic_id}:questions_count");
            
            // If topic was changed, clear old topic cache too
            if ($question->wasChanged('topic_id')) {
                Cache::forget("topic:{$question->getOriginal('topic_id')}:questions_count");
            }
        });

        static::deleted(function ($question) {
            // Clear cache for current topic
            Cache::forget("topic:{$question->topic_id}:questions_count");
        });
    }

    // Define topics relationship: Consolidates main topic and extra_topic string into a single collection
    public function getTopicsAttribute()
    {
        $topicIds = collect([$this->topic_id]);
        
        if (!empty($this->extra_topic)) {
            $extraIds = explode(',', $this->extra_topic);
            foreach ($extraIds as $id) {
                $topicIds->push((int) trim($id));
            }
        }
        
        return Topic::whereIn('id', $topicIds->unique()->filter())->get();
    }

    // DEPRECATED: Pivot table removed. User extra_topic string instead.
    public function additionalTopics()
    {
        return collect();
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
        // 1. Collect every extra topic ID referenced across all questions
        $allExtraIds = collect();
        foreach ($questions as $q) {
            $raw = is_array($q) ? ($q['extra_topic'] ?? null) : ($q->extra_topic ?? null);
            if (!empty($raw)) {
                foreach (explode(',', $raw) as $id) {
                    $allExtraIds->push((int) trim($id));
                }
            }
        }

        // 2. Single query to resolve all needed topic names
        $topicNames = [];
        if ($allExtraIds->isNotEmpty()) {
            $topicNames = Topic::whereIn('id', $allExtraIds->unique()->filter()->values())
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

            $raw = is_array($qObj) ? ($qObj['extra_topic'] ?? null) : ($qObj->extra_topic ?? null);
            if (!empty($raw)) {
                $mainTopicId = (int) (is_array($qObj) ? ($qObj['topic_id'] ?? 0) : ($qObj->topic_id ?? 0));
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
                $qObj->setRelation('_badges_resolved', collect()); // mark as processed
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
        foreach ($this->topics as $topic) {
            if ($topic->id === $this->topic_id) continue;
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
    public function mistakes()
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
    public function detailedExplanation()
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
                // Make sure we're working with valid strings before comparing
                $safeSubmittedAnswer = trim((string)$submittedAnswer);
                if (empty($safeSubmittedAnswer)) {
                    return false;
                }

                $evaluationService = app(\App\Services\WrittenAnswerEvaluationService::class);

                if (app()->environment('testing')) {
                    $evaluationService->setCacheConfig(false);
                }

                return (bool) $evaluationService->isCorrect($this, $safeSubmittedAnswer);
            } catch (\Throwable $e) {
                // Log error but don't crash
                Log::error('Error checking written answer', [
                    'question_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                // Default to false on error
                return $this->matchesWrittenAnswerFallback((string) $submittedAnswer);
            }
        }
        
        return false;
    }

    /**
     * Legacy fallback used when the main written evaluator is unavailable.
     */
    public function matchesWrittenAnswerFallback(string $submittedAnswer): bool
    {
        return $this->isWrittenAnswerCorrect($submittedAnswer);
    }

    /**
     * Check if a written answer is correct using forgiving text comparison
     * 
     * @param string $submittedAnswer
     * @return bool
     */
    protected function isWrittenAnswerCorrect(string $submittedAnswer): bool
    {
        try {
            // Trim and lowercase for basic normalization
            $submittedAnswer = trim(strtolower($submittedAnswer));
            $correctAnswer = trim(strtolower($this->correct_answer));
            
            // Empty answers are never correct
            if (empty($submittedAnswer)) {
                return false;
            }

            // Immediately reject extremely short answers (single letters/characters)
            // If the correct answer is more than 3 characters, reject single character answers
            if (strlen($correctAnswer) > 3 && strlen($submittedAnswer) <= 1) {
                return false;
            }
            
            // Initial checks for obviously incorrect answers
            
            // 1. Length check - if student answer is too short compared to the correct answer
            $lengthRatio = strlen($submittedAnswer) / strlen($correctAnswer);
            $lengthMismatch = $lengthRatio < 0.6 || $lengthRatio > 1.75;
            
            // More stringent length check for short correct answers (to prevent "m" matching "midbrain")
            if (strlen($correctAnswer) < 10 && $lengthRatio < 0.5) {
                return false;
            }
            
            if ($lengthMismatch) {
            }
            
            // 2. Check exact match first (fastest path)
            if ($submittedAnswer === $correctAnswer) {
                return true;
            }
            
            // 3. Check alternative answers if available
            if (!empty($this->alternative_answers)) {
                // Split the text by newlines and check each alternative answer
                $altAnswers = array_filter(explode("\n", $this->alternative_answers), function($line) {
                    return trim($line) !== '';
                });

                foreach ($altAnswers as $altAnswer) {
                    if (empty($altAnswer)) continue;

                    $altAnswer = trim(strtolower($altAnswer));
                    if ($submittedAnswer === $altAnswer) {
                        return true;
                    }
                }
            }
            
            // Deeper similarity analysis
            
            // 4. Determine similarity threshold based on question and answer properties
            $baseThreshold = self::DEFAULT_SIMILARITY_THRESHOLD;
            
            // Adjust threshold based on various factors
            $adjustedThreshold = $baseThreshold;
            
            // Length mismatch requires higher threshold
            if ($lengthMismatch) {
                $adjustedThreshold += 0.1;
            }
            
            // Short answers should have higher threshold (easier to match by chance)
            if (str_word_count($correctAnswer) < 5) {
                $adjustedThreshold += 0.05;
            }
            
            // For questions with numbers/units, require higher similarity (precision matters)
            if (preg_match('/\d/', $correctAnswer)) {
                $adjustedThreshold += 0.05;
            }
            
            
            // 5. Normalize and compare texts
            $normalizedSubmitted = $this->normalizeTextForComparison($submittedAnswer);
            $normalizedCorrect = $this->normalizeTextForComparison($correctAnswer);
            
            // 6. Early rejection for clearly different answers
            // If normalized texts share almost no words, they're clearly different
            $submittedWords = explode(' ', $normalizedSubmitted);
            $correctWords = explode(' ', $normalizedCorrect);
            $sharedWordCount = count(array_intersect($submittedWords, $correctWords));
            
            if ($sharedWordCount === 0) {
                return false;
            }
            
            // 7. Calculate similarity
            $similarityScore = $this->calculateTextSimilarity($normalizedSubmitted, $normalizedCorrect);
            
            // 8. Check if similarity exceeds threshold
            if ($similarityScore >= $adjustedThreshold) {
                // Even with high similarity, check for critical negations
                if ($this->containsOppositeNegation($submittedAnswer, $correctAnswer)) {
                    return false;
                }
                
                return true;
            }
            
            // 9. Check similarity against alternative answers
            if (!empty($this->alternative_answers)) {
                // Split the text by newlines and check each alternative answer
                $altAnswers = array_filter(explode("\n", $this->alternative_answers), function($line) {
                    return trim($line) !== '';
                });

                foreach ($altAnswers as $altAnswer) {
                    if (empty($altAnswer)) continue;

                    $normalizedAlt = $this->normalizeTextForComparison(trim(strtolower($altAnswer)));
                    $altSimilarity = $this->calculateTextSimilarity($normalizedSubmitted, $normalizedAlt);

                    if ($altSimilarity >= $adjustedThreshold) {
                        return true;
                    }
                }
            }
            
            // 10. Last resort: key terms check (much more selective now with word boundaries)
            if ($this->containsKeyTerms($submittedAnswer, $correctAnswer)) {
                return true;
            }
            
            return false;
        } catch (\Throwable $e) {
            // Log error but don't crash
            Log::error('Error in written answer comparison', [
                'question_id' => $this->id,
                'submitted' => $submittedAnswer,
                'correct' => $this->correct_answer,
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            // Default to false on error
            return false;
        }
    }
    
    /**
     * Normalize text for smart comparison
     * 
     * @param string $text
     * @return string
     */
    protected function normalizeTextForComparison(string $text): string
    {
        try {
            // Input validation and length protection
            if (empty($text)) {
                return '';
            }
            
            // Protect against excessive text length
            if (strlen($text) > 2000) {
                Log::warning('Text too long for normalization, truncating', [
                    'length' => strlen($text),
                    'truncated_to' => 2000
                ]);
                $text = substr($text, 0, 2000);
            }
            
            // Normalize number formats
            $text = $this->normalizeNumberFormats($text);
            
            // Step 1: Pre-processing
            // Standardize common variations (spacing, punctuation)
            $text = str_replace(['/', '-', '_', '+'], ' ', $text);
            
            // Step 2: Uniform spacing for compound terms - SAFE REGEX
            // This helps with compound medical terms like "T cell" vs "Tcell"
            $text = $this->safeRegexReplace('/([a-z])([A-Z])/', '$1 $2', $text); // Split camelCase
            
            // Step 3: Remove punctuation - SAFE REGEX
            $text = $this->safeRegexReplace('/[^\p{L}\p{N}\s]/u', ' ', $text);
            
            // Step 4: Remove extra whitespace - SAFE REGEX  
            $text = $this->safeRegexReplace('/\s+/', ' ', $text);
            
            // Step 5: Remove common filler words
            $fillerWords = [
                'a', 'an', 'the', 'and', 'or', 'but', 'if', 'is', 'are', 
                'that', 'this', 'to', 'in', 'on', 'at', 'for', 'of', 'by', 'with'
            ];
            
            $words = explode(' ', $text);
            $words = array_filter($words, function($word) use ($fillerWords) {
                return !in_array(strtolower($word), $fillerWords) && strlen(trim($word)) > 1;
            });
            
            return implode(' ', $words);
        } catch (\Exception $e) {
            Log::error('Error in text normalization:', [
                'text' => substr($text, 0, 100) . '...',
                'error' => $e->getMessage()
            ]);
            return $text; // Return original text in case of error
        }
    }
    
    /**
     * Normalize different number formats to enable better comparison
     * 
     * @param string $text
     * @return string
     */
    protected function normalizeNumberFormats(string $text): string
    {
        try {
            // Length protection
            if (strlen($text) > 2000) {
                $text = substr($text, 0, 2000);
            }
            
            // Replace comma decimal separators with dots (e.g., "2,5" -> "2.5") - SAFE REGEX
            $text = $this->safeRegexReplace('/(\d),(\d)/', '$1.$2', $text);
            
            // Standardize spacing around units (e.g., "5mg" -> "5 mg") - SAFE REGEX
            $text = $this->safeRegexReplace('/(\d)([a-zA-Z]+)/', '$1 $2', $text);
            
            // Handle standard number words (e.g., "three" -> "3")
            $numberWords = [
                'zero' => '0', 'one' => '1', 'two' => '2', 'three' => '3', 'four' => '4',
                'five' => '5', 'six' => '6', 'seven' => '7', 'eight' => '8', 'nine' => '9', 'ten' => '10'
            ];
            
            foreach ($numberWords as $word => $digit) {
                $text = $this->safeRegexReplace('/\b' . $word . '\b/i', $digit, $text);
            }
            
            // Standardize range formats (e.g., "5-10" as "5 to 10") - SAFE REGEX
            $text = $this->safeRegexReplace('/(\d+)\s*(?:to|-)\s*(\d+)/', '$1 to $2', $text);
            
            return $text;
        } catch (\Exception $e) {
            Log::error('Error normalizing numbers:', [
                'error' => $e->getMessage(),
                'text' => substr($text, 0, 100) . '...'
            ]);
            return $text; // Return original text in case of error
        }
    }
    
    /**
     * Check if submitted answer contains the key terms from the correct answer
     * This helps when answer structure differs but essential concepts are present
     * 
     * @param string $submittedAnswer
     * @param string $correctAnswer
     * @return bool
     */
    protected function containsKeyTerms(string $submittedAnswer, string $correctAnswer): bool
    {
        try {
            // Extract important words (longer words tend to be more significant)
            $correctWords = explode(' ', $correctAnswer);
            
            // Important short medical terms that should always be considered key terms
            $importantShortTerms = [
                'cell', 'gene', 'mrna', 'dna', 'rna', 'lung', 'heart', 'bone', 'atom',
                'acid', 'base', 'salt', 'vein', 'burn', 'gas', 'pain', 'gene', 'fat',
                'acth', 'tsh', 'pth', 'fsh', 'lh', 'gh', 'cns', 'csf', 'ecg', 'eeg',
                'mri', 'ct', 'pet', 'bun', 'alt', 'ast', 'ldh', 'hdl', 'ldl', 'vldl'
            ];
            
            $keyTerms = array_filter($correctWords, function($word) use ($importantShortTerms) {
                $word = strtolower(trim($word));
                // Consider both longer words (> 4 chars) and important short medical terms
                return strlen($word) > 4 || in_array($word, $importantShortTerms);
            });
            
            // If correct answer doesn't have enough key terms, we can't use this method
            if (count($keyTerms) < 2) {
                return false;
            }
            
            // Simple word matching without complex position checking
            $matchCount = 0;
            $submittedLower = strtolower($submittedAnswer);
            
            foreach ($keyTerms as $term) {
                if (strpos($submittedLower, strtolower($term)) !== false) {
                    $matchCount++;
                }
            }
            
            // Require at least 60% of key terms to be present
            $requiredMatchRatio = 0.6;
            $actualMatchRatio = count($keyTerms) > 0 ? $matchCount / count($keyTerms) : 0;
            
            return $actualMatchRatio >= $requiredMatchRatio;
            
        } catch (\Exception $e) {
            Log::error('Error in key terms check', [
                'error' => $e->getMessage(),
                'question_id' => $this->id
            ]);
            return false;
        }
    }
    
    /**
     * Calculate text similarity using a simple, reliable approach
     * This method is designed to work in both local and production environments
     * 
     * @param string $text1
     * @param string $text2
     * @return float Value between 0 and 1
     */
    protected function calculateTextSimilarity(string $text1, string $text2): float
    {
        try {
            // MEMORY PROTECTION: Limit input size to prevent memory exhaustion
            if (strlen($text1) > 5000 || strlen($text2) > 5000) {
                Log::warning('Text too long for similarity calculation', [
                    'text1_length' => strlen($text1),
                    'text2_length' => strlen($text2),
                    'max_allowed' => 5000
                ]);
                return 0.0;
            }
            
            // If either is empty after normalization, they can't be similar
            if (empty($text1) || empty($text2)) {
                return 0.0;
            }
            
            // Quick length check - if lengths are dramatically different, they're likely not similar
            $lengthRatio = min(strlen($text1), strlen($text2)) / max(strlen($text1), strlen($text2));
            if ($lengthRatio < 0.3) {
                return 0.0; // Return 0 for very different lengths
            }
            
            // OPTIMIZED: Use simple word overlap for performance and memory safety
            $words1 = array_filter(explode(' ', strtolower(trim($text1))), function($word) {
                return strlen($word) > 2;
            });
            $words2 = array_filter(explode(' ', strtolower(trim($text2))), function($word) {
                return strlen($word) > 2;
            });
            
            // MEMORY PROTECTION: Limit word count to prevent memory issues
            if (count($words1) > 1000 || count($words2) > 1000) {
                Log::warning('Too many words for similarity calculation', [
                    'words1_count' => count($words1),
                    'words2_count' => count($words2),
                    'max_allowed' => 1000
                ]);
                return 0.0;
            }
            
            // Empty after filtering?
            if (empty($words1) || empty($words2)) {
                return 0.0;
            }
            
            $intersection = array_intersect($words1, $words2);
            $union = array_unique(array_merge($words1, $words2));
            
            $wordOverlapRatio = count($union) > 0 ? count($intersection) / count($union) : 0.0;
            
            // Simple character-based similarity for short strings only
            $charSimilarity = 0;
            if (strlen($text1) <= 100 && strlen($text2) <= 100) {
                $same = 0;
                $maxLen = min(strlen($text1), strlen($text2));
                for ($i = 0; $i < $maxLen; $i++) {
                    if ($text1[$i] === $text2[$i]) {
                        $same++;
                    }
                }
                $charSimilarity = $maxLen > 0 ? $same / $maxLen : 0;
            }
            
            // Weighted combination: 70% word overlap, 30% character similarity
            $similarity = ($wordOverlapRatio * 0.7) + ($charSimilarity * 0.3);
            
            // PERFORMANCE MONITORING: Log slow calculations
            $executionStart = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
            $executionTime = microtime(true) - $executionStart;
            if ($executionTime > 0.1) { // 100ms threshold
                Log::warning('Text similarity calculation slow', [
                    'execution_time' => round($executionTime * 1000, 2) . 'ms',
                    'text1_length' => strlen($text1),
                    'text2_length' => strlen($text2),
                    'words1_count' => count($words1),
                    'words2_count' => count($words2)
                ]);
            }
            
            return $similarity;
            
        } catch (\Exception $e) {
            Log::error('Error in similarity calculation', [
                'error' => $e->getMessage(),
                'text1_length' => strlen($text1),
                'text2_length' => strlen($text2),
                'memory_usage' => memory_get_usage(true)
            ]);
            // Return low similarity on error to be conservative
            return 0.1;
        }
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
