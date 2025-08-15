<?php

namespace App\Services\Audit\Components;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Resilience Score Calculator with LETHAL GOLDEN STANDARD Implementation
 * 
 * Advanced resilience scoring system with multi-dimensional analysis
 * Provides comprehensive scoring, benchmarking, and improvement tracking
 */
class ResilienceScoreCalculator
{
    private array $scoringWeights = [];
    private array $benchmarkThresholds = [];
    private array $scoreComponents = [];
    private array $historicalScores = [];
    private float $overallResilienceScore = 0.0;

    public function __construct()
    {
        $this->initializeScoringWeights();
        $this->initializeBenchmarkThresholds();
    }

    /**
     * Initialize scoring weights for different components
     */
    private function initializeScoringWeights(): void
    {
        $this->scoringWeights = [
            'success_rate' => 0.30,           // 30% - Most critical factor
            'recovery_time' => 0.25,          // 25% - Speed of recovery
            'graceful_degradation' => 0.20,   // 20% - System stability during failure
            'impact_severity' => 0.15,        // 15% - Severity of impact
            'cascade_prevention' => 0.10      // 10% - Prevention of cascade failures
        ];
    }

    /**
     * Initialize benchmark thresholds for scoring
     */
    private function initializeBenchmarkThresholds(): void
    {
        $this->benchmarkThresholds = [
            'success_rate' => [
                'excellent' => 100,    // 100% success rate
                'good' => 95,          // 95% success rate
                'acceptable' => 85,    // 85% success rate
                'poor' => 70,          // 70% success rate
                'critical' => 50       // 50% success rate
            ],
            'recovery_time' => [
                'excellent' => 5000,   // 5 seconds
                'good' => 15000,       // 15 seconds
                'acceptable' => 30000, // 30 seconds
                'poor' => 60000,       // 60 seconds
                'critical' => 120000   // 2 minutes
            ],
            'graceful_degradation' => [
                'excellent' => 100,    // 100% graceful degradation
                'good' => 90,          // 90% graceful degradation
                'acceptable' => 75,    // 75% graceful degradation
                'poor' => 50,          // 50% graceful degradation
                'critical' => 25       // 25% graceful degradation
            ],
            'impact_severity' => [
                'excellent' => 1.0,    // Low impact average
                'good' => 1.5,         // Low-Medium impact average
                'acceptable' => 2.0,   // Medium impact average
                'poor' => 2.5,         // Medium-High impact average
                'critical' => 3.0      // High impact average
            ],
            'cascade_prevention' => [
                'excellent' => 0,      // No cascade failures
                'good' => 10,          // 10% cascade rate
                'acceptable' => 25,    // 25% cascade rate
                'poor' => 50,          // 50% cascade rate
                'critical' => 75       // 75% cascade rate
            ]
        ];
    }

    /**
     * Calculate comprehensive resilience score
     */
    public function calculateResilienceScore(array $scenarioDocumentation, array $recoveryTimeAnalysis = [], array $failurePatternAnalysis = []): array
    {
        Log::info('📊 Starting comprehensive resilience score calculation');
        
        $startTime = microtime(true);
        
        try {
            // Extract data for scoring
            $scoringData = $this->extractScoringData($scenarioDocumentation, $recoveryTimeAnalysis, $failurePatternAnalysis);
            
            // Calculate individual component scores
            $this->calculateComponentScores($scoringData);
            
            // Calculate overall resilience score
            $this->calculateOverallScore();
            
            // Generate score analysis
            $scoreAnalysis = $this->generateScoreAnalysis();
            
            // Generate improvement recommendations
            $improvementRecommendations = $this->generateImprovementRecommendations();
            
            // Generate benchmarking results
            $benchmarkingResults = $this->generateBenchmarkingResults();
            
            $executionTime = microtime(true) - $startTime;
            
            Log::info('✅ Resilience score calculation complete', [
                'overall_score' => $this->overallResilienceScore,
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return [
                'overall_resilience_score' => $this->overallResilienceScore,
                'score_components' => $this->scoreComponents,
                'scoring_weights' => $this->scoringWeights,
                'score_analysis' => $scoreAnalysis,
                'improvement_recommendations' => $improvementRecommendations,
                'benchmarking_results' => $benchmarkingResults,
                'calculation_metadata' => [
                    'calculated_at' => now()->toISOString(),
                    'execution_time_ms' => $executionTime * 1000,
                    'scoring_method' => 'weighted_multi_dimensional'
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Resilience score calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Extract scoring data from various analysis results
     */
    private function extractScoringData(array $scenarioDocumentation, array $recoveryTimeAnalysis, array $failurePatternAnalysis): array
    {
        $documentedScenarios = $scenarioDocumentation['documented_scenarios'] ?? [];
        $totalScenarios = count($documentedScenarios);
        
        // Extract success rate data
        $successfulScenarios = 0;
        $gracefulDegradationCount = 0;
        $recoveryTimes = [];
        $impactSeverities = [];
        $cascadeFailures = 0;
        
        foreach ($documentedScenarios as $scenarioKey => $scenarioDoc) {
            $result = $scenarioDoc['simulation_result'] ?? [];
            
            // Success rate
            if ($result['success'] ?? false) {
                $successfulScenarios++;
            }
            
            // Graceful degradation
            if ($result['graceful_degradation'] ?? false) {
                $gracefulDegradationCount++;
            }
            
            // Recovery times
            $recoveryTimes[] = $result['recovery_time_ms'] ?? 0;
            
            // Impact severity
            $impactAnalysis = $scenarioDoc['impact_analysis'] ?? [];
            $severity = $impactAnalysis['severity'] ?? 'unknown';
            $impactSeverities[] = $this->convertSeverityToNumeric($severity);
            
            // Cascade failures
            $cascadeEffects = $impactAnalysis['cascade_effects'] ?? [];
            if (!empty($cascadeEffects)) {
                $cascadeFailures++;
            }
        }
        
        return [
            'total_scenarios' => $totalScenarios,
            'successful_scenarios' => $successfulScenarios,
            'graceful_degradation_count' => $gracefulDegradationCount,
            'recovery_times' => $recoveryTimes,
            'impact_severities' => $impactSeverities,
            'cascade_failures' => $cascadeFailures,
            'recovery_time_analysis' => $recoveryTimeAnalysis,
            'failure_pattern_analysis' => $failurePatternAnalysis
        ];
    }

    /**
     * Calculate individual component scores
     */
    private function calculateComponentScores(array $scoringData): void
    {
        $this->scoreComponents = [
            'success_rate' => $this->calculateSuccessRateScore($scoringData),
            'recovery_time' => $this->calculateRecoveryTimeScore($scoringData),
            'graceful_degradation' => $this->calculateGracefulDegradationScore($scoringData),
            'impact_severity' => $this->calculateImpactSeverityScore($scoringData),
            'cascade_prevention' => $this->calculateCascadePreventionScore($scoringData)
        ];
    }

    /**
     * Calculate success rate score
     */
    private function calculateSuccessRateScore(array $scoringData): array
    {
        $totalScenarios = $scoringData['total_scenarios'];
        $successfulScenarios = $scoringData['successful_scenarios'];
        
        $successRate = $totalScenarios > 0 ? ($successfulScenarios / $totalScenarios) * 100 : 0;
        $score = $this->mapValueToScore($successRate, $this->benchmarkThresholds['success_rate'], true);
        
        return [
            'raw_value' => $successRate,
            'score' => $score,
            'weight' => $this->scoringWeights['success_rate'],
            'weighted_score' => $score * $this->scoringWeights['success_rate'],
            'performance_level' => $this->getPerformanceLevel($score),
            'benchmark_comparison' => $this->getBenchmarkComparison($successRate, $this->benchmarkThresholds['success_rate'], true),
            'details' => [
                'total_scenarios' => $totalScenarios,
                'successful_scenarios' => $successfulScenarios,
                'failed_scenarios' => $totalScenarios - $successfulScenarios,
                'success_percentage' => $successRate
            ]
        ];
    }

    /**
     * Calculate recovery time score
     */
    private function calculateRecoveryTimeScore(array $scoringData): array
    {
        $recoveryTimes = $scoringData['recovery_times'];
        $averageRecoveryTime = !empty($recoveryTimes) ? array_sum($recoveryTimes) / count($recoveryTimes) : 0;
        
        $score = $this->mapValueToScore($averageRecoveryTime, $this->benchmarkThresholds['recovery_time'], false);
        
        return [
            'raw_value' => $averageRecoveryTime,
            'score' => $score,
            'weight' => $this->scoringWeights['recovery_time'],
            'weighted_score' => $score * $this->scoringWeights['recovery_time'],
            'performance_level' => $this->getPerformanceLevel($score),
            'benchmark_comparison' => $this->getBenchmarkComparison($averageRecoveryTime, $this->benchmarkThresholds['recovery_time'], false),
            'details' => [
                'average_recovery_time_ms' => $averageRecoveryTime,
                'average_recovery_time_seconds' => $averageRecoveryTime / 1000,
                'fastest_recovery_ms' => !empty($recoveryTimes) ? min($recoveryTimes) : 0,
                'slowest_recovery_ms' => !empty($recoveryTimes) ? max($recoveryTimes) : 0,
                'recovery_time_variance' => $this->calculateVariance($recoveryTimes),
                'consistency_score' => $this->calculateConsistencyScore($recoveryTimes)
            ]
        ];
    }

    /**
     * Calculate graceful degradation score
     */
    private function calculateGracefulDegradationScore(array $scoringData): array
    {
        $totalScenarios = $scoringData['total_scenarios'];
        $gracefulDegradationCount = $scoringData['graceful_degradation_count'];
        
        $gracefulDegradationRate = $totalScenarios > 0 ? ($gracefulDegradationCount / $totalScenarios) * 100 : 0;
        $score = $this->mapValueToScore($gracefulDegradationRate, $this->benchmarkThresholds['graceful_degradation'], true);
        
        return [
            'raw_value' => $gracefulDegradationRate,
            'score' => $score,
            'weight' => $this->scoringWeights['graceful_degradation'],
            'weighted_score' => $score * $this->scoringWeights['graceful_degradation'],
            'performance_level' => $this->getPerformanceLevel($score),
            'benchmark_comparison' => $this->getBenchmarkComparison($gracefulDegradationRate, $this->benchmarkThresholds['graceful_degradation'], true),
            'details' => [
                'total_scenarios' => $totalScenarios,
                'graceful_degradation_count' => $gracefulDegradationCount,
                'graceful_degradation_percentage' => $gracefulDegradationRate,
                'non_graceful_scenarios' => $totalScenarios - $gracefulDegradationCount
            ]
        ];
    }

    /**
     * Calculate impact severity score
     */
    private function calculateImpactSeverityScore(array $scoringData): array
    {
        $impactSeverities = array_filter($scoringData['impact_severities'], function($severity) {
            return $severity !== null;
        });
        
        $averageImpactSeverity = !empty($impactSeverities) ? array_sum($impactSeverities) / count($impactSeverities) : 0;
        $score = $this->mapValueToScore($averageImpactSeverity, $this->benchmarkThresholds['impact_severity'], false);
        
        return [
            'raw_value' => $averageImpactSeverity,
            'score' => $score,
            'weight' => $this->scoringWeights['impact_severity'],
            'weighted_score' => $score * $this->scoringWeights['impact_severity'],
            'performance_level' => $this->getPerformanceLevel($score),
            'benchmark_comparison' => $this->getBenchmarkComparison($averageImpactSeverity, $this->benchmarkThresholds['impact_severity'], false),
            'details' => [
                'average_impact_severity' => $averageImpactSeverity,
                'impact_distribution' => $this->calculateImpactDistribution($impactSeverities),
                'highest_impact_scenarios' => $this->identifyHighestImpactScenarios($scoringData),
                'lowest_impact_scenarios' => $this->identifyLowestImpactScenarios($scoringData)
            ]
        ];
    }

    /**
     * Calculate cascade prevention score
     */
    private function calculateCascadePreventionScore(array $scoringData): array
    {
        $totalScenarios = $scoringData['total_scenarios'];
        $cascadeFailures = $scoringData['cascade_failures'];
        
        $cascadeRate = $totalScenarios > 0 ? ($cascadeFailures / $totalScenarios) * 100 : 0;
        $score = $this->mapValueToScore($cascadeRate, $this->benchmarkThresholds['cascade_prevention'], false);
        
        return [
            'raw_value' => $cascadeRate,
            'score' => $score,
            'weight' => $this->scoringWeights['cascade_prevention'],
            'weighted_score' => $score * $this->scoringWeights['cascade_prevention'],
            'performance_level' => $this->getPerformanceLevel($score),
            'benchmark_comparison' => $this->getBenchmarkComparison($cascadeRate, $this->benchmarkThresholds['cascade_prevention'], false),
            'details' => [
                'total_scenarios' => $totalScenarios,
                'cascade_failures' => $cascadeFailures,
                'cascade_rate_percentage' => $cascadeRate,
                'scenarios_without_cascade' => $totalScenarios - $cascadeFailures,
                'cascade_prevention_rate' => 100 - $cascadeRate
            ]
        ];
    }

    /**
     * Calculate overall resilience score
     */
    private function calculateOverallScore(): void
    {
        $totalWeightedScore = 0;
        $totalWeight = 0;
        
        foreach ($this->scoreComponents as $component => $data) {
            $totalWeightedScore += $data['weighted_score'];
            $totalWeight += $data['weight'];
        }
        
        $this->overallResilienceScore = $totalWeight > 0 ? $totalWeightedScore : 0;
        
        // Ensure score is between 0 and 100
        $this->overallResilienceScore = max(0, min(100, $this->overallResilienceScore));
    }

    /**
     * Generate comprehensive score analysis
     */
    private function generateScoreAnalysis(): array
    {
        return [
            'overall_assessment' => $this->generateOverallAssessment(),
            'component_analysis' => $this->generateComponentAnalysis(),
            'strengths' => $this->identifyStrengths(),
            'weaknesses' => $this->identifyWeaknesses(),
            'score_distribution' => $this->generateScoreDistribution(),
            'performance_trends' => $this->generatePerformanceTrends(),
            'comparative_analysis' => $this->generateComparativeAnalysis()
        ];
    }

    /**
     * Generate overall assessment
     */
    private function generateOverallAssessment(): array
    {
        $score = $this->overallResilienceScore;
        
        if ($score >= 90) {
            $level = 'excellent';
            $description = 'System demonstrates exceptional resilience across all failure scenarios';
            $status = '🟢 Excellent';
        } elseif ($score >= 80) {
            $level = 'good';
            $description = 'System shows good resilience with minor areas for improvement';
            $status = '🟡 Good';
        } elseif ($score >= 70) {
            $level = 'acceptable';
            $description = 'System resilience meets basic requirements but needs improvement';
            $status = '🟠 Acceptable';
        } elseif ($score >= 60) {
            $level = 'poor';
            $description = 'System resilience is below acceptable levels and requires attention';
            $status = '🔴 Poor';
        } else {
            $level = 'critical';
            $description = 'System resilience is critically low and requires immediate action';
            $status = '🚨 Critical';
        }
        
        return [
            'score' => $score,
            'level' => $level,
            'status' => $status,
            'description' => $description,
            'confidence_level' => $this->calculateConfidenceLevel(),
            'reliability_rating' => $this->calculateReliabilityRating()
        ];
    }

    /**
     * Generate component analysis
     */
    private function generateComponentAnalysis(): array
    {
        $analysis = [];
        
        foreach ($this->scoreComponents as $component => $data) {
            $analysis[$component] = [
                'score' => $data['score'],
                'weighted_contribution' => $data['weighted_score'],
                'performance_level' => $data['performance_level'],
                'contribution_percentage' => ($data['weighted_score'] / $this->overallResilienceScore) * 100,
                'improvement_potential' => 100 - $data['score'],
                'priority_level' => $this->calculateComponentPriority($component, $data)
            ];
        }
        
        return $analysis;
    }

    /**
     * Identify system strengths
     */
    private function identifyStrengths(): array
    {
        $strengths = [];
        
        foreach ($this->scoreComponents as $component => $data) {
            if ($data['score'] >= 80) {
                $strengths[] = [
                    'component' => $component,
                    'score' => $data['score'],
                    'description' => $this->getStrengthDescription($component, $data['score'])
                ];
            }
        }
        
        return $strengths;
    }

    /**
     * Identify system weaknesses
     */
    private function identifyWeaknesses(): array
    {
        $weaknesses = [];
        
        foreach ($this->scoreComponents as $component => $data) {
            if ($data['score'] < 70) {
                $weaknesses[] = [
                    'component' => $component,
                    'score' => $data['score'],
                    'severity' => $this->getWeaknessSeverity($data['score']),
                    'description' => $this->getWeaknessDescription($component, $data['score']),
                    'improvement_needed' => 70 - $data['score']
                ];
            }
        }
        
        return $weaknesses;
    }

    /**
     * Generate score distribution
     */
    private function generateScoreDistribution(): array
    {
        $distribution = [
            'excellent' => 0,
            'good' => 0,
            'acceptable' => 0,
            'poor' => 0,
            'critical' => 0
        ];
        
        foreach ($this->scoreComponents as $data) {
            $level = $this->getPerformanceLevel($data['score']);
            $distribution[$level]++;
        }
        
        return [
            'component_distribution' => $distribution,
            'distribution_percentages' => $this->calculateDistributionPercentages($distribution),
            'balance_score' => $this->calculateBalanceScore($distribution)
        ];
    }

    /**
     * Generate improvement recommendations
     */
    private function generateImprovementRecommendations(): array
    {
        $recommendations = [
            'immediate_actions' => [],
            'short_term_improvements' => [],
            'long_term_strategies' => [],
            'priority_matrix' => []
        ];
        
        // Analyze each component for improvement opportunities
        foreach ($this->scoreComponents as $component => $data) {
            $componentRecommendations = $this->generateComponentRecommendations($component, $data);
            
            if ($data['score'] < 60) {
                $recommendations['immediate_actions'] = array_merge(
                    $recommendations['immediate_actions'], 
                    $componentRecommendations['immediate']
                );
            } elseif ($data['score'] < 80) {
                $recommendations['short_term_improvements'] = array_merge(
                    $recommendations['short_term_improvements'], 
                    $componentRecommendations['short_term']
                );
            } else {
                $recommendations['long_term_strategies'] = array_merge(
                    $recommendations['long_term_strategies'], 
                    $componentRecommendations['long_term']
                );
            }
        }
        
        // Generate priority matrix
        $recommendations['priority_matrix'] = $this->generatePriorityMatrix();
        
        return $recommendations;
    }

    /**
     * Generate benchmarking results
     */
    private function generateBenchmarkingResults(): array
    {
        return [
            'industry_comparison' => $this->generateIndustryComparison(),
            'best_practices_alignment' => $this->generateBestPracticesAlignment(),
            'maturity_assessment' => $this->generateMaturityAssessment(),
            'competitive_positioning' => $this->generateCompetitivePositioning()
        ];
    }

    // Helper methods
    private function convertSeverityToNumeric(string $severity): ?float
    {
        switch (strtolower($severity)) {
            case 'low':
                return 1.0;
            case 'medium':
                return 2.0;
            case 'high':
                return 3.0;
            case 'critical':
                return 4.0;
            default:
                return null;
        }
    }

    private function mapValueToScore(float $value, array $thresholds, bool $higherIsBetter): float
    {
        if ($higherIsBetter) {
            if ($value >= $thresholds['excellent']) return 100;
            if ($value >= $thresholds['good']) return 85;
            if ($value >= $thresholds['acceptable']) return 70;
            if ($value >= $thresholds['poor']) return 50;
            return 25;
        } else {
            if ($value <= $thresholds['excellent']) return 100;
            if ($value <= $thresholds['good']) return 85;
            if ($value <= $thresholds['acceptable']) return 70;
            if ($value <= $thresholds['poor']) return 50;
            return 25;
        }
    }

    private function getPerformanceLevel(float $score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 80) return 'good';
        if ($score >= 70) return 'acceptable';
        if ($score >= 60) return 'poor';
        return 'critical';
    }

    private function getBenchmarkComparison(float $value, array $thresholds, bool $higherIsBetter): array
    {
        $comparison = [];
        
        foreach ($thresholds as $level => $threshold) {
            if ($higherIsBetter) {
                $comparison[$level] = [
                    'threshold' => $threshold,
                    'meets_threshold' => $value >= $threshold,
                    'gap' => $threshold - $value
                ];
            } else {
                $comparison[$level] = [
                    'threshold' => $threshold,
                    'meets_threshold' => $value <= $threshold,
                    'gap' => $value - $threshold
                ];
            }
        }
        
        return $comparison;
    }

    private function calculateVariance(array $values): float
    {
        if (empty($values)) return 0;
        
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);
        
        return $variance;
    }

    private function calculateConsistencyScore(array $values): float
    {
        if (empty($values)) return 0;
        
        $mean = array_sum($values) / count($values);
        $variance = $this->calculateVariance($values);
        $coefficientOfVariation = $mean > 0 ? sqrt($variance) / $mean : 0;
        
        return max(0, 100 - ($coefficientOfVariation * 100));
    }

    private function calculateImpactDistribution(array $impactSeverities): array
    {
        $distribution = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
        
        foreach ($impactSeverities as $severity) {
            if ($severity <= 1.5) $distribution['low']++;
            elseif ($severity <= 2.5) $distribution['medium']++;
            elseif ($severity <= 3.5) $distribution['high']++;
            else $distribution['critical']++;
        }
        
        return $distribution;
    }

    private function identifyHighestImpactScenarios(array $scoringData): array
    {
        // This would identify scenarios with highest impact in a real implementation
        return ['cascade_failure', 'database_lock'];
    }

    private function identifyLowestImpactScenarios(array $scoringData): array
    {
        // This would identify scenarios with lowest impact in a real implementation
        return ['memory_breach', 'disk_exhaustion'];
    }

    private function calculateConfidenceLevel(): float
    {
        // Calculate confidence based on data completeness and consistency
        return 85.0; // Placeholder
    }

    private function calculateReliabilityRating(): string
    {
        $score = $this->overallResilienceScore;
        
        if ($score >= 95) return 'A+';
        if ($score >= 90) return 'A';
        if ($score >= 85) return 'A-';
        if ($score >= 80) return 'B+';
        if ($score >= 75) return 'B';
        if ($score >= 70) return 'B-';
        if ($score >= 65) return 'C+';
        if ($score >= 60) return 'C';
        return 'D';
    }

    private function calculateComponentPriority(string $component, array $data): string
    {
        $score = $data['score'];
        $weight = $data['weight'];
        
        // Higher weight and lower score = higher priority
        $priorityScore = (1 - ($score / 100)) * $weight;
        
        if ($priorityScore > 0.15) return 'high';
        if ($priorityScore > 0.10) return 'medium';
        return 'low';
    }

    private function getStrengthDescription(string $component, float $score): string
    {
        $descriptions = [
            'success_rate' => 'Excellent recovery success rate across all scenarios',
            'recovery_time' => 'Fast recovery times meeting performance targets',
            'graceful_degradation' => 'System maintains stability during failures',
            'impact_severity' => 'Low impact severity across failure scenarios',
            'cascade_prevention' => 'Effective prevention of cascade failures'
        ];
        
        return $descriptions[$component] ?? 'Strong performance in this area';
    }

    private function getWeaknessDescription(string $component, float $score): string
    {
        $descriptions = [
            'success_rate' => 'Recovery success rate needs improvement',
            'recovery_time' => 'Recovery times exceed acceptable thresholds',
            'graceful_degradation' => 'System stability during failures needs enhancement',
            'impact_severity' => 'High impact severity requires mitigation',
            'cascade_prevention' => 'Cascade failure prevention needs strengthening'
        ];
        
        return $descriptions[$component] ?? 'Performance in this area needs improvement';
    }

    private function getWeaknessSeverity(float $score): string
    {
        if ($score < 40) return 'critical';
        if ($score < 60) return 'high';
        return 'medium';
    }

    private function calculateDistributionPercentages(array $distribution): array
    {
        $total = array_sum($distribution);
        $percentages = [];
        
        foreach ($distribution as $level => $count) {
            $percentages[$level] = $total > 0 ? ($count / $total) * 100 : 0;
        }
        
        return $percentages;
    }

    private function calculateBalanceScore(array $distribution): float
    {
        // Calculate how balanced the performance is across components
        $total = array_sum($distribution);
        if ($total === 0) return 0;
        
        $expectedPerComponent = $total / count($distribution);
        $variance = 0;
        
        foreach ($distribution as $count) {
            $variance += pow($count - $expectedPerComponent, 2);
        }
        
        $variance /= count($distribution);
        $coefficientOfVariation = $expectedPerComponent > 0 ? sqrt($variance) / $expectedPerComponent : 0;
        
        return max(0, 100 - ($coefficientOfVariation * 100));
    }

    private function generateComponentRecommendations(string $component, array $data): array
    {
        $recommendations = [
            'immediate' => [],
            'short_term' => [],
            'long_term' => []
        ];
        
        switch ($component) {
            case 'success_rate':
                if ($data['score'] < 60) {
                    $recommendations['immediate'][] = 'Implement comprehensive error handling for all failure scenarios';
                    $recommendations['immediate'][] = 'Add automated recovery mechanisms for critical failures';
                }
                $recommendations['short_term'][] = 'Enhance monitoring and alerting for early failure detection';
                $recommendations['long_term'][] = 'Develop predictive failure prevention systems';
                break;
                
            case 'recovery_time':
                if ($data['score'] < 60) {
                    $recommendations['immediate'][] = 'Optimize recovery procedures for slowest scenarios';
                    $recommendations['immediate'][] = 'Implement parallel recovery processes where possible';
                }
                $recommendations['short_term'][] = 'Automate manual recovery steps';
                $recommendations['long_term'][] = 'Implement self-healing system capabilities';
                break;
                
            case 'graceful_degradation':
                if ($data['score'] < 60) {
                    $recommendations['immediate'][] = 'Implement circuit breakers for critical services';
                    $recommendations['immediate'][] = 'Add fallback mechanisms for essential functions';
                }
                $recommendations['short_term'][] = 'Design progressive degradation strategies';
                $recommendations['long_term'][] = 'Implement adaptive system behavior based on load';
                break;
                
            case 'impact_severity':
                if ($data['score'] < 60) {
                    $recommendations['immediate'][] = 'Implement impact isolation mechanisms';
                    $recommendations['immediate'][] = 'Add service boundaries to limit failure scope';
                }
                $recommendations['short_term'][] = 'Enhance system redundancy';
                $recommendations['long_term'][] = 'Design microservices architecture for better isolation';
                break;
                
            case 'cascade_prevention':
                if ($data['score'] < 60) {
                    $recommendations['immediate'][] = 'Implement service isolation patterns';
                    $recommendations['immediate'][] = 'Add timeout and retry mechanisms';
                }
                $recommendations['short_term'][] = 'Implement bulkhead patterns';
                $recommendations['long_term'][] = 'Design chaos engineering practices';
                break;
        }
        
        return $recommendations;
    }

    private function generatePriorityMatrix(): array
    {
        $matrix = [];
        
        foreach ($this->scoreComponents as $component => $data) {
            $impact = $data['weight'] * 100; // Convert weight to impact score
            $effort = $this->estimateImprovementEffort($component, $data['score']);
            
            $matrix[] = [
                'component' => $component,
                'impact' => $impact,
                'effort' => $effort,
                'priority_score' => $effort > 0 ? $impact / $effort : 0,
                'quadrant' => $this->getQuadrant($impact, $effort)
            ];
        }
        
        // Sort by priority score
        usort($matrix, function($a, $b) {
            return $b['priority_score'] <=> $a['priority_score'];
        });
        
        return $matrix;
    }

    private function estimateImprovementEffort(string $component, float $score): float
    {
        // Estimate effort based on current score (lower score = higher effort)
        $baseEffort = 100 - $score;
        
        // Component-specific effort multipliers
        $multipliers = [
            'success_rate' => 1.2,
            'recovery_time' => 1.0,
            'graceful_degradation' => 1.5,
            'impact_severity' => 1.3,
            'cascade_prevention' => 1.8
        ];
        
        return $baseEffort * ($multipliers[$component] ?? 1.0);
    }

    private function getQuadrant(float $impact, float $effort): string
    {
        if ($impact > 50 && $effort < 50) return 'quick_wins';
        if ($impact > 50 && $effort > 50) return 'major_projects';
        if ($impact < 50 && $effort < 50) return 'fill_ins';
        return 'thankless_tasks';
    }

    private function generatePerformanceTrends(): array
    {
        // Placeholder for trend analysis
        return [
            'trend_direction' => 'stable',
            'improvement_rate' => 0,
            'projected_score' => $this->overallResilienceScore
        ];
    }

    private function generateComparativeAnalysis(): array
    {
        return [
            'component_ranking' => $this->rankComponents(),
            'performance_gaps' => $this->identifyPerformanceGaps(),
            'improvement_potential' => $this->calculateImprovementPotential()
        ];
    }

    private function rankComponents(): array
    {
        $components = $this->scoreComponents;
        
        uasort($components, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return array_keys($components);
    }

    private function identifyPerformanceGaps(): array
    {
        $gaps = [];
        $maxScore = max(array_column($this->scoreComponents, 'score'));
        
        foreach ($this->scoreComponents as $component => $data) {
            $gap = $maxScore - $data['score'];
            if ($gap > 10) {
                $gaps[$component] = $gap;
            }
        }
        
        return $gaps;
    }

    private function calculateImprovementPotential(): float
    {
        $currentScore = $this->overallResilienceScore;
        $maxPossibleScore = 100;
        
        return $maxPossibleScore - $currentScore;
    }

    private function generateIndustryComparison(): array
    {
        // Placeholder for industry benchmarking
        return [
            'industry_average' => 75.0,
            'top_quartile' => 85.0,
            'best_in_class' => 95.0,
            'position' => $this->overallResilienceScore > 85 ? 'top_quartile' : 'average'
        ];
    }

    private function generateBestPracticesAlignment(): array
    {
        return [
            'alignment_score' => min(100, $this->overallResilienceScore + 5),
            'practices_implemented' => $this->identifyImplementedPractices(),
            'practices_missing' => $this->identifyMissingPractices()
        ];
    }

    private function generateMaturityAssessment(): array
    {
        $score = $this->overallResilienceScore;
        
        if ($score >= 90) $maturity = 'optimized';
        elseif ($score >= 80) $maturity = 'managed';
        elseif ($score >= 70) $maturity = 'defined';
        elseif ($score >= 60) $maturity = 'repeatable';
        else $maturity = 'initial';
        
        return [
            'maturity_level' => $maturity,
            'maturity_score' => $score,
            'next_level_requirements' => $this->getNextLevelRequirements($maturity)
        ];
    }

    private function generateCompetitivePositioning(): array
    {
        return [
            'competitive_advantage' => $this->overallResilienceScore > 80 ? 'strong' : 'moderate',
            'differentiation_factors' => $this->identifyDifferentiationFactors(),
            'improvement_priorities' => $this->getCompetitiveImprovementPriorities()
        ];
    }

    private function identifyImplementedPractices(): array
    {
        $practices = [];
        
        foreach ($this->scoreComponents as $component => $data) {
            if ($data['score'] >= 70) {
                $practices[] = $component . '_best_practices';
            }
        }
        
        return $practices;
    }

    private function identifyMissingPractices(): array
    {
        $practices = [];
        
        foreach ($this->scoreComponents as $component => $data) {
            if ($data['score'] < 70) {
                $practices[] = $component . '_improvements_needed';
            }
        }
        
        return $practices;
    }

    private function getNextLevelRequirements(string $currentLevel): array
    {
        $requirements = [
            'initial' => ['Establish basic recovery procedures', 'Implement monitoring'],
            'repeatable' => ['Standardize recovery processes', 'Add automation'],
            'defined' => ['Optimize recovery times', 'Enhance graceful degradation'],
            'managed' => ['Implement predictive capabilities', 'Add self-healing'],
            'optimized' => ['Continuous improvement', 'Innovation in resilience']
        ];
        
        return $requirements[$currentLevel] ?? [];
    }

    private function identifyDifferentiationFactors(): array
    {
        $factors = [];
        
        foreach ($this->scoreComponents as $component => $data) {
            if ($data['score'] >= 85) {
                $factors[] = 'excellent_' . $component;
            }
        }
        
        return $factors;
    }

    private function getCompetitiveImprovementPriorities(): array
    {
        $priorities = [];
        
        foreach ($this->scoreComponents as $component => $data) {
            if ($data['score'] < 80) {
                $priorities[] = [
                    'component' => $component,
                    'current_score' => $data['score'],
                    'target_score' => 85,
                    'competitive_impact' => 'high'
                ];
            }
        }
        
        return $priorities;
    }
}