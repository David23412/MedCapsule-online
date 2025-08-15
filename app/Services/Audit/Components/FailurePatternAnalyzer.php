<?php

namespace App\Services\Audit\Components;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Failure Pattern Analyzer with LETHAL GOLDEN STANDARD Implementation
 * 
 * Advanced pattern recognition and analysis for failure scenarios
 * Identifies trends, correlations, and predictive patterns
 */
class FailurePatternAnalyzer
{
    private array $patternData = [];
    private array $correlationMatrix = [];
    private array $trendAnalysis = [];
    private array $predictivePatterns = [];
    private array $riskFactors = [];

    /**
     * Analyze failure patterns from scenario documentation
     */
    public function analyzeFailurePatterns(array $scenarioDocumentation): array
    {
        Log::info('🔍 Starting comprehensive failure pattern analysis');
        
        $startTime = microtime(true);
        
        try {
            // Extract pattern data
            $this->extractPatternData($scenarioDocumentation);
            
            // Analyze failure correlations
            $this->analyzeFailureCorrelations();
            
            // Identify trend patterns
            $this->identifyTrendPatterns();
            
            // Analyze risk factors
            $this->analyzeRiskFactors();
            
            // Generate predictive patterns
            $this->generatePredictivePatterns();
            
            // Generate pattern insights
            $patternInsights = $this->generatePatternInsights();
            
            $executionTime = microtime(true) - $startTime;
            
            Log::info('✅ Failure pattern analysis complete', [
                'patterns_analyzed' => count($this->patternData),
                'correlations_found' => count($this->correlationMatrix),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return [
                'pattern_data' => $this->patternData,
                'correlation_matrix' => $this->correlationMatrix,
                'trend_analysis' => $this->trendAnalysis,
                'predictive_patterns' => $this->predictivePatterns,
                'risk_factors' => $this->riskFactors,
                'pattern_insights' => $patternInsights,
                'analysis_metadata' => [
                    'analyzed_at' => now()->toISOString(),
                    'execution_time_ms' => $executionTime * 1000,
                    'patterns_count' => count($this->patternData)
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Failure pattern analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Extract pattern data from scenario documentation
     */
    private function extractPatternData(array $scenarioDocumentation): void
    {
        $documentedScenarios = $scenarioDocumentation['documented_scenarios'] ?? [];
        
        foreach ($documentedScenarios as $scenarioKey => $scenarioDoc) {
            $this->patternData[$scenarioKey] = [
                'scenario' => $scenarioKey,
                'success' => $scenarioDoc['simulation_result']['success'] ?? false,
                'recovery_time_ms' => $scenarioDoc['simulation_result']['recovery_time_ms'] ?? 0,
                'graceful_degradation' => $scenarioDoc['simulation_result']['graceful_degradation'] ?? false,
                'services_affected' => $scenarioDoc['simulation_result']['services_affected'] ?? 0,
                'memory_usage' => $scenarioDoc['system_state']['memory_usage'] ?? 0,
                'memory_peak' => $scenarioDoc['system_state']['memory_peak'] ?? 0,
                'disk_free_space' => $scenarioDoc['system_state']['disk_free_space'] ?? 0,
                'recovery_steps' => count($scenarioDoc['recovery_documentation']['recovery_steps'] ?? []),
                'impact_severity' => $scenarioDoc['impact_analysis']['severity'] ?? 'unknown',
                'user_impact' => $scenarioDoc['impact_analysis']['user_impact'] ?? 'unknown',
                'business_impact' => $scenarioDoc['impact_analysis']['business_impact'] ?? 'unknown',
                'technical_impact' => $scenarioDoc['impact_analysis']['technical_impact'] ?? 'unknown',
                'affected_components' => $scenarioDoc['impact_analysis']['affected_components'] ?? [],
                'cascade_effects' => $scenarioDoc['impact_analysis']['cascade_effects'] ?? [],
                'timestamp' => $scenarioDoc['timestamp'] ?? now()->toISOString()
            ];
        }
    }

    /**
     * Analyze correlations between different failure factors
     */
    private function analyzeFailureCorrelations(): void
    {
        $correlations = [];
        
        // Recovery time correlations
        $correlations['recovery_time'] = [
            'vs_services_affected' => $this->calculateCorrelation('recovery_time_ms', 'services_affected'),
            'vs_memory_usage' => $this->calculateCorrelation('recovery_time_ms', 'memory_usage'),
            'vs_recovery_steps' => $this->calculateCorrelation('recovery_time_ms', 'recovery_steps'),
            'vs_cascade_effects' => $this->calculateCorrelation('recovery_time_ms', 'cascade_effects_count')
        ];
        
        // Success rate correlations
        $correlations['success_rate'] = [
            'vs_graceful_degradation' => $this->calculateSuccessCorrelation('graceful_degradation'),
            'vs_impact_severity' => $this->calculateSuccessCorrelation('impact_severity'),
            'vs_services_affected' => $this->calculateCorrelation('success_numeric', 'services_affected')
        ];
        
        // Impact correlations
        $correlations['impact'] = [
            'severity_vs_recovery_time' => $this->calculateImpactCorrelation('impact_severity', 'recovery_time_ms'),
            'user_impact_vs_business_impact' => $this->calculateImpactCorrelation('user_impact', 'business_impact'),
            'technical_impact_vs_services_affected' => $this->calculateImpactCorrelation('technical_impact', 'services_affected')
        ];
        
        // System resource correlations
        $correlations['system_resources'] = [
            'memory_vs_recovery_time' => $this->calculateCorrelation('memory_usage', 'recovery_time_ms'),
            'disk_vs_recovery_time' => $this->calculateCorrelation('disk_free_space', 'recovery_time_ms'),
            'memory_peak_vs_success' => $this->calculateCorrelation('memory_peak', 'success_numeric')
        ];
        
        $this->correlationMatrix = $correlations;
    }

    /**
     * Calculate correlation between two numeric variables
     */
    private function calculateCorrelation(string $var1, string $var2): array
    {
        $values1 = [];
        $values2 = [];
        
        foreach ($this->patternData as $data) {
            $val1 = $this->extractNumericValue($data, $var1);
            $val2 = $this->extractNumericValue($data, $var2);
            
            if ($val1 !== null && $val2 !== null) {
                $values1[] = $val1;
                $values2[] = $val2;
            }
        }
        
        if (count($values1) < 2) {
            return ['correlation' => 0, 'strength' => 'insufficient_data', 'significance' => 'none'];
        }
        
        $correlation = $this->pearsonCorrelation($values1, $values2);
        
        return [
            'correlation' => $correlation,
            'strength' => $this->interpretCorrelationStrength($correlation),
            'significance' => $this->assessCorrelationSignificance($correlation, count($values1)),
            'sample_size' => count($values1)
        ];
    }

    /**
     * Calculate correlation for success-related variables
     */
    private function calculateSuccessCorrelation(string $variable): array
    {
        $successValues = [];
        $variableValues = [];
        
        foreach ($this->patternData as $data) {
            $success = $data['success'] ? 1 : 0;
            $varValue = $this->extractNumericValue($data, $variable);
            
            if ($varValue !== null) {
                $successValues[] = $success;
                $variableValues[] = $varValue;
            }
        }
        
        if (count($successValues) < 2) {
            return ['correlation' => 0, 'strength' => 'insufficient_data'];
        }
        
        $correlation = $this->pearsonCorrelation($successValues, $variableValues);
        
        return [
            'correlation' => $correlation,
            'strength' => $this->interpretCorrelationStrength($correlation),
            'sample_size' => count($successValues)
        ];
    }

    /**
     * Calculate correlation for impact-related variables
     */
    private function calculateImpactCorrelation(string $var1, string $var2): array
    {
        $values1 = [];
        $values2 = [];
        
        foreach ($this->patternData as $data) {
            $val1 = $this->convertImpactToNumeric($data[$var1] ?? 'unknown');
            $val2 = $this->extractNumericValue($data, $var2);
            
            if ($val1 !== null && $val2 !== null) {
                $values1[] = $val1;
                $values2[] = $val2;
            }
        }
        
        if (count($values1) < 2) {
            return ['correlation' => 0, 'strength' => 'insufficient_data'];
        }
        
        $correlation = $this->pearsonCorrelation($values1, $values2);
        
        return [
            'correlation' => $correlation,
            'strength' => $this->interpretCorrelationStrength($correlation),
            'sample_size' => count($values1)
        ];
    }

    /**
     * Extract numeric value from pattern data
     */
    private function extractNumericValue(array $data, string $variable): ?float
    {
        switch ($variable) {
            case 'success_numeric':
                return $data['success'] ? 1.0 : 0.0;
            case 'cascade_effects_count':
                return (float) count($data['cascade_effects']);
            case 'graceful_degradation':
                return $data['graceful_degradation'] ? 1.0 : 0.0;
            default:
                return isset($data[$variable]) ? (float) $data[$variable] : null;
        }
    }

    /**
     * Convert impact level to numeric value
     */
    private function convertImpactToNumeric(string $impact): ?float
    {
        switch (strtolower($impact)) {
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

    /**
     * Calculate Pearson correlation coefficient
     */
    private function pearsonCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n !== count($y) || $n < 2) {
            return 0.0;
        }
        
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }
        
        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt((($n * $sumX2) - ($sumX * $sumX)) * (($n * $sumY2) - ($sumY * $sumY)));
        
        return $denominator != 0 ? $numerator / $denominator : 0.0;
    }

    /**
     * Interpret correlation strength
     */
    private function interpretCorrelationStrength(float $correlation): string
    {
        $abs = abs($correlation);
        
        if ($abs >= 0.8) {
            return 'very_strong';
        } elseif ($abs >= 0.6) {
            return 'strong';
        } elseif ($abs >= 0.4) {
            return 'moderate';
        } elseif ($abs >= 0.2) {
            return 'weak';
        } else {
            return 'very_weak';
        }
    }

    /**
     * Assess correlation significance
     */
    private function assessCorrelationSignificance(float $correlation, int $sampleSize): string
    {
        $abs = abs($correlation);
        
        // Simple significance assessment based on correlation strength and sample size
        if ($sampleSize < 3) {
            return 'insufficient_sample';
        } elseif ($abs >= 0.6 && $sampleSize >= 5) {
            return 'significant';
        } elseif ($abs >= 0.4 && $sampleSize >= 4) {
            return 'moderate_significance';
        } else {
            return 'not_significant';
        }
    }

    /**
     * Identify trend patterns across scenarios
     */
    private function identifyTrendPatterns(): void
    {
        $trends = [
            'recovery_time_trends' => $this->analyzeRecoveryTimeTrends(),
            'success_rate_trends' => $this->analyzeSuccessRateTrends(),
            'impact_severity_trends' => $this->analyzeImpactSeverityTrends(),
            'resource_usage_trends' => $this->analyzeResourceUsageTrends(),
            'cascade_effect_trends' => $this->analyzeCascadeEffectTrends()
        ];
        
        $this->trendAnalysis = $trends;
    }

    /**
     * Analyze recovery time trends
     */
    private function analyzeRecoveryTimeTrends(): array
    {
        $recoveryTimes = [];
        $scenarios = [];
        
        foreach ($this->patternData as $scenario => $data) {
            $recoveryTimes[] = $data['recovery_time_ms'];
            $scenarios[] = $scenario;
        }
        
        return [
            'average_recovery_time' => array_sum($recoveryTimes) / count($recoveryTimes),
            'fastest_scenario' => $scenarios[array_search(min($recoveryTimes), $recoveryTimes)],
            'slowest_scenario' => $scenarios[array_search(max($recoveryTimes), $recoveryTimes)],
            'recovery_time_variance' => $this->calculateVariance($recoveryTimes),
            'consistency_score' => $this->calculateConsistencyScore($recoveryTimes),
            'performance_distribution' => $this->analyzePerformanceDistribution($recoveryTimes, $scenarios)
        ];
    }

    /**
     * Analyze success rate trends
     */
    private function analyzeSuccessRateTrends(): array
    {
        $successCount = 0;
        $totalCount = count($this->patternData);
        $successfulScenarios = [];
        $failedScenarios = [];
        
        foreach ($this->patternData as $scenario => $data) {
            if ($data['success']) {
                $successCount++;
                $successfulScenarios[] = $scenario;
            } else {
                $failedScenarios[] = $scenario;
            }
        }
        
        return [
            'overall_success_rate' => $totalCount > 0 ? ($successCount / $totalCount) * 100 : 0,
            'successful_scenarios' => $successfulScenarios,
            'failed_scenarios' => $failedScenarios,
            'success_consistency' => count($failedScenarios) === 0 ? 'perfect' : 'variable',
            'reliability_score' => $this->calculateReliabilityScore($successCount, $totalCount)
        ];
    }

    /**
     * Analyze impact severity trends
     */
    private function analyzeImpactSeverityTrends(): array
    {
        $severityDistribution = [];
        $severityByScenario = [];
        
        foreach ($this->patternData as $scenario => $data) {
            $severity = $data['impact_severity'];
            $severityDistribution[$severity] = ($severityDistribution[$severity] ?? 0) + 1;
            $severityByScenario[$scenario] = $severity;
        }
        
        return [
            'severity_distribution' => $severityDistribution,
            'most_common_severity' => array_search(max($severityDistribution), $severityDistribution),
            'severity_by_scenario' => $severityByScenario,
            'high_impact_scenarios' => $this->identifyHighImpactScenarios(),
            'severity_consistency' => $this->calculateSeverityConsistency($severityDistribution)
        ];
    }

    /**
     * Analyze resource usage trends
     */
    private function analyzeResourceUsageTrends(): array
    {
        $memoryUsages = [];
        $diskUsages = [];
        
        foreach ($this->patternData as $data) {
            $memoryUsages[] = $data['memory_usage'];
            $diskUsages[] = $data['disk_free_space'];
        }
        
        return [
            'memory_usage_stats' => [
                'average' => array_sum($memoryUsages) / count($memoryUsages),
                'max' => max($memoryUsages),
                'min' => min($memoryUsages),
                'variance' => $this->calculateVariance($memoryUsages)
            ],
            'disk_usage_stats' => [
                'average_free' => array_sum($diskUsages) / count($diskUsages),
                'max_free' => max($diskUsages),
                'min_free' => min($diskUsages),
                'variance' => $this->calculateVariance($diskUsages)
            ],
            'resource_correlation' => $this->pearsonCorrelation($memoryUsages, $diskUsages)
        ];
    }

    /**
     * Analyze cascade effect trends
     */
    private function analyzeCascadeEffectTrends(): array
    {
        $cascadeScenarios = [];
        $noCascadeScenarios = [];
        $cascadeEffectCounts = [];
        
        foreach ($this->patternData as $scenario => $data) {
            $cascadeCount = count($data['cascade_effects']);
            $cascadeEffectCounts[] = $cascadeCount;
            
            if ($cascadeCount > 0) {
                $cascadeScenarios[] = $scenario;
            } else {
                $noCascadeScenarios[] = $scenario;
            }
        }
        
        return [
            'cascade_scenarios' => $cascadeScenarios,
            'no_cascade_scenarios' => $noCascadeScenarios,
            'cascade_rate' => count($this->patternData) > 0 ? (count($cascadeScenarios) / count($this->patternData)) * 100 : 0,
            'average_cascade_effects' => array_sum($cascadeEffectCounts) / count($cascadeEffectCounts),
            'cascade_impact_correlation' => $this->analyzeCascadeImpactCorrelation()
        ];
    }

    /**
     * Generate predictive patterns
     */
    private function generatePredictivePatterns(): void
    {
        $patterns = [
            'failure_predictors' => $this->identifyFailurePredictors(),
            'recovery_time_predictors' => $this->identifyRecoveryTimePredictors(),
            'cascade_predictors' => $this->identifyCascadePredictors(),
            'risk_indicators' => $this->identifyRiskIndicators()
        ];
        
        $this->predictivePatterns = $patterns;
    }

    /**
     * Identify failure predictors
     */
    private function identifyFailurePredictors(): array
    {
        $predictors = [];
        
        // Analyze which factors correlate with failure
        foreach ($this->correlationMatrix['success_rate'] as $factor => $correlation) {
            if (abs($correlation['correlation']) > 0.3) {
                $predictors[$factor] = [
                    'correlation' => $correlation['correlation'],
                    'strength' => $correlation['strength'],
                    'predictive_power' => $this->calculatePredictivePower($correlation['correlation'])
                ];
            }
        }
        
        return $predictors;
    }

    /**
     * Identify recovery time predictors
     */
    private function identifyRecoveryTimePredictors(): array
    {
        $predictors = [];
        
        foreach ($this->correlationMatrix['recovery_time'] as $factor => $correlation) {
            if (abs($correlation['correlation']) > 0.3) {
                $predictors[$factor] = [
                    'correlation' => $correlation['correlation'],
                    'strength' => $correlation['strength'],
                    'predictive_power' => $this->calculatePredictivePower($correlation['correlation'])
                ];
            }
        }
        
        return $predictors;
    }

    /**
     * Identify cascade predictors
     */
    private function identifyCascadePredictors(): array
    {
        $cascadeFactors = [];
        
        foreach ($this->patternData as $scenario => $data) {
            if (count($data['cascade_effects']) > 0) {
                $cascadeFactors[] = [
                    'scenario' => $scenario,
                    'services_affected' => $data['services_affected'],
                    'recovery_time' => $data['recovery_time_ms'],
                    'impact_severity' => $data['impact_severity']
                ];
            }
        }
        
        return [
            'cascade_scenarios' => $cascadeFactors,
            'common_cascade_triggers' => $this->identifyCommonCascadeTriggers($cascadeFactors),
            'cascade_prevention_factors' => $this->identifyCascadePreventionFactors()
        ];
    }

    /**
     * Identify risk indicators
     */
    private function identifyRiskIndicators(): array
    {
        return [
            'high_risk_scenarios' => $this->identifyHighRiskScenarios(),
            'resource_risk_thresholds' => $this->identifyResourceRiskThresholds(),
            'early_warning_indicators' => $this->identifyEarlyWarningIndicators(),
            'compound_risk_factors' => $this->identifyCompoundRiskFactors()
        ];
    }

    /**
     * Analyze risk factors
     */
    private function analyzeRiskFactors(): void
    {
        $riskFactors = [
            'scenario_risk_levels' => $this->calculateScenarioRiskLevels(),
            'system_vulnerability_factors' => $this->identifySystemVulnerabilityFactors(),
            'environmental_risk_factors' => $this->identifyEnvironmentalRiskFactors(),
            'operational_risk_factors' => $this->identifyOperationalRiskFactors()
        ];
        
        $this->riskFactors = $riskFactors;
    }

    /**
     * Calculate risk levels for each scenario
     */
    private function calculateScenarioRiskLevels(): array
    {
        $riskLevels = [];
        
        foreach ($this->patternData as $scenario => $data) {
            $riskScore = 0;
            
            // Recovery time risk
            if ($data['recovery_time_ms'] > 30000) $riskScore += 3;
            elseif ($data['recovery_time_ms'] > 10000) $riskScore += 2;
            elseif ($data['recovery_time_ms'] > 5000) $riskScore += 1;
            
            // Success risk
            if (!$data['success']) $riskScore += 4;
            
            // Impact risk
            switch ($data['impact_severity']) {
                case 'critical': $riskScore += 4; break;
                case 'high': $riskScore += 3; break;
                case 'medium': $riskScore += 2; break;
                case 'low': $riskScore += 1; break;
            }
            
            // Cascade risk
            if (count($data['cascade_effects']) > 0) $riskScore += 2;
            
            // Services affected risk
            if ($data['services_affected'] > 3) $riskScore += 2;
            elseif ($data['services_affected'] > 1) $riskScore += 1;
            
            $riskLevels[$scenario] = [
                'risk_score' => $riskScore,
                'risk_level' => $this->categorizeRiskLevel($riskScore),
                'risk_factors' => $this->identifyScenarioRiskFactors($data)
            ];
        }
        
        return $riskLevels;
    }

    /**
     * Generate comprehensive pattern insights
     */
    private function generatePatternInsights(): array
    {
        return [
            'key_findings' => $this->generateKeyFindings(),
            'critical_patterns' => $this->identifyCriticalPatterns(),
            'improvement_opportunities' => $this->identifyImprovementOpportunities(),
            'pattern_recommendations' => $this->generatePatternRecommendations(),
            'monitoring_recommendations' => $this->generateMonitoringRecommendations()
        ];
    }

    /**
     * Generate key findings from pattern analysis
     */
    private function generateKeyFindings(): array
    {
        $findings = [];
        
        // Recovery time findings
        $avgRecoveryTime = $this->trendAnalysis['recovery_time_trends']['average_recovery_time'];
        if ($avgRecoveryTime > 30000) {
            $findings[] = "Average recovery time ({$avgRecoveryTime}ms) exceeds acceptable threshold";
        }
        
        // Success rate findings
        $successRate = $this->trendAnalysis['success_rate_trends']['overall_success_rate'];
        if ($successRate < 100) {
            $findings[] = "System reliability at {$successRate}% - improvement needed";
        }
        
        // Correlation findings
        foreach ($this->correlationMatrix as $category => $correlations) {
            foreach ($correlations as $factor => $correlation) {
                if ($correlation['strength'] === 'strong' || $correlation['strength'] === 'very_strong') {
                    $findings[] = "Strong correlation found: {$category} vs {$factor} ({$correlation['correlation']})";
                }
            }
        }
        
        return $findings;
    }

    /**
     * Identify critical patterns requiring immediate attention
     */
    private function identifyCriticalPatterns(): array
    {
        $critical = [];
        
        // High-risk scenarios
        foreach ($this->riskFactors['scenario_risk_levels'] as $scenario => $risk) {
            if ($risk['risk_level'] === 'critical' || $risk['risk_level'] === 'high') {
                $critical[] = "High-risk scenario: {$scenario} (Risk Level: {$risk['risk_level']})";
            }
        }
        
        // Failed scenarios
        $failedScenarios = $this->trendAnalysis['success_rate_trends']['failed_scenarios'];
        foreach ($failedScenarios as $scenario) {
            $critical[] = "Failed scenario requiring immediate attention: {$scenario}";
        }
        
        return $critical;
    }

    /**
     * Identify improvement opportunities
     */
    private function identifyImprovementOpportunities(): array
    {
        $opportunities = [];
        
        // Recovery time improvements
        $slowestScenario = $this->trendAnalysis['recovery_time_trends']['slowest_scenario'];
        $opportunities[] = "Optimize recovery time for {$slowestScenario} scenario";
        
        // Cascade effect prevention
        $cascadeScenarios = $this->trendAnalysis['cascade_effect_trends']['cascade_scenarios'];
        if (!empty($cascadeScenarios)) {
            $opportunities[] = "Implement cascade failure prevention for: " . implode(', ', $cascadeScenarios);
        }
        
        return $opportunities;
    }

    /**
     * Generate pattern-based recommendations
     */
    private function generatePatternRecommendations(): array
    {
        $recommendations = [];
        
        // Based on correlations
        foreach ($this->correlationMatrix['recovery_time'] as $factor => $correlation) {
            if ($correlation['correlation'] > 0.5) {
                $recommendations[] = "Monitor and optimize {$factor} to improve recovery times";
            }
        }
        
        // Based on risk factors
        foreach ($this->riskFactors['scenario_risk_levels'] as $scenario => $risk) {
            if ($risk['risk_score'] > 5) {
                $recommendations[] = "Implement additional safeguards for {$scenario} scenario";
            }
        }
        
        return $recommendations;
    }

    /**
     * Generate monitoring recommendations
     */
    private function generateMonitoringRecommendations(): array
    {
        return [
            'key_metrics_to_monitor' => [
                'recovery_time_ms',
                'services_affected',
                'memory_usage',
                'cascade_effects_count'
            ],
            'alert_thresholds' => [
                'recovery_time_ms' => 30000,
                'services_affected' => 2,
                'memory_usage' => 80, // percentage
                'cascade_effects' => 1
            ],
            'monitoring_frequency' => 'continuous',
            'dashboard_metrics' => [
                'real_time_recovery_times',
                'success_rate_trends',
                'risk_level_distribution',
                'correlation_heatmap'
            ]
        ];
    }

    // Helper methods
    private function calculateVariance(array $values): float
    {
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

    private function calculateReliabilityScore(int $successCount, int $totalCount): float
    {
        return $totalCount > 0 ? ($successCount / $totalCount) * 100 : 0;
    }

    private function analyzePerformanceDistribution(array $recoveryTimes, array $scenarios): array
    {
        $distribution = [];
        
        for ($i = 0; $i < count($recoveryTimes); $i++) {
            $time = $recoveryTimes[$i];
            $scenario = $scenarios[$i];
            
            if ($time < 5000) {
                $distribution['excellent'][] = $scenario;
            } elseif ($time < 15000) {
                $distribution['good'][] = $scenario;
            } elseif ($time < 30000) {
                $distribution['acceptable'][] = $scenario;
            } else {
                $distribution['poor'][] = $scenario;
            }
        }
        
        return $distribution;
    }

    private function identifyHighImpactScenarios(): array
    {
        $highImpact = [];
        
        foreach ($this->patternData as $scenario => $data) {
            if (in_array($data['impact_severity'], ['high', 'critical'])) {
                $highImpact[] = $scenario;
            }
        }
        
        return $highImpact;
    }

    private function calculateSeverityConsistency(array $severityDistribution): string
    {
        $maxCount = max($severityDistribution);
        $totalCount = array_sum($severityDistribution);
        
        $dominanceRatio = $maxCount / $totalCount;
        
        if ($dominanceRatio > 0.8) {
            return 'very_consistent';
        } elseif ($dominanceRatio > 0.6) {
            return 'consistent';
        } elseif ($dominanceRatio > 0.4) {
            return 'moderate';
        } else {
            return 'inconsistent';
        }
    }

    private function analyzeCascadeImpactCorrelation(): float
    {
        $cascadeCounts = [];
        $impactScores = [];
        
        foreach ($this->patternData as $data) {
            $cascadeCounts[] = count($data['cascade_effects']);
            $impactScores[] = $this->convertImpactToNumeric($data['impact_severity']);
        }
        
        return $this->pearsonCorrelation($cascadeCounts, array_filter($impactScores));
    }

    private function calculatePredictivePower(float $correlation): string
    {
        $abs = abs($correlation);
        
        if ($abs >= 0.8) {
            return 'very_high';
        } elseif ($abs >= 0.6) {
            return 'high';
        } elseif ($abs >= 0.4) {
            return 'moderate';
        } elseif ($abs >= 0.2) {
            return 'low';
        } else {
            return 'very_low';
        }
    }

    private function identifyCommonCascadeTriggers(array $cascadeFactors): array
    {
        // Analyze common factors in cascade scenarios
        $triggers = [];
        
        foreach ($cascadeFactors as $factor) {
            if ($factor['services_affected'] > 2) {
                $triggers[] = 'multiple_service_dependency';
            }
            if ($factor['recovery_time'] > 30000) {
                $triggers[] = 'slow_recovery_time';
            }
            if ($factor['impact_severity'] === 'critical') {
                $triggers[] = 'critical_impact_severity';
            }
        }
        
        return array_unique($triggers);
    }

    private function identifyCascadePreventionFactors(): array
    {
        return [
            'service_isolation',
            'circuit_breakers',
            'graceful_degradation',
            'fast_recovery_mechanisms',
            'redundancy_systems'
        ];
    }

    private function identifyHighRiskScenarios(): array
    {
        $highRisk = [];
        
        foreach ($this->riskFactors['scenario_risk_levels'] as $scenario => $risk) {
            if ($risk['risk_level'] === 'high' || $risk['risk_level'] === 'critical') {
                $highRisk[] = $scenario;
            }
        }
        
        return $highRisk;
    }

    private function identifyResourceRiskThresholds(): array
    {
        return [
            'memory_usage_threshold' => '80%',
            'disk_free_space_threshold' => '10%',
            'recovery_time_threshold' => '30000ms',
            'services_affected_threshold' => '2'
        ];
    }

    private function identifyEarlyWarningIndicators(): array
    {
        return [
            'increasing_recovery_times',
            'memory_usage_trending_up',
            'disk_space_decreasing',
            'service_dependency_increases',
            'error_rate_increases'
        ];
    }

    private function identifyCompoundRiskFactors(): array
    {
        return [
            'high_memory_usage_with_slow_recovery',
            'multiple_services_affected_with_cascade_effects',
            'critical_impact_with_failed_recovery',
            'low_disk_space_with_high_memory_usage'
        ];
    }

    private function identifySystemVulnerabilityFactors(): array
    {
        return [
            'single_points_of_failure',
            'insufficient_error_handling',
            'lack_of_graceful_degradation',
            'inadequate_monitoring',
            'slow_recovery_mechanisms'
        ];
    }

    private function identifyEnvironmentalRiskFactors(): array
    {
        return [
            'resource_constraints',
            'configuration_issues',
            'dependency_vulnerabilities',
            'infrastructure_limitations'
        ];
    }

    private function identifyOperationalRiskFactors(): array
    {
        return [
            'insufficient_monitoring',
            'lack_of_automated_recovery',
            'inadequate_alerting',
            'missing_runbooks',
            'insufficient_testing'
        ];
    }

    private function categorizeRiskLevel(int $riskScore): string
    {
        if ($riskScore >= 10) {
            return 'critical';
        } elseif ($riskScore >= 7) {
            return 'high';
        } elseif ($riskScore >= 4) {
            return 'medium';
        } elseif ($riskScore >= 2) {
            return 'low';
        } else {
            return 'minimal';
        }
    }

    private function identifyScenarioRiskFactors(array $data): array
    {
        $factors = [];
        
        if ($data['recovery_time_ms'] > 30000) {
            $factors[] = 'slow_recovery';
        }
        if (!$data['success']) {
            $factors[] = 'recovery_failure';
        }
        if (count($data['cascade_effects']) > 0) {
            $factors[] = 'cascade_effects';
        }
        if ($data['services_affected'] > 2) {
            $factors[] = 'multiple_services_affected';
        }
        if (in_array($data['impact_severity'], ['high', 'critical'])) {
            $factors[] = 'high_impact';
        }
        
        return $factors;
    }
}