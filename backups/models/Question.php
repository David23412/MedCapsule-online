<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Question extends Model
{
    use HasFactory;

    // Question types constants
    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_WRITTEN = 'written';

    // Similarity threshold for written answers
    const SIMILARITY_THRESHOLD = 0.8;

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
        'similarity_threshold'
    ];

    protected $casts = [
        'topic_id' => 'integer',
        'last_attempted_at' => 'datetime',
        'total_attempts' => 'integer',
        'correct_attempts' => 'integer',
        'alternative_answers' => 'array',
        'similarity_threshold' => 'float'
    ];

    // Define the relationship: A Question belongs to a Topic
    public function topic()
    {
        return $this->belongsTo(Topic::class);
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
        // Handle null or empty values
        if ($submittedAnswer === null || trim($submittedAnswer) === '') {
            return false;
        }
        
        // Ensure submitted answer is always a string
        $submittedAnswer = (string)$submittedAnswer;
        
        if ($this->question_type === self::TYPE_MULTIPLE_CHOICE) {
            return $submittedAnswer === $this->correct_answer;
        } elseif ($this->question_type === self::TYPE_WRITTEN) {
            return $this->isWrittenAnswerCorrect($submittedAnswer);
        }
        
        return false;
    }

    /**
     * Check if a written answer is correct using direct text comparison
     * This is a simplified version that doesn't rely on external services
     * 
     * @param string $submittedAnswer
     * @return bool
     */
    protected function isWrittenAnswerCorrect(string $submittedAnswer): bool
    {
        Log::info('Checking written answer', [
            'question_id' => $this->id,
            'submitted' => $submittedAnswer,
            'correct' => $this->correct_answer
        ]);
        
        // Normalize the submitted answer and correct answer
        $normalizedSubmitted = $this->normalizeText($submittedAnswer);
        $normalizedCorrect = $this->normalizeText($this->correct_answer);
        
        // Check for exact match after normalization
        if ($normalizedSubmitted === $normalizedCorrect) {
            return true;
        }
        
        // Get similarity threshold (use default if not set)
        $threshold = $this->similarity_threshold ?? self::SIMILARITY_THRESHOLD;
        
        // Check main similarity
        $similarity = $this->getTextSimilarity($normalizedSubmitted, $normalizedCorrect);
        if ($similarity >= $threshold) {
            return true;
        }
        
        // Check alternative answers if available
        if (!empty($this->alternative_answers)) {
            foreach ($this->alternative_answers as $altAnswer) {
                if (empty($altAnswer)) continue;
                
                $normalizedAlt = $this->normalizeText($altAnswer);
                
                // Check for exact match with alternative
                if ($normalizedSubmitted === $normalizedAlt) {
                    return true;
                }
                
                // Check similarity with alternative
                $altSimilarity = $this->getTextSimilarity($normalizedSubmitted, $normalizedAlt);
                if ($altSimilarity >= $threshold) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Normalize text for comparison
     * 
     * @param string $text
     * @return string
     */
    protected function normalizeText(string $text): string
    {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Remove punctuation
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Calculate similarity between two text strings
     * Uses Levenshtein distance with additional word overlap comparison
     * 
     * @param string $text1
     * @param string $text2
     * @return float Value between 0 and 1, where 1 means identical
     */
    protected function getTextSimilarity(string $text1, string $text2): float
    {
        // If either is empty, return 0
        if (empty($text1) || empty($text2)) {
            return 0.0;
        }
        
        // Remove common filler words
        $fillerWords = ['a', 'an', 'the', 'and', 'or', 'but', 'if', 'is', 'are', 'that', 'this', 'to', 'in', 'on', 'at', 'for'];
        
        $text1Words = explode(' ', $text1);
        $text2Words = explode(' ', $text2);
        
        $text1Words = array_filter($text1Words, function($word) use ($fillerWords) {
            return !in_array(strtolower($word), $fillerWords) && strlen($word) > 1;
        });
        
        $text2Words = array_filter($text2Words, function($word) use ($fillerWords) {
            return !in_array(strtolower($word), $fillerWords) && strlen($word) > 1;
        });
        
        // Check word overlap ratio
        $intersection = array_intersect($text1Words, $text2Words);
        $union = array_unique(array_merge($text1Words, $text2Words));
        
        $wordOverlapRatio = count($intersection) / (count($union) ?: 1);
        
        // Recreate filtered text for Levenshtein
        $filteredText1 = implode(' ', $text1Words);
        $filteredText2 = implode(' ', $text2Words);
        
        // If either is empty after filtering, use a low similarity score but not zero
        if (empty($filteredText1) || empty($filteredText2)) {
            return 0.3; // Low but not zero, as original texts weren't empty
        }
        
        // Get Levenshtein distance (with a cap to avoid excessive processing)
        $maxLen = 255; // PHP's levenshtein has a limit
        $truncatedText1 = substr($filteredText1, 0, $maxLen);
        $truncatedText2 = substr($filteredText2, 0, $maxLen);
        
        $distance = levenshtein($truncatedText1, $truncatedText2);
        $maxLength = max(strlen($truncatedText1), strlen($truncatedText2));
        
        // Avoid division by zero
        if ($maxLength === 0) {
            return 0.0;
        }
        
        // Calculate Levenshtein similarity
        $levenshteinSimilarity = 1 - ($distance / $maxLength);
        
        // Calculate combined similarity (weighting word overlap more heavily)
        return ($levenshteinSimilarity * 0.4) + ($wordOverlapRatio * 0.6);
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

        // Automatically clear relevant caches when a question is updated or deleted
        static::updated(function ($question) {
            // Clear cache for both total_attempts and correct_attempts
            Cache::forget("question:{$question->id}:total_attempts");
            Cache::forget("question:{$question->id}:correct_attempts");
        });

        static::deleted(function ($question) {
            // Clear cache for both total_attempts and correct_attempts
            Cache::forget("question:{$question->id}:total_attempts");
            Cache::forget("question:{$question->id}:correct_attempts");
        });
    }
}
