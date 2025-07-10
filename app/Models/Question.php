<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\CacheService;

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
        'question',
        'explanation',
        'image_url',
        'question_type',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
        'alternative_answers',
        'total_attempts',
        'correct_attempts',
        'last_attempted_at',
        'similarity_threshold',
        'source',
        'deleted'
    ];

    protected $casts = [
        'topic_id' => 'integer',
        'last_attempted_at' => 'datetime',
        'total_attempts' => 'integer',
        'correct_attempts' => 'integer',
        'alternative_answers' => 'array',
        'similarity_threshold' => 'float',
        'deleted' => 'boolean'
    ];

    // Define the relationship: A Question belongs to a Topic
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get all mistakes associated with this question.
     */
    public function mistakes()
    {
        return $this->hasMany(Mistake::class);
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
     * Returns 0 if no attempts have been made.
     */
    public function getSuccessRateAttribute(): float
    {
        return $this->total_attempts > 0 
            ? ($this->correct_attempts / $this->total_attempts) * 100 
            : 0;
    }

    /**
     * Record an attempt and update total and correct attempts.
     * Returns true if the answer was correct, false otherwise.
     *
     * @param string $submittedAnswer The answer submitted by the user.
     * @return bool
     */
    public function recordAttempt(string $submittedAnswer): bool
    {
        // Use appropriate grading method based on question type
        $isCorrect = $this->isAnswerCorrect($submittedAnswer);

        // Increment total attempts and correct attempts if applicable
        $this->increment('total_attempts');
        if ($isCorrect) {
            $this->increment('correct_attempts');
        }

        // Update the last attempted timestamp
        $this->update(['last_attempted_at' => now()]);

        return $isCorrect;
    }

    /**
     * Check if an answer is correct based on the question type
     * Handles null values for $submittedAnswer
     * 
     * @param string|null $submittedAnswer
     * @return bool
     */
    public function isAnswerCorrect($submittedAnswer): bool
    {
        // Handle null or empty answers
        if ($submittedAnswer === null) {
            return false;
        }
        
        // Ensure submitted answer is always a string
        $submittedAnswer = (string)$submittedAnswer;
        
        if ($this->question_type === self::TYPE_MULTIPLE_CHOICE) {
            return $submittedAnswer === $this->correct_answer;
        } elseif ($this->question_type === self::TYPE_WRITTEN) {
            try {
                // Make sure we're working with valid strings before comparing
                $safeSubmittedAnswer = trim((string)$submittedAnswer);
                if (empty($safeSubmittedAnswer)) {
                    return false;
                }
                return $this->isWrittenAnswerCorrect($safeSubmittedAnswer);
            } catch (\Exception $e) {
                // Log error but don't crash
                Log::error('Error checking written answer', [
                    'question_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                // Default to false on error
                return false;
            }
        }
        
        return false;
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
                Log::info('Answer rejected - too short', [
                    'question_id' => $this->id,
                    'submitted_length' => strlen($submittedAnswer),
                    'correct_length' => strlen($correctAnswer)
                ]);
                return false;
            }
            
            // Initial checks for obviously incorrect answers
            
            // 1. Length check - if student answer is too short compared to the correct answer
            $lengthRatio = strlen($submittedAnswer) / strlen($correctAnswer);
            $lengthMismatch = $lengthRatio < 0.6 || $lengthRatio > 1.75;
            
            // More stringent length check for short correct answers (to prevent "m" matching "midbrain")
            if (strlen($correctAnswer) < 10 && $lengthRatio < 0.5) {
                Log::info('Answer rejected - too short for short answer question', [
                    'question_id' => $this->id,
                    'ratio' => $lengthRatio
                ]);
                return false;
            }
            
            if ($lengthMismatch) {
                Log::info('Answer length mismatch', [
                    'question_id' => $this->id,
                    'ratio' => $lengthRatio
                ]);
            }
            
            // 2. Check exact match first (fastest path)
            if ($submittedAnswer === $correctAnswer) {
                return true;
            }
            
            // 3. Check alternative answers if available
            if (!empty($this->alternative_answers)) {
                foreach ($this->alternative_answers as $altAnswer) {
                    if (empty($altAnswer)) continue;
                    
                    $altAnswer = trim(strtolower($altAnswer));
                    if ($submittedAnswer === $altAnswer) {
                        return true;
                    }
                }
            }
            
            // Deeper similarity analysis
            
            // 4. Determine similarity threshold based on question and answer properties
            $baseThreshold = $this->similarity_threshold ?? self::DEFAULT_SIMILARITY_THRESHOLD;
            
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
            
            Log::info('Using adjusted threshold', [
                'question_id' => $this->id,
                'base_threshold' => $baseThreshold,
                'adjusted_threshold' => $adjustedThreshold
            ]);
            
            // 5. Normalize and compare texts
            $normalizedSubmitted = $this->normalizeTextForComparison($submittedAnswer);
            $normalizedCorrect = $this->normalizeTextForComparison($correctAnswer);
            
            // 6. Early rejection for clearly different answers
            // If normalized texts share almost no words, they're clearly different
            $submittedWords = explode(' ', $normalizedSubmitted);
            $correctWords = explode(' ', $normalizedCorrect);
            $sharedWordCount = count(array_intersect($submittedWords, $correctWords));
            
            if ($sharedWordCount === 0) {
                Log::info('No shared words after normalization, rejecting immediately', [
                    'question_id' => $this->id
                ]);
                return false;
            }
            
            // 7. Calculate similarity
            $similarityScore = $this->calculateTextSimilarity($normalizedSubmitted, $normalizedCorrect);
            
            Log::info('Similarity calculation complete', [
                'question_id' => $this->id,
                'score' => $similarityScore,
                'threshold' => $adjustedThreshold,
                'submitted' => substr($submittedAnswer, 0, 50) . (strlen($submittedAnswer) > 50 ? '...' : '')
            ]);
            
            // 8. Check if similarity exceeds threshold
            if ($similarityScore >= $adjustedThreshold) {
                // Even with high similarity, check for critical negations
                if ($this->containsOppositeNegation($submittedAnswer, $correctAnswer)) {
                    Log::info('Answer rejected due to opposite negation', [
                        'question_id' => $this->id
                    ]);
                    return false;
                }
                
                Log::info('Answer accepted by similarity check', [
                    'question_id' => $this->id,
                    'score' => $similarityScore,
                    'threshold' => $adjustedThreshold
                ]);
                return true;
            }
            
            // 9. Check similarity against alternative answers
            if (!empty($this->alternative_answers)) {
                foreach ($this->alternative_answers as $altAnswer) {
                    if (empty($altAnswer)) continue;
                    
                    $normalizedAlt = $this->normalizeTextForComparison(trim(strtolower($altAnswer)));
                    $altSimilarity = $this->calculateTextSimilarity($normalizedSubmitted, $normalizedAlt);
                    
                    if ($altSimilarity >= $adjustedThreshold) {
                        Log::info('Answer accepted by alternative similarity check', [
                            'question_id' => $this->id,
                            'score' => $altSimilarity,
                            'threshold' => $adjustedThreshold
                        ]);
                        return true;
                    }
                }
            }
            
            // 10. Last resort: key terms check (much more selective now with word boundaries)
            if ($this->containsKeyTerms($submittedAnswer, $correctAnswer)) {
                Log::info('Answer accepted by key terms check', [
                    'question_id' => $this->id
                ]);
                return true;
            }
            
            Log::info('Answer rejected - all checks failed', [
                'question_id' => $this->id,
                'best_similarity' => $similarityScore
            ]);
            return false;
        } catch (\Exception $e) {
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
            // Normalize number formats
            $text = $this->normalizeNumberFormats($text);
            
            // Step 1: Pre-processing
            // Standardize common variations (spacing, punctuation)
            $text = str_replace(['/', '-', '_', '+'], ' ', $text);
            
            // Step 2: Uniform spacing for compound terms
            // This helps with compound medical terms like "T cell" vs "Tcell"
            $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text); // Split camelCase
            
            // Step 3: Remove punctuation
            $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
            
            // Step 4: Remove extra whitespace
            $text = preg_replace('/\s+/', ' ', $text);
            
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
                'text' => $text,
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
            // Replace comma decimal separators with dots (e.g., "2,5" -> "2.5")
            $text = preg_replace('/(\d),(\d)/', '$1.$2', $text);
            
            // Standardize spacing around units (e.g., "5mg" -> "5 mg")
            $text = preg_replace('/(\d)([a-zA-Z]+)/', '$1 $2', $text);
            
            // Handle standard number words (e.g., "three" -> "3")
            $numberWords = [
                'zero' => '0', 'one' => '1', 'two' => '2', 'three' => '3', 'four' => '4',
                'five' => '5', 'six' => '6', 'seven' => '7', 'eight' => '8', 'nine' => '9', 'ten' => '10'
            ];
            
            foreach ($numberWords as $word => $digit) {
                $text = preg_replace('/\b' . $word . '\b/i', $digit, $text);
            }
            
            // Standardize range formats (e.g., "5-10" as "5 to 10")
            $text = preg_replace('/(\d+)\s*(?:to|-)\s*(\d+)/', '$1 to $2', $text);
            
            return $text;
        } catch (\Exception $e) {
            Log::error('Error normalizing numbers:', [
                'error' => $e->getMessage(),
                'text' => $text
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
            // If either is empty after normalization, they can't be similar
            if (empty($text1) || empty($text2)) {
                return 0.0;
            }
            
            // Quick length check - if lengths are dramatically different, they're likely not similar
            $lengthRatio = min(strlen($text1), strlen($text2)) / max(strlen($text1), strlen($text2));
            if ($lengthRatio < 0.3) {
                return 0.0; // Return 0 for very different lengths
            }
            
            // Calculate word overlap ratio (Jaccard similarity)
            $words1 = explode(' ', $text1);
            $words2 = explode(' ', $text2);
            
            // Filter out single character words and common words
            $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those'];
            
            $words1 = array_filter($words1, function($word) use ($commonWords) { 
                $word = strtolower(trim($word));
                return strlen($word) > 2 && !in_array($word, $commonWords); 
            });
            $words2 = array_filter($words2, function($word) use ($commonWords) { 
                $word = strtolower(trim($word)); 
                return strlen($word) > 2 && !in_array($word, $commonWords); 
            });
            
            // Empty after filtering?
            if (empty($words1) || empty($words2)) {
                return 0.0;
            }
            
            $intersection = array_intersect($words1, $words2);
            $union = array_unique(array_merge($words1, $words2));
            
            $wordOverlapRatio = count($intersection) / (count($union) ?: 1);
            
            // Simple character-based similarity for short strings
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
            return ($wordOverlapRatio * 0.7) + ($charSimilarity * 0.3);
            
        } catch (\Exception $e) {
            Log::error('Error in similarity calculation', [
                'error' => $e->getMessage(),
                'text1_length' => strlen($text1),
                'text2_length' => strlen($text2)
            ]);
            // Return low similarity on error to be conservative
            return 0.1;
        }
    }
    


    /**
     * Get all incorrect answers (mistakes) for a user.
     * This is useful for tracking user progress and mistakes.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getMistakesForUser(int $userId)
    {
        return static::where('total_attempts', '>', 0)
            ->where('correct_attempts', '<', 'total_attempts')
            ->whereHas('mistakes', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get();
    }

    /**
     * Get the total number of attempts made by users for a specific question.
     * Can be used to display insights on the question's difficulty.
     *
     * @return int
     */
    public function getTotalAttemptsCount(): int
    {
        // Cache the total attempts for 1 hour for better performance
        return Cache::remember("question:{$this->id}:total_attempts", 3600, function () {
            return $this->total_attempts;
        });
    }

    /**
     * Get the total number of correct attempts made by users for a specific question.
     * Can be used for analytics and question difficulty analysis.
     *
     * @return int
     */
    public function getCorrectAttemptsCount(): int
    {
        // Cache the correct attempts for 1 hour for better performance
        return Cache::remember("question:{$this->id}:correct_attempts", 3600, function () {
            return $this->correct_attempts;
        });
    }

    /**
     * Boot method for handling model events like saving and updating.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically clear all caches when a question is updated or deleted
        static::deleted(function ($question) {
            try {
                // Clear all caches using the CacheService
                $cacheService = app(CacheService::class);
                $cacheService->clearQuestionCaches($question->id, $question->topic_id);
                
                // Force clear all session data that might contain questions
                session()->flush();
                
                // Log the deletion and cache clearing
                Log::info('Question deleted and all caches cleared', [
                    'question_id' => $question->id,
                    'topic_id' => $question->topic_id,
                    'session_cleared' => true
                ]);
            } catch (\Exception $e) {
                Log::error('Error clearing caches after question deletion', [
                    'question_id' => $question->id,
                    'error' => $e->getMessage()
                ]);
            }
        });

        // Similar cache clearing for updates
        static::updated(function ($question) {
            try {
                // Clear all caches using the CacheService
                $cacheService = app(CacheService::class);
                $cacheService->clearQuestionCaches($question->id, $question->topic_id);
                
                // Log the update and cache clearing
                Log::info('Question updated and all caches cleared', [
                    'question_id' => $question->id,
                    'topic_id' => $question->topic_id
                ]);
            } catch (\Exception $e) {
                Log::error('Error clearing caches after question update', [
                    'question_id' => $question->id,
                    'error' => $e->getMessage()
                ]);
            }
        });

        // PHASE 2: Dispatch QuestionDeleted event when question is deleted
        static::deleted(function ($question) {
            event(new \App\Events\QuestionDeleted(
                $question,
                $question->topic_id,
                $question->source
            ));
        });
    }

    /**
     * Check if answer contains negation that changes meaning
     * This helps prevent answers with opposite meanings from being marked correct
     * 
     * @param string $submittedAnswer
     * @param string $correctAnswer
     * @return bool True if negation changes meaning
     */
    protected function containsOppositeNegation(string $submittedAnswer, string $correctAnswer): bool 
    {
        $negationWords = ['not', 'no', 'never', 'doesn\'t', 'does not', 'isn\'t', 'is not', 'cannot', 'can\'t'];
        
        // Check if negation patterns exist in one but not the other
        $submittedHasNegation = false;
        $correctHasNegation = false;
        
        foreach ($negationWords as $negation) {
            if (strpos($submittedAnswer, $negation) !== false) {
                $submittedHasNegation = true;
            }
            
            if (strpos($correctAnswer, $negation) !== false) {
                $correctHasNegation = true;
            }
        }
        
        // If negation status differs, answers likely have opposite meanings
        return $submittedHasNegation !== $correctHasNegation;
    }
}
