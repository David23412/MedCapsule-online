<?php

namespace App\Services\Audit\Components;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Exception;

/**
 * Recovery Time Analyzer with LETHAL GOLDEN STANDARD Implementation
 * 
 * Comprehensive recovery time measurement and analysis system
 * Provides detailed metrics, benchmarking, and performance analysis
 */
class RecoveryTimeAnalyzer
{
    private array $recoveryMeasurements = [];
    private array $benchmarkThresholds = [];
    private array $performanceMetrics = [];
    private array $timeSeriesData = [];

    public function __construct()
    {
        $this->initializeBenchmarkThresholds();
    }

    /**
     * Initialize benchmark thresholds for different scenario types
     */
    private function initializeBenchmarkThresholds(): void
    {
        $this->benchmarkThresholds = [
            'queue_overload' => [
                'excellent' => 5000,    // 5 seconds
                'good' => 15000,        // 15 seconds
                'acceptable' => 30000,  // 30 seconds
                'poor' => 60000,        // 60 seconds
                'critical' => 120000    // 2 minutes
            ],
            'database_lock' => [
                'excellent' => 1000,    // 1 second
                'good' => 5000,         // 5 seconds
                'acceptable' => 10000,  // 10 seconds
                'poor' => 30000,        // 30 seconds
                'critical' => 60000     // 1 minute
            ],
            'disk_exhaustion' => [
                'excellent' => 10000,   // 10 seconds
                'good' => 30000,        // 30 seconds
                'acceptable' => 60000,  // 1 minute
                'poor' => 180000,       // 3 minutes
                'critical' => 300000    // 5 minutes
            ],
            'memory_breach' => [
                'excellent' => 2000,    // 2 seconds
                'good' => 5000,         // 5 seconds
                'acceptable' => 10000,  // 10 seconds
                'poor' => 30000,        // 30 seconds
                'critical' => 60000     // 1 minute
            ],
            'cascade_failure' => [
                'excellent' => 15000,   // 15 seconds
                'good' => 30000,        // 30 seconds
                'acceptable' => 45000,  // 45 seconds
                'poor' => 90000,        // 1.5 minutes
                'critical' => 180000    // 3 minutes
            ]
        ];
    }

    /**
     * Analyze recovery times from scenario documentation
     */
    public function analyzeRecoveryTimes(array $scenarioDocumentation): array
    {
        Log::info('⏱️ Starting comprehensive recovery time analysis');
        
        $startTime = microtime(true);
        
        try {
            // Extract recovery measurements
            $this->extractRecoveryMeasurements($scenarioDocumentation);
            
            // Perform detailed analysis
            $this->performDetailedAnalysis();
            
            // Generate performance metrics
            $this->generatePerformanceMetrics();
            
            // Create time series analysis
            $this->createTimeSeriesAnalysis();
            
            // Generate benchmarking results
            $benchmarkResults = $this->generateBenchmarkResults();
            
            $executionTime = microtime(true) - $startTime;
            
            Log::info('✅ Recovery time analysis complete', [
                'scenarios_analyzed' => count($this->recoveryMeasurements),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return [
                'recovery_measurements' => $this->recoveryMeasurements,
                'performance_metrics' => $this->performanceMetrics,
                'benchmark_results' => $benchmarkResults,
                'time_series_data' => $this->timeSeriesData,
                'analysis_metadata' => [
                    'analyzed_at' => now()->toISOString(),
                    'execution_time_ms' => $executionTime * 1000,
                    'scenarios_count' => count($this->recoveryMeasurements)
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Recovery time analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Extract recovery measurements from scenario documentation
     */
    private function extractRecoveryMeasurements(array $scenarioDocumentation): void
    {
        $documentedScenarios = $scenarioDocumentation['documented_scenarios'] ?? [];
        
        foreach ($documentedScenarios as $scenarioKey => $scenarioDoc) {
            if (isset($scenarioDoc['simulation_result'])) {
                $result = $scenarioDoc['simulation_result'];
                $recoveryTime = $result['recovery_time_ms'] ?? 0;
                
                $this->recoveryMeasurements[$scenarioKey] = [
                    'scenario' => $scenarioKey,
                    'recovery_time_ms' => $recoveryTime,
                    'recovery_time_seconds' => $recoveryTime / 1000,
                    'success' => $result['success'] ?? false,
                    'graceful_degradation' => $result['graceful_degradation'] ?? false,
                    'services_affected' => $result['services_affected'] ?? 0,
                    'timestamp' => $scenarioDoc['timestamp'] ?? now()->toISOString(),
                    'system_state' => $scenarioDoc['system_state'] ?? [],
                    'recovery_steps' => count($scenarioDoc['recovery_documentation']['recovery_steps'] ?? [])
                ];
                
                // Add detailed recovery step timings if available
                if (isset($scenarioDoc['recovery_documentation']['recovery_steps'])) {
                    $this->recoveryMeasurements[$scenarioKey]['step_timings'] = 
                        $this->extractStepTimings($scenarioDoc['recovery_documentation']['recovery_steps']);
                }
            }
        }
    }

    /**
     * Extract timing data from recovery steps
     */
    private function extractStepTimings(array $recoverySteps): array
    {
        $stepTimings = [];
        
        foreach ($recoverySteps as $step) {
            $stepTimings[] = [
                'step' => $step['step'],
                'action' => $step['action'],
                'timestamp' => $step['timestamp'] ?? 0,
                'status' => $step['status'],
                'duration_ms' => $this->calculateStepDuration($step, $recoverySteps)
            ];
        }
        
        return $stepTimings;
    }

    /**
     * Calculate duration for individual recovery step
     */
    private function calculateStepDuration(array $currentStep, array $allSteps): float
    {
        $currentTimestamp = $currentStep['timestamp'] ?? 0;
        $nextStep = null;
        
        // Find next step
        foreach ($allSteps as $step) {
            if (($step['step'] ?? 0) === ($currentStep['step'] ?? 0) + 1) {
                $nextStep = $step;
                break;
            }
        }
        
        if ($nextStep) {
            return ($nextStep['timestamp'] ?? 0) - $currentTimestamp;
        }
        
        // If no next step, assume 10% of total recovery time
        return $currentTimestamp * 0.1;
    }

    /**
     * Perform detailed recovery time analysis
     */
    private function performDetailedAnalysis(): void
    {
        if (empty($this->recoveryMeasurements)) {
            return;
        }
        
        $recoveryTimes = array_column($this->recoveryMeasurements, 'recovery_time_ms');
        
        $this->performanceMetrics['statistical_analysis'] = [
            'mean' => array_sum($recoveryTimes) / count($recoveryTimes),
            'median' => $this->calculateMedian($recoveryTimes),
            'min' => min($recoveryTimes),
            'max' => max($recoveryTimes),
            'standard_deviation' => $this->calculateStandardDeviation($recoveryTimes),
            'variance' => $this->calculateVariance($recoveryTimes),
            'range' => max($recoveryTimes) - min($recoveryTimes)
        ];
        
        // Analyze recovery patterns
        $this->analyzeRecoveryPatterns();
        
        // Analyze performance distribution
        $this->analyzePerformanceDistribution();
        
        // Identify outliers
        $this->identifyOutliers();
    }

    /**
     * Analyze recovery patterns across scenarios
     */
    private function analyzeRecoveryPatterns(): void
    {
        $patterns = [
            'fastest_recovery' => null,
            'slowest_recovery' => null,
            'most_reliable' => null,
            'least_reliable' => null,
            'correlation_analysis' => []
        ];
        
        $fastestTime = PHP_FLOAT_MAX;
        $slowestTime = 0;
        $highestSuccessRate = 0;
        $lowestSuccessRate = 100;
        
        foreach ($this->recoveryMeasurements as $scenario => $measurement) {
            $recoveryTime = $measurement['recovery_time_ms'];
            $successRate = $measurement['success'] ? 100 : 0;
            
            // Track fastest/slowest
            if ($recoveryTime < $fastestTime) {
                $fastestTime = $recoveryTime;
                $patterns['fastest_recovery'] = [
                    'scenario' => $scenario,
                    'time_ms' => $recoveryTime,
                    'time_seconds' => $recoveryTime / 1000
                ];
            }
            
            if ($recoveryTime > $slowestTime) {
                $slowestTime = $recoveryTime;
                $patterns['slowest_recovery'] = [
                    'scenario' => $scenario,
                    'time_ms' => $recoveryTime,
                    'time_seconds' => $recoveryTime / 1000
                ];
            }
            
            // Track reliability
            if ($successRate > $highestSuccessRate) {
                $highestSuccessRate = $successRate;
                $patterns['most_reliable'] = [
                    'scenario' => $scenario,
                    'success_rate' => $successRate,
                    'recovery_time_ms' => $recoveryTime
                ];
            }
            
            if ($successRate < $lowestSuccessRate) {
                $lowestSuccessRate = $successRate;
                $patterns['least_reliable'] = [
                    'scenario' => $scenario,
                    'success_rate' => $successRate,
                    'recovery_time_ms' => $recoveryTime
                ];
            }
            
            // Correlation analysis
            $patterns['correlation_analysis'][$scenario] = [
                'recovery_time_vs_services_affected' => $this->calculateCorrelation(
                    $recoveryTime, 
                    $measurement['services_affected']
                ),
                'recovery_time_vs_steps' => $this->calculateCorrelation(
                    $recoveryTime, 
                    $measurement['recovery_steps']
                )
            ];
        }
        
        $this->performanceMetrics['recovery_patterns'] = $patterns;
    }

    /**
     * Analyze performance distribution across scenarios
     */
    private function analyzePerformanceDistribution(): void
    {
        $distribution = [
            'excellent' => 0,
            'good' => 0,
            'acceptable' => 0,
            'poor' => 0,
            'critical' => 0
        ];
        
        $scenarioDistribution = [];
        
        foreach ($this->recoveryMeasurements as $scenario => $measurement) {
            $recoveryTime = $measurement['recovery_time_ms'];
            $performance = $this->categorizePerformance($scenario, $recoveryTime);
            
            $distribution[$performance]++;
            $scenarioDistribution[$scenario] = $performance;
        }
        
        $this->performanceMetrics['performance_distribution'] = [
            'overall' => $distribution,
            'by_scenario' => $scenarioDistribution,
            'percentages' => $this->calculateDistributionPercentages($distribution)
        ];
    }

    /**
     * Identify statistical outliers in recovery times
     */
    private function identifyOutliers(): void
    {
        $recoveryTimes = array_column($this->recoveryMeasurements, 'recovery_time_ms');
        $mean = $this->performanceMetrics['statistical_analysis']['mean'];
        $stdDev = $this->performanceMetrics['statistical_analysis']['standard_deviation'];
        
        $outliers = [];
        
        foreach ($this->recoveryMeasurements as $scenario => $measurement) {
            $recoveryTime = $measurement['recovery_time_ms'];
            $zScore = abs(($recoveryTime - $mean) / $stdDev);
            
            if ($zScore > 2) { // More than 2 standard deviations
                $outliers[] = [
                    'scenario' => $scenario,
                    'recovery_time_ms' => $recoveryTime,
                    'z_score' => $zScore,
                    'deviation_type' => $recoveryTime > $mean ? 'slow' : 'fast',
                    'severity' => $zScore > 3 ? 'extreme' : 'moderate'
                ];
            }
        }
        
        $this->performanceMetrics['outliers'] = $outliers;
    }

    /**
     * Generate comprehensive performance metrics
     */
    private function generatePerformanceMetrics(): void
    {
        // Calculate overall performance score
        $this->performanceMetrics['overall_score'] = $this->calculateOverallPerformanceScore();
        
        // Generate scenario-specific metrics
        $this->performanceMetrics['scenario_metrics'] = $this->generateScenarioMetrics();
        
        // Calculate reliability metrics
        $this->performanceMetrics['reliability_metrics'] = $this->calculateReliabilityMetrics();
        
        // Generate trend analysis
        $this->performanceMetrics['trend_analysis'] = $this->generateTrendAnalysis();
    }

    /**
     * Calculate overall performance score (0-100)
     */
    private function calculateOverallPerformanceScore(): float
    {
        if (empty($this->recoveryMeasurements)) {
            return 0.0;
        }
        
        $totalScore = 0;
        $scenarioCount = 0;
        
        foreach ($this->recoveryMeasurements as $scenario => $measurement) {
            $recoveryTime = $measurement['recovery_time_ms'];
            $scenarioScore = $this->calculateScenarioPerformanceScore($scenario, $recoveryTime);
            
            $totalScore += $scenarioScore;
            $scenarioCount++;
        }
        
        return $scenarioCount > 0 ? $totalScore / $scenarioCount : 0.0;
    }

    /**
     * Calculate performance score for individual scenario
     */
    private function calculateScenarioPerformanceScore(string $scenario, float $recoveryTime): float
    {
        $thresholds = $this->benchmarkThresholds[$scenario] ?? $this->benchmarkThresholds['cascade_failure'];
        
        if ($recoveryTime <= $thresholds['excellent']) {
            return 100.0;
        } elseif ($recoveryTime <= $thresholds['good']) {
            return 85.0;
        } elseif ($recoveryTime <= $thresholds['acceptable']) {
            return 70.0;
        } elseif ($recoveryTime <= $thresholds['poor']) {
            return 50.0;
        } else {
            return 25.0;
        }
    }

    /**
     * Generate scenario-specific metrics
     */
    private function generateScenarioMetrics(): array
    {
        $scenarioMetrics = [];
        
        foreach ($this->recoveryMeasurements as $scenario => $measurement) {
            $recoveryTime = $measurement['recovery_time_ms'];
            $performance = $this->categorizePerformance($scenario, $recoveryTime);
            $score = $this->calculateScenarioPerformanceScore($scenario, $recoveryTime);
            
            $scenarioMetrics[$scenario] = [
                'recovery_time_ms' => $recoveryTime,
                'recovery_time_seconds' => $recoveryTime / 1000,
                'performance_category' => $performance,
                'performance_score' => $score,
                'benchmark_comparison' => $this->getBenchmarkComparison($scenario, $recoveryTime),
                'success_rate' => $measurement['success'] ? 100 : 0,
                'graceful_degradation' => $measurement['graceful_degradation'],
                'services_affected' => $measurement['services_affected'],
                'recovery_steps' => $measurement['recovery_steps']
            ];
        }
        
        return $scenarioMetrics;
    }

    /**
     * Calculate reliability metrics
     */
    private function calculateReliabilityMetrics(): array
    {
        $successfulRecoveries = 0;
        $gracefulDegradations = 0;
        $totalScenarios = count($this->recoveryMeasurements);
        
        foreach ($this->recoveryMeasurements as $measurement) {
            if ($measurement['success']) {
                $successfulRecoveries++;
            }
            if ($measurement['graceful_degradation']) {
                $gracefulDegradations++;
            }
        }
        
        return [
            'success_rate' => $totalScenarios > 0 ? ($successfulRecoveries / $totalScenarios) * 100 : 0,
            'graceful_degradation_rate' => $totalScenarios > 0 ? ($gracefulDegradations / $totalScenarios) * 100 : 0,
            'failure_rate' => $totalScenarios > 0 ? (($totalScenarios - $successfulRecoveries) / $totalScenarios) * 100 : 0,
            'reliability_score' => $this->calculateReliabilityScore($successfulRecoveries, $gracefulDegradations, $totalScenarios)
        ];
    }

    /**
     * Calculate overall reliability score
     */
    private function calculateReliabilityScore(int $successful, int $graceful, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }
        
        $successWeight = 0.7;
        $gracefulWeight = 0.3;
        
        $successScore = ($successful / $total) * 100;
        $gracefulScore = ($graceful / $total) * 100;
        
        return ($successScore * $successWeight) + ($gracefulScore * $gracefulWeight);
    }

    /**
     * Generate trend analysis
     */
    private function generateTrendAnalysis(): array
    {
        // For now, return basic trend indicators
        // In a real implementation, this would analyze historical data
        return [
            'trend_direction' => 'stable', // stable, improving, degrading
            'performance_consistency' => $this->calculatePerformanceConsistency(),
            'recovery_time_trend' => 'consistent',
            'reliability_trend' => 'stable'
        ];
    }

    /**
     * Calculate performance consistency
     */
    private function calculatePerformanceConsistency(): float
    {
        if (empty($this->recoveryMeasurements)) {
            return 0.0;
        }
        
        $recoveryTimes = array_column($this->recoveryMeasurements, 'recovery_time_ms');
        $mean = array_sum($recoveryTimes) / count($recoveryTimes);
        $stdDev = $this->calculateStandardDeviation($recoveryTimes);
        
        // Lower coefficient of variation indicates higher consistency
        $coefficientOfVariation = $mean > 0 ? ($stdDev / $mean) : 0;
        
        // Convert to consistency score (0-100, higher is more consistent)
        return max(0, 100 - ($coefficientOfVariation * 100));
    }

    /**
     * Create time series analysis
     */
    private function createTimeSeriesAnalysis(): void
    {
        $timeSeriesData = [];
        
        foreach ($this->recoveryMeasurements as $scenario => $measurement) {
            $timeSeriesData[] = [
                'timestamp' => $measurement['timestamp'],
                'scenario' => $scenario,
                'recovery_time_ms' => $measurement['recovery_time_ms'],
                'success' => $measurement['success'],
                'performance_score' => $this->calculateScenarioPerformanceScore($scenario, $measurement['recovery_time_ms'])
            ];
        }
        
        // Sort by timestamp
        usort($timeSeriesData, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });
        
        $this->timeSeriesData = [
            'data_points' => $timeSeriesData,
            'summary' => [
                'total_measurements' => count($timeSeriesData),
                'time_span' => $this->calculateTimeSpan($timeSeriesData),
                'average_interval' => $this->calculateAverageInterval($timeSeriesData)
            ]
        ];
    }

    /**
     * Generate benchmark results
     */
    private function generateBenchmarkResults(): array
    {
        $benchmarkResults = [];
        
        foreach ($this->recoveryMeasurements as $scenario => $measurement) {
            $recoveryTime = $measurement['recovery_time_ms'];
            $thresholds = $this->benchmarkThresholds[$scenario] ?? $this->benchmarkThresholds['cascade_failure'];
            
            $benchmarkResults[$scenario] = [
                'recovery_time_ms' => $recoveryTime,
                'performance_category' => $this->categorizePerformance($scenario, $recoveryTime),
                'benchmark_comparison' => $this->getBenchmarkComparison($scenario, $recoveryTime),
                'meets_sla' => $recoveryTime <= $thresholds['acceptable'],
                'threshold_analysis' => [
                    'excellent_threshold' => $thresholds['excellent'],
                    'good_threshold' => $thresholds['good'],
                    'acceptable_threshold' => $thresholds['acceptable'],
                    'poor_threshold' => $thresholds['poor'],
                    'critical_threshold' => $thresholds['critical'],
                    'current_performance' => $this->categorizePerformance($scenario, $recoveryTime)
                ]
            ];
        }
        
        return $benchmarkResults;
    }

    /**
     * Categorize performance based on recovery time
     */
    private function categorizePerformance(string $scenario, float $recoveryTime): string
    {
        $thresholds = $this->benchmarkThresholds[$scenario] ?? $this->benchmarkThresholds['cascade_failure'];
        
        if ($recoveryTime <= $thresholds['excellent']) {
            return 'excellent';
        } elseif ($recoveryTime <= $thresholds['good']) {
            return 'good';
        } elseif ($recoveryTime <= $thresholds['acceptable']) {
            return 'acceptable';
        } elseif ($recoveryTime <= $thresholds['poor']) {
            return 'poor';
        } else {
            return 'critical';
        }
    }

    /**
     * Get benchmark comparison details
     */
    private function getBenchmarkComparison(string $scenario, float $recoveryTime): array
    {
        $thresholds = $this->benchmarkThresholds[$scenario] ?? $this->benchmarkThresholds['cascade_failure'];
        $category = $this->categorizePerformance($scenario, $recoveryTime);
        
        return [
            'category' => $category,
            'percentage_of_threshold' => $this->calculateThresholdPercentage($recoveryTime, $thresholds, $category),
            'improvement_needed' => $this->calculateImprovementNeeded($recoveryTime, $thresholds),
            'status' => $this->getPerformanceStatus($category)
        ];
    }

    /**
     * Calculate what percentage of threshold the recovery time represents
     */
    private function calculateThresholdPercentage(float $recoveryTime, array $thresholds, string $category): float
    {
        switch ($category) {
            case 'excellent':
                return ($recoveryTime / $thresholds['excellent']) * 100;
            case 'good':
                return ($recoveryTime / $thresholds['good']) * 100;
            case 'acceptable':
                return ($recoveryTime / $thresholds['acceptable']) * 100;
            case 'poor':
                return ($recoveryTime / $thresholds['poor']) * 100;
            default:
                return ($recoveryTime / $thresholds['critical']) * 100;
        }
    }

    /**
     * Calculate improvement needed to reach next performance tier
     */
    private function calculateImprovementNeeded(float $recoveryTime, array $thresholds): array
    {
        $improvements = [];
        
        foreach (['excellent', 'good', 'acceptable'] as $tier) {
            if ($recoveryTime > $thresholds[$tier]) {
                $improvements[$tier] = [
                    'target_time_ms' => $thresholds[$tier],
                    'improvement_needed_ms' => $recoveryTime - $thresholds[$tier],
                    'improvement_percentage' => (($recoveryTime - $thresholds[$tier]) / $recoveryTime) * 100
                ];
            }
        }
        
        return $improvements;
    }

    /**
     * Get performance status indicator
     */
    private function getPerformanceStatus(string $category): string
    {
        switch ($category) {
            case 'excellent':
                return '🟢 Excellent';
            case 'good':
                return '🟡 Good';
            case 'acceptable':
                return '🟠 Acceptable';
            case 'poor':
                return '🔴 Poor';
            default:
                return '🚨 Critical';
        }
    }

    // Statistical helper methods
    private function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        } else {
            return $values[floor($count / 2)];
        }
    }

    private function calculateStandardDeviation(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);
        
        return sqrt($variance);
    }

    private function calculateVariance(array $values): float
    {
        $mean = array_sum($values) / count($values);
        return array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);
    }

    private function calculateCorrelation(float $x, float $y): float
    {
        // Simplified correlation calculation
        // In a real implementation, this would use proper correlation algorithms
        return 0.0;
    }

    private function calculateDistributionPercentages(array $distribution): array
    {
        $total = array_sum($distribution);
        $percentages = [];
        
        foreach ($distribution as $category => $count) {
            $percentages[$category] = $total > 0 ? ($count / $total) * 100 : 0;
        }
        
        return $percentages;
    }

    private function calculateTimeSpan(array $timeSeriesData): string
    {
        if (empty($timeSeriesData)) {
            return '0 seconds';
        }
        
        $first = strtotime($timeSeriesData[0]['timestamp']);
        $last = strtotime($timeSeriesData[count($timeSeriesData) - 1]['timestamp']);
        
        return ($last - $first) . ' seconds';
    }

    private function calculateAverageInterval(array $timeSeriesData): float
    {
        if (count($timeSeriesData) < 2) {
            return 0.0;
        }
        
        $intervals = [];
        for ($i = 1; $i < count($timeSeriesData); $i++) {
            $current = strtotime($timeSeriesData[$i]['timestamp']);
            $previous = strtotime($timeSeriesData[$i - 1]['timestamp']);
            $intervals[] = $current - $previous;
        }
        
        return array_sum($intervals) / count($intervals);
    }

    /**
     * Get recovery measurements
     */
    public function getRecoveryMeasurements(): array
    {
        return $this->recoveryMeasurements;
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return $this->performanceMetrics;
    }

    /**
     * Get benchmark thresholds
     */
    public function getBenchmarkThresholds(): array
    {
        return $this->benchmarkThresholds;
    }
}