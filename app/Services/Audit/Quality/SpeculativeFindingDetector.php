<?php

namespace App\Services\Audit\Quality;

use App\Services\Audit\Models\Finding;
use Illuminate\Support\Facades\Log;

/**
 * Speculative Finding Detector with LETHAL GOLDEN STANDARD Implementation
 * 
 * Advanced detection system for identifying and removing speculative findings
 * Ensures only concrete, evidence-based findings are included in reports
 */
class SpeculativeFindingDetector
{
    private array $speculationIndicators = [];
    private array $speculationPatterns = [];
    private array $confidenceThresholds = [];

    public function __construct()
    {
        $this->initializeSpeculationIndicators();
        $this->initializeSpeculationPatterns();
        $this->initializeConfidenceThresholds();
    }

    /**
     * Analyze finding for speculative characteristics
     */
    public function analyzeForSpeculation(Finding $finding): array
    {
        Log::info('🔍 Analyzing finding for speculation: ' . $finding->getId());

        $speculativeAnalysis = [
            'finding_id' => $finding->getId(),
            'is_speculative' => false,
            'speculation_confidence' => 0.0,
            'speculation_indicators' => [],
            'speculation_score_breakdown' => [],
            'evidence_strength' => 0.0,
            'certainty_level' => 'unknown',
            'recommendation' => 'retain'
        ];

        try {
            // Phase 1: Language Pattern Analysis
            $languageAnalysis = $this->analyzeLanguagePatterns($finding);
            $speculativeAnalysis['speculation_score_breakdown']['language_patterns'] = $languageAnalysis;

            // Phase 2: Evidence Strength Analysis
            $evidenceAnalysis = $this->analyzeEvidenceStrength($finding);
            $speculativeAnalysis['speculation_score_breakdown']['evidence_strength'] = $evidenceAnalysis;

            // Phase 3: Certainty Level Analysis
            $certaintyAnalysis = $this->analyzeCertaintyLevel($finding);
            $speculativeAnalysis['speculation_score_breakdown']['certainty_level'] = $certaintyAnalysis;

            // Phase 4: Reproducibility Analysis
            $reproducibilityAnalysis = $this->analyzeReproducibility($finding);
            $speculativeAnalysis['speculation_score_breakdown']['reproducibility'] = $reproducibilityAnalysis;

            // Phase 5: Context Analysis
            $contextAnalysis = $this->analyzeContext($finding);
            $speculativeAnalysis['speculation_score_breakdown']['context'] = $contextAnalysis;

            // Calculate overall speculation confidence
            $speculativeAnalysis['speculation_confidence'] = $this->calculateSpeculationConfidence($speculativeAnalysis['speculation_score_breakdown']);
            $speculativeAnalysis['evidence_strength'] = $evidenceAnalysis['strength_score'];
            $speculativeAnalysis['certainty_level'] = $certaintyAnalysis['certainty_level'];

            // Identify speculation indicators
            $speculativeAnalysis['speculation_indicators'] = $this->identifySpeculationIndicators($speculativeAnalysis['speculation_score_breakdown']);

            // Determine if finding is speculative
            $speculativeAnalysis['is_speculative'] = $this->determineSpeculativeStatus($speculativeAnalysis);
            $speculativeAnalysis['recommendation'] = $speculativeAnalysis['is_speculative'] ? 'remove' : 'retain';

            Log::info('✅ Speculation analysis completed', [
                'finding_id' => $finding->getId(),
                'is_speculative' => $speculativeAnalysis['is_speculative'],
                'speculation_confidence' => $speculativeAnalysis['speculation_confidence']
            ]);

            return $speculativeAnalysis;

        } catch (\Exception $e) {
            Log::error('❌ Speculation analysis failed', [
                'finding_id' => $finding->getId(),
                'error' => $e->getMessage()
            ]);

            $speculativeAnalysis['is_speculative'] = true; // Err on the side of caution
            $speculativeAnalysis['recommendation'] = 'remove';
            return $speculativeAnalysis;
        }
    }

    /**
     * Initialize speculation indicators
     */
    private function initializeSpeculationIndicators(): void
    {
        $this->speculationIndicators = [
            'language_patterns' => [
                'speculative_words' => ['might', 'could', 'may', 'possibly', 'potentially', 'seems', 'appears', 'likely', 'probably', 'suggests'],
                'uncertainty_phrases' => ['it is possible', 'there is a chance', 'it appears that', 'it seems like', 'this could indicate'],
                'weak_assertions' => ['this might be', 'could be related to', 'may indicate', 'possibly caused by'],
                'hedging_language' => ['somewhat', 'rather', 'quite', 'fairly', 'relatively', 'arguably']
            ],
            'evidence_weakness' => [
                'insufficient_evidence' => 'Less than 2 pieces of evidence',
                'weak_evidence_types' => ['assumption', 'speculation', 'hypothesis'],
                'missing_concrete_data' => 'No quantifiable metrics or concrete data',
                'unverified_claims' => 'Claims without supporting evidence'
            ],
            'certainty_indicators' => [
                'low_confidence' => 'Confidence level below 70%',
                'multiple_possibilities' => 'Multiple potential causes mentioned',
                'conditional_statements' => 'Heavy use of if/then statements',
                'qualification_overuse' => 'Excessive use of qualifiers'
            ]
        ];
    }

    /**
     * Initialize speculation patterns
     */
    private function initializeSpeculationPatterns(): void
    {
        $this->speculationPatterns = [
            'description_patterns' => [
                '/\b(might|could|may|possibly|potentially)\b/i',
                '/\b(seems|appears|looks like)\b/i',
                '/\b(probably|likely|presumably)\b/i',
                '/\b(suggests|indicates|implies)\b/i',
                '/\b(it is possible|there is a chance)\b/i'
            ],
            'recommendation_patterns' => [
                '/\b(should consider|might want to|could try)\b/i',
                '/\b(it would be good|it might help)\b/i',
                '/\b(perhaps|maybe|possibly)\b/i'
            ],
            'evidence_patterns' => [
                '/\b(based on limited|insufficient evidence)\b/i',
                '/\b(preliminary analysis|initial assessment)\b/i',
                '/\b(requires further investigation)\b/i'
            ]
        ];
    }

    /**
     * Initialize confidence thresholds
     */
    private function initializeConfidenceThresholds(): void
    {
        $this->confidenceThresholds = [
            'speculation_threshold' => 60.0,  // Above this = speculative
            'certainty_threshold' => 80.0,    // Above this = certain
            'evidence_threshold' => 70.0,     // Above this = strong evidence
            'removal_threshold' => 75.0       // Above this = remove finding
        ];
    }

    /**
     * Analyze language patterns for speculation
     */
    private function analyzeLanguagePatterns(Finding $finding): array
    {
        $description = $finding->getDescription();
        $recommendation = $finding->getRecommendation();
        $text = $description . ' ' . $recommendation;

        $analysis = [
            'speculative_word_count' => 0,
            'uncertainty_phrase_count' => 0,
            'weak_assertion_count' => 0,
            'hedging_language_count' => 0,
            'total_speculation_indicators' => 0,
            'speculation_density' => 0.0,
            'language_confidence' => 100.0
        ];

        // Count speculative words
        foreach ($this->speculationIndicators['language_patterns']['speculative_words'] as $word) {
            $analysis['speculative_word_count'] += substr_count(strtolower($text), strtolower($word));
        }

        // Count uncertainty phrases
        foreach ($this->speculationIndicators['language_patterns']['uncertainty_phrases'] as $phrase) {
            $analysis['uncertainty_phrase_count'] += substr_count(strtolower($text), strtolower($phrase));
        }

        // Count weak assertions
        foreach ($this->speculationIndicators['language_patterns']['weak_assertions'] as $assertion) {
            $analysis['weak_assertion_count'] += substr_count(strtolower($text), strtolower($assertion));
        }

        // Count hedging language
        foreach ($this->speculationIndicators['language_patterns']['hedging_language'] as $hedge) {
            $analysis['hedging_language_count'] += substr_count(strtolower($text), strtolower($hedge));
        }

        // Calculate totals
        $analysis['total_speculation_indicators'] = $analysis['speculative_word_count'] + 
                                                   $analysis['uncertainty_phrase_count'] + 
                                                   $analysis['weak_assertion_count'] + 
                                                   $analysis['hedging_language_count'];

        // Calculate speculation density (indicators per 100 words)
        $wordCount = str_word_count($text);
        $analysis['speculation_density'] = $wordCount > 0 ? ($analysis['total_speculation_indicators'] / $wordCount) * 100 : 0;

        // Calculate language confidence (inverse of speculation density)
        $analysis['language_confidence'] = max(0, 100 - ($analysis['speculation_density'] * 10));

        return $analysis;
    }

    /**
     * Analyze evidence strength
     */
    private function analyzeEvidenceStrength(Finding $finding): array
    {
        $evidence = $finding->getEvidence();
        $metrics = $finding->getMetrics();
        $fileReferences = $finding->getFileReferences();

        $analysis = [
            'evidence_count' => count($evidence),
            'metrics_count' => count($metrics),
            'file_reference_count' => count($fileReferences),
            'concrete_evidence_count' => 0,
            'weak_evidence_count' => 0,
            'quantifiable_metrics' => 0,
            'strength_score' => 0.0,
            'evidence_quality' => 'unknown'
        ];

        // Analyze evidence quality
        foreach ($evidence as $evidenceItem) {
            $evidenceType = $evidenceItem['type'] ?? 'unknown';
            
            if (in_array($evidenceType, ['static_analysis', 'runtime_log', 'security_scan', 'performance_test'])) {
                $analysis['concrete_evidence_count']++;
            } elseif (in_array($evidenceType, ['assumption', 'speculation', 'hypothesis', 'observation'])) {
                $analysis['weak_evidence_count']++;
            }
        }

        // Analyze metrics
        foreach ($metrics as $metric) {
            if (is_numeric($metric['value'] ?? null)) {
                $analysis['quantifiable_metrics']++;
            }
        }

        // Calculate strength score
        $strengthFactors = [];
        
        // Evidence count factor (0-30 points)
        $strengthFactors[] = min(30, $analysis['evidence_count'] * 10);
        
        // Concrete evidence factor (0-25 points)
        $strengthFactors[] = min(25, $analysis['concrete_evidence_count'] * 12.5);
        
        // Quantifiable metrics factor (0-20 points)
        $strengthFactors[] = min(20, $analysis['quantifiable_metrics'] * 10);
        
        // File references factor (0-15 points)
        $strengthFactors[] = min(15, $analysis['file_reference_count'] * 7.5);
        
        // Weak evidence penalty (0-10 points deduction)
        $strengthFactors[] = -min(10, $analysis['weak_evidence_count'] * 5);

        $analysis['strength_score'] = max(0, array_sum($strengthFactors));

        // Determine evidence quality
        if ($analysis['strength_score'] >= 80) {
            $analysis['evidence_quality'] = 'strong';
        } elseif ($analysis['strength_score'] >= 60) {
            $analysis['evidence_quality'] = 'moderate';
        } elseif ($analysis['strength_score'] >= 40) {
            $analysis['evidence_quality'] = 'weak';
        } else {
            $analysis['evidence_quality'] = 'very_weak';
        }

        return $analysis;
    }

    /**
     * Analyze certainty level
     */
    private function analyzeCertaintyLevel(Finding $finding): array
    {
        $description = $finding->getDescription();
        $recommendation = $finding->getRecommendation();
        $text = $description . ' ' . $recommendation;

        $analysis = [
            'definitive_statements' => 0,
            'conditional_statements' => 0,
            'qualifier_count' => 0,
            'certainty_indicators' => 0,
            'uncertainty_indicators' => 0,
            'certainty_score' => 0.0,
            'certainty_level' => 'unknown'
        ];

        // Count definitive statements
        $definitivePatterns = ['/\bis\b/', '/\bwill\b/', '/\bmust\b/', '/\balways\b/', '/\bnever\b/'];
        foreach ($definitivePatterns as $pattern) {
            $analysis['definitive_statements'] += preg_match_all($pattern, strtolower($text));
        }

        // Count conditional statements
        $conditionalPatterns = ['/\bif\b/', '/\bunless\b/', '/\bwhen\b/', '/\bshould\b/', '/\bwould\b/'];
        foreach ($conditionalPatterns as $pattern) {
            $analysis['conditional_statements'] += preg_match_all($pattern, strtolower($text));
        }

        // Count qualifiers
        $qualifiers = ['very', 'quite', 'rather', 'somewhat', 'fairly', 'relatively', 'mostly', 'generally'];
        foreach ($qualifiers as $qualifier) {
            $analysis['qualifier_count'] += substr_count(strtolower($text), $qualifier);
        }

        // Calculate certainty score
        $wordCount = str_word_count($text);
        if ($wordCount > 0) {
            $definitiveRatio = $analysis['definitive_statements'] / $wordCount;
            $conditionalRatio = $analysis['conditional_statements'] / $wordCount;
            $qualifierRatio = $analysis['qualifier_count'] / $wordCount;

            $analysis['certainty_score'] = max(0, min(100, 
                ($definitiveRatio * 100) - 
                ($conditionalRatio * 50) - 
                ($qualifierRatio * 30) + 50
            ));
        }

        // Determine certainty level
        if ($analysis['certainty_score'] >= 80) {
            $analysis['certainty_level'] = 'high';
        } elseif ($analysis['certainty_score'] >= 60) {
            $analysis['certainty_level'] = 'moderate';
        } elseif ($analysis['certainty_score'] >= 40) {
            $analysis['certainty_level'] = 'low';
        } else {
            $analysis['certainty_level'] = 'very_low';
        }

        return $analysis;
    }

    /**
     * Analyze reproducibility
     */
    private function analyzeReproducibility(Finding $finding): array
    {
        // Simulate reproducibility analysis based on evidence and metrics
        $evidence = $finding->getEvidence();
        $metrics = $finding->getMetrics();

        $analysis = [
            'reproducibility_score' => 0.0,
            'reproducibility_level' => 'unknown',
            'reproducibility_factors' => []
        ];

        // Calculate reproducibility based on evidence quality
        $reproductibilityFactors = [];

        // Evidence-based reproducibility
        foreach ($evidence as $evidenceItem) {
            $evidenceType = $evidenceItem['type'] ?? 'unknown';
            switch ($evidenceType) {
                case 'static_analysis':
                case 'runtime_log':
                    $reproductibilityFactors[] = 25;
                    break;
                case 'security_scan':
                case 'performance_test':
                    $reproductibilityFactors[] = 20;
                    break;
                case 'code_inspection':
                    $reproductibilityFactors[] = 15;
                    break;
                default:
                    $reproductibilityFactors[] = 5;
            }
        }

        // Metrics-based reproducibility
        foreach ($metrics as $metric) {
            if (is_numeric($metric['value'] ?? null)) {
                $reproductibilityFactors[] = 10;
            }
        }

        $analysis['reproducibility_score'] = min(100, array_sum($reproductibilityFactors));

        // Determine reproducibility level
        if ($analysis['reproducibility_score'] >= 80) {
            $analysis['reproducibility_level'] = 'high';
        } elseif ($analysis['reproducibility_score'] >= 60) {
            $analysis['reproducibility_level'] = 'moderate';
        } elseif ($analysis['reproducibility_score'] >= 40) {
            $analysis['reproducibility_level'] = 'low';
        } else {
            $analysis['reproducibility_level'] = 'very_low';
        }

        return $analysis;
    }

    /**
     * Analyze context
     */
    private function analyzeContext(Finding $finding): array
    {
        $fileReferences = $finding->getFileReferences();
        $category = $finding->getCategory();
        $severity = $finding->getSeverity();

        $analysis = [
            'context_completeness' => 0.0,
            'context_quality' => 'unknown',
            'context_factors' => []
        ];

        $contextFactors = [];

        // File reference context
        foreach ($fileReferences as $fileRef) {
            if (!empty($fileRef['context'])) {
                $contextFactors[] = 20;
            }
            if (!empty($fileRef['line'])) {
                $contextFactors[] = 15;
            }
        }

        // Category context
        if (!empty($category)) {
            $contextFactors[] = 25;
        }

        // Severity context
        if (!empty($severity)) {
            $contextFactors[] = 20;
        }

        $analysis['context_completeness'] = min(100, array_sum($contextFactors));

        // Determine context quality
        if ($analysis['context_completeness'] >= 80) {
            $analysis['context_quality'] = 'complete';
        } elseif ($analysis['context_completeness'] >= 60) {
            $analysis['context_quality'] = 'adequate';
        } elseif ($analysis['context_completeness'] >= 40) {
            $analysis['context_quality'] = 'limited';
        } else {
            $analysis['context_quality'] = 'insufficient';
        }

        return $analysis;
    }

    /**
     * Calculate overall speculation confidence
     */
    private function calculateSpeculationConfidence(array $scoreBreakdown): float
    {
        $weights = [
            'language_patterns' => 0.30,
            'evidence_strength' => 0.25,
            'certainty_level' => 0.20,
            'reproducibility' => 0.15,
            'context' => 0.10
        ];

        $speculationScore = 0.0;

        // Language patterns contribution (higher speculation density = higher speculation score)
        $languageScore = $scoreBreakdown['language_patterns']['speculation_density'] * 2;
        $speculationScore += min(100, $languageScore) * $weights['language_patterns'];

        // Evidence strength contribution (weaker evidence = higher speculation score)
        $evidenceScore = 100 - $scoreBreakdown['evidence_strength']['strength_score'];
        $speculationScore += $evidenceScore * $weights['evidence_strength'];

        // Certainty level contribution (lower certainty = higher speculation score)
        $certaintyScore = 100 - $scoreBreakdown['certainty_level']['certainty_score'];
        $speculationScore += $certaintyScore * $weights['certainty_level'];

        // Reproducibility contribution (lower reproducibility = higher speculation score)
        $reproducibilityScore = 100 - $scoreBreakdown['reproducibility']['reproducibility_score'];
        $speculationScore += $reproducibilityScore * $weights['reproducibility'];

        // Context contribution (incomplete context = higher speculation score)
        $contextScore = 100 - $scoreBreakdown['context']['context_completeness'];
        $speculationScore += $contextScore * $weights['context'];

        return min(100, $speculationScore);
    }

    /**
     * Identify speculation indicators
     */
    private function identifySpeculationIndicators(array $scoreBreakdown): array
    {
        $indicators = [];

        // Language pattern indicators
        $languageAnalysis = $scoreBreakdown['language_patterns'];
        if ($languageAnalysis['speculation_density'] > 5) {
            $indicators[] = 'High speculation density in language (' . round($languageAnalysis['speculation_density'], 2) . '%)';
        }
        if ($languageAnalysis['speculative_word_count'] > 3) {
            $indicators[] = 'Excessive use of speculative words (' . $languageAnalysis['speculative_word_count'] . ')';
        }

        // Evidence strength indicators
        $evidenceAnalysis = $scoreBreakdown['evidence_strength'];
        if ($evidenceAnalysis['strength_score'] < 60) {
            $indicators[] = 'Weak evidence strength (' . round($evidenceAnalysis['strength_score'], 2) . '%)';
        }
        if ($evidenceAnalysis['weak_evidence_count'] > 0) {
            $indicators[] = 'Contains weak evidence types (' . $evidenceAnalysis['weak_evidence_count'] . ')';
        }

        // Certainty level indicators
        $certaintyAnalysis = $scoreBreakdown['certainty_level'];
        if ($certaintyAnalysis['certainty_score'] < 60) {
            $indicators[] = 'Low certainty level (' . round($certaintyAnalysis['certainty_score'], 2) . '%)';
        }

        // Reproducibility indicators
        $reproducibilityAnalysis = $scoreBreakdown['reproducibility'];
        if ($reproducibilityAnalysis['reproducibility_score'] < 60) {
            $indicators[] = 'Low reproducibility (' . round($reproducibilityAnalysis['reproducibility_score'], 2) . '%)';
        }

        // Context indicators
        $contextAnalysis = $scoreBreakdown['context'];
        if ($contextAnalysis['context_completeness'] < 60) {
            $indicators[] = 'Incomplete context (' . round($contextAnalysis['context_completeness'], 2) . '%)';
        }

        return $indicators;
    }

    /**
     * Determine speculative status
     */
    private function determineSpeculativeStatus(array $speculativeAnalysis): bool
    {
        $speculationConfidence = $speculativeAnalysis['speculation_confidence'];
        $evidenceStrength = $speculativeAnalysis['evidence_strength'];
        $certaintyLevel = $speculativeAnalysis['certainty_level'];

        // Primary threshold check
        if ($speculationConfidence >= $this->confidenceThresholds['speculation_threshold']) {
            return true;
        }

        // Secondary checks for edge cases
        if ($evidenceStrength < 40 && $certaintyLevel === 'very_low') {
            return true;
        }

        if (count($speculativeAnalysis['speculation_indicators']) >= 3) {
            return true;
        }

        return false;
    }
}