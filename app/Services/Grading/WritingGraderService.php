<?php
// app/Services/Grading/WritingGraderService.php

namespace App\Services\Grading;

use App\Services\Grading\Engines\TextProcessor;
use App\Services\Grading\Engines\SimilarityEngine;
use App\Services\Grading\Engines\BayesianEngine;
use App\Services\Grading\Cache\IntelligentCacheManager;
use App\Services\WrittenAnswerEvaluationService; // Your existing service
use App\Models\Grading\Evaluation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * AI-Ready Enhanced Writing Grader Service
 * The main orchestrator that integrates all grading components
 *
 * Features:
 * - 100% Compatible with existing quiz modes
 * - Intelligent caching and performance optimization
 * - Multi-algorithm similarity scoring
 * - Medical domain expertise
 * - AI training data collection
 * - Confidence scoring and reasoning
 */
class WritingGraderService
{
    private TextProcessor $textProcessor;
    private SimilarityEngine $similarityEngine;
    private IntelligentCacheManager $cache;
    private WrittenAnswerEvaluationService $existingService; // Your existing service

    // Performance tracking
    private array $performanceStats = [];

    public function __construct(
        TextProcessor $textProcessor,
        SimilarityEngine $similarityEngine,
        IntelligentCacheManager $cache,
        WrittenAnswerEvaluationService $existingService
    ) {
        $this->textProcessor = $textProcessor;
        $this->similarityEngine = $similarityEngine;
        $this->cache = $cache;
        $this->existingService = $existingService;
    }

    /**
     * Enhanced grading that works with your existing quiz modes
     * AI-Ready: Maintains compatibility while adding intelligence
     * 100% ROBUST: Handles all edge cases gracefully
     */
    public function gradeAnswer(string $studentAnswer = '', string $correctAnswer = '', string $subject = 'general', array $options = []): array
    {
        $startTime = microtime(true);

        // 🚨 SUPER AGENT DEBUG: Force enhanced system to run
        Log::emergency('🚨 SUPER AGENT MODE ACTIVATED - Starting Enhanced Grading', [
            'student_length' => strlen($studentAnswer),
            'correct_length' => strlen($correctAnswer),
            'subject' => $subject,
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Step 1: Quick validation and normalization
            $validation = $this->validateInputs($studentAnswer, $correctAnswer);
            if (!$validation['valid']) {
                Log::emergency('🚨 VALIDATION FAILED', ['error' => $validation['error']]);
                return $this->createErrorResponse($validation['error'], $startTime);
            }

            // Step 2: Get existing service evaluation for comparison
            $existingResult = $this->existingService->evaluateAnswer($studentAnswer, $correctAnswer);
            Log::emergency('🚨 EXISTING SERVICE RESULT', ['similarity' => $existingResult['similarity'] ?? 'MISSING']);

            // Step 3: Enhanced processing with AI components
            Log::emergency('🚨 STARTING ENHANCED PROCESSING');
            $enhancedResult = $this->processEnhancedGrading($studentAnswer, $correctAnswer, $subject, $existingResult);
            Log::emergency('🚨 ENHANCED PROCESSING COMPLETED', [
                'enhanced_score' => $enhancedResult['enhanced_score'] ?? 'MISSING',
                'similarities_count' => count($enhancedResult['similarities'] ?? [])
            ]);

            // Step 4: Ensemble decision making
            $finalResult = $this->ensembleDecision($enhancedResult, $existingResult);
            Log::emergency('🚨 ENSEMBLE DECISION COMPLETED', [
                'final_similarity' => $finalResult['similarity'] ?? 'MISSING'
            ]);

            // Step 5: Store evaluation for learning (if enabled)
            $this->storeEvaluation($studentAnswer, $correctAnswer, $finalResult, $subject);

            // Step 6: Add performance metrics
            $finalResult['processing_time'] = round((microtime(true) - $startTime) * 1000, 2); // Convert to milliseconds
            $finalResult['performance'] = $this->getPerformanceMetrics();

            // Step 7: Log for AI training
            $this->logGradingResult($studentAnswer, $correctAnswer, $finalResult);

            Log::emergency('🚨 ENHANCED GRADING SUCCESSFUL', [
                'final_similarity' => $finalResult['similarity'],
                'processing_time' => $finalResult['processing_time']
            ]);

            return $finalResult;

        } catch (\Exception $e) {
            Log::emergency('🚨 ENHANCED GRADING FAILED - FALLING BACK', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Graceful fallback to existing service
            return $this->existingService->evaluateAnswer($studentAnswer, $correctAnswer);
        }
    }

    /**
     * Process enhanced grading with all available algorithms
     */
    private function processEnhancedGrading(string $studentAnswer, string $correctAnswer, string $subject, array $existingResult): array
    {
        // Step 1: Medical abbreviation expansion (CRITICAL FIX)
        // This must happen BEFORE text processing to preserve medical abbreviations
        $expandedStudent = $this->expandMedicalAbbreviations($studentAnswer, $subject);
        $expandedCorrect = $this->expandMedicalAbbreviations($correctAnswer, $subject);

        // Step 2: Text processing with caching
        $processedStudent = $this->cache->remember(
            "process:student:" . md5($expandedStudent),
            3600,
            fn() => $this->textProcessor->normalizeText($expandedStudent)
        );

        $processedCorrect = $this->cache->remember(
            "process:correct:" . md5($expandedCorrect),
            3600,
            fn() => $this->textProcessor->normalizeText($expandedCorrect)
        );

        // Step 2: FORCE ENHANCED similarity analysis using RAW TEXT BEFORE PROCESSING
        // CRITICAL FIX: Use raw expanded text directly to ensure enhanced algorithms work
        $forceEnhancedCacheKey = 'enhanced_similarity_all:' . md5($expandedStudent . $expandedCorrect);

        $similarities = $this->cache->remember(
            $forceEnhancedCacheKey,
            3600,
            function() use ($expandedStudent, $expandedCorrect, $forceEnhancedCacheKey) {
                // SUPER AGENT MODE: Force the enhanced SimilarityEngine to run
                $result = $this->similarityEngine->calculateAll(
                    $expandedStudent,  // Use raw expanded text
                    $expandedCorrect   // Use raw expanded text
                );

                // NUCLEAR DEBUG: Verify enhanced algorithms are working
                Log::emergency('🚨 SUPER AGENT MODE - Enhanced SimilarityEngine Results', [
                    'raw_student' => $expandedStudent,
                    'raw_correct' => $expandedCorrect,
                    'cache_key' => $forceEnhancedCacheKey,
                    'algorithm_results' => $result,
                    'cosine_score' => $result['cosine'] ?? 'MISSING',
                    'medical_score' => $result['medical'] ?? 'MISSING',
                    'ensemble_score' => $result['ensemble'] ?? 'MISSING',
                    'all_keys' => array_keys($result),
                    'enhancement_confirmed' => !empty($result['medical']) ? '✅ ENHANCED' : '❌ BYPASSED'
                ]);

                return $result;
            }
        );

        // Step 3: Medical domain analysis (if applicable)
        $medicalAnalysis = $this->performMedicalAnalysis($processedStudent, $processedCorrect, $subject);

        // Step 4: Calculate enhanced similarity score
        $enhancedScore = $this->calculateEnhancedScore($similarities, $medicalAnalysis, $subject);

        // Step 5: Generate detailed reasoning
        $reasoning = $this->generateDetailedReasoning($enhancedScore, $similarities, $medicalAnalysis, $existingResult);

        return [
            'enhanced_score' => $enhancedScore,
            'similarities' => $similarities,
            'processed_student' => $processedStudent,
            'processed_correct' => $processedCorrect,
            'medical_analysis' => $medicalAnalysis,
            'reasoning' => $reasoning,
            'confidence' => $this->calculateOverallConfidence($similarities, $enhancedScore),
            // CRITICAL FIX: Ensure algorithm_breakdown includes all similarity scores
            'algorithm_breakdown' => $similarities
        ];
    }

    /**
     * Perform medical domain-specific analysis
     */
    private function performMedicalAnalysis(string $processedStudent, string $processedCorrect, string $subject): array
    {
        $analysis = [
            'is_medical' => false,
            'medical_terms_matched' => 0,
            'total_medical_terms' => 0,
            'abbreviations_expanded' => 0,
            'domain_confidence' => 0.0
        ];

        // Check if subject is medical
        if (in_array($subject, ['medical', 'anatomy', 'physiology', 'biochemistry', 'pathology', 'histology'])) {
            $analysis['is_medical'] = true;

            // Get medical terms from processed text
            $studentWords = explode(' ', $processedStudent);
            $correctWords = explode(' ', $processedCorrect);

            // Medical patterns to detect
            $medicalPatterns = [
                '/^(cardio|neuro|pulmo|hepato|nephro|osteo|derma|hemato|endo|exo)/i',
                '/^(gastro|ophthalmo|oto|rhino|laryngo|dermat|gyneco|obstet)/i',
                '/(ology|itis|oma|ectomy|plasty|scope|graph|stomy|tomy)$/i',
            ];

            $studentMedicalTerms = [];
            $correctMedicalTerms = [];

            foreach ($studentWords as $word) {
                foreach ($medicalPatterns as $pattern) {
                    if (preg_match($pattern, $word)) {
                        $studentMedicalTerms[] = $word;
                        break;
                    }
                }
            }

            foreach ($correctWords as $word) {
                foreach ($medicalPatterns as $pattern) {
                    if (preg_match($pattern, $word)) {
                        $correctMedicalTerms[] = $word;
                        break;
                    }
                }
            }

            $analysis['total_medical_terms'] = count($correctMedicalTerms);
            $matchedTerms = array_intersect($studentMedicalTerms, $correctMedicalTerms);
            $analysis['medical_terms_matched'] = count($matchedTerms);

            // Domain confidence based on medical term presence
            if ($analysis['total_medical_terms'] > 0) {
                $analysis['domain_confidence'] = $analysis['medical_terms_matched'] / $analysis['total_medical_terms'];
            }
        }

        return $analysis;
    }

    /**
     * Calculate enhanced similarity score
     */
    private function calculateEnhancedScore(array $similarities, array $medicalAnalysis, string $subject): float
    {
        // Base score from ensemble similarity
        $baseScore = $similarities['ensemble'];

        // Apply medical domain adjustments
        if ($medicalAnalysis['is_medical']) {
            // Boost score for medical content understanding
            $medicalBoost = $medicalAnalysis['domain_confidence'] * 0.2;
            $baseScore = min(1.0, $baseScore + $medicalBoost);
        }

        // Subject-specific adjustments
        $subjectMultiplier = match($subject) {
            'medical', 'anatomy', 'physiology', 'biochemistry' => 1.1,
            'histology', 'pathology' => 1.05,
            default => 1.0
        };

        $adjustedScore = $baseScore * $subjectMultiplier;

        // Ensure score is within valid range
        return max(0.0, min(1.0, $adjustedScore));
    }

    /**
     * Generate detailed reasoning for the grade
     */
    private function generateDetailedReasoning(float $enhancedScore, array $similarities, array $medicalAnalysis, array $existingResult): string
    {
        $reasons = [];

        // Overall assessment
        if ($enhancedScore >= 0.9) {
            $reasons[] = "Excellent match with high confidence";
        } elseif ($enhancedScore >= 0.7) {
            $reasons[] = "Good understanding of the core concepts";
        } elseif ($enhancedScore >= 0.5) {
            $reasons[] = "Partial understanding with room for improvement";
        } else {
            $reasons[] = "Needs significant improvement";
        }

        // Algorithm-specific insights
        if ($similarities['levenshtein'] > 0.8) {
            $reasons[] = "Excellent attention to detail and spelling";
        }

        if ($similarities['semantic'] > 0.7) {
            $reasons[] = "Good understanding of related concepts";
        }

        if ($medicalAnalysis['is_medical'] && $medicalAnalysis['domain_confidence'] > 0.8) {
            $reasons[] = "Strong grasp of medical terminology";
        } elseif ($medicalAnalysis['is_medical'] && $medicalAnalysis['domain_confidence'] < 0.3) {
            $reasons[] = "Could benefit from more precise medical terminology";
        }

        // Compare with existing result
        $existingScore = $existingResult['similarity'] ?? 0;
        if (abs($enhancedScore - $existingScore) > 0.2) {
            if ($enhancedScore > $existingScore) {
                $reasons[] = "Enhanced analysis detected additional understanding";
            } else {
                $reasons[] = "Conservative assessment to ensure accuracy";
            }
        }

        return implode('. ', $reasons) . '.';
    }

    /**
     * Ensemble decision making combining enhanced and existing results
     * CRITICAL FIX: Ensure algorithm results are properly integrated
     */
    private function ensembleDecision(array $enhanced, array $existing): array
    {
        $enhancedScore = $enhanced['enhanced_score'] ?? 0;
        $existingScore = $existing['isCorrect'] ? 1.0 : ($existing['similarity'] ?? 0);
        $enhancedConfidence = $enhanced['confidence'] ?? 0.5;

        // CRITICAL FIX: Get algorithm breakdown from similarities data
        $algorithmBreakdown = $enhanced['similarities'] ?? [];

        // Ensure algorithm_breakdown has all required similarity scores
        if (empty($algorithmBreakdown)) {
            Log::warning('Empty algorithm breakdown in ensemble decision', [
                'enhanced' => $enhanced,
                'existing' => $existing
            ]);
            // Fallback: create basic breakdown
            $algorithmBreakdown = [
                'cosine' => $enhancedScore,
                'jaccard' => 0.0,
                'levenshtein' => 0.0,
                'semantic' => 0.0,
                'medical' => 0.0,
                'ensemble_score' => $enhancedScore,
                'confidence' => $enhancedConfidence,
                'processing_time' => 0.0
            ];
        }

        // Decision logic based on confidence and agreement
        if ($enhancedConfidence > 0.8) {
            // High confidence in enhanced result
            $finalScore = $enhancedScore;
            $confidence = $enhancedConfidence;
            $reasoning = $enhanced['reasoning'];
        } elseif (abs($enhancedScore - $existingScore) < 0.2) {
            // Results agree, use average
            $finalScore = ($enhancedScore + $existingScore) / 2;
            $confidence = 0.75;
            $reasoning = $enhanced['reasoning'] ?? $existing['reason'];
        } else {
            // Results disagree, use conservative approach
            $finalScore = min($enhancedScore, $existingScore);
            $confidence = 0.6;
            $reasoning = $existing['reason'] ?? 'Conservative assessment applied';
        }

        // CRITICAL FIX: Ensure all algorithm scores are included in the response
        $result = [
            'isCorrect' => $finalScore >= 0.7,
            'similarity' => $finalScore,
            'confidence' => $confidence,
            'reason' => $reasoning,
            'enhanced_data' => $enhanced,
            'existing_data' => $existing,
            'algorithm_breakdown' => $algorithmBreakdown
        ];

        // Debug log to verify algorithm scores are included

        return $result;
    }

    /**
     * Calculate overall confidence in the result
     */
    private function calculateOverallConfidence(array $similarities, float $finalScore): float
    {
        $baseConfidence = $similarities['confidence'] ?? 0.5;

        // Adjust based on algorithm agreement
        $algorithmValues = array_filter([
            $similarities['cosine'] ?? null,
            $similarities['jaccard'] ?? null,
            $similarities['levenshtein'] ?? null,
            $similarities['semantic'] ?? null,
            $similarities['medical'] ?? null
        ]);

        if (count($algorithmValues) > 1) {
            $mean = array_sum($algorithmValues) / count($algorithmValues);
            $variance = 0;

            foreach ($algorithmValues as $value) {
                $variance += pow($value - $mean, 2);
            }
            $variance /= count($algorithmValues);

            // Lower variance = higher confidence
            $agreementConfidence = max(0.1, 1.0 - sqrt($variance));
            $baseConfidence = ($baseConfidence + $agreementConfidence) / 2;
        }

        return min(1.0, max(0.1, $baseConfidence));
    }

    /**
     * Input validation
     */
    private function validateInputs(string $studentAnswer, string $correctAnswer): array
    {
        if (empty(trim($studentAnswer)) && empty(trim($correctAnswer))) {
            return ['valid' => false, 'error' => 'Both answers are empty'];
        }

        if (empty(trim($correctAnswer))) {
            return ['valid' => false, 'error' => 'Correct answer cannot be empty'];
        }

        if (strlen($studentAnswer) > 10000 || strlen($correctAnswer) > 10000) {
            return ['valid' => false, 'error' => 'Answer too long (max 10000 characters)'];
        }

        return ['valid' => true];
    }

    /**
     * Create error response
     */
    private function createErrorResponse(string $error, float $startTime): array
    {
        return [
            'isCorrect' => false,
            'similarity' => 0.0,
            'confidence' => 0.0,
            'reason' => $error,
            'processing_time' => microtime(true) - $startTime,
            'error' => true
        ];
    }

    /**
     * Log grading result for AI training
     */
    private function logGradingResult(string $studentAnswer, string $correctAnswer, array $result): void
    {
        $this->performanceStats[] = [
            'student_length' => strlen($studentAnswer),
            'correct_length' => strlen($correctAnswer),
            'similarity' => $result['similarity'],
            'confidence' => $result['confidence'],
            'processing_time' => $result['processing_time'],
            'timestamp' => now()->toISOString()
        ];

        // Log every 50 evaluations for monitoring
        if (count($this->performanceStats) % 50 === 0) {
        }
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        if (empty($this->performanceStats)) {
            return [];
        }

        return [
            'total_evaluations' => count($this->performanceStats),
            'average_similarity' => array_sum(array_column($this->performanceStats, 'similarity')) / count($this->performanceStats),
            'average_confidence' => array_sum(array_column($this->performanceStats, 'confidence')) / count($this->performanceStats),
            'average_processing_time' => array_sum(array_column($this->performanceStats, 'processing_time')) / count($this->performanceStats),
            'cache_stats' => $this->cache->getStatistics(),
            'algorithm_stats' => $this->similarityEngine->getAlgorithmStats()
        ];
    }

    /**
     * Get comprehensive statistics for monitoring
     */
    public function getStatistics(): array
    {
        return [
            'writing_grader' => $this->getPerformanceMetrics(),
            'text_processor' => ['status' => 'operational'], // TextProcessor doesn't have stats method yet
            'similarity_engine' => $this->similarityEngine->getAlgorithmStats(),
            'cache_manager' => $this->cache->getStatistics(),
            'uptime' => now()->toISOString()
        ];
    }

    /**
     * Store evaluation for learning and analytics
     */
    private function storeEvaluation(string $studentAnswer, string $correctAnswer, array $result, string $subject): void
    {
        try {
            // Only store if learning is enabled in config
            if (!config('grading.learning.enabled', true)) {
                return;
            }

            // Prepare evaluation data
            $evaluationData = [
                'student_answer' => $studentAnswer,
                'correct_answer' => $correctAnswer,
                'subject' => $subject,
                'similarity_score' => $result['similarity'],
                'confidence_score' => $result['confidence'],
                'processing_time_microseconds' => (int)($result['processing_time'] * 1000), // Convert to microseconds
                'metadata' => [
                    'algorithm_breakdown' => $result['algorithm_breakdown'] ?? [],
                    'enhanced_data' => $result['enhanced_data'] ?? [],
                    'existing_data' => $result['existing_data'] ?? [],
                    'cache_used' => false, // Will be updated by cache manager
                ],
                'feedback' => [
                    'reasoning' => $result['reason'] ?? '',
                    'recommendations' => [], // Can be expanded later
                    'performance' => $result['performance'] ?? [],
                ],
                'algorithm_version' => '2.0', // Current version
                'cache_used' => false,
                'error_message' => $result['error'] ?? null,
            ];

            // Store evaluation using the model
            Evaluation::createEvaluation($evaluationData);


        } catch (\Exception $e) {
            Log::warning('Failed to store evaluation for learning', [
                'error' => $e->getMessage(),
                'student_length' => strlen($studentAnswer),
                'correct_length' => strlen($correctAnswer)
            ]);
        }
    }

    /**
     * Get learning insights from stored evaluations
     */
    public function getLearningInsights(int $hours = 24): array
    {
        try {
            return Evaluation::getLearningInsights($hours);
        } catch (\Exception $e) {
            Log::error('Failed to get learning insights', [
                'error' => $e->getMessage()
            ]);

            return [
                'total_validated' => 0,
                'system_vs_human_correlation' => 0,
                'common_mistakes' => [],
                'improvement_suggestions' => ['Unable to load insights at this time']
            ];
        }
    }

    /**
     * Get accuracy metrics from stored evaluations
     */
    public function getAccuracyMetrics(?string $subject = null, int $hours = 24): array
    {
        try {
            return Evaluation::getAccuracyMetrics($subject, $hours);
        } catch (\Exception $e) {
            Log::error('Failed to get accuracy metrics', [
                'error' => $e->getMessage()
            ]);

            return [
                'total_evaluations' => 0,
                'accuracy' => 0,
                'precision' => 0,
                'recall' => 0,
                'f1_score' => 0,
                'avg_confidence' => 0,
                'avg_similarity' => 0
            ];
        }
    }

    /**
     * Get performance metrics from stored evaluations
     */
    public function getSystemPerformanceMetrics(?string $subject = null, int $hours = 24): array
    {
        try {
            return Evaluation::getPerformanceMetrics($subject, $hours);
        } catch (\Exception $e) {
            Log::error('Failed to get performance metrics', [
                'error' => $e->getMessage()
            ]);

            return [
                'total_evaluations' => 0,
                'avg_processing_time' => 0,
                'median_processing_time' => 0,
                'p95_processing_time' => 0,
                'cache_hit_rate' => 0,
                'error_rate' => 0
            ];
        }
    }

    /**
     * Update evaluation with human feedback for learning
     */
    public function updateWithHumanFeedback(string $evaluationId, float $humanScore, array $feedback = []): bool
    {
        try {
            $evaluation = Evaluation::find($evaluationId);

            if (!$evaluation) {
                Log::warning('Evaluation not found for human feedback', [
                    'evaluation_id' => $evaluationId
                ]);
                return false;
            }

            $evaluation->validateWithHuman($humanScore, $feedback);


            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update evaluation with human feedback', [
                'error' => $e->getMessage(),
                'evaluation_id' => $evaluationId
            ]);
            return false;
        }
    }

    /**
     * Clear all caches (for maintenance/testing)
     */
    public function clearCaches(): void
    {
        $this->cache->clearAll();
        $this->performanceStats = [];

    }

    /**
     * Expand medical abbreviations before text processing
     * CRITICAL FIX: This must happen before TextProcessor normalization
     */
    private function expandMedicalAbbreviations(string $text, string $subject): string
    {
        // Only expand abbreviations for medical subjects
        if (!in_array($subject, ['medical', 'anatomy', 'physiology', 'biochemistry', 'pathology', 'histology'])) {
            return $text;
        }

        // Most comprehensive medical abbreviation database - 200+ entries
        $abbreviations = [
            // Vital Signs & Basic Measurements
            'bp' => 'blood pressure',
            'hr' => 'heart rate',
            'rr' => 'respiratory rate',
            'temp' => 'temperature',
            'o2' => 'oxygen',
            'co2' => 'carbon dioxide',
            'spo2' => 'oxygen saturation',
            'bg' => 'blood glucose',
            'bgl' => 'blood glucose level',

            // Chief Complaints & Symptoms
            'cp' => 'chest pain',
            'sob' => 'shortness of breath',
            'diaphoresis' => 'sweating',
            'n/v' => 'nausea and vomiting',
            'n&v' => 'nausea and vomiting',
            'abd' => 'abdominal',
            'ha' => 'headache',
            'loc' => 'loss of consciousness',
            'ams' => 'altered mental status',
            'sz' => 'seizure',
            'szs' => 'seizures',
            'syncope' => 'fainting',
            'dyspnea' => 'difficulty breathing',
            'palpitations' => 'heart palpitations',
            'edema' => 'swelling',
            'rash' => 'skin rash',
            'pruritus' => 'itching',
            'hemoptysis' => 'coughing up blood',
            'hematemesis' => 'vomiting blood',
            'melena' => 'black tarry stools',
            'dysuria' => 'painful urination',
            'oliguria' => 'decreased urine output',
            'polyuria' => 'excessive urination',
            'nocturia' => 'frequent nighttime urination',

            // Medical Conditions & Diseases
            'mi' => 'myocardial infarction',
            'cad' => 'coronary artery disease',
            'chf' => 'congestive heart failure',
            'afib' => 'atrial fibrillation',
            'vt' => 'ventricular tachycardia',
            'dm' => 'diabetes mellitus',
            'dka' => 'diabetic ketoacidosis',
            'copd' => 'chronic obstructive pulmonary disease',
            'asthma' => 'asthma',
            'pneumonia' => 'pneumonia',
            'tb' => 'tuberculosis',
            'uti' => 'urinary tract infection',
            'pyelonephritis' => 'kidney infection',
            'gerd' => 'gastroesophageal reflux disease',
            'peptic ulcer' => 'stomach ulcer',
            'ibd' => 'inflammatory bowel disease',
            'crohns' => 'crohn disease',
            'uc' => 'ulcerative colitis',
            'hepatitis' => 'liver inflammation',
            'cirrhosis' => 'liver scarring',
            'ckd' => 'chronic kidney disease',
            'esrd' => 'end stage renal disease',
            'anemia' => 'low red blood cells',
            'leukemia' => 'blood cancer',
            'lymphoma' => 'lymph node cancer',
            'sepsis' => 'bloodstream infection',

            // Medical Procedures & Tests
            'ecg' => 'electrocardiogram',
            'ekg' => 'electrocardiogram',
            'echo' => 'echocardiogram',
            'ct' => 'computed tomography',
            'mri' => 'magnetic resonance imaging',
            'xray' => 'x-ray',
            'us' => 'ultrasound',
            'usg' => 'ultrasonography',
            'cbc' => 'complete blood count',
            'bmp' => 'basic metabolic panel',
            'cmp' => 'comprehensive metabolic panel',
            'lft' => 'liver function test',
            'lipid' => 'cholesterol panel',
            'bun' => 'blood urea nitrogen',
            'cre' => 'creatinine',
            'na' => 'sodium',
            'k' => 'potassium',
            'cl' => 'chloride',
            'co2' => 'carbon dioxide',
            'glu' => 'glucose',
            'ca' => 'calcium',
            'mg' => 'magnesium',
            'phos' => 'phosphate',
            'wbc' => 'white blood cell',
            'rbc' => 'red blood cell',
            'hgb' => 'hemoglobin',
            'hct' => 'hematocrit',
            'plt' => 'platelet',
            'inr' => 'international normalized ratio',
            'ptt' => 'partial thromboplastin time',
            'tt' => 'thrombin time',
            'fib' => 'fibrinogen',
            'ddimer' => 'd-dimer',
            'troponin' => 'troponin',
            'ckmb' => 'creatine kinase mb',
            'ntprobnp' => 'nt-pro brain natriuretic peptide',
            'bnp' => 'brain natriuretic peptide',
            'crp' => 'c-reactive protein',
            'esr' => 'erythrocyte sedimentation rate',
            'tsh' => 'thyroid stimulating hormone',
            't4' => 'thyroxine',
            't3' => 'triiodothyronine',
            'fsh' => 'follicle stimulating hormone',
            'lh' => 'luteinizing hormone',
            'testosterone' => 'testosterone',
            'estrogen' => 'estrogen',
            'progesterone' => 'progesterone',
            'cortisol' => 'cortisol',
            'acth' => 'adrenocorticotropic hormone',
            'insulin' => 'insulin',
            'c-peptide' => 'c-peptide',
            'hba1c' => 'hemoglobin a1c',
            'vitd' => 'vitamin d',
            'b12' => 'vitamin b12',
            'folate' => 'folic acid',
            'ferritin' => 'ferritin',
            'iron' => 'iron',
            'tibc' => 'total iron binding capacity',

            // Anatomy & Systems
            'cns' => 'central nervous system',
            'pns' => 'peripheral nervous system',
            'sns' => 'sympathetic nervous system',
            'psns' => 'parasympathetic nervous system',
            'gi' => 'gastrointestinal',
            'gu' => 'genitourinary',
            'gyn' => 'gynecology',
            'obs' => 'obstetrics',
            'ent' => 'ear nose throat',
            'optho' => 'ophthalmology',
            'psych' => 'psychiatry',
            'derm' => 'dermatology',
            'endo' => 'endocrinology',
            'hem' => 'hematology',
            'onc' => 'oncology',
            'neph' => 'nephrology',
            'pulm' => 'pulmonology',
            'rheum' => 'rheumatology',
            'cards' => 'cardiology',
            'neuro' => 'neurology',
            'ortho' => 'orthopedics',
            'urol' => 'urology',
            'vascular' => 'vascular surgery',
            'thoracic' => 'thoracic surgery',
            'plastics' => 'plastic surgery',

            // Medications & Treatments
            'asa' => 'aspirin',
            'nsaid' => 'non-steroidal anti-inflammatory drug',
            'acei' => 'angiotensin converting enzyme inhibitor',
            'arb' => 'angiotensin receptor blocker',
            'ccb' => 'calcium channel blocker',
            'bb' => 'beta blocker',
            'diuretic' => 'water pill',
            'statin' => 'cholesterol lowering medication',
            'antibiotic' => 'antibiotic',
            'antiviral' => 'antiviral',
            'antifungal' => 'antifungal',
            'anticoagulant' => 'blood thinner',
            'antiplatelet' => 'antiplatelet',
            'antihypertensive' => 'blood pressure medication',
            'antidiabetic' => 'diabetes medication',
            'insulin' => 'insulin',
            'metformin' => 'metformin',
            'lisinopril' => 'lisinopril',
            'amlodipine' => 'amlodipine',
            'hydrochlorothiazide' => 'hydrochlorothiazide',
            'furosemide' => 'furosemide',
            'prednisone' => 'prednisone',
            'albuterol' => 'albuterol',
            'ipratropium' => 'ipratropium',
            'tiotropium' => 'tiotropium',
            'fluticasone' => 'fluticasone',
            'salmeterol' => 'salmeterol',
            'montelukast' => 'montelukast',
            'omeprazole' => 'omeprazole',
            'ranitidine' => 'ranitidine',
            'famotidine' => 'famotidine',
            'lansoprazole' => 'lansoprazole',
            'pantoprazole' => 'pantoprazole',
            'esomeprazole' => 'esomeprazole',
            'dexlansoprazole' => 'dexlansoprazole',
            'rabeprazole' => 'rabeprazole',
            'amoxicillin' => 'amoxicillin',
            'azithromycin' => 'azithromycin',
            'ciprofloxacin' => 'ciprofloxacin',
            'doxycycline' => 'doxycycline',
            'levofloxacin' => 'levofloxacin',
            'metronidazole' => 'metronidazole',
            'vancomycin' => 'vancomycin',
            'ceftriaxone' => 'ceftriaxone',
            'piperacillin' => 'piperacillin',
            'tazobactam' => 'tazobactam',
            'meropenem' => 'meropenem',
            'imipenem' => 'imipenem',
            'cilastatin' => 'cilastatin',
            'gentamicin' => 'gentamicin',
            'tobramycin' => 'tobramycin',
            'amikacin' => 'amikacin',
            'fluconazole' => 'fluconazole',
            'itraconazole' => 'itraconazole',
            'voriconazole' => 'voriconazole',
            'caspofungin' => 'caspofungin',
            'amphotericin' => 'amphotericin',
            'acyclovir' => 'acyclovir',
            'valacyclovir' => 'valacyclovir',
            'ganciclovir' => 'ganciclovir',
            'oseltamivir' => 'oseltamivir',
            'zanamivir' => 'zanamivir',
            'warfarin' => 'warfarin',
            'heparin' => 'heparin',
            'lmwh' => 'low molecular weight heparin',
            'enoxaparin' => 'enoxaparin',
            'dalteparin' => 'dalteparin',
            'tinzaparin' => 'tinzaparin',
            'fondaparinux' => 'fondaparinux',
            'rivaroxaban' => 'rivaroxaban',
            'apixaban' => 'apixaban',
            'dabigatran' => 'dabigatran',
            'edoxaban' => 'edoxaban',
            'clopidogrel' => 'clopidogrel',
            'prasugrel' => 'prasugrel',
            'ticagrelor' => 'ticagrelor',
            'aspirin' => 'aspirin',
            'dipyridamole' => 'dipyridamole',

            // Common Phrases & Patient Presentation
            'c/o' => 'complaining of',
            'w/' => 'with',
            'w/o' => 'without',
            'yo' => 'year old',
            '♀' => 'female',
            '♂' => 'male',
            'tx' => 'treatment',
            'dx' => 'diagnosis',
            'hx' => 'history',
            'sx' => 'symptoms',
            'fx' => 'function',
            'vs' => 'versus',
            'bid' => 'twice daily',
            'tid' => 'three times daily',
            'qid' => 'four times daily',
            'qod' => 'every other day',
            'prn' => 'as needed',
            'po' => 'by mouth',
            'iv' => 'intravenous',
            'im' => 'intramuscular',
            'sc' => 'subcutaneous',
            'sl' => 'sublingual',
            'top' => 'topical',
            'inh' => 'inhaled',
            'rect' => 'rectal',
            'vag' => 'vaginal',
            'ung' => 'ointment',
            'sol' => 'solution',
            'susp' => 'suspension',
            'tab' => 'tablet',
            'cap' => 'capsule',
            'inj' => 'injection',
            'neb' => 'nebulizer',
            'dpi' => 'dry powder inhaler',
            '↑' => 'increased',
            '↓' => 'decreased',
            '→' => 'leading to',
            '±' => 'plus or minus',
            '≈' => 'approximately',
            '>' => 'greater than',
            '<' => 'less than',
            '≥' => 'greater than or equal to',
            '≤' => 'less than or equal to',
            '=' => 'equals',
            '≠' => 'not equal to',

            // Medical Patient & Anatomy (CRITICAL ADDITIONS)
            'pt' => 'patient',
            'pts' => 'patients',
            'ra' => 'right atrium',
            'rv' => 'right ventricle',
            'la' => 'left atrium',
            'lv' => 'left ventricle',

            // Medical Specialties & Roles (Avoid conflicts with medical terms)
            'do' => 'doctor of osteopathy',
            'rn' => 'registered nurse',
            'np' => 'nurse practitioner',
            'pa' => 'physician assistant',
            'rt' => 'respiratory therapist',
            'ot' => 'occupational therapist',
            'st' => 'speech therapist',
            'mt' => 'medical technologist',
            'phlebotomist' => 'blood drawer',
            'pharmacist' => 'medication expert',
            'dietitian' => 'nutrition specialist',
            'social worker' => 'patient advocate',
            'chaplain' => 'spiritual care provider',

            // Time & Frequency
            'qd' => 'once daily',
            'qhs' => 'at bedtime',
            'qam' => 'in the morning',
            'qpm' => 'in the afternoon',
            'qnoon' => 'at noon',
            'qmidnight' => 'at midnight',
            'ac' => 'before meals',
            'pc' => 'after meals',
            'hs' => 'at bedtime',
            'stat' => 'immediately',
            'now' => 'immediately',
            'asap' => 'as soon as possible',

            // Units & Measurements
            'mmhg' => 'millimeters of mercury',
            'mm' => 'millimeters',
            'cm' => 'centimeters',
            'm' => 'meters',
            'kg' => 'kilograms',
            'lbs' => 'pounds',
            'g' => 'grams',
            'mg' => 'milligrams',
            'mcg' => 'micrograms',
            'ml' => 'milliliters',
            'l' => 'liters',
            'cc' => 'cubic centimeters',
            'iu' => 'international units',
            'u' => 'units',
            'meq' => 'milliequivalents',
            'mmol' => 'millimoles',
            'mosm' => 'milliosmoles',
            'kcal' => 'kilocalories',
            'gm' => 'grams',
            'tbsp' => 'tablespoon',
            'tsp' => 'teaspoon',
            'oz' => 'ounces',
            'c' => 'cup',
            'pt' => 'pint',
            'qt' => 'quart',
            'gal' => 'gallon',

            // Emergency & Critical Care
            'code blue' => 'cardiac arrest',
            'code red' => 'fire',
            'code white' => 'aggressive patient',
            'code gray' => 'combative patient',
            'code silver' => 'weapon',
            'code orange' => 'hazardous spill',
            'code yellow' => 'bomb threat',
            'code black' => 'bomb',
            'code green' => 'evacuation',
            'code violet' => 'violent patient',
            'rapid response' => 'emergency response team',
            'rri' => 'rapid response initiated',
            'cpr' => 'cardiopulmonary resuscitation',
            'defib' => 'defibrillation',
            'intubation' => 'breathing tube placement',
            'ventilation' => 'mechanical breathing',
            'vasopressors' => 'blood pressure support medications',
            'inotropes' => 'heart strength medications',
            'crystalloids' => 'iv fluids',
            'colloids' => 'blood products',
            'transfusion' => 'blood product administration',
            'ecmo' => 'extracorporeal membrane oxygenation',
            'iabc' => 'intra-aortic balloon counterpulsation',
            'cath' => 'catheterization',
            'pci' => 'percutaneous coronary intervention',
            'cabg' => 'coronary artery bypass graft',
            'tpa' => 'tissue plasminogen activator',
            'thrombolytics' => 'clot busting medications',
            'antidotes' => 'poison reversal medications',
            'naloxone' => 'opioid reversal medication',
            'flumazenil' => 'benodiazepine reversal medication',
            'atropine' => 'heart rate medication',
            'epinephrine' => 'adrenaline',
            'norepinephrine' => 'noradrenaline',
            'dopamine' => 'dopamine',
            'dobutamine' => 'dobutamine',
            'milrinone' => 'milrinone',
            'amiodarone' => 'amiodarone',
            'lidocaine' => 'lidocaine',
            'procainamide' => 'procainamide',
            'digoxin' => 'digoxin',
            'adenosine' => 'adenosine',
            'calcium gluconate' => 'calcium replacement',
            'insulin' => 'insulin',
            'dextrose' => 'sugar solution',
            'sodium bicarbonate' => 'acid neutralizer',
            'magnesium sulfate' => 'magnesium replacement',
            'potassium chloride' => 'potassium replacement',
            'calcium chloride' => 'calcium replacement',
        ];

        // Case-insensitive replacement with word boundaries
        foreach ($abbreviations as $abbr => $expansion) {
            // Use word boundaries to avoid partial replacements
            $text = preg_replace('/\b' . preg_quote($abbr, '/') . '\b/i', $expansion, $text);
        }

        return $text;
    }
}
