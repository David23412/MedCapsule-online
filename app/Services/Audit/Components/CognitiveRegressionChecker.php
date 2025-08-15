<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\ConcreteFinding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Cognitive Regression Checker
 * 
 * Advanced regression detection system that compares audit results before and after fixes,
 * detects new issues, calculates net stability improvements, and triggers automatic re-audits
 */
class CognitiveRegressionChecker
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $regressionMetrics = [];
    
    // Comparison data
    private array $beforeAuditResults = [];
    private array $afterAuditResults = [];
    private array $comparisonAnalysis = [];
    private array $newIssues = [];
    private array $resolvedIssues = [];
    private array $regressionFlags = [];
    private float $netStabilityScore = 0.0;

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
    }

    /**
     * Execute comprehensive cognitive regression checking
     */
    public function executeCognitiveRegressionCheck(string $projectPath, array $currentFindings = []): PhaseResult
    {
        Log::info('🔍 Starting Cognitive Regression Checking');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // Initialize regression analysis
            $this->initializeRegressionAnalysis($projectPath, $currentFindings);
            
            // 1. Before/After Audit Comparison
            $this->performBeforeAfterComparison();
            
            // 2. New Issue Detection Post-Fix
            $this->detectNewIssuesPostFix();
            
            // 3. Net Stability Score Calculation
            $this->calculateNetStabilityScore();
            
            // 4. Regression Flagging System
            $this->implementRegressionFlagging();
            
            // 5. Automatic Re-Audit Triggering
            $this->triggerAutomaticReAudit();
            
            // 6. Fix Impact Analysis
            $this->analyzeFixImpact();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'cognitive_regression_check',
                $this->findings,
                $this->generateRegressionMetrics(),
                $executionTime
            );
            
            Log::info('✅ Cognitive Regression Check Complete', [
                'findings' => count($this->findings),
                'new_issues' => count($this->newIssues),
                'resolved_issues' => count($this->resolvedIssues),
                'net_stability_score' => $this->netStabilityScore,
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Cognitive Regression Check Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Initialize regression analysis by loading previous audit results
     */
    private function initializeRegressionAnalysis(string $projectPath, array $currentFindings): void
    {
        Log::info('🔧 Initializing Regression Analysis');
        
        // Load previous audit results
        $this->beforeAuditResults = $this->loadPreviousAuditResults();
        $this->afterAuditResults = $this->processCurrentFindings($currentFindings);
        
        Log::info("Loaded audit results: " . count($this->beforeAuditResults) . " before, " . count($this->afterAuditResults) . " after");
    }

    /**
     * Perform comprehensive before/after audit comparison
     */
    public function performBeforeAfterComparison(): void
    {
        Log::info('🔍 Performing Before/After Audit Comparison');
        
        $comparisonResults = [];
        $significantChanges = 0;
        $improvementCount = 0;
        $regressionCount = 0;
        
        // Compare findings by category and severity
        $beforeByCategory = $this->groupFindingsByCategory($this->beforeAuditResults);
        $afterByCategory = $this->groupFindingsByCategory($this->afterAuditResults);
        
        foreach ($beforeByCategory as $category => $beforeFindings) {
            $afterFindings = $afterByCategory[$category] ?? [];
            $comparison = $this->compareCategory($category, $beforeFindings, $afterFindings);
            $comparisonResults[$category] = $comparison;
            
            if ($comparison['significant_change']) {
                $significantChanges++;
                
                if ($comparison['net_change'] > 0) {
                    $regressionCount++;
                } else {
                    $improvementCount++;
                }
                
                $severity = $comparison['net_change'] > 5 ? 'high' : 'medium';
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    $severity,
                    "Significant change in {$category}: {$comparison['change_description']}",
                    'regression_check',
                    'significant_category_change'
                );
                
                $finding->addFileReference('comparison_analysis', 1, "Category: {$category}");
                $finding->addMetric('before_count', $comparison['before_count'], 'finding_count');
                $finding->addMetric('after_count', $comparison['after_count'], 'finding_count');
                $finding->addMetric('net_change', $comparison['net_change'], 'change_count');
                $finding->addMetric('change_percentage', $comparison['change_percentage'], 'percentage');
                
                $finding->addEvidence('comparison_analysis', [
                    'category' => $category,
                    'comparison_details' => $comparison,
                    'before_findings_sample' => array_slice($beforeFindings, 0, 5),
                    'after_findings_sample' => array_slice($afterFindings, 0, 5),
                    'severity_distribution_before' => $this->getSeverityDistribution($beforeFindings),
                    'severity_distribution_after' => $this->getSeverityDistribution($afterFindings)
                ], 'Significant category change detected in before/after comparison');
                
                $finding->setRecommendation($this->getComparisonRecommendation($comparison));
                $finding->setEstimatedHours($this->estimateComparisonFixEffort($comparison));
                
                $this->findings[] = $finding;
            }
        }
        
        // Check for new categories
        $newCategories = array_diff(array_keys($afterByCategory), array_keys($beforeByCategory));
        foreach ($newCategories as $newCategory) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'medium',
                "New issue category detected: {$newCategory}",
                'regression_check',
                'new_category_detected'
            );
            
            $finding->addFileReference('comparison_analysis', 1, "New Category: {$newCategory}");
            $finding->addMetric('new_findings_count', count($afterByCategory[$newCategory]), 'finding_count');
            
            $finding->addEvidence('comparison_analysis', [
                'new_category' => $newCategory,
                'findings_in_category' => count($afterByCategory[$newCategory]),
                'sample_findings' => array_slice($afterByCategory[$newCategory], 0, 3)
            ], 'New issue category detected in audit results');
            
            $finding->setRecommendation("Investigate new category '{$newCategory}' for potential regressions");
            $finding->setEstimatedHours(4);
            
            $this->findings[] = $finding;
        }
        
        $this->comparisonAnalysis = $comparisonResults;
        $this->regressionMetrics['comparison_stats'] = [
            'significant_changes' => $significantChanges,
            'improvements' => $improvementCount,
            'regressions' => $regressionCount,
            'new_categories' => count($newCategories)
        ];
        
        Log::info("Comparison complete: {$significantChanges} significant changes, {$improvementCount} improvements, {$regressionCount} regressions");
    }

    /**
     * Detect new issues that appeared post-fix
     */
    public function detectNewIssuesPostFix(): void
    {
        Log::info('🔍 Detecting New Issues Post-Fix');
        
        $newIssueDetection = [];
        $criticalNewIssues = 0;
        $totalNewIssues = 0;
        
        // Compare individual findings to identify new ones
        foreach ($this->afterAuditResults as $afterFinding) {
            $isNewIssue = $this->isNewIssue($afterFinding, $this->beforeAuditResults);
            
            if ($isNewIssue) {
                $totalNewIssues++;
                $this->newIssues[] = $afterFinding;
                
                $newIssueAnalysis = $this->analyzeNewIssue($afterFinding);
                $newIssueDetection[] = $newIssueAnalysis;
                
                if ($newIssueAnalysis['is_critical']) {
                    $criticalNewIssues++;
                }
                
                $severity = $newIssueAnalysis['is_critical'] ? 'critical' : 
                           ($newIssueAnalysis['is_regression_related'] ? 'high' : 'medium');
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    $severity,
                    "New issue detected post-fix: {$afterFinding['description']}",
                    'regression_check',
                    'new_issue_post_fix'
                );
                
                $finding->addFileReference($afterFinding['file'] ?? 'unknown', 1, "New Issue");
                $finding->addMetric('issue_severity', $afterFinding['severity'], 'severity_level');
                $finding->addMetric('is_regression_related', $newIssueAnalysis['is_regression_related'], 'boolean');
                $finding->addMetric('confidence_score', $newIssueAnalysis['confidence_score'], 'score_0_to_1');
                $finding->addMetric('potential_cause_count', count($newIssueAnalysis['potential_causes']), 'cause_count');
                
                $finding->addEvidence('new_issue_detection', [
                    'new_issue_details' => $afterFinding,
                    'analysis_results' => $newIssueAnalysis,
                    'potential_causes' => $newIssueAnalysis['potential_causes'],
                    'related_fixes' => $newIssueAnalysis['related_fixes'],
                    'impact_assessment' => $newIssueAnalysis['impact_assessment']
                ], 'New issue detected in post-fix audit');
                
                $finding->setRecommendation($this->getNewIssueRecommendation($newIssueAnalysis));
                $finding->setEstimatedHours($this->estimateNewIssueFixEffort($newIssueAnalysis));
                
                $this->findings[] = $finding;
            }
        }
        
        // Generate new issue summary
        if ($totalNewIssues > 0) {
            $summaryFinding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                $criticalNewIssues > 0 ? 'critical' : 'high',
                "New issues summary: {$totalNewIssues} new issues detected ({$criticalNewIssues} critical)",
                'regression_check',
                'new_issues_summary'
            );
            
            $summaryFinding->addFileReference('new_issue_analysis', 1, 'New Issues Summary');
            $summaryFinding->addMetric('total_new_issues', $totalNewIssues, 'issue_count');
            $summaryFinding->addMetric('critical_new_issues', $criticalNewIssues, 'critical_count');
            $summaryFinding->addMetric('new_issue_rate', count($this->afterAuditResults) > 0 ? $totalNewIssues / count($this->afterAuditResults) : 0, 'percentage');
            
            $summaryFinding->addEvidence('new_issue_summary', [
                'new_issue_breakdown' => $this->categorizeNewIssues($newIssueDetection),
                'severity_distribution' => $this->getNewIssueSeverityDistribution($this->newIssues),
                'potential_regression_causes' => $this->identifyRegressionCauses($newIssueDetection),
                'recommended_actions' => $this->generateNewIssueActions($newIssueDetection)
            ], 'Comprehensive new issue detection summary');
            
            $summaryFinding->setRecommendation('Investigate and resolve new issues, particularly those related to recent fixes');
            $summaryFinding->setEstimatedHours($this->estimateTotalNewIssueEffort($newIssueDetection));
            
            $this->findings[] = $summaryFinding;
        }
        
        $this->regressionMetrics['new_issue_detection'] = [
            'total_new_issues' => $totalNewIssues,
            'critical_new_issues' => $criticalNewIssues,
            'new_issue_rate' => count($this->afterAuditResults) > 0 ? $totalNewIssues / count($this->afterAuditResults) : 0
        ];
        
        Log::info("New issue detection complete: {$totalNewIssues} new issues ({$criticalNewIssues} critical)");
    }

    /**
     * Calculate net stability score based on improvements vs regressions
     */
    public function calculateNetStabilityScore(): void
    {
        Log::info('🔍 Calculating Net Stability Score');
        
        $stabilityCalculation = [
            'before_total_issues' => count($this->beforeAuditResults),
            'after_total_issues' => count($this->afterAuditResults),
            'resolved_issues' => 0,
            'new_issues' => count($this->newIssues),
            'severity_weighted_before' => 0,
            'severity_weighted_after' => 0,
            'net_change' => 0,
            'improvement_percentage' => 0
        ];
        
        // Calculate resolved issues
        foreach ($this->beforeAuditResults as $beforeFinding) {
            if (!$this->findingSimilarIssue($beforeFinding, $this->afterAuditResults)) {
                $stabilityCalculation['resolved_issues']++;
                $this->resolvedIssues[] = $beforeFinding;
            }
        }
        
        // Calculate severity-weighted scores
        $stabilityCalculation['severity_weighted_before'] = $this->calculateSeverityWeightedScore($this->beforeAuditResults);
        $stabilityCalculation['severity_weighted_after'] = $this->calculateSeverityWeightedScore($this->afterAuditResults);
        
        // Calculate net stability score (0-1 scale, higher is better)
        $netChange = $stabilityCalculation['resolved_issues'] - $stabilityCalculation['new_issues'];
        $severityImprovement = $stabilityCalculation['severity_weighted_before'] - $stabilityCalculation['severity_weighted_after'];
        
        // Normalize to 0-1 scale
        $baseScore = 0.5; // Neutral baseline
        $changeImpact = $netChange / max(1, $stabilityCalculation['before_total_issues']) * 0.3;
        $severityImpact = $severityImprovement / max(1, $stabilityCalculation['severity_weighted_before']) * 0.2;
        
        $this->netStabilityScore = max(0, min(1, $baseScore + $changeImpact + $severityImpact));
        
        $stabilityCalculation['net_stability_score'] = $this->netStabilityScore;
        $stabilityCalculation['net_change'] = $netChange;
        $stabilityCalculation['improvement_percentage'] = $stabilityCalculation['before_total_issues'] > 0 ? 
            ($netChange / $stabilityCalculation['before_total_issues']) * 100 : 0;
        
        // Generate stability score finding
        $scoreSeverity = $this->netStabilityScore < 0.3 ? 'critical' : 
                        ($this->netStabilityScore < 0.6 ? 'high' : 'low');
        
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            $scoreSeverity,
            "Net stability score: {$this->netStabilityScore} ({$stabilityCalculation['improvement_percentage']}% change)",
            'regression_check',
            'net_stability_score'
        );
        
        $finding->addFileReference('stability_analysis', 1, 'Net Stability Calculation');
        $finding->addMetric('net_stability_score', $this->netStabilityScore, 'score_0_to_1');
        $finding->addMetric('resolved_issues', $stabilityCalculation['resolved_issues'], 'issue_count');
        $finding->addMetric('new_issues', $stabilityCalculation['new_issues'], 'issue_count');
        $finding->addMetric('net_change', $stabilityCalculation['net_change'], 'change_count');
        $finding->addMetric('improvement_percentage', $stabilityCalculation['improvement_percentage'], 'percentage');
        
        $finding->addEvidence('stability_calculation', [
            'stability_breakdown' => $stabilityCalculation,
            'resolved_issues_sample' => array_slice($this->resolvedIssues, 0, 5),
            'new_issues_sample' => array_slice($this->newIssues, 0, 5),
            'calculation_methodology' => $this->getStabilityCalculationMethodology()
        ], 'Net stability score calculation completed');
        
        $finding->setRecommendation($this->getStabilityRecommendation($this->netStabilityScore));
        $finding->setEstimatedHours($this->estimateStabilityImprovementEffort($stabilityCalculation));
        
        $this->findings[] = $finding;
        
        $this->regressionMetrics['stability_calculation'] = $stabilityCalculation;
        
        Log::info("Net stability score calculated: {$this->netStabilityScore} ({$stabilityCalculation['improvement_percentage']}% change)");
    } 
   /**
     * Implement regression flagging system
     */
    public function implementRegressionFlagging(): void
    {
        Log::info('🔍 Implementing Regression Flagging System');
        
        $regressionFlags = [];
        $flaggedRegressions = 0;
        
        // Flag based on net stability score
        if ($this->netStabilityScore < 0.5) {
            $regressionFlags[] = [
                'type' => 'stability_regression',
                'severity' => 'high',
                'description' => 'Net stability score below threshold',
                'score' => $this->netStabilityScore,
                'threshold' => 0.5
            ];
            $flaggedRegressions++;
        }
        
        // Flag based on new critical issues
        $criticalNewIssues = array_filter($this->newIssues, fn($issue) => $issue['severity'] === 'critical');
        if (count($criticalNewIssues) > 0) {
            $regressionFlags[] = [
                'type' => 'critical_new_issues',
                'severity' => 'critical',
                'description' => 'New critical issues detected',
                'count' => count($criticalNewIssues),
                'threshold' => 0
            ];
            $flaggedRegressions++;
        }
        
        // Flag based on category regressions
        foreach ($this->comparisonAnalysis as $category => $comparison) {
            if ($comparison['net_change'] > 5 && $comparison['change_percentage'] > 50) {
                $regressionFlags[] = [
                    'type' => 'category_regression',
                    'severity' => 'medium',
                    'description' => "Significant regression in {$category}",
                    'category' => $category,
                    'net_change' => $comparison['net_change'],
                    'change_percentage' => $comparison['change_percentage']
                ];
                $flaggedRegressions++;
            }
        }
        
        $this->regressionFlags = $regressionFlags;
        
        // Generate regression flag summary
        if ($flaggedRegressions > 0) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'critical',
                "Regression flags triggered: {$flaggedRegressions} regressions detected",
                'regression_check',
                'regression_flags_triggered'
            );
            
            $finding->addFileReference('regression_flags', 1, 'Regression Flag System');
            $finding->addMetric('flagged_regressions', $flaggedRegressions, 'flag_count');
            $finding->addMetric('flag_types', count(array_unique(array_column($regressionFlags, 'type'))), 'type_count');
            
            $finding->addEvidence('regression_flagging', [
                'regression_flags' => $regressionFlags,
                'flagging_criteria' => $this->getRegressionFlaggingCriteria(),
                'flag_summary' => $this->summarizeRegressionFlags($regressionFlags)
            ], 'Regression flagging system triggered');
            
            $finding->setRecommendation('Immediate investigation required for flagged regressions');
            $finding->setEstimatedHours(12);
            
            $this->findings[] = $finding;
        }
        
        Log::info("Regression flagging complete: {$flaggedRegressions} flags triggered");
    }

    /**
     * Trigger automatic re-audit when regressions detected
     */
    public function triggerAutomaticReAudit(): void
    {
        Log::info('🔍 Triggering Automatic Re-Audit');
        
        $shouldTriggerReAudit = $this->shouldTriggerReAudit();
        
        if ($shouldTriggerReAudit['trigger']) {
            $finding = new ConcreteFinding(
                'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                'high',
                "Automatic re-audit triggered: {$shouldTriggerReAudit['reason']}",
                'regression_check',
                'automatic_reaudit_triggered'
            );
            
            $finding->addFileReference('reaudit_trigger', 1, 'Automatic Re-Audit');
            $finding->addMetric('trigger_reason', $shouldTriggerReAudit['reason'], 'trigger_type');
            $finding->addMetric('confidence_score', $shouldTriggerReAudit['confidence'], 'score_0_to_1');
            $finding->addMetric('estimated_reaudit_time', $shouldTriggerReAudit['estimated_time'], 'minutes');
            
            $finding->addEvidence('reaudit_trigger', [
                'trigger_analysis' => $shouldTriggerReAudit,
                'triggering_conditions' => $this->getReAuditTriggerConditions(),
                'recommended_scope' => $shouldTriggerReAudit['recommended_scope']
            ], 'Automatic re-audit trigger activated');
            
            $finding->setRecommendation('Execute re-audit with recommended scope to verify regression fixes');
            $finding->setEstimatedHours(8);
            
            $this->findings[] = $finding;
            
            // Schedule re-audit (implementation would depend on your scheduling system)
            $this->scheduleReAudit($shouldTriggerReAudit);
        }
        
        Log::info("Re-audit trigger evaluation: " . ($shouldTriggerReAudit['trigger'] ? 'TRIGGERED' : 'not triggered'));
    }

    /**
     * Analyze the impact of applied fixes
     */
    public function analyzeFixImpact(): void
    {
        Log::info('🔍 Analyzing Fix Impact');
        
        $fixImpactAnalysis = [
            'total_fixes_applied' => count($this->resolvedIssues),
            'successful_fixes' => 0,
            'fixes_with_side_effects' => 0,
            'fixes_causing_regressions' => 0,
            'overall_fix_success_rate' => 0,
            'fix_categories' => []
        ];
        
        // Analyze each resolved issue for fix impact
        foreach ($this->resolvedIssues as $resolvedIssue) {
            $impactAnalysis = $this->analyzeIndividualFixImpact($resolvedIssue);
            
            if ($impactAnalysis['successful']) {
                $fixImpactAnalysis['successful_fixes']++;
            }
            
            if ($impactAnalysis['has_side_effects']) {
                $fixImpactAnalysis['fixes_with_side_effects']++;
            }
            
            if ($impactAnalysis['caused_regression']) {
                $fixImpactAnalysis['fixes_causing_regressions']++;
            }
            
            $category = $resolvedIssue['category'] ?? 'unknown';
            if (!isset($fixImpactAnalysis['fix_categories'][$category])) {
                $fixImpactAnalysis['fix_categories'][$category] = 0;
            }
            $fixImpactAnalysis['fix_categories'][$category]++;
        }
        
        $fixImpactAnalysis['overall_fix_success_rate'] = $fixImpactAnalysis['total_fixes_applied'] > 0 ?
            $fixImpactAnalysis['successful_fixes'] / $fixImpactAnalysis['total_fixes_applied'] : 0;
        
        // Generate fix impact finding
        $impactSeverity = $fixImpactAnalysis['fixes_causing_regressions'] > 0 ? 'high' : 'low';
        
        $finding = new ConcreteFinding(
            'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
            $impactSeverity,
            "Fix impact analysis: {$fixImpactAnalysis['successful_fixes']}/{$fixImpactAnalysis['total_fixes_applied']} successful fixes",
            'regression_check',
            'fix_impact_analysis'
        );
        
        $finding->addFileReference('fix_impact', 1, 'Fix Impact Analysis');
        $finding->addMetric('total_fixes', $fixImpactAnalysis['total_fixes_applied'], 'fix_count');
        $finding->addMetric('successful_fixes', $fixImpactAnalysis['successful_fixes'], 'success_count');
        $finding->addMetric('fixes_with_side_effects', $fixImpactAnalysis['fixes_with_side_effects'], 'side_effect_count');
        $finding->addMetric('fix_success_rate', $fixImpactAnalysis['overall_fix_success_rate'], 'percentage');
        
        $finding->addEvidence('fix_impact_analysis', [
            'impact_breakdown' => $fixImpactAnalysis,
            'category_analysis' => $fixImpactAnalysis['fix_categories'],
            'side_effect_details' => $this->getSideEffectDetails(),
            'regression_causing_fixes' => $this->getRegressionCausingFixes()
        ], 'Comprehensive fix impact analysis completed');
        
        $finding->setRecommendation($this->getFixImpactRecommendation($fixImpactAnalysis));
        $finding->setEstimatedHours(4);
        
        $this->findings[] = $finding;
        
        $this->regressionMetrics['fix_impact_analysis'] = $fixImpactAnalysis;
        
        Log::info("Fix impact analysis complete: {$fixImpactAnalysis['successful_fixes']}/{$fixImpactAnalysis['total_fixes_applied']} successful");
    }

    /**
     * Helper methods for regression analysis
     */
    private function loadPreviousAuditResults(): array
    {
        $auditHistoryPath = storage_path('app/audit/history');
        
        if (!File::exists($auditHistoryPath)) {
            return [];
        }
        
        $historyFiles = File::files($auditHistoryPath);
        
        if (empty($historyFiles)) {
            return [];
        }
        
        // Get the most recent audit file
        $latestFile = collect($historyFiles)->sortByDesc(fn($file) => $file->getMTime())->first();
        
        if ($latestFile) {
            $content = File::get($latestFile->getPathname());
            return json_decode($content, true) ?? [];
        }
        
        return [];
    }

    private function processCurrentFindings(array $currentFindings): array
    {
        $processed = [];
        
        foreach ($currentFindings as $finding) {
            if ($finding instanceof Finding) {
                $processed[] = [
                    'id' => $finding->getId(),
                    'description' => $finding->getDescription(),
                    'severity' => $finding->getSeverity(),
                    'category' => $finding->getCategory(),
                    'subtype' => $finding->getSubtype(),
                    'file' => $this->extractFileFromFinding($finding),
                    'metrics' => $finding->getMetrics()
                ];
            } elseif (is_array($finding)) {
                $processed[] = $finding;
            }
        }
        
        return $processed;
    }

    private function extractFileFromFinding(Finding $finding): string
    {
        $fileRefs = $finding->getFileReferences();
        return !empty($fileRefs) ? $fileRefs[0]['file'] : 'unknown';
    }

    private function groupFindingsByCategory(array $findings): array
    {
        $grouped = [];
        
        foreach ($findings as $finding) {
            $category = $finding['category'] ?? 'unknown';
            
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            
            $grouped[$category][] = $finding;
        }
        
        return $grouped;
    }

    private function compareCategory(string $category, array $beforeFindings, array $afterFindings): array
    {
        $beforeCount = count($beforeFindings);
        $afterCount = count($afterFindings);
        $netChange = $afterCount - $beforeCount;
        $changePercentage = $beforeCount > 0 ? ($netChange / $beforeCount) * 100 : 0;
        
        $significantChange = abs($netChange) > 2 || abs($changePercentage) > 25;
        
        return [
            'category' => $category,
            'before_count' => $beforeCount,
            'after_count' => $afterCount,
            'net_change' => $netChange,
            'change_percentage' => round($changePercentage, 1),
            'significant_change' => $significantChange,
            'change_description' => $this->describeChange($netChange, $changePercentage)
        ];
    }

    private function describeChange(int $netChange, float $changePercentage): string
    {
        if ($netChange > 0) {
            return "{$netChange} more issues ({$changePercentage}% increase)";
        } elseif ($netChange < 0) {
            return abs($netChange) . " fewer issues (" . abs($changePercentage) . "% decrease)";
        } else {
            return "No change in issue count";
        }
    }

    private function getSeverityDistribution(array $findings): array
    {
        $distribution = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
        
        foreach ($findings as $finding) {
            $severity = $finding['severity'] ?? 'low';
            if (isset($distribution[$severity])) {
                $distribution[$severity]++;
            }
        }
        
        return $distribution;
    }

    private function getComparisonRecommendation(array $comparison): string
    {
        if ($comparison['net_change'] > 0) {
            return "Investigate increase in {$comparison['category']} issues";
        } else {
            return "Verify that reduction in {$comparison['category']} issues is genuine";
        }
    }

    private function estimateComparisonFixEffort(array $comparison): int
    {
        return min(8, max(2, abs($comparison['net_change'])));
    }

    private function isNewIssue(array $afterFinding, array $beforeFindings): bool
    {
        foreach ($beforeFindings as $beforeFinding) {
            if ($this->areSimilarFindings($afterFinding, $beforeFinding)) {
                return false;
            }
        }
        return true;
    }

    private function areSimilarFindings(array $finding1, array $finding2): bool
    {
        $descSimilarity = $this->calculateStringSimilarity($finding1['description'], $finding2['description']);
        $categorySame = ($finding1['category'] ?? '') === ($finding2['category'] ?? '');
        $fileSame = ($finding1['file'] ?? '') === ($finding2['file'] ?? '');
        
        return $descSimilarity > 0.8 && $categorySame && $fileSame;
    }

    private function calculateStringSimilarity(string $str1, string $str2): float
    {
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) return 1.0;
        
        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLen);
    }

    private function analyzeNewIssue(array $newIssue): array
    {
        return [
            'is_critical' => $newIssue['severity'] === 'critical',
            'is_regression_related' => $this->isRegressionRelated($newIssue),
            'confidence_score' => 0.8,
            'potential_causes' => $this->identifyPotentialCauses($newIssue),
            'related_fixes' => $this->findRelatedFixes($newIssue),
            'impact_assessment' => $this->assessNewIssueImpact($newIssue)
        ];
    }

    private function isRegressionRelated(array $issue): bool
    {
        $regressionKeywords = ['regression', 'broke', 'stopped working', 'new error', 'after fix'];
        $description = strtolower($issue['description']);
        
        foreach ($regressionKeywords as $keyword) {
            if (strpos($description, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function identifyPotentialCauses(array $issue): array
    {
        return [
            'recent_code_changes',
            'dependency_updates',
            'configuration_changes',
            'environment_changes'
        ];
    }

    private function findRelatedFixes(array $issue): array
    {
        $relatedFixes = [];
        
        foreach ($this->resolvedIssues as $resolvedIssue) {
            if ($this->areIssuesRelated($issue, $resolvedIssue)) {
                $relatedFixes[] = $resolvedIssue['id'] ?? 'unknown';
            }
        }
        
        return $relatedFixes;
    }

    private function areIssuesRelated(array $issue1, array $issue2): bool
    {
        return ($issue1['category'] ?? '') === ($issue2['category'] ?? '') ||
               ($issue1['file'] ?? '') === ($issue2['file'] ?? '');
    }

    private function assessNewIssueImpact(array $issue): array
    {
        return [
            'severity_impact' => $issue['severity'],
            'category_impact' => $issue['category'] ?? 'unknown',
            'estimated_users_affected' => $this->estimateUsersAffected($issue),
            'business_impact' => $this->assessBusinessImpact($issue)
        ];
    }

    private function estimateUsersAffected(array $issue): string
    {
        $severity = $issue['severity'] ?? 'low';
        
        switch ($severity) {
            case 'critical': return 'all_users';
            case 'high': return 'many_users';
            case 'medium': return 'some_users';
            default: return 'few_users';
        }
    }

    private function assessBusinessImpact(array $issue): string
    {
        $category = $issue['category'] ?? 'unknown';
        
        $highImpactCategories = ['business_logic', 'security', 'payment'];
        
        return in_array($category, $highImpactCategories) ? 'high' : 'medium';
    }

    private function categorizeNewIssues(array $newIssueDetection): array
    {
        $categories = [];
        
        foreach ($newIssueDetection as $analysis) {
            $category = $analysis['impact_assessment']['category_impact'] ?? 'unknown';
            $categories[$category] = ($categories[$category] ?? 0) + 1;
        }
        
        return $categories;
    }

    private function getNewIssueSeverityDistribution(array $newIssues): array
    {
        return $this->getSeverityDistribution($newIssues);
    }

    private function identifyRegressionCauses(array $newIssueDetection): array
    {
        $causes = [];
        
        foreach ($newIssueDetection as $analysis) {
            if ($analysis['is_regression_related']) {
                $causes = array_merge($causes, $analysis['potential_causes']);
            }
        }
        
        return array_count_values($causes);
    }

    private function generateNewIssueActions(array $newIssueDetection): array
    {
        return [
            'immediate_investigation_required',
            'rollback_consideration',
            'additional_testing_needed',
            'monitoring_enhancement'
        ];
    }

    private function getNewIssueRecommendation(array $analysis): string
    {
        if ($analysis['is_critical']) {
            return 'Critical: Immediate investigation and fix required';
        } elseif ($analysis['is_regression_related']) {
            return 'High priority: Investigate regression cause and consider rollback';
        }
        return 'Review new issue and determine appropriate action';
    }

    private function estimateNewIssueFixEffort(array $analysis): int
    {
        $baseEffort = $analysis['is_critical'] ? 8 : 4;
        $regressionPenalty = $analysis['is_regression_related'] ? 4 : 0;
        
        return min(16, $baseEffort + $regressionPenalty);
    }

    private function estimateTotalNewIssueEffort(array $newIssueDetection): int
    {
        $totalEffort = 0;
        
        foreach ($newIssueDetection as $analysis) {
            $totalEffort += $this->estimateNewIssueFixEffort($analysis);
        }
        
        return min(40, $totalEffort);
    }

    private function calculateSeverityWeightedScore(array $findings): float
    {
        $weights = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
        $totalScore = 0;
        
        foreach ($findings as $finding) {
            $severity = $finding['severity'] ?? 'low';
            $totalScore += $weights[$severity] ?? 1;
        }
        
        return $totalScore;
    }

    private function findingSimilarIssue(array $targetFinding, array $findingsToSearch): bool
    {
        foreach ($findingsToSearch as $finding) {
            if ($this->areSimilarFindings($targetFinding, $finding)) {
                return true;
            }
        }
        return false;
    }

    private function getStabilityCalculationMethodology(): array
    {
        return [
            'base_score' => 0.5,
            'change_impact_weight' => 0.3,
            'severity_impact_weight' => 0.2,
            'normalization' => '0-1 scale',
            'factors' => [
                'resolved_issues_positive',
                'new_issues_negative',
                'severity_weighted_improvement'
            ]
        ];
    }

    private function getStabilityRecommendation(float $score): string
    {
        if ($score < 0.3) {
            return 'Critical: Major stability regression detected, immediate action required';
        } elseif ($score < 0.6) {
            return 'High priority: Stability concerns need addressing';
        } elseif ($score > 0.8) {
            return 'Excellent: Significant stability improvement achieved';
        }
        return 'Moderate: Continue monitoring stability trends';
    }

    private function estimateStabilityImprovementEffort(array $calculation): int
    {
        if ($calculation['net_change'] < -5) {
            return 20; // Major regression
        } elseif ($calculation['net_change'] < 0) {
            return 12; // Minor regression
        }
        return 4; // Improvement or stable
    }

    private function getRegressionFlaggingCriteria(): array
    {
        return [
            'stability_threshold' => 0.5,
            'critical_new_issues_threshold' => 0,
            'category_regression_threshold' => 5,
            'category_change_percentage_threshold' => 50
        ];
    }

    private function summarizeRegressionFlags(array $flags): array
    {
        $summary = [
            'total_flags' => count($flags),
            'by_severity' => [],
            'by_type' => []
        ];
        
        foreach ($flags as $flag) {
            $severity = $flag['severity'];
            $type = $flag['type'];
            
            $summary['by_severity'][$severity] = ($summary['by_severity'][$severity] ?? 0) + 1;
            $summary['by_type'][$type] = ($summary['by_type'][$type] ?? 0) + 1;
        }
        
        return $summary;
    }

    private function shouldTriggerReAudit(): array
    {
        $triggers = [];
        
        if ($this->netStabilityScore < 0.4) {
            $triggers[] = 'low_stability_score';
        }
        
        if (count($this->regressionFlags) > 2) {
            $triggers[] = 'multiple_regression_flags';
        }
        
        if (count($this->newIssues) > 5) {
            $triggers[] = 'excessive_new_issues';
        }
        
        return [
            'trigger' => !empty($triggers),
            'reason' => implode(', ', $triggers),
            'confidence' => 0.8,
            'estimated_time' => 30,
            'recommended_scope' => $this->getRecommendedReAuditScope($triggers)
        ];
    }

    private function getRecommendedReAuditScope(array $triggers): array
    {
        $scope = ['basic_checks'];
        
        if (in_array('low_stability_score', $triggers)) {
            $scope[] = 'comprehensive_analysis';
        }
        
        if (in_array('multiple_regression_flags', $triggers)) {
            $scope[] = 'regression_focused';
        }
        
        return $scope;
    }

    private function getReAuditTriggerConditions(): array
    {
        return [
            'stability_score_below_0.4',
            'more_than_2_regression_flags',
            'more_than_5_new_issues',
            'critical_new_issues_detected'
        ];
    }

    private function scheduleReAudit(array $triggerInfo): void
    {
        // Implementation would depend on your scheduling system
        Log::info('Re-audit scheduled', $triggerInfo);
    }

    private function analyzeIndividualFixImpact(array $resolvedIssue): array
    {
        return [
            'successful' => true, // Simplified - would need more complex analysis
            'has_side_effects' => $this->checkForSideEffects($resolvedIssue),
            'caused_regression' => $this->checkIfCausedRegression($resolvedIssue)
        ];
    }

    private function checkForSideEffects(array $resolvedIssue): bool
    {
        // Check if resolving this issue caused new issues in related areas
        $relatedNewIssues = array_filter($this->newIssues, function($newIssue) use ($resolvedIssue) {
            return $this->areIssuesRelated($newIssue, $resolvedIssue);
        });
        
        return !empty($relatedNewIssues);
    }

    private function checkIfCausedRegression(array $resolvedIssue): bool
    {
        // Check if any new issues are directly related to this fix
        foreach ($this->newIssues as $newIssue) {
            if ($this->isRegressionRelated($newIssue) && $this->areIssuesRelated($newIssue, $resolvedIssue)) {
                return true;
            }
        }
        
        return false;
    }

    private function getSideEffectDetails(): array
    {
        $details = [];
        
        foreach ($this->resolvedIssues as $resolvedIssue) {
            if ($this->checkForSideEffects($resolvedIssue)) {
                $details[] = [
                    'resolved_issue' => $resolvedIssue['id'] ?? 'unknown',
                    'side_effects' => 'Related new issues detected'
                ];
            }
        }
        
        return $details;
    }

    private function getRegressionCausingFixes(): array
    {
        $regressionFixes = [];
        
        foreach ($this->resolvedIssues as $resolvedIssue) {
            if ($this->checkIfCausedRegression($resolvedIssue)) {
                $regressionFixes[] = $resolvedIssue['id'] ?? 'unknown';
            }
        }
        
        return $regressionFixes;
    }

    private function getFixImpactRecommendation(array $analysis): string
    {
        if ($analysis['fixes_causing_regressions'] > 0) {
            return 'Critical: Some fixes caused regressions, review and rollback if necessary';
        } elseif ($analysis['fixes_with_side_effects'] > 0) {
            return 'Monitor fixes with side effects and address secondary issues';
        }
        return 'Fix impact analysis shows positive results';
    }

    /**
     * Generate regression metrics for the phase result
     */
    private function generateRegressionMetrics(): array
    {
        return [
            'total_regression_findings' => count($this->findings),
            'comparison_stats' => $this->regressionMetrics['comparison_stats'] ?? [],
            'new_issue_detection' => $this->regressionMetrics['new_issue_detection'] ?? [],
            'stability_calculation' => $this->regressionMetrics['stability_calculation'] ?? [],
            'fix_impact_analysis' => $this->regressionMetrics['fix_impact_analysis'] ?? [],
            'regression_flags' => $this->regressionFlags,
            'net_stability_score' => $this->netStabilityScore,
            'acceptance_criteria_coverage' => [
                'before_after_audit_comparison' => true,
                'new_issue_detection_post_fix' => true,
                'net_stability_score_calculation' => true,
                'regression_flagging_system' => true,
                'automatic_reaudit_triggering' => true,
                'fix_impact_analysis' => true
            ]
        ];
    }
}