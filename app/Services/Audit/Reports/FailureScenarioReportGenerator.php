<?php

namespace App\Services\Audit\Reports;

use Illuminate\Support\Facades\File;

/**
 * Failure Scenario Report Generator
 * 
 * Generates comprehensive markdown reports for failure scenario documentation
 * with screenshots, recovery metrics, and improvement recommendations
 */
class FailureScenarioReportGenerator
{
    private array $scenarioDocumentation;
    private string $screenshotPath;

    public function __construct(array $scenarioDocumentation)
    {
        $this->scenarioDocumentation = $scenarioDocumentation;
        $this->screenshotPath = storage_path('app/audit/screenshots');
    }

    /**
     * Generate comprehensive failure scenario report section
     */
    public function generateReportSection(): string
    {
        $report = "## 🔥 Fail-Safe Scenario Documentation & Analysis\n\n";
        
        // Executive Summary
        $report .= $this->generateExecutiveSummary();
        
        // Resilience Score Analysis
        $report .= $this->generateResilienceScoreAnalysis();
        
        // Detailed Scenario Documentation
        $report .= $this->generateDetailedScenarioDocumentation();
        
        // Recovery Metrics Analysis
        $report .= $this->generateRecoveryMetricsAnalysis();
        
        // Failure Pattern Analysis
        $report .= $this->generateFailurePatternAnalysis();
        
        // Screenshot Documentation
        $report .= $this->generateScreenshotDocumentation();
        
        // Improvement Recommendations
        $report .= $this->generateImprovementRecommendations();
        
        return $report;
    }

    /**
     * Generate executive summary section
     */
    private function generateExecutiveSummary(): string
    {
        $summary = $this->scenarioDocumentation['executive_summary'] ?? [];
        
        $report = "### 📊 Executive Summary\n\n";
        
        $report .= "| Metric | Value | Status |\n";
        $report .= "|--------|-------|--------|\n";
        $report .= "| **Total Scenarios Tested** | " . ($summary['total_scenarios_tested'] ?? 0) . " | ✅ |\n";
        $report .= "| **Successful Recoveries** | " . ($summary['successful_recoveries'] ?? 0) . " | ";
        $report .= ($summary['successful_recoveries'] ?? 0) === ($summary['total_scenarios_tested'] ?? 0) ? "✅" : "⚠️";
        $report .= " |\n";
        
        $avgRecoveryTime = $summary['average_recovery_time_ms'] ?? 0;
        $report .= "| **Average Recovery Time** | " . round($avgRecoveryTime, 2) . "ms | ";
        $report .= $avgRecoveryTime < 30000 ? "✅" : ($avgRecoveryTime < 60000 ? "⚠️" : "🔴");
        $report .= " |\n";
        
        $resilienceScore = $summary['resilience_score'] ?? 0;
        $report .= "| **Resilience Score** | " . round($resilienceScore, 1) . "% | ";
        $report .= $resilienceScore >= 80 ? "✅" : ($resilienceScore >= 60 ? "⚠️" : "🔴");
        $report .= " |\n";
        
        $report .= "| **Screenshots Captured** | " . ($this->scenarioDocumentation['documentation_metadata']['total_screenshots'] ?? 0) . " | ✅ |\n\n";
        
        // Critical Findings
        if (!empty($summary['critical_findings'])) {
            $report .= "#### 🔴 Critical Findings\n\n";
            foreach ($summary['critical_findings'] as $finding) {
                $report .= "- **CRITICAL**: {$finding}\n";
            }
            $report .= "\n";
        }
        
        return $report;
    }

    /**
     * Generate resilience score analysis
     */
    private function generateResilienceScoreAnalysis(): string
    {
        $resilienceScore = $this->scenarioDocumentation['executive_summary']['resilience_score'] ?? 0;
        
        $report = "### 🛡️ Resilience Score Analysis\n\n";
        
        $report .= "**Overall Resilience Score: " . round($resilienceScore, 1) . "%**\n\n";
        
        if ($resilienceScore >= 90) {
            $report .= "🟢 **EXCELLENT**: System demonstrates exceptional resilience across all failure scenarios.\n\n";
        } elseif ($resilienceScore >= 80) {
            $report .= "🟡 **GOOD**: System shows good resilience with minor areas for improvement.\n\n";
        } elseif ($resilienceScore >= 60) {
            $report .= "🟠 **MODERATE**: System resilience needs improvement in several areas.\n\n";
        } else {
            $report .= "🔴 **POOR**: System resilience is critically low and requires immediate attention.\n\n";
        }
        
        // Resilience breakdown
        $report .= "#### Resilience Components\n\n";
        $report .= "The resilience score is calculated based on:\n";
        $report .= "- **Success Rate (40%)**: Percentage of scenarios that recovered successfully\n";
        $report .= "- **Recovery Time (30%)**: Speed of recovery (lower is better)\n";
        $report .= "- **Graceful Degradation (30%)**: System's ability to degrade gracefully\n\n";
        
        return $report;
    }

    /**
     * Generate detailed scenario documentation
     */
    private function generateDetailedScenarioDocumentation(): string
    {
        $report = "### 📋 Detailed Scenario Documentation\n\n";
        
        $documentedScenarios = $this->scenarioDocumentation['documented_scenarios'] ?? [];
        
        foreach ($documentedScenarios as $scenarioKey => $scenarioDoc) {
            $report .= $this->generateScenarioSection($scenarioKey, $scenarioDoc);
        }
        
        return $report;
    }

    /**
     * Generate individual scenario section
     */
    private function generateScenarioSection(string $scenarioKey, array $scenarioDoc): string
    {
        $report = "#### 🔥 " . ucwords(str_replace('_', ' ', $scenarioKey)) . " Scenario\n\n";
        
        // Scenario Configuration
        if (isset($scenarioDoc['scenario_config'])) {
            $config = $scenarioDoc['scenario_config'];
            $report .= "**Configuration:**\n";
            $report .= "- **Name**: " . ($config['name'] ?? 'Unknown') . "\n";
            $report .= "- **Description**: " . ($config['description'] ?? 'No description') . "\n";
            $report .= "- **Severity**: " . strtoupper($config['severity'] ?? 'unknown') . "\n";
            $report .= "- **Recovery Threshold**: " . ($config['recovery_threshold'] ?? 0) . "ms\n\n";
        }
        
        // Simulation Results
        if (isset($scenarioDoc['simulation_result'])) {
            $result = $scenarioDoc['simulation_result'];
            $report .= "**Simulation Results:**\n";
            $report .= "- **Status**: " . ($result['success'] ? "✅ SUCCESS" : "❌ FAILED") . "\n";
            $report .= "- **Recovery Time**: " . round($result['recovery_time_ms'] ?? 0, 2) . "ms\n";
            
            if (isset($result['graceful_degradation'])) {
                $report .= "- **Graceful Degradation**: " . ($result['graceful_degradation'] ? "✅ YES" : "❌ NO") . "\n";
            }
            
            if (isset($result['services_affected'])) {
                $report .= "- **Services Affected**: " . $result['services_affected'] . "\n";
            }
            
            if (isset($result['error'])) {
                $report .= "- **Error**: `" . $result['error'] . "`\n";
            }
            
            $report .= "\n";
        }
        
        // System State During Failure
        if (isset($scenarioDoc['system_state'])) {
            $report .= "**System State During Failure:**\n";
            $state = $scenarioDoc['system_state'];
            $report .= "- **Memory Usage**: " . $this->formatBytes($state['memory_usage'] ?? 0) . "\n";
            $report .= "- **Memory Peak**: " . $this->formatBytes($state['memory_peak'] ?? 0) . "\n";
            $report .= "- **Disk Free Space**: " . $this->formatBytes($state['disk_free_space'] ?? 0) . "\n";
            $report .= "- **Environment**: " . ($state['environment'] ?? 'unknown') . "\n";
            $report .= "- **Debug Mode**: " . ($state['debug_mode'] ? 'ON' : 'OFF') . "\n\n";
        }
        
        // Recovery Documentation
        if (isset($scenarioDoc['recovery_documentation'])) {
            $recovery = $scenarioDoc['recovery_documentation'];
            $report .= "**Recovery Process:**\n";
            $report .= "- **Total Recovery Time**: " . round($recovery['total_recovery_time_ms'] ?? 0, 2) . "ms\n";
            $report .= "- **Recovery Successful**: " . ($recovery['recovery_successful'] ? "✅ YES" : "❌ NO") . "\n";
            $report .= "- **Recovery Steps**: " . count($recovery['recovery_steps'] ?? []) . "\n\n";
            
            if (!empty($recovery['recovery_steps'])) {
                $report .= "**Recovery Steps:**\n";
                foreach ($recovery['recovery_steps'] as $step) {
                    $status = $step['status'] === 'completed' ? '✅' : ($step['status'] === 'failed' ? '❌' : '⏳');
                    $report .= "{$step['step']}. {$status} **{$step['action']}** - {$step['description']}\n";
                }
                $report .= "\n";
            }
            
            if (!empty($recovery['lessons_learned'])) {
                $report .= "**Lessons Learned:**\n";
                foreach ($recovery['lessons_learned'] as $lesson) {
                    $report .= "- {$lesson}\n";
                }
                $report .= "\n";
            }
            
            if (!empty($recovery['improvement_recommendations'])) {
                $report .= "**Scenario-Specific Recommendations:**\n";
                foreach ($recovery['improvement_recommendations'] as $recommendation) {
                    $report .= "- {$recommendation}\n";
                }
                $report .= "\n";
            }
        }
        
        // Impact Analysis
        if (isset($scenarioDoc['impact_analysis'])) {
            $impact = $scenarioDoc['impact_analysis'];
            $report .= "**Impact Analysis:**\n";
            $report .= "- **Severity**: " . strtoupper($impact['severity'] ?? 'unknown') . "\n";
            $report .= "- **User Impact**: " . strtoupper($impact['user_impact'] ?? 'unknown') . "\n";
            $report .= "- **Business Impact**: " . strtoupper($impact['business_impact'] ?? 'unknown') . "\n";
            $report .= "- **Technical Impact**: " . strtoupper($impact['technical_impact'] ?? 'unknown') . "\n";
            
            if (!empty($impact['affected_components'])) {
                $report .= "- **Affected Components**: " . implode(', ', $impact['affected_components']) . "\n";
            }
            
            if (!empty($impact['cascade_effects'])) {
                $report .= "- **Cascade Effects**: " . implode(', ', $impact['cascade_effects']) . "\n";
            }
            
            $report .= "\n";
        }
        
        // Screenshots
        if (isset($scenarioDoc['screenshots'])) {
            $report .= "**Screenshots Captured:**\n";
            foreach ($scenarioDoc['screenshots'] as $type => $filename) {
                $report .= "- **" . ucwords(str_replace('_', ' ', $type)) . "**: `{$filename}`\n";
            }
            $report .= "\n";
        }
        
        $report .= "---\n\n";
        
        return $report;
    }

    /**
     * Generate recovery metrics analysis
     */
    private function generateRecoveryMetricsAnalysis(): string
    {
        $report = "### ⏱️ Recovery Metrics Analysis\n\n";
        
        $recoveryMetrics = $this->scenarioDocumentation['recovery_metrics'] ?? [];
        
        if (empty($recoveryMetrics)) {
            $report .= "No recovery metrics available.\n\n";
            return $report;
        }
        
        $report .= "| Scenario | Recovery Time | Success Rate | Graceful Degradation | Services Affected |\n";
        $report .= "|----------|---------------|--------------|---------------------|-------------------|\n";
        
        foreach ($recoveryMetrics as $scenario => $metrics) {
            $recoveryTime = round($metrics['recovery_time_ms'] ?? 0, 2);
            $successRate = $metrics['success_rate'] ?? 0;
            $gracefulDegradation = $metrics['graceful_degradation'] ? '✅' : '❌';
            $servicesAffected = $metrics['services_affected'] ?? 0;
            
            $timeStatus = $recoveryTime < 10000 ? '✅' : ($recoveryTime < 30000 ? '⚠️' : '🔴');
            $successStatus = $successRate > 0 ? '✅' : '🔴';
            
            $report .= "| **" . ucwords(str_replace('_', ' ', $scenario)) . "** | ";
            $report .= "{$timeStatus} {$recoveryTime}ms | ";
            $report .= "{$successStatus} {$successRate}% | ";
            $report .= "{$gracefulDegradation} | ";
            $report .= "{$servicesAffected} |\n";
        }
        
        $report .= "\n";
        
        return $report;
    }

    /**
     * Generate failure pattern analysis
     */
    private function generateFailurePatternAnalysis(): string
    {
        $report = "### 🔍 Failure Pattern Analysis\n\n";
        
        $patterns = $this->scenarioDocumentation['failure_patterns'] ?? [];
        
        // Common Failure Points
        if (!empty($patterns['common_failure_points'])) {
            $report .= "#### Common Failure Points\n\n";
            foreach ($patterns['common_failure_points'] as $failure) {
                $report .= "- **" . ucwords(str_replace('_', ' ', $failure['scenario'])) . "**: ";
                $report .= $failure['failure_reason'] . " (Recovery: " . round($failure['recovery_time'], 2) . "ms)\n";
            }
            $report .= "\n";
        }
        
        // Recovery Time Patterns
        if (!empty($patterns['recovery_time_patterns'])) {
            $report .= "#### Recovery Time Patterns\n\n";
            $report .= "| Scenario | Recovery Time | Performance |\n";
            $report .= "|----------|---------------|-------------|\n";
            
            foreach ($patterns['recovery_time_patterns'] as $scenario => $time) {
                $performance = $time < 10000 ? '🟢 Excellent' : ($time < 30000 ? '🟡 Good' : '🔴 Poor');
                $report .= "| " . ucwords(str_replace('_', ' ', $scenario)) . " | " . round($time, 2) . "ms | {$performance} |\n";
            }
            $report .= "\n";
        }
        
        // Success Rate Patterns
        if (!empty($patterns['success_rate_patterns'])) {
            $report .= "#### Success Rate Patterns\n\n";
            $report .= "| Scenario | Success Rate | Status |\n";
            $report .= "|----------|--------------|--------|\n";
            
            foreach ($patterns['success_rate_patterns'] as $scenario => $rate) {
                $status = $rate > 0 ? '✅ Success' : '❌ Failed';
                $report .= "| " . ucwords(str_replace('_', ' ', $scenario)) . " | {$rate}% | {$status} |\n";
            }
            $report .= "\n";
        }
        
        // Resilience Patterns
        if (!empty($patterns['resilience_patterns'])) {
            $resilience = $patterns['resilience_patterns'];
            $report .= "#### Resilience Patterns\n\n";
            $report .= "- **Best Recovery Scenario**: " . ucwords(str_replace('_', ' ', $resilience['best_recovery_scenario'] ?? 'N/A')) . "\n";
            $report .= "- **Worst Recovery Scenario**: " . ucwords(str_replace('_', ' ', $resilience['worst_recovery_scenario'] ?? 'N/A')) . "\n";
            $report .= "- **Successful Scenarios**: " . implode(', ', array_map(fn($s) => ucwords(str_replace('_', ' ', $s)), $resilience['successful_scenarios'] ?? [])) . "\n\n";
        }
        
        return $report;
    }

    /**
     * Generate screenshot documentation
     */
    private function generateScreenshotDocumentation(): string
    {
        $report = "### 📸 Screenshot Documentation\n\n";
        
        $metadata = $this->scenarioDocumentation['documentation_metadata'] ?? [];
        $totalScreenshots = $metadata['total_screenshots'] ?? 0;
        $screenshotPath = $metadata['screenshot_path'] ?? $this->screenshotPath;
        
        $report .= "**Screenshot Summary:**\n";
        $report .= "- **Total Screenshots**: {$totalScreenshots}\n";
        $report .= "- **Storage Path**: `{$screenshotPath}`\n";
        $report .= "- **Generated At**: " . ($metadata['generated_at'] ?? 'Unknown') . "\n\n";
        
        // List screenshot files if directory exists
        if (File::exists($screenshotPath)) {
            $screenshots = File::files($screenshotPath);
            
            if (!empty($screenshots)) {
                $report .= "**Screenshot Files:**\n\n";
                
                // Group screenshots by scenario
                $groupedScreenshots = [];
                foreach ($screenshots as $screenshot) {
                    $filename = $screenshot->getFilename();
                    $parts = explode('_', $filename);
                    
                    if (count($parts) >= 3) {
                        $scenario = $parts[2] ?? 'unknown';
                        if (!isset($groupedScreenshots[$scenario])) {
                            $groupedScreenshots[$scenario] = [];
                        }
                        $groupedScreenshots[$scenario][] = $filename;
                    }
                }
                
                foreach ($groupedScreenshots as $scenario => $files) {
                    $report .= "**" . ucwords(str_replace('_', ' ', $scenario)) . " Scenario:**\n";
                    foreach ($files as $file) {
                        $type = explode('_', $file)[0] ?? 'unknown';
                        $report .= "- " . ucwords(str_replace('_', ' ', $type)) . ": `{$file}`\n";
                    }
                    $report .= "\n";
                }
            }
        }
        
        return $report;
    }

    /**
     * Generate improvement recommendations
     */
    private function generateImprovementRecommendations(): string
    {
        $report = "### 💡 Improvement Recommendations\n\n";
        
        $recommendations = $this->scenarioDocumentation['improvement_recommendations'] ?? [];
        
        if (empty($recommendations)) {
            $report .= "✅ No specific improvement recommendations. System resilience is satisfactory.\n\n";
            return $report;
        }
        
        $report .= "Based on the failure scenario analysis, the following improvements are recommended:\n\n";
        
        foreach ($recommendations as $index => $recommendation) {
            $report .= ($index + 1) . ". **{$recommendation}**\n";
        }
        
        $report .= "\n";
        
        // Priority recommendations based on resilience score
        $resilienceScore = $this->scenarioDocumentation['executive_summary']['resilience_score'] ?? 0;
        
        if ($resilienceScore < 60) {
            $report .= "#### 🔴 High Priority Actions\n\n";
            $report .= "Due to the low resilience score ({$resilienceScore}%), immediate action is required:\n\n";
            $report .= "1. **Implement comprehensive error handling** for all failure scenarios\n";
            $report .= "2. **Add monitoring and alerting** for critical system components\n";
            $report .= "3. **Develop automated recovery mechanisms** for common failure patterns\n";
            $report .= "4. **Establish disaster recovery procedures** with documented runbooks\n";
            $report .= "5. **Conduct regular resilience testing** to prevent regression\n\n";
        } elseif ($resilienceScore < 80) {
            $report .= "#### 🟡 Medium Priority Actions\n\n";
            $report .= "To improve the resilience score from {$resilienceScore}% to 80%+:\n\n";
            $report .= "1. **Optimize recovery times** for slower scenarios\n";
            $report .= "2. **Improve graceful degradation** mechanisms\n";
            $report .= "3. **Enhance monitoring coverage** for edge cases\n";
            $report .= "4. **Document recovery procedures** for operations team\n\n";
        }
        
        return $report;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}