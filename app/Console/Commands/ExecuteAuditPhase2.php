<?php

namespace App\Console\Commands;

use App\Services\Audit\AuditOrchestrator;
use App\Services\Audit\Components\CodeAnalyzer;
use App\Services\Audit\Components\EvidenceTracker;
use App\Services\Audit\Components\FileScanner;
use App\Services\Audit\Components\QualityGateEnforcer;
use App\Services\Audit\Components\ReproducibilityAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExecuteAuditPhase2 extends Command
{
    protected $signature = 'audit:phase2 {--output=storage/audit-phase2-results.md}';
    protected $description = 'Execute Phase 2 of comprehensive audit: Code Analysis with military precision';

    public function handle()
    {
        $this->info('🔥 PHASE 2 AUDIT ENGAGED - MILITARY PRECISION ACTIVATED');
        $this->info('🎯 TARGET: MedCapsule Laravel Codebase');
        $this->info('🧬 EVIDENCE CHAIN: Discovery → Verification → Quantification → Documentation');
        
        $startTime = microtime(true);
        
        try {
            // Initialize components
            $fileScanner = new FileScanner();
            $evidenceTracker = new EvidenceTracker();
            $qualityEnforcer = new QualityGateEnforcer();
            $reproducibilityAuditor = new ReproducibilityAuditor();
            
            // Execute Task 2.1: Code Analyzer with Complexity Metrics
            $this->info('');
            $this->info('🔍 EXECUTING TASK 2.1: Code Analyzer with Complexity Metrics');
            $this->info('📊 Analyzing: Cyclomatic complexity, maintainability, anti-patterns, PSR-4');
            
            $codeAnalyzer = new CodeAnalyzer($fileScanner);
            $codeAnalysisResult = $codeAnalyzer->analyzeArchitecture(base_path());
            
            $this->info("✅ Code Analysis Complete:");
            $this->info("   📁 Files Analyzed: " . count($fileScanner->getPHPFiles()));
            $this->info("   🔍 Findings: " . count($codeAnalysisResult->getFindings()));
            $this->info("   ⏱️  Execution Time: " . round($codeAnalysisResult->getExecutionTime(), 2) . 's');
            
            // Validate findings with Evidence Tracker
            $this->info('');
            $this->info('🧬 EVIDENCE VALIDATION: Applying False Positive Accountability');
            
            $validatedResult = $evidenceTracker->validatePhaseResults($codeAnalysisResult);
            $evidenceStats = $evidenceTracker->getEvidenceStatistics();
            
            $this->info("✅ Evidence Validation Complete:");
            $this->info("   📊 Evidence Quality Score: " . $evidenceStats['evidence_quality_score'] . '%');
            $this->info("   ✅ Valid Findings: " . $evidenceStats['valid_findings']);
            $this->info("   ❌ Rejected Findings: " . $evidenceStats['rejected_findings']);
            
            // Apply Reproducibility Testing
            $this->info('');
            $this->info('🔄 REPRODUCIBILITY TESTING: Multi-run validation');
            
            $findings = $validatedResult->getFindings();
            foreach ($findings as $finding) {
                $isReproducible = $reproducibilityAuditor->confirmRepeatability($finding);
                $this->line("   " . ($isReproducible ? '✅' : '❌') . " {$finding->getId()}: {$finding->getDescription()}");
            }
            
            $reproStats = $reproducibilityAuditor->getReproducibilityStatistics();
            $this->info("🔄 Reproducibility Score: " . $reproStats['reproducibility_rate'] . '%');
            
            // Generate Phase 2 Report
            $this->info('');
            $this->info('📋 GENERATING PHASE 2 AUDIT REPORT');
            
            $reportContent = $this->generatePhase2Report($validatedResult, $evidenceStats, $reproStats);
            $outputPath = $this->option('output');
            
            file_put_contents($outputPath, $reportContent);
            
            $executionTime = microtime(true) - $startTime;
            
            $this->info('');
            $this->info('🎯 PHASE 2 MISSION ACCOMPLISHED');
            $this->info("📄 Report Generated: {$outputPath}");
            $this->info("⏱️  Total Execution Time: " . round($executionTime, 2) . 's');
            $this->info("🔥 EVIDENCE-BACKED FINDINGS: " . count($findings));
            $this->info("🧬 ZERO FALSE POSITIVES TOLERANCE: MAINTAINED");
            
        } catch (\Exception $e) {
            $this->error('❌ PHASE 2 AUDIT FAILED');
            $this->error('Error: ' . $e->getMessage());
            Log::error('Phase 2 audit failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }
    
    private function generatePhase2Report($phaseResult, $evidenceStats, $reproStats): string
    {
        $findings = $phaseResult->getFindings();
        $metrics = $phaseResult->getMetrics();
        
        $report = "# Phase 2 Audit Report - Code Analysis\n\n";
        $report .= "**Generated**: " . now()->toISOString() . "\n";
        $report .= "**Target**: MedCapsule Laravel Application\n";
        $report .= "**Phase**: 2.1 - Code Analyzer with Complexity Metrics\n\n";
        
        // Executive Summary
        $report .= "## Executive Summary\n\n";
        $report .= "- **Total Findings**: " . count($findings) . "\n";
        $report .= "- **Evidence Quality Score**: {$evidenceStats['evidence_quality_score']}%\n";
        $report .= "- **Reproducibility Score**: {$reproStats['reproducibility_rate']}%\n";
        $report .= "- **Execution Time**: " . round($phaseResult->getExecutionTime(), 2) . "s\n\n";
        
        // Findings by Severity
        $findingsBySeverity = $phaseResult->getFindingsBySeverity();
        $report .= "## Findings by Severity\n\n";
        $report .= "| Severity | Count | Reproducible |\n";
        $report .= "|----------|-------|-------------|\n";
        
        foreach (['critical', 'high', 'medium', 'low'] as $severity) {
            $count = count($findingsBySeverity[$severity]);
            $reproducible = array_reduce($findingsBySeverity[$severity], function($carry, $finding) {
                return $carry + ($finding->isReproducible() ? 1 : 0);
            }, 0);
            
            $severityIcon = match($severity) {
                'critical' => '🔴',
                'high' => '🟠',
                'medium' => '🟡',
                'low' => '🟢'
            };
            
            $report .= "| {$severityIcon} " . ucfirst($severity) . " | {$count} | {$reproducible}/{$count} |\n";
        }
        
        $report .= "\n";
        
        // Detailed Findings
        $report .= "## Detailed Findings\n\n";
        
        foreach ($findings as $finding) {
            $report .= "### {$finding->getId()}: {$finding->getDescription()}\n\n";
            $report .= "- **File**: " . ($finding->getFileReferences()[0]['file'] ?? 'N/A') . "\n";
            $report .= "- **Line**: " . ($finding->getFileReferences()[0]['line'] ?? 'N/A') . "\n";
            $report .= "- **Severity**: {$finding->getSeverityIcon()} " . ucfirst($finding->getSeverity()) . "\n";
            $report .= "- **Reproducible**: {$finding->getReproducibilityIcon()}\n";
            
            // Metrics
            if (!empty($finding->getMetrics())) {
                $report .= "- **Metrics**:\n";
                foreach ($finding->getMetrics() as $metric) {
                    $report .= "  - {$metric['name']}: {$metric['value']} {$metric['unit']}\n";
                }
            }
            
            // Evidence
            if (!empty($finding->getEvidence())) {
                $report .= "- **Evidence**: " . count($finding->getEvidence()) . " pieces of evidence collected\n";
            }
            
            // Recommendation
            if ($finding->getRecommendation()) {
                $report .= "- **Fix**: {$finding->getRecommendation()}\n";
            }
            
            if ($finding->getEstimatedHours() > 0) {
                $report .= "- **Estimated Hours**: {$finding->getEstimatedHours()}h\n";
            }
            
            $report .= "\n";
        }
        
        // Metrics Summary
        $report .= "## Metrics Summary\n\n";
        foreach ($metrics as $metric) {
            $report .= "- **{$metric['name']}**: {$metric['value']} {$metric['unit']}\n";
        }
        
        $report .= "\n";
        
        // Evidence Quality Report
        $report .= "## Evidence Quality Report\n\n";
        $report .= "- **Total Findings Processed**: {$evidenceStats['total_findings']}\n";
        $report .= "- **Valid Findings**: {$evidenceStats['valid_findings']}\n";
        $report .= "- **Rejected Findings**: {$evidenceStats['rejected_findings']}\n";
        $report .= "- **Evidence Quality Score**: {$evidenceStats['evidence_quality_score']}%\n";
        $report .= "- **Validation Rate**: {$evidenceStats['validation_rate']}%\n\n";
        
        // Reproducibility Report
        $report .= "## Reproducibility Report\n\n";
        $report .= "- **Total Tests**: {$reproStats['total_tests']}\n";
        $report .= "- **Reproducible Tests**: {$reproStats['reproducible_tests']}\n";
        $report .= "- **Reproducibility Rate**: {$reproStats['reproducibility_rate']}%\n";
        $report .= "- **Threshold**: {$reproStats['threshold']}%\n";
        $report .= "- **Iterations per Test**: {$reproStats['iterations_per_test']}\n\n";
        
        $report .= "---\n";
        $report .= "*Report generated by MedCapsule Comprehensive Audit System*\n";
        $report .= "*Phase 2.1: Code Analyzer with Military Precision*\n";
        
        return $report;
    }
}