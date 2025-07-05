<?php
/**
 * BACKUP: Text Similarity Mechanism
 * 
 * This file contains the similarity comparison mechanism extracted from the Question model.
 * It can be used as a reference for implementing text similarity comparison in the future.
 * 
 * Original implementation from app/Models/Question.php
 * Backed up on: <?= date('Y-m-d H:i:s') ?>
 */

/**
 * Check if a written answer is correct using direct text comparison
 * This is a simplified version that doesn't rely on external services
 * 
 * @param string $submittedAnswer The submitted answer to check
 * @param string $correctAnswer The correct answer to compare against
 * @param array $alternativeAnswers Optional array of alternative correct answers
 * @param float $threshold The similarity threshold to consider the answer correct (0.0 to 1.0)
 * @return bool True if the answer is correct, false otherwise
 */
function isWrittenAnswerCorrect(string $submittedAnswer, string $correctAnswer, array $alternativeAnswers = [], float $threshold = 0.8): bool
{
    // Log for debugging
    // Log::info('Checking written answer', [
    //     'submitted' => $submittedAnswer,
    //     'correct' => $correctAnswer
    // ]);
    
    // Normalize the submitted answer and correct answer
    $normalizedSubmitted = normalizeText($submittedAnswer);
    $normalizedCorrect = normalizeText($correctAnswer);
    
    // Check for exact match after normalization
    if ($normalizedSubmitted === $normalizedCorrect) {
        return true;
    }
    
    // Check main similarity
    $similarity = getTextSimilarity($normalizedSubmitted, $normalizedCorrect);
    if ($similarity >= $threshold) {
        return true;
    }
    
    // Check alternative answers if available
    if (!empty($alternativeAnswers)) {
        foreach ($alternativeAnswers as $altAnswer) {
            if (empty($altAnswer)) continue;
            
            $normalizedAlt = normalizeText($altAnswer);
            
            // Check for exact match with alternative
            if ($normalizedSubmitted === $normalizedAlt) {
                return true;
            }
            
            // Check similarity with alternative
            $altSimilarity = getTextSimilarity($normalizedSubmitted, $normalizedAlt);
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
 * @param string $text The text to normalize
 * @return string The normalized text
 */
function normalizeText(string $text): string
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
 * @param string $text1 First text to compare
 * @param string $text2 Second text to compare
 * @return float Value between 0 and 1, where 1 means identical
 */
function getTextSimilarity(string $text1, string $text2): float
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
 * Usage examples:
 * 
 * // Example 1: Exact match
 * $isCorrect1 = isWrittenAnswerCorrect("The heart pumps blood", "The heart pumps blood");
 * // Result: true
 * 
 * // Example 2: Similar enough
 * $isCorrect2 = isWrittenAnswerCorrect("Heart pumps blood throughout body", "The heart pumps blood");
 * // Result: true (if similarity >= threshold)
 * 
 * // Example 3: Not similar enough
 * $isCorrect3 = isWrittenAnswerCorrect("Lungs exchange oxygen", "The heart pumps blood");
 * // Result: false
 * 
 * // Example 4: With alternative answers
 * $isCorrect4 = isWrittenAnswerCorrect(
 *     "The cardiac muscle contracts to circulate blood",
 *     "The heart pumps blood",
 *     ["The cardiac muscle contracts to move blood", "Blood is pumped by the heart"]
 * );
 * // Result: true (if matches one of the alternatives)
 */ 