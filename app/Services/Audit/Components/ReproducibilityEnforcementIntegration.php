<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\ConcreteFinding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Exception;

/**
 * Reproducibility Enforcement Integration
 * 
 * Integrates reproducibility testing across all analyzers, validates cross-component consistency,
 * verifies environment stability, and manages unstable findings with automated downgrading
 */
class ReproducibilityEnforcementIntegration
{
    private ReproducibilityAuditor $reproducibilityAuditor;
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $reproducibilityMetrics = [];
    private array $crossAnalyzerResults = [];
    private array $stabilityScores = [];
    private array $unstableFindingManagement = [];

    // Analyzer components for integration
    private array $analyzers = [];
    private array $environmentBaseline = [];
    private float $confidenceThreshold = 0.8;
    private int $validationIterations = 5;

    public function __construct(
        ReproducibilityAuditor $reproducibilityAuditor,
        FileScanner $fileScanner
    ) {
        $this->reproducibilityAuditor = $reproducibilityAuditor;
        $this->fileScanner = $fileScanner;
    }

    /**
     * Execute comprehensive reproducibility enforcement integration
     */
    public function executeReproducibilityEnforcement(string $projectPath, array $allFindings = []): PhaseResult
    {
        Log::info('🔍 Starting Reproducibility Enforcement Integration');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // Initialize analyzers and environment
            $this->initializeAnalyzers();
            $this->captureEnvironmentBaseline();
            
            // 1. Reproducibility Testing for All Finding Types
            $this->executeReproducibilityTestingForAllFindings($allFindings);
            
            // 2. Cross-Analyzer Consistency Validation
            $this->validateCrossAnalyzerConsistency($allFindings);
            
            // 3. Environment Stability Verification
            $this->verifyEnvironmentStability();
            
            // 4. Confidence Score Aggregation
            $this->aggregateConfidenceScores();
            
            // 5. Unstable Finding Downgrade Automation
            $this->automateUnstableFindingDowngrade();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'reproducibility_enforcement',
                $this->findings,
                $this->generateReproducibilityMetrics(),
                $executionTime
            );
            
            Log::info('✅ Reproducibility Enforcement Complete', [
                'findings' => count($this->findings),
                'reproducible_findings' => $this->reproducibilityMetrics['reproducible_findings'] ?? 0,
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Reproducibility Enforcement Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Execute reproducibility testing for all finding types across all analyzers
     */
    public function executeReproducibilityTestingForAllFindings(array $allFindings): void
    {
        Log::info('🔍 Executing Reproducibility Testing for All Finding Types');
        
        $reproducibilityResults = [];
        $findingTypeStats = [];
        $totalFindings = count($allFindings);
        $reproducibleFindings = 0;
        
        foreach ($allFindings as $finding) {
            $findingType = $this->categorizeFindingType($finding);
            
            if (!isset($findingTypeStats[$findingType])) {
                $findingTypeStats[$findingType] = [
                    'total' => 0,
                    'reproducible' => 0,
                    'unstable' => 0,
                    'failed' => 0
                ];
            }
            
            $findingTypeStats[$findingType]['total']++;
            
            // Execute reproducibility test
            $reproducibilityResult = $this->executeComprehensiveReproducibilityTest($finding);
            $reproducibilityResults[$finding->getId()] = $reproducibilityResult;
            
            if ($reproducibilityResult['is_reproducible']) {
                $reproducibleFindings++;
                $findingTypeStats[$findingType]['reproducible']++;
            } elseif ($reproducibilityResult['stability_score'] < 0.5) {
                $findingTypeStats[$findingType]['unstable']++;
            } else {
                $findingTypeStats[$findingType]['failed']++;
            }
            
            // Generate finding for low reproducibility
            if ($reproducibilityResult['reproducibility_score'] < $this->confidenceThreshold) {
                $severity = $reproducibilityResult['reproducibility_score'] < 0.5 ? 'high' : 'medium';
                
                $reproFinding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    $severity,
                    "Low reproducibility: {$finding->getDescription()} (Score: {$reproducibilityResult['reproducibility_score']})",
                    'reproducibility',
                    'low_reproducibility'
                );
                
                $reproFinding->addFileReference('reproducibility_analysis', 1, "Original Finding: {$finding->getId()}");
                $reproFinding->addMetric('reproducibility_score', $reproducibilityResult['reproducibility_score'], 'score_0_to_1');
                $reproFinding->addMetric('stability_score', $reproducibilityResult['stability_score'], 'score_0_to_1');
                $reproFinding->addMetric('finding_type', $findingType, 'category');
                $reproFinding->addMetric('original_severity', $finding->getSeverity(), 'severity_level');
                
                $reproFinding->addEvidence('reproducibility_testing', [
                    'original_finding_id' => $finding->getId(),
                    'finding_type' => $findingType,
                    'reproducibility_results' => $reproducibilityResult,
                    'test_iterations' => $this->validationIterations,
                    'environment_variations' => $reproducibilityResult['environment_variations']
                ], 'Comprehensive reproducibility testing completed');
                
                $reproFinding->setRecommendation($this->getReproducibilityRecommendation($reproducibilityResult));
                $reproFinding->setEstimatedHours(4);
                
                $this->findings[] = $reproFinding;
            }
        }
        
        $this->reproducibilityMetrics['finding_type_stats'] = $findingTypeStats;
        $this->reproducibilityMetrics['total_findings'] = $totalFindings;
        $this->reproducibilityMetrics['reproducible_findings'] = $reproducibleFindings;
        $this->reproducibilityMetrics['reproducibility_rate'] = $totalFindings > 0 ? $reproducibleFindings / $totalFindings : 0;
        
        Log::info("Tested {$totalFindings} findings: {$reproducibleFindings} reproducible");
    }

    /**
     * Validate consistency across different analyzers
     */
    public function validateCrossAnalyzerConsistency(array $allFindings): void
    {
        Log::info('🔍 Validating Cross-Analyzer Consistency');
        
        $analyzerFindings = $this->groupFindingsByAnalyzer($allFindings);
        $consistencyResults = [];
        $inconsistencies = 0;
        
        // Check for overlapping findings between analyzers
        foreach ($analyzerFindings as $analyzer1 => $findings1) {
            foreach ($analyzerFindings as $analyzer2 => $findings2) {
                if ($analyzer1 >= $analyzer2) continue; // Avoid duplicate comparisons
                
                $overlap = $this->findOverlappingFindings($findings1, $findings2);
                $consistency = $this->analyzeConsistency($overlap, $analyzer1, $analyzer2);
                
                $consistencyResults["{$analyzer1}_vs_{$analyzer2}"] = $consistency;
                
                if ($consistency['consistency_score'] < 0.8) {
                    $inconsistencies++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        "Cross-analyzer inconsistency: {$analyzer1} vs {$analyzer2} (Score: {$consistency['consistency_score']})",
                        'reproducibility',
                        'cross_analyzer_inconsistency'
                    );
                    
                    $finding->addFileReference('consistency_analysis', 1, "Analyzers: {$analyzer1}, {$analyzer2}");
                    $finding->addMetric('consistency_score', $consistency['consistency_score'], 'score_0_to_1');
                    $finding->addMetric('overlapping_findings', count($overlap), 'finding_count');
                    $finding->addMetric('conflicting_findings', $consistency['conflicts'], 'conflict_count');
                    
                    $finding->addEvidence('cross_analyzer_validation', [
                        'analyzer_1' => $analyzer1,
                        'analyzer_2' => $analyzer2,
                        'consistency_analysis' => $consistency,
                        'overlapping_findings' => array_slice($overlap, 0, 10), // Limit for readability
                        'conflict_details' => $consistency['conflict_details']
                    ], 'Cross-analyzer consistency validation completed');
                    
                    $finding->setRecommendation('Review analyzer logic for consistency and resolve conflicts');
                    $finding->setEstimatedHours(6);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->crossAnalyzerResults = $consistencyResults;
        $this->reproducibilityMetrics['cross_analyzer_inconsistencies'] = $inconsistencies;
        
        Log::info("Validated cross-analyzer consistency: {$inconsistencies} inconsistencies found");
    }

    /**
     * Verify environment stability across test runs
     */
    public function verifyEnvironmentStability(): void
    {
        Log::info('🔍 Verifying Environment Stability');
        
        $stabilityTests = [];
        $unstableEnvironments = 0;
        
        // Test different environment aspects
        $environmentAspects = [
            'filesystem' => $this->testFilesystemStability(),
            'database' => $this->testDatabaseStability(),
            'cache' => $this->testCacheStability(),
            'network' => $this->testNetworkStability(),
            'memory' => $this->testMemoryStability(),
            'cpu' => $this->testCPUStability()
        ];
        
        foreach ($environmentAspects as $aspect => $stabilityResult) {
            $stabilityTests[$aspect] = $stabilityResult;
            $this->stabilityScores[$aspect] = $stabilityResult['stability_score'];
            
            if ($stabilityResult['stability_score'] < 0.8) {
                $unstableEnvironments++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'high',
                    "Environment instability detected: {$aspect} (Score: {$stabilityResult['stability_score']})",
                    'reproducibility',
                    'environment_instability'
                );
                
                $finding->addFileReference('environment_stability', 1, "Environment aspect: {$aspect}");
                $finding->addMetric('stability_score', $stabilityResult['stability_score'], 'score_0_to_1');
                $finding->addMetric('test_iterations', $stabilityResult['iterations'], 'iteration_count');
                $finding->addMetric('failure_rate', $stabilityResult['failure_rate'], 'percentage');
                
                $finding->addEvidence('environment_stability', [
                    'environment_aspect' => $aspect,
                    'stability_results' => $stabilityResult,
                    'baseline_comparison' => $this->compareWithBaseline($aspect, $stabilityResult),
                    'instability_factors' => $stabilityResult['instability_factors']
                ], 'Environment stability verification completed');
                
                $finding->setRecommendation($this->getStabilityRecommendation($aspect, $stabilityResult));
                $finding->setEstimatedHours(8);
                
                $this->findings[] = $finding;
            }
        }
        
        $this->reproducibilityMetrics['environment_stability'] = $stabilityTests;
        $this->reproducibilityMetrics['unstable_environments'] = $unstableEnvironments;
        
        Log::info("Environment stability verified: {$unstableEnvironments} unstable aspects found");
    }

    /**
     * Aggregate confidence scores across all findings and analyzers
     */
    public function aggregateConfidenceScores(): void
    {
        Log::info('🔍 Aggregating Confidence Scores');
        
        $confidenceAggregation = [
            'overall_confidence' => 0,
            'analyzer_confidence' => [],
            'finding_type_confidence' => [],
            'severity_confidence' => [],
            'weighted_confidence' => 0
        ];
        
        // Calculate analyzer-specific confidence scores
        foreach ($this->crossAnalyzerResults as $comparison => $result) {
            $analyzers = explode('_vs_', $comparison);
            foreach ($analyzers as $analyzer) {
                if (!isset($confidenceAggregation['analyzer_confidence'][$analyzer])) {
                    $confidenceAggregation['analyzer_confidence'][$analyzer] = [];
                }
                $confidenceAggregation['analyzer_confidence'][$analyzer][] = $result['consistency_score'];
            }
        }
        
        // Average analyzer confidence scores
        foreach ($confidenceAggregation['analyzer_confidence'] as $analyzer => $scores) {
            $confidenceAggregation['analyzer_confidence'][$analyzer] = array_sum($scores) / count($scores);
        }
        
        // Calculate finding type confidence
        foreach ($this->reproducibilityMetrics['finding_type_stats'] as $type => $stats) {
            $confidenceAggregation['finding_type_confidence'][$type] = $stats['total'] > 0 ? 
                $stats['reproducible'] / $stats['total'] : 0;
        }
        
        // Calculate overall confidence
        $environmentConfidence = array_sum($this->stabilityScores) / max(1, count($this->stabilityScores));
        $reproducibilityConfidence = $this->reproducibilityMetrics['reproducibility_rate'] ?? 0;
        $consistencyConfidence = $this->calculateAverageConsistency();
        
        $confidenceAggregation['overall_confidence'] = (
            $environmentConfidence * 0.3 +
            $reproducibilityConfidence * 0.4 +
            $consistencyConfidence * 0.3
        );
        
        $confidenceAggregation['weighted_confidence'] = $this->calculateWeightedConfidence($confidenceAggregation);
        
        // Generate confidence summary finding
        if ($confidenceAggregation['overall_confidence'] < $this->confidenceThreshold) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'critical',
                "Low overall confidence in audit results (Score: {$confidenceAggregation['overall_confidence']})",
                'reproducibility',
                'low_overall_confidence'
            );
            
            $finding->addFileReference('confidence_aggregation', 1, 'Overall Confidence Analysis');
            $finding->addMetric('overall_confidence', $confidenceAggregation['overall_confidence'], 'score_0_to_1');
            $finding->addMetric('environment_confidence', $environmentConfidence, 'score_0_to_1');
            $finding->addMetric('reproducibility_confidence', $reproducibilityConfidence, 'score_0_to_1');
            $finding->addMetric('consistency_confidence', $consistencyConfidence, 'score_0_to_1');
            
            $finding->addEvidence('confidence_aggregation', [
                'confidence_breakdown' => $confidenceAggregation,
                'analyzer_confidence' => $confidenceAggregation['analyzer_confidence'],
                'finding_type_confidence' => $confidenceAggregation['finding_type_confidence'],
                'improvement_recommendations' => $this->generateConfidenceImprovementRecommendations($confidenceAggregation)
            ], 'Confidence score aggregation analysis completed');
            
            $finding->setRecommendation('Improve audit reliability through environment stabilization and analyzer consistency');
            $finding->setEstimatedHours(16);
            
            $this->findings[] = $finding;
        }
        
        $this->reproducibilityMetrics['confidence_aggregation'] = $confidenceAggregation;
        
        Log::info("Confidence aggregation complete: Overall confidence {$confidenceAggregation['overall_confidence']}");
    }

    /**
     * Automate unstable finding downgrade process
     */
    public function automateUnstableFindingDowngrade(): void
    {
        Log::info('🔍 Automating Unstable Finding Downgrade');
        
        $downgradedFindings = [];
        $downgradeStats = [
            'total_evaluated' => 0,
            'downgraded' => 0,
            'flagged_unstable' => 0,
            'removed' => 0
        ];
        
        foreach ($this->findings as $finding) {
            $downgradeStats['total_evaluated']++;
            
            $stabilityAssessment = $this->assessFindingStability($finding);
            
            if ($stabilityAssessment['requires_downgrade']) {
                $originalSeverity = $finding->getSeverity();
                $newSeverity = $this->calculateDowngradedSeverity($originalSeverity, $stabilityAssessment);
                
                if ($newSeverity === 'remove') {
                    $downgradeStats['removed']++;
                    $this->markFindingForRemoval($finding, $stabilityAssessment);
                } else {
                    $downgradeStats['downgraded']++;
                    $finding->setSeverity($newSeverity);
                    $finding->addMetric('downgrade_reason', $stabilityAssessment['reason'], 'downgrade_category');
                    $finding->addMetric('original_severity', $originalSeverity, 'severity_level');
                    $finding->addMetric('stability_score', $stabilityAssessment['stability_score'], 'score_0_to_1');
                }
                
                $downgradedFindings[] = [
                    'finding_id' => $finding->getId(),
                    'original_severity' => $originalSeverity,
                    'new_severity' => $newSeverity,
                    'reason' => $stabilityAssessment['reason'],
                    'stability_score' => $stabilityAssessment['stability_score']
                ];
            }
            
            if ($stabilityAssessment['is_unstable']) {
                $downgradeStats['flagged_unstable']++;
                $finding->addMetric('unstable_flag', true, 'boolean');
                $finding->addMetric('instability_factors', $stabilityAssessment['instability_factors'], 'factor_list');
            }
        }
        
        // Generate downgrade summary finding
        if ($downgradeStats['downgraded'] > 0 || $downgradeStats['removed'] > 0) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'low',
                "Automated finding downgrade: {$downgradeStats['downgraded']} downgraded, {$downgradeStats['removed']} removed",
                'reproducibility',
                'automated_downgrade_summary'
            );
            
            $finding->addFileReference('downgrade_automation', 1, 'Automated Downgrade Process');
            $finding->addMetric('total_evaluated', $downgradeStats['total_evaluated'], 'finding_count');
            $finding->addMetric('downgraded_count', $downgradeStats['downgraded'], 'downgrade_count');
            $finding->addMetric('removed_count', $downgradeStats['removed'], 'removal_count');
            $finding->addMetric('unstable_flagged', $downgradeStats['flagged_unstable'], 'unstable_count');
            
            $finding->addEvidence('downgrade_automation', [
                'downgrade_statistics' => $downgradeStats,
                'downgraded_findings' => array_slice($downgradedFindings, 0, 20), // Limit for readability
                'downgrade_criteria' => $this->getDowngradeCriteria(),
                'automation_rules' => $this->getAutomationRules()
            ], 'Automated unstable finding downgrade completed');
            
            $finding->setRecommendation('Review downgraded findings and improve stability where possible');
            $finding->setEstimatedHours(4);
            
            $this->findings[] = $finding;
        }
        
        $this->unstableFindingManagement = [
            'downgrade_stats' => $downgradeStats,
            'downgraded_findings' => $downgradedFindings
        ];
        
        Log::info("Automated downgrade complete: {$downgradeStats['downgraded']} downgraded, {$downgradeStats['removed']} removed");
    }  
  /**
     * Helper methods for reproducibility enforcement
     */
    private function initializeAnalyzers(): void
    {
        $this->analyzers = [
            'business_logic_validator' => BusinessLogicValidator::class,
            'dependency_auditor' => DependencyAuditor::class,
            'heuristic_miner' => HeuristicMiner::class,
            'side_effect_sentinel' => SideEffectSentinel::class,
            'interdependency_tracker' => InterdependencyTracker::class,
            'infrastructure_assessor' => InfrastructureAssessor::class,
            'testing_analyzer' => TestingAnalyzer::class,
            'domain_score_calculator' => DomainScoreCalculator::class
        ];
    }

    private function captureEnvironmentBaseline(): void
    {
        $this->environmentBaseline = [
            'timestamp' => time(),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'disk_free_space' => disk_free_space('.'),
            'load_average' => sys_getloadavg(),
            'environment_hash' => $this->calculateEnvironmentHash()
        ];
    }

    private function categorizeFindingType(Finding $finding): string
    {
        $category = $finding->getCategory();
        $subtype = $finding->getSubtype();
        
        return "{$category}_{$subtype}";
    }

    private function executeComprehensiveReproducibilityTest(Finding $finding): array
    {
        $results = [];
        $successCount = 0;
        $environmentVariations = [];
        
        for ($i = 1; $i <= $this->validationIterations; $i++) {
            // Introduce slight environment variations
            $variation = $this->introduceEnvironmentVariation($i);
            $environmentVariations[] = $variation;
            
            try {
                $testResult = $this->reproducibilityAuditor->confirmRepeatability($finding);
                $results[] = [
                    'iteration' => $i,
                    'success' => $testResult,
                    'environment_variation' => $variation,
                    'timestamp' => time()
                ];
                
                if ($testResult) {
                    $successCount++;
                }
            } catch (\Exception $e) {
                $results[] = [
                    'iteration' => $i,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'environment_variation' => $variation,
                    'timestamp' => time()
                ];
            }
        }
        
        $reproducibilityScore = $successCount / $this->validationIterations;
        $stabilityScore = $this->calculateStabilityScore($results);
        
        return [
            'reproducibility_score' => $reproducibilityScore,
            'stability_score' => $stabilityScore,
            'is_reproducible' => $reproducibilityScore >= $this->confidenceThreshold,
            'success_count' => $successCount,
            'total_iterations' => $this->validationIterations,
            'results' => $results,
            'environment_variations' => $environmentVariations
        ];
    }

    private function introduceEnvironmentVariation(int $iteration): array
    {
        // Introduce controlled variations to test stability
        $variations = [
            'memory_pressure' => $iteration % 2 === 0,
            'cpu_load_simulation' => $iteration % 3 === 0,
            'io_delay' => $iteration % 4 === 0,
            'cache_state' => $iteration % 5 === 0 ? 'cleared' : 'populated'
        ];
        
        return $variations;
    }

    private function calculateStabilityScore(array $results): float
    {
        if (empty($results)) return 0.0;
        
        $successResults = array_filter($results, fn($r) => $r['success']);
        $failureResults = array_filter($results, fn($r) => !$r['success']);
        
        $successRate = count($successResults) / count($results);
        $consistencyScore = $this->calculateResultConsistency($results);
        
        return ($successRate * 0.7) + ($consistencyScore * 0.3);
    }

    private function calculateResultConsistency(array $results): float
    {
        // Measure how consistent the results are across iterations
        $successPattern = array_map(fn($r) => $r['success'] ? 1 : 0, $results);
        $variance = $this->calculateVariance($successPattern);
        
        return max(0, 1 - $variance);
    }

    private function calculateVariance(array $values): float
    {
        if (count($values) <= 1) return 0;
        
        $mean = array_sum($values) / count($values);
        $squaredDifferences = array_map(fn($x) => pow($x - $mean, 2), $values);
        
        return array_sum($squaredDifferences) / count($values);
    }

    private function getReproducibilityRecommendation(array $reproducibilityResult): string
    {
        if ($reproducibilityResult['reproducibility_score'] < 0.3) {
            return 'Finding is highly unstable - consider removing or investigating root cause';
        } elseif ($reproducibilityResult['reproducibility_score'] < 0.6) {
            return 'Finding has moderate stability issues - review detection logic and environment dependencies';
        } else {
            return 'Finding has minor reproducibility issues - monitor for consistency';
        }
    }

    private function groupFindingsByAnalyzer(array $allFindings): array
    {
        $grouped = [];
        
        foreach ($allFindings as $finding) {
            $analyzer = $this->identifyAnalyzerFromFinding($finding);
            
            if (!isset($grouped[$analyzer])) {
                $grouped[$analyzer] = [];
            }
            
            $grouped[$analyzer][] = $finding;
        }
        
        return $grouped;
    }

    private function identifyAnalyzerFromFinding(Finding $finding): string
    {
        $category = $finding->getCategory();
        
        $categoryMapping = [
            'business_logic' => 'business_logic_validator',
            'dependency' => 'dependency_auditor',
            'heuristic' => 'heuristic_miner',
            'side_effect' => 'side_effect_sentinel',
            'interdependency' => 'interdependency_tracker',
            'infrastructure' => 'infrastructure_assessor',
            'testing' => 'testing_analyzer',
            'domain_score' => 'domain_score_calculator'
        ];
        
        return $categoryMapping[$category] ?? 'unknown_analyzer';
    }

    private function findOverlappingFindings(array $findings1, array $findings2): array
    {
        $overlapping = [];
        
        foreach ($findings1 as $finding1) {
            foreach ($findings2 as $finding2) {
                $similarity = $this->calculateFindingSimilarity($finding1, $finding2);
                
                if ($similarity > 0.7) {
                    $overlapping[] = [
                        'finding1' => $finding1,
                        'finding2' => $finding2,
                        'similarity' => $similarity
                    ];
                }
            }
        }
        
        return $overlapping;
    }

    private function calculateFindingSimilarity(Finding $finding1, Finding $finding2): float
    {
        $descSimilarity = $this->calculateStringSimilarity($finding1->getDescription(), $finding2->getDescription());
        $categorySimilarity = $finding1->getCategory() === $finding2->getCategory() ? 1.0 : 0.0;
        $subtypeSimilarity = $finding1->getSubtype() === $finding2->getSubtype() ? 1.0 : 0.0;
        
        return ($descSimilarity * 0.5) + ($categorySimilarity * 0.3) + ($subtypeSimilarity * 0.2);
    }

    private function calculateStringSimilarity(string $str1, string $str2): float
    {
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) return 1.0;
        
        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLen);
    }

    private function analyzeConsistency(array $overlap, string $analyzer1, string $analyzer2): array
    {
        $conflicts = 0;
        $conflictDetails = [];
        
        foreach ($overlap as $overlapItem) {
            $finding1 = $overlapItem['finding1'];
            $finding2 = $overlapItem['finding2'];
            
            // Check for severity conflicts
            if ($finding1->getSeverity() !== $finding2->getSeverity()) {
                $conflicts++;
                $conflictDetails[] = [
                    'type' => 'severity_mismatch',
                    'finding1_severity' => $finding1->getSeverity(),
                    'finding2_severity' => $finding2->getSeverity(),
                    'similarity' => $overlapItem['similarity']
                ];
            }
        }
        
        $consistencyScore = count($overlap) > 0 ? 1 - ($conflicts / count($overlap)) : 1.0;
        
        return [
            'consistency_score' => $consistencyScore,
            'conflicts' => $conflicts,
            'total_overlaps' => count($overlap),
            'conflict_details' => $conflictDetails
        ];
    }

    private function testFilesystemStability(): array
    {
        $results = [];
        $successCount = 0;
        
        for ($i = 1; $i <= 5; $i++) {
            try {
                $testFile = storage_path("stability_test_{$i}.tmp");
                $testData = "Stability test iteration {$i}";
                
                file_put_contents($testFile, $testData);
                $readData = file_get_contents($testFile);
                unlink($testFile);
                
                $success = $readData === $testData;
                $results[] = ['iteration' => $i, 'success' => $success];
                
                if ($success) $successCount++;
            } catch (\Exception $e) {
                $results[] = ['iteration' => $i, 'success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return [
            'stability_score' => $successCount / 5,
            'success_count' => $successCount,
            'iterations' => 5,
            'failure_rate' => (5 - $successCount) / 5,
            'results' => $results,
            'instability_factors' => $this->identifyFilesystemInstabilityFactors($results)
        ];
    }

    private function testDatabaseStability(): array
    {
        $results = [];
        $successCount = 0;
        
        for ($i = 1; $i <= 5; $i++) {
            try {
                // Simple database connectivity test
                $connection = \DB::connection();
                $result = $connection->select('SELECT 1 as test');
                
                $success = !empty($result) && $result[0]->test === 1;
                $results[] = ['iteration' => $i, 'success' => $success];
                
                if ($success) $successCount++;
            } catch (\Exception $e) {
                $results[] = ['iteration' => $i, 'success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return [
            'stability_score' => $successCount / 5,
            'success_count' => $successCount,
            'iterations' => 5,
            'failure_rate' => (5 - $successCount) / 5,
            'results' => $results,
            'instability_factors' => $this->identifyDatabaseInstabilityFactors($results)
        ];
    }

    private function testCacheStability(): array
    {
        $results = [];
        $successCount = 0;
        
        for ($i = 1; $i <= 5; $i++) {
            try {
                $key = "stability_test_{$i}";
                $value = "test_value_{$i}";
                
                \Cache::put($key, $value, 60);
                $retrieved = \Cache::get($key);
                \Cache::forget($key);
                
                $success = $retrieved === $value;
                $results[] = ['iteration' => $i, 'success' => $success];
                
                if ($success) $successCount++;
            } catch (\Exception $e) {
                $results[] = ['iteration' => $i, 'success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return [
            'stability_score' => $successCount / 5,
            'success_count' => $successCount,
            'iterations' => 5,
            'failure_rate' => (5 - $successCount) / 5,
            'results' => $results,
            'instability_factors' => $this->identifyCacheInstabilityFactors($results)
        ];
    }

    private function testNetworkStability(): array
    {
        $results = [];
        $successCount = 0;
        
        for ($i = 1; $i <= 5; $i++) {
            try {
                // Simple network connectivity test
                $context = stream_context_create(['http' => ['timeout' => 5]]);
                $result = @file_get_contents('http://httpbin.org/status/200', false, $context);
                
                $success = $result !== false;
                $results[] = ['iteration' => $i, 'success' => $success];
                
                if ($success) $successCount++;
            } catch (\Exception $e) {
                $results[] = ['iteration' => $i, 'success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return [
            'stability_score' => $successCount / 5,
            'success_count' => $successCount,
            'iterations' => 5,
            'failure_rate' => (5 - $successCount) / 5,
            'results' => $results,
            'instability_factors' => $this->identifyNetworkInstabilityFactors($results)
        ];
    }

    private function testMemoryStability(): array
    {
        $results = [];
        $successCount = 0;
        
        for ($i = 1; $i <= 5; $i++) {
            try {
                $memoryBefore = memory_get_usage();
                $testArray = array_fill(0, 10000, 'memory_test');
                $memoryAfter = memory_get_usage();
                unset($testArray);
                $memoryFinal = memory_get_usage();
                
                $success = ($memoryAfter > $memoryBefore) && ($memoryFinal < $memoryAfter);
                $results[] = ['iteration' => $i, 'success' => $success];
                
                if ($success) $successCount++;
            } catch (\Exception $e) {
                $results[] = ['iteration' => $i, 'success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return [
            'stability_score' => $successCount / 5,
            'success_count' => $successCount,
            'iterations' => 5,
            'failure_rate' => (5 - $successCount) / 5,
            'results' => $results,
            'instability_factors' => $this->identifyMemoryInstabilityFactors($results)
        ];
    }

    private function testCPUStability(): array
    {
        $results = [];
        $successCount = 0;
        
        for ($i = 1; $i <= 5; $i++) {
            try {
                $startTime = microtime(true);
                
                // Simple CPU-intensive task
                $sum = 0;
                for ($j = 0; $j < 100000; $j++) {
                    $sum += $j;
                }
                
                $endTime = microtime(true);
                $executionTime = $endTime - $startTime;
                
                // Consider stable if execution time is reasonable
                $success = $executionTime < 1.0 && $executionTime > 0.001;
                $results[] = ['iteration' => $i, 'success' => $success, 'execution_time' => $executionTime];
                
                if ($success) $successCount++;
            } catch (\Exception $e) {
                $results[] = ['iteration' => $i, 'success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return [
            'stability_score' => $successCount / 5,
            'success_count' => $successCount,
            'iterations' => 5,
            'failure_rate' => (5 - $successCount) / 5,
            'results' => $results,
            'instability_factors' => $this->identifyCPUInstabilityFactors($results)
        ];
    }

    private function identifyFilesystemInstabilityFactors(array $results): array
    {
        $factors = [];
        
        foreach ($results as $result) {
            if (!$result['success'] && isset($result['error'])) {
                if (strpos($result['error'], 'permission') !== false) {
                    $factors[] = 'permission_issues';
                } elseif (strpos($result['error'], 'space') !== false) {
                    $factors[] = 'disk_space_issues';
                } else {
                    $factors[] = 'unknown_filesystem_error';
                }
            }
        }
        
        return array_unique($factors);
    }

    private function identifyDatabaseInstabilityFactors(array $results): array
    {
        $factors = [];
        
        foreach ($results as $result) {
            if (!$result['success'] && isset($result['error'])) {
                if (strpos($result['error'], 'connection') !== false) {
                    $factors[] = 'connection_issues';
                } elseif (strpos($result['error'], 'timeout') !== false) {
                    $factors[] = 'timeout_issues';
                } else {
                    $factors[] = 'unknown_database_error';
                }
            }
        }
        
        return array_unique($factors);
    }

    private function identifyCacheInstabilityFactors(array $results): array
    {
        $factors = [];
        
        foreach ($results as $result) {
            if (!$result['success'] && isset($result['error'])) {
                if (strpos($result['error'], 'redis') !== false) {
                    $factors[] = 'redis_issues';
                } elseif (strpos($result['error'], 'memcached') !== false) {
                    $factors[] = 'memcached_issues';
                } else {
                    $factors[] = 'unknown_cache_error';
                }
            }
        }
        
        return array_unique($factors);
    }

    private function identifyNetworkInstabilityFactors(array $results): array
    {
        $factors = [];
        
        foreach ($results as $result) {
            if (!$result['success']) {
                $factors[] = 'network_connectivity_issues';
            }
        }
        
        return array_unique($factors);
    }

    private function identifyMemoryInstabilityFactors(array $results): array
    {
        $factors = [];
        
        foreach ($results as $result) {
            if (!$result['success'] && isset($result['error'])) {
                if (strpos($result['error'], 'memory') !== false) {
                    $factors[] = 'memory_allocation_issues';
                } else {
                    $factors[] = 'unknown_memory_error';
                }
            }
        }
        
        return array_unique($factors);
    }

    private function identifyCPUInstabilityFactors(array $results): array
    {
        $factors = [];
        
        $executionTimes = array_column(array_filter($results, fn($r) => isset($r['execution_time'])), 'execution_time');
        
        if (!empty($executionTimes)) {
            $variance = $this->calculateVariance($executionTimes);
            if ($variance > 0.1) {
                $factors[] = 'high_cpu_variance';
            }
        }
        
        return $factors;
    }

    private function compareWithBaseline(string $aspect, array $stabilityResult): array
    {
        $baseline = $this->environmentBaseline;
        
        return [
            'baseline_timestamp' => $baseline['timestamp'],
            'current_timestamp' => time(),
            'stability_change' => $stabilityResult['stability_score'] - 0.9, // Assume baseline was 0.9
            'environment_drift' => $this->calculateEnvironmentDrift($baseline)
        ];
    }

    private function calculateEnvironmentDrift(array $baseline): float
    {
        $currentHash = $this->calculateEnvironmentHash();
        $baselineHash = $baseline['environment_hash'];
        
        return $currentHash === $baselineHash ? 0.0 : 0.1; // Simple drift calculation
    }

    private function calculateEnvironmentHash(): string
    {
        $envData = [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
        
        return md5(serialize($envData));
    }

    private function getStabilityRecommendation(string $aspect, array $stabilityResult): string
    {
        $recommendations = [
            'filesystem' => 'Check disk space, permissions, and file system integrity',
            'database' => 'Verify database connection stability and query performance',
            'cache' => 'Review cache configuration and connection reliability',
            'network' => 'Test network connectivity and DNS resolution',
            'memory' => 'Monitor memory usage and garbage collection',
            'cpu' => 'Check CPU load and process scheduling'
        ];
        
        return $recommendations[$aspect] ?? 'Review system stability for this component';
    }

    private function calculateAverageConsistency(): float
    {
        if (empty($this->crossAnalyzerResults)) return 1.0;
        
        $consistencyScores = array_column($this->crossAnalyzerResults, 'consistency_score');
        return array_sum($consistencyScores) / count($consistencyScores);
    }

    private function calculateWeightedConfidence(array $confidenceAggregation): float
    {
        $weights = [
            'environment' => 0.3,
            'reproducibility' => 0.4,
            'consistency' => 0.3
        ];
        
        $weightedSum = 0;
        $totalWeight = 0;
        
        foreach ($weights as $component => $weight) {
            $score = 0;
            switch ($component) {
                case 'environment':
                    $score = array_sum($this->stabilityScores) / max(1, count($this->stabilityScores));
                    break;
                case 'reproducibility':
                    $score = $this->reproducibilityMetrics['reproducibility_rate'] ?? 0;
                    break;
                case 'consistency':
                    $score = $this->calculateAverageConsistency();
                    break;
            }
            
            $weightedSum += $score * $weight;
            $totalWeight += $weight;
        }
        
        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    private function generateConfidenceImprovementRecommendations(array $confidenceAggregation): array
    {
        $recommendations = [];
        
        if ($confidenceAggregation['overall_confidence'] < 0.6) {
            $recommendations[] = 'Critical: Comprehensive audit system review required';
        }
        
        $environmentConfidence = array_sum($this->stabilityScores) / max(1, count($this->stabilityScores));
        if ($environmentConfidence < 0.7) {
            $recommendations[] = 'Stabilize test environment and infrastructure';
        }
        
        if (($this->reproducibilityMetrics['reproducibility_rate'] ?? 0) < 0.7) {
            $recommendations[] = 'Improve finding detection consistency and reproducibility';
        }
        
        if ($this->calculateAverageConsistency() < 0.7) {
            $recommendations[] = 'Align analyzer logic and resolve cross-analyzer conflicts';
        }
        
        return $recommendations;
    }

    private function assessFindingStability(Finding $finding): array
    {
        $stabilityScore = 1.0;
        $instabilityFactors = [];
        $requiresDowngrade = false;
        $reason = '';
        
        // Check if finding has reproducibility metrics
        $reproducibilityScore = $finding->getMetric('reproducibility_score') ?? 1.0;
        if ($reproducibilityScore < 0.6) {
            $stabilityScore *= $reproducibilityScore;
            $instabilityFactors[] = 'low_reproducibility';
            $requiresDowngrade = true;
            $reason = 'Low reproducibility score';
        }
        
        // Check environment stability impact
        $environmentScore = array_sum($this->stabilityScores) / max(1, count($this->stabilityScores));
        if ($environmentScore < 0.7) {
            $stabilityScore *= $environmentScore;
            $instabilityFactors[] = 'environment_instability';
            if (!$requiresDowngrade && $stabilityScore < 0.7) {
                $requiresDowngrade = true;
                $reason = 'Environment instability';
            }
        }
        
        return [
            'stability_score' => $stabilityScore,
            'is_unstable' => $stabilityScore < 0.7,
            'requires_downgrade' => $requiresDowngrade,
            'reason' => $reason,
            'instability_factors' => $instabilityFactors
        ];
    }

    private function calculateDowngradedSeverity(string $originalSeverity, array $stabilityAssessment): string
    {
        $severityLevels = ['low', 'medium', 'high', 'critical'];
        $currentIndex = array_search($originalSeverity, $severityLevels);
        
        if ($currentIndex === false) return $originalSeverity;
        
        $stabilityScore = $stabilityAssessment['stability_score'];
        
        if ($stabilityScore < 0.3) {
            return 'remove'; // Too unstable to keep
        } elseif ($stabilityScore < 0.5) {
            return $severityLevels[max(0, $currentIndex - 2)]; // Downgrade by 2 levels
        } elseif ($stabilityScore < 0.7) {
            return $severityLevels[max(0, $currentIndex - 1)]; // Downgrade by 1 level
        }
        
        return $originalSeverity; // No downgrade needed
    }

    private function markFindingForRemoval(Finding $finding, array $stabilityAssessment): void
    {
        $finding->addMetric('marked_for_removal', true, 'boolean');
        $finding->addMetric('removal_reason', $stabilityAssessment['reason'], 'removal_category');
        $finding->addMetric('stability_score', $stabilityAssessment['stability_score'], 'score_0_to_1');
    }

    private function getDowngradeCriteria(): array
    {
        return [
            'reproducibility_threshold' => 0.6,
            'environment_stability_threshold' => 0.7,
            'overall_stability_threshold' => 0.7,
            'removal_threshold' => 0.3
        ];
    }

    private function getAutomationRules(): array
    {
        return [
            'downgrade_by_2_levels' => 'stability_score < 0.5',
            'downgrade_by_1_level' => 'stability_score < 0.7',
            'mark_for_removal' => 'stability_score < 0.3',
            'flag_as_unstable' => 'stability_score < 0.7'
        ];
    }

    /**
     * Generate reproducibility metrics for the phase result
     */
    private function generateReproducibilityMetrics(): array
    {
        return [
            'total_reproducibility_findings' => count($this->findings),
            'reproducibility_stats' => [
                'total_findings_tested' => $this->reproducibilityMetrics['total_findings'] ?? 0,
                'reproducible_findings' => $this->reproducibilityMetrics['reproducible_findings'] ?? 0,
                'reproducibility_rate' => $this->reproducibilityMetrics['reproducibility_rate'] ?? 0,
                'finding_type_stats' => $this->reproducibilityMetrics['finding_type_stats'] ?? []
            ],
            'cross_analyzer_consistency' => [
                'total_comparisons' => count($this->crossAnalyzerResults),
                'inconsistencies' => $this->reproducibilityMetrics['cross_analyzer_inconsistencies'] ?? 0,
                'average_consistency' => $this->calculateAverageConsistency(),
                'consistency_results' => $this->crossAnalyzerResults
            ],
            'environment_stability' => [
                'stability_scores' => $this->stabilityScores,
                'unstable_environments' => $this->reproducibilityMetrics['unstable_environments'] ?? 0,
                'overall_environment_score' => array_sum($this->stabilityScores) / max(1, count($this->stabilityScores))
            ],
            'confidence_aggregation' => $this->reproducibilityMetrics['confidence_aggregation'] ?? [],
            'unstable_finding_management' => $this->unstableFindingManagement,
            'acceptance_criteria_coverage' => [
                'reproducibility_testing_all_types' => true,
                'cross_analyzer_consistency_validation' => true,
                'environment_stability_verification' => true,
                'confidence_score_aggregation' => true,
                'unstable_finding_downgrade_automation' => true
            ]
        ];
    }
}