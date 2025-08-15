<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WrittenAnswerEvaluationService
{
    protected $textProcessor;
    protected $medicalDomainService;
    protected $answerSimilarityService;
    protected $medicalPatternService;
    protected $answerFeedbackService;
    
    const DEFAULT_SIMILARITY_THRESHOLD = 0.85;
    
    private $cacheEnabled = true;
    private $cacheTtl = 1800; // 30 minutes - optimized for memory

    public function __construct(
        TextProcessingService $textProcessor,
        MedicalDomainService $medicalDomainService,
        AnswerSimilarityService $answerSimilarityService,
        MedicalPatternService $medicalPatternService,
        AnswerFeedbackService $answerFeedbackService
    ) {
        $this->textProcessor = $textProcessor;
        $this->medicalDomainService = $medicalDomainService;
        $this->answerSimilarityService = $answerSimilarityService;
        $this->medicalPatternService = $medicalPatternService;
        $this->answerFeedbackService = $answerFeedbackService;
        
        // Optimize cache settings for performance
        $this->medicalDomainService->setCacheConfig(true, 3600);
        $this->answerSimilarityService->setCacheConfig(true, 1800);
        $this->medicalPatternService->setCacheConfig(true, 3600);
    }

    /**
     * OPTIMIZED: Main evaluation method - much faster and lighter
     */
    public function evaluateAnswer(
        string $submittedAnswer, 
        string $correctAnswer, 
        array $alternativeAnswers = [], 
        float $similarityThreshold = self::DEFAULT_SIMILARITY_THRESHOLD
    ): array {
        $startTime = microtime(true);
        
        // Quick validation
        $submittedAnswer = trim($submittedAnswer);
        $correctAnswer = trim($correctAnswer);
        
        if (empty($submittedAnswer)) {
            return $this->quickResult(false, 0.0, 'No answer provided', $startTime);
        }
        
        if (empty($correctAnswer)) {
            return $this->quickResult(false, 0.0, 'No correct answer available', $startTime);
        }
        
        // Fast exact match check
        if (strtolower($submittedAnswer) === strtolower($correctAnswer)) {
            return $this->quickResult(true, 1.0, 'Exact match', $startTime);
        }
        
        // Preprocess answers
        $processedSubmitted = $this->medicalDomainService->preprocessSymbols($submittedAnswer);
        $processedCorrect = $this->medicalDomainService->preprocessSymbols($correctAnswer);
        
        // Identify course topic for context
        $courseTopic = $this->medicalDomainService->identifyCourseTopic($correctAnswer);
        
        // Calculate similarity metrics
        $metrics = $this->answerSimilarityService->calculateSimilarityMetrics($processedSubmitted, $processedCorrect);
        
        // Calculate combined similarity with optimized weights
        $combinedSimilarity = $this->calculateOptimizedSimilarity($metrics, $courseTopic);
        
        // Apply domain boost
        $domainBoost = $this->medicalDomainService->calculateDomainContextBoost($processedSubmitted, $processedCorrect);
        $finalSimilarity = min(1.0, $combinedSimilarity + $domainBoost);
        
        // Check for critical issues
        $hasCriticalIssues = $this->checkCriticalIssues($processedSubmitted, $processedCorrect, $courseTopic);
        
        // Determine if correct
        $isCorrect = !$hasCriticalIssues && $finalSimilarity >= $similarityThreshold;
        
        // Generate feedback
        $feedback = $this->answerFeedbackService->generateDetailedFeedback(
            $processedSubmitted, 
            $processedCorrect, 
            $metrics, 
            $isCorrect, 
            $finalSimilarity, 
            $similarityThreshold, 
            $courseTopic
        );
        
        return [
            'isCorrect' => $isCorrect,
            'similarity' => $finalSimilarity,
            'metrics' => $metrics,
            'courseTopic' => $courseTopic,
            'feedback' => $feedback,
            'threshold' => $similarityThreshold,
            'processing_time' => microtime(true) - $startTime,
            'reason' => $isCorrect ? 'Answer meets similarity threshold' : 'Answer below threshold or has critical issues'
        ];
    }

    /**
     * OPTIMIZED: Calculate similarity with performance-focused weights
     */
    private function calculateOptimizedSimilarity(array $metrics, ?string $courseTopic): float
    {
        // Simplified weighting for better performance
        $weights = [
            'keyword' => 0.35,      // Most important for medical content
            'conceptual' => 0.25,   // Important for understanding
            'levenshtein' => 0.20,  // Good for exact matches
            'jaccard' => 0.15,      // Good for term overlap
            'pattern' => 0.05       // Bonus for pattern matching
        ];
        
        $similarity = 0.0;
        
        foreach ($weights as $metric => $weight) {
            if (isset($metrics[$metric])) {
                $value = is_array($metrics[$metric]) ? ($metrics[$metric]['score'] ?? 0) : $metrics[$metric];
                $similarity += $value * $weight;
            }
        }
        
        return min(1.0, $similarity);
    }

    /**
     * OPTIMIZED: Quick critical issues check
     */
    private function checkCriticalIssues(string $submitted, string $correct, ?string $courseTopic): bool
    {
        if (!$courseTopic) {
            return false;
        }
        
        // Check for missing critical terms
        if (!$this->medicalPatternService->checkForCriticalTerms($courseTopic, $submitted, $correct)) {
            return true;
        }
        
        // Check for contradictions
        if ($this->medicalPatternService->containsContradictions($submitted, $correct, $courseTopic)) {
            return true;
        }
        
        return false;
    }

    /**
     * Quick result helper
     */
    private function quickResult(bool $isCorrect, float $similarity, string $reason, float $startTime): array
    {
        return [
            'isCorrect' => $isCorrect,
            'similarity' => $similarity,
            'reason' => $reason,
            'processing_time' => microtime(true) - $startTime,
            'metrics' => [],
            'feedback' => $this->answerFeedbackService->generateQuickFeedback($isCorrect, $similarity)
        ];
    }

    /**
     * OPTIMIZED: Question-based evaluation
     */
    public function evaluateQuestionAnswer(Question $question, string $submittedAnswer): array
    {
        $threshold = $question->similarity_threshold ?? self::DEFAULT_SIMILARITY_THRESHOLD;
        $alternativeAnswers = $question->alternative_answers ?? [];
        
        return $this->evaluateAnswer(
            $submittedAnswer,
            $question->correct_answer,
            $alternativeAnswers,
            $threshold
        );
    }

    /**
     * Simple correctness check
     */
    public function isCorrect(Question $question, string $submittedAnswer): bool
    {
        $result = $this->evaluateQuestionAnswer($question, $submittedAnswer);
        return $result['isCorrect'];
    }

    /**
     * Set cache configuration
     */
    public function setCacheConfig(bool $enabled, int $ttl = 1800): void
    {
        $this->cacheEnabled = $enabled;
        $this->cacheTtl = $ttl;
    }

    /**
     * Cache helper
     */
    private function getCachedOrCompute(string $key, \Closure $callback)
    {
        if (!$this->cacheEnabled) {
            return $callback();
        }

        return Cache::remember($key, $this->cacheTtl, $callback);
    }
}