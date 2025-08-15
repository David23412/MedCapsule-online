<?php

namespace App\Services\Audit\Quality;

use App\Services\Audit\Models\Finding;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Report Downgrader with LETHAL GOLDEN STANDARD Implementation
 * 
 * Comprehensive report quality assessment and downgrade system
 * Automatically downgrades low-quality sections and findings
 */
class ReportDowngrader
{
    private array $qualityThresholds = [];
    private array $downgradePolicies = [];
    private array $severityMap = [];

    public function __construct()
    {
        $this->initializeQualityThresholds();
        $this->initializeDowngradePolicies();
        $this->initializeSeverityMap();
    }

    /**
     * Assess and downgrade report sections based on quality
     */
    public function assessAndDowngradeReport(array $findings, array $qualityScores): array
    {
        Log::info('📉 Assessing report for quality-based downgrades');
        
        $downgradeResult = [
            'original_findings_count' => count($findings),
            'downgraded_findings' => [],
            'removed_findings' => [],
            'quality_adjustments' => [],
            'downgrade_summary' => [],
            'final_findings_count' => 0,
            'quality_improvement_score' => 0.0,
            'assessment_timestamp' => now()->toISOString()
        ];

        try {
            $processedFindings = [];

            foreach ($findings as $finding) {
                $findingId = $finding->getId();
                $qualityScore = $qualityScores[$findingId] ?? null;

                if (!$qualityScore) {
                    // No quality score available - treat as low quality
                    $downgradeResult['removed_findings'][] = [
                        'finding_id' => $findingId,
                        'reason' => 'No quality score available',
                        'original_severity' => $finding->getSeverity()
                    ];
                    continue;
                }

                $downgradeAction = $this->determineDowngradeAction($finding, $qualityScore);
                
                switch ($downgradeAction['action']) {
                    case 'remove':
                        $downgradeResult['removed_findings'][] = [
                            'finding_id' => $findingId,
                            'reason' => $downgradeAction['reason'],
                            'original_severity' => $finding->getSeverity(),
                            'quality_score' => $qualityScore['overall_score']
                        ];
                        break;

                    case 'downgrade':
                        $downgradedFinding = $this->downgradeFinding($finding, $downgradeAction);
                        $processedFindings[] = $downgradedFinding;
                        
                        $downgradeResult['downgraded_findings'][] = [
                            'finding_id' => $findingId,
                            'original_severity' => $finding->getSeverity(),
                            'new_severity' => $downgradedFinding->getSeverity(),
                            'reason' => $downgradeAction['reason'],
                            'quality_score' => $qualityScore['overall_score']
                        ];
                        break;

                    case 'keep':
                        $processedFindings[] = $finding;
                        break;
                }
            }

            $downgradeResult['final_findings_count'] = count($processedFindings);
            $downgradeResult['quality_adjustments'] = $this->calculateQualityAdjustments($downgradeResult);
            $downgradeResult['downgrade_summary'] = $this->generateDowngradeSummary($downgradeResult);
            $downgradeResult['quality_improvement_score'] = $this->calculateQualityImprovementScore($downgradeResult, $qualityScores);

            Log::info('✅ Report downgrade assessment completed', [
                'original_count' => $downgradeResult['original_findings_count'],
                'final_count' => $downgradeResult['final_findings_count'],
                'downgraded_count' => count($downgradeResult['downgraded_findings']),
                'removed_count' => count($downgradeResult['removed_findings'])
            ]);

            return [
                'processed_findings' => $processedFindings,
                'downgrade_result' => $downgradeResult
            ];

        } catch (\Exception $e) {
            Log::error('❌ Report downgrade assessment failed', [
                'error' => $e->getMessage()
            ]);

            $downgradeResult['error'] = 'Assessment failed: ' . $e->getMessage();
            return [
                'processed_findings' => $findings, // Return original findings on error
                'downgrade_result' => $downgradeResult
            ];
        }
    }

    /**
     * Initialize quality thresholds
     */
    private function initializeQualityThresholds(): void
    {
        $this->qualityThresholds = [
            'removal_threshold' => 40.0,      // Below this = remove finding
            'downgrade_threshold' => 60.0,    // Below this = downgrade severity
            'acceptable_threshold' => 75.0,   // Above this = keep as-is
            'excellent_threshold' => 90.0     // Above this = high confidence
        ];
    }

    /**
     * Initialize downgrade policies
     */
    private function initializeDowngradePolicies(): void
    {
        $this->downgradePolicies = [
            'evidence_completeness' => [
                'weight' => 0.3,
                'min_threshold' => 50.0
            ],
            'evidence_accuracy' => [
                'weight' => 0.25,
                'min_threshold' => 60.0
            ],
            'reproducibility' => [
                'weight' => 0.2,
                'min_threshold' => 70.0
            ],
            'objectivity' => [
                'weight' => 0.15,
                'min_threshold' => 65.0
            ],
            'relevance' => [
                'weight' => 0.1,
                'min_threshold' => 70.0
            ]
        ];
    }

    /**
     * Initialize severity mapping
     */
    private function initializeSeverityMap(): void
    {
        $this->severityMap = [
            'critical' => ['high', 'medium'],
            'high' => ['medium', 'low'],
            'medium' => ['low', 'info'],
            'low' => ['info'],
            'info' => [] // Cannot downgrade further
        ];
    }

    /**
     * Determine downgrade action for a finding
     */
    private function determineDowngradeAction(Finding $finding, array $qualityScore): array
    {
        $overallScore = $qualityScore['overall_score'];
        $dimensionScores = $qualityScore['dimension_scores'];
        
        // Check for removal criteria
        if ($overallScore < $this->qualityThresholds['removal_threshold']) {
            return [
                'action' => 'remove',
                'reason' => 'Overall quality score too low (' . round($overallScore, 1) . '%)'
            ];
        }

        // Check for critical dimension failures
        foreach ($this->downgradePolicies as $dimension => $policy) {
            if (isset($dimensionScores[$dimension])) {
                $dimensionScore = $dimensionScores[$dimension]['score'];
                if ($dimensionScore < $policy['min_threshold']) {
                    return [
                        'action' => 'remove',
                        'reason' => "Critical failure in {$dimension} dimension (" . round($dimensionScore, 1) . '%)'
                    ];
                }
            }
        }

        // Check for downgrade criteria
        if ($overallScore < $this->qualityThresholds['downgrade_threshold']) {
            $downgradeSeverity = $this->calculateDowngradeSeverity($finding, $qualityScore);
            return [
                'action' => 'downgrade',
                'reason' => 'Quality score below acceptable threshold (' . round($overallScore, 1) . '%)',
                'new_severity' => $downgradeSeverity
            ];
        }

        // Check for specific quality issues requiring downgrade
        $qualityIssues = $qualityScore['quality_issues'] ?? [];
        if (count($qualityIssues) >= 3) {
            $downgradeSeverity = $this->calculateDowngradeSeverity($finding, $qualityScore);
            return [
                'action' => 'downgrade',
                'reason' => 'Multiple quality issues detected (' . count($qualityIssues) . ' issues)',
                'new_severity' => $downgradeSeverity
            ];
        }

        return [
            'action' => 'keep',
            'reason' => 'Quality score acceptable (' . round($overallScore, 1) . '%)'
        ];
    }

    /**
     * Calculate appropriate downgrade severity
     */
    private function calculateDowngradeSeverity(Finding $finding, array $qualityScore): string
    {
        $currentSeverity = $finding->getSeverity();
        $overallScore = $qualityScore['overall_score'];
        
        $availableDowngrades = $this->severityMap[$currentSeverity] ?? [];
        
        if (empty($availableDowngrades)) {
            return $currentSeverity; // Cannot downgrade further
        }

        // Determine downgrade level based on quality score
        if ($overallScore < 45) {
            // Severe quality issues - downgrade by 2 levels if possible
            return $availableDowngrades[1] ?? $availableDowngrades[0];
        } else {
            // Moderate quality issues - downgrade by 1 level
            return $availableDowngrades[0];
        }
    }

    /**
     * Downgrade a finding
     */
    private function downgradeFinding(Finding $finding, array $downgradeAction): Finding
    {
        $downgradedFinding = clone $finding;
        
        // Update severity
        $newSeverity = $downgradeAction['new_severity'];
        $downgradedFinding->setSeverity($newSeverity);
        
        // Add downgrade note to description
        $originalDescription = $downgradedFinding->getDescription();
        $downgradeNote = "\n\n**Quality Downgrade Notice**: This finding was downgraded from '{$finding->getSeverity()}' to '{$newSeverity}' due to: {$downgradeAction['reason']}";
        $downgradedFinding->setDescription($originalDescription . $downgradeNote);
        
        // Update recommendation with quality improvement suggestions
        $originalRecommendation = $downgradedFinding->getRecommendation();
        $qualityRecommendation = "\n\n**Quality Improvement**: To restore original severity, improve evidence quality by addressing the identified quality issues.";
        $downgradedFinding->setRecommendation($originalRecommendation . $qualityRecommendation);
        
        return $downgradedFinding;
    }

    /**
     * Calculate quality adjustments
     */
    private function calculateQualityAdjustments(array $downgradeResult): array
    {
        $originalCount = $downgradeResult['original_findings_count'];
        $finalCount = $downgradeResult['final_findings_count'];
        $downgradedCount = count($downgradeResult['downgraded_findings']);
        $removedCount = count($downgradeResult['removed_findings']);
        
        return [
            'retention_rate' => $originalCount > 0 ? ($finalCount / $originalCount) * 100 : 0,
            'downgrade_rate' => $originalCount > 0 ? ($downgradedCount / $originalCount) * 100 : 0,
            'removal_rate' => $originalCount > 0 ? ($removedCount / $originalCount) * 100 : 0,
            'quality_filter_effectiveness' => $removedCount + $downgradedCount
        ];
    }

    /**
     * Generate downgrade summary
     */
    private function generateDowngradeSummary(array $downgradeResult): array
    {
        $summary = [
            'total_adjustments' => count($downgradeResult['downgraded_findings']) + count($downgradeResult['removed_findings']),
            'severity_changes' => [],
            'removal_reasons' => [],
            'quality_impact' => []
        ];

        // Analyze severity changes
        foreach ($downgradeResult['downgraded_findings'] as $downgrade) {
            $change = $downgrade['original_severity'] . ' → ' . $downgrade['new_severity'];
            $summary['severity_changes'][$change] = ($summary['severity_changes'][$change] ?? 0) + 1;
        }

        // Analyze removal reasons
        foreach ($downgradeResult['removed_findings'] as $removal) {
            $reason = $removal['reason'];
            $summary['removal_reasons'][$reason] = ($summary['removal_reasons'][$reason] ?? 0) + 1;
        }

        // Calculate quality impact
        $summary['quality_impact'] = [
            'findings_improved' => count($downgradeResult['downgraded_findings']),
            'findings_removed' => count($downgradeResult['removed_findings']),
            'overall_quality_gain' => $this->calculateOverallQualityGain($downgradeResult)
        ];

        return $summary;
    }

    /**
     * Calculate overall quality gain from downgrades
     */
    private function calculateOverallQualityGain(array $downgradeResult): float
    {
        $originalCount = $downgradeResult['original_findings_count'];
        $adjustmentCount = count($downgradeResult['downgraded_findings']) + count($downgradeResult['removed_findings']);
        
        if ($originalCount == 0) {
            return 0.0;
        }

        // Quality gain is proportional to the percentage of findings that were adjusted
        $adjustmentRate = ($adjustmentCount / $originalCount) * 100;
        
        // Assume each adjustment improves overall report quality
        return min(100, $adjustmentRate * 0.5); // Conservative estimate
    }

    /**
     * Calculate quality improvement score
     */
    private function calculateQualityImprovementScore(array $downgradeResult, array $qualityScores): float
    {
        $totalOriginalScore = 0;
        $totalImprovedScore = 0;
        $processedCount = 0;

        // Calculate average quality score before and after adjustments
        foreach ($qualityScores as $findingId => $qualityScore) {
            $totalOriginalScore += $qualityScore['overall_score'];
            $processedCount++;

            // Check if this finding was removed (contributes 0 to improved score)
            $wasRemoved = false;
            foreach ($downgradeResult['removed_findings'] as $removal) {
                if ($removal['finding_id'] === $findingId) {
                    $wasRemoved = true;
                    break;
                }
            }

            if (!$wasRemoved) {
                // Finding was kept or downgraded - use original quality score
                $totalImprovedScore += $qualityScore['overall_score'];
            }
            // Removed findings contribute 0 to improved score
        }

        if ($processedCount == 0) {
            return 0.0;
        }

        $originalAverage = $totalOriginalScore / $processedCount;
        $improvedAverage = $totalImprovedScore / max(1, $downgradeResult['final_findings_count']);

        // Quality improvement is the difference in average quality
        return max(0, $improvedAverage - $originalAverage);
    }

    /**
     * Get quality thresholds
     */
    public function getQualityThresholds(): array
    {
        return $this->qualityThresholds;
    }

    /**
     * Get downgrade policies
     */
    public function getDowngradePolicies(): array
    {
        return $this->downgradePolicies;
    }
}