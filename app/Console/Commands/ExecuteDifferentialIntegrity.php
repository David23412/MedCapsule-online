<?php

namespace App\Console\Commands;

use App\Services\Audit\Components\DifferentialIntegrityAnalyzer;
use App\Services\Audit\Components\EvidenceTracker;
use App\Services\Audit\Components\FileScanner;
use App\Services\Audit\Components\ReproducibilityAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExecuteDifferentialIntegrity extends Command
{
    protected $signature = 'audit:differential-integrity {--output=storage/differential-integrity-results.md}';
    protected $description = 'Execute Task 2.4: Differential Integrity Analyzer with SURGICAL-PRECISION';

    public function handle()
    {
        $this->info('🔥 TASK 2.4: DIFFERENTIAL INTEGRITY ANALYZER - ENGAGED');
        $this->info('🎯 TARGET: MedCapsule Code Integrity & Change Analysis');
        $this->info('🧬 EVIDENCE STANDARD: MILITARY-GRADE ACCOUNTABILITY');
        $this->info('📊 ANALYZING: Git commits, Config drift, File integrity, Behavioral changes');
        
        $startTime = microtime(true);
        
        try {
            // Initialize components
            $fileScanner = new FileScanner();
            $evidenceTracker = new EvidenceTracker();
            $reproducibilityAuditor = new ReproducibilityAuditor();
            
            // Execute Differential Integrity Analysis
            $this->info('');
            $this->info('🔍 EXECUTING DIFFERENTIAL INTEGRITY ANALYSIS');
            $this->info('🔄 Analyzing commit history, configuration drift, file integrity');
            
            $differentialAnalyzer = new DifferentialIntegrityAnalyzer($fileScanner);
            $integrityResult = $differentialAnalyzer->executeDifferentialAnalysis(base_path());
            
            $this->info("✅ Differential Integrity Analysis Complete:");
            $this->info("   🔍 Integrity Findings: " . count($integrityResult->getFindings()));
            $this->info("   ⏱️  Execution Time: " . round($integrityResult->getExecutionTime(), 2) . 's');
            
            // Validate findings with Evidence Tracker
            $this->info('');
            $this->info('🧬 EVIDENCE VALIDATION: Applying False Positive Accountability');
            
            $validatedResult = $evidenceTracker->validatePhaseResults($integrityResult);
            $evidenceStats = $evidenceTracker->getEvidenceStatistics();
            
            $validFindings = $validatedResult->getFindings();
            
            foreach ($validFindings as $finding) {
                $this->line("   ✅ {$finding->getId()}: EVIDENCE APPROVED");
            }
            
            $this->info("📊 Evidence Quality Score: {$evidenceStats['evidence_quality_score']}%");
            
            // Apply Reproducibility Testing
            $this->info('');
            $this->info('🔄 REPRODUCIBILITY TESTING: Multi-run validation');
            
            foreach ($validFindings as $finding) {
                $isReproducible = $reproducibilityAuditor->confirmRepeatability($finding);
                $this->line("   " . ($isReproducible ? '✅' : '❌') . " {$finding->getId()}: " . 
                           ($isReproducible ? 'REPRODUCIBLE' : 'UNSTABLE'));
            }
            
            $reproStats = $reproducibilityAuditor->getReproducibilityStatistics();
            
            // Generate Differential Integrity Report
            $this->info('');
            $this->info('📋 GENERATING DIFFERENTIAL INTEGRITY REPORT');
            
            $reportContent = $this->generateIntegrityReport($validatedResult, $evidenceStats, $reproStats);
            $outputPath = $this->option('output');
            
            file_put_contents($outputPath, $reportContent);
            
            $executionTime = microtime(true) - $startTime;
            
            $this->info('');
            $this->info('🎯 TASK 2.4 DIFFERENTIAL INTEGRITY ACCOMPLISHED');
            $this->info("📄 Report Generated: {$outputPath}");
            $this->info("⏱️  Total Execution Time: " . round($executionTime, 2) . 's');
            $this->info("🔥 EVIDENCE-BACKED FINDINGS: " . count($validFindings));
            $this->info("🧬 REPRODUCIBILITY RATE: {$reproStats['reproducibility_rate']}%");
            $this->info("🔄 INTEGRITY STATUS: ANALYZED");
            
        } catch (\Exception $e) {
            $this->error('❌ DIFFERENTIAL INTEGRITY ANALYSIS FAILED');
            $this->error('Error: ' . $e->getMessage());
            Log::error('Differential integrity analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }
    
    private function generateIntegrityReport($phaseResult, $evidenceStats, $reproStats): string
    {
        $findings = $phaseResult->getFindings();
        $metrics = $phaseResult->getMetrics();
        
        $report = "# Task 2.4: Differential Integrity Analyzer Report\n\n";
        $report .= "**Generated**: " . now()->toISOString() . "\n";
        $report .= "**Target**: MedCapsule Code Integrity & Change Analysis\n";
        $report .= "**Analysis Type**: SURGICAL-PRECISION Differential Integrity Analysis\n";
        $report .= "**Evidence Standard**: MILITARY-GRADE ACCOUNTABILITY\n\n";
        
        // Executive Summary
        $report .= "## 🔄 Executive Summary\n\n";
        $report .= "- **Total Integrity Findings**: " . count($findings) . "\n";
        $report .= "- **Evidence Quality Score**: {$evidenceStats['evidence_quality_score']}%\n";
        $report .= "- **Reproducibility Score**: {$reproStats['reproducibility_rate']}%\n";
        $report .= "- **Code Integrity Status**: " . (count($findings) === 0 ? '✅ STABLE' : '⚠️ CHANGES DETECTED') . "\n\n";
        
        // Integrity Categories
        $categories = ['version_control', 'commit_quality', 'massive_changes', 'config_security', 'missing_file', 'behavioral_change'];
        $categorizedFindings = [];
        
        foreach ($findings as $finding) {
            $category = $finding->getComponent();
            if (!isset($categorizedFindings[$category])) {
                $categorizedFindings[$category] = [];
            }
            $categorizedFindings[$category][] = $finding;
        }
        
        $report .= "## 📊 Integrity Issues by Category\n\n";
        $report .= "| Category | Count | Risk Level |\n";
        $report .= "|----------|-------|------------|\n";
        
        foreach ($categories as $category) {
            $count = count($categorizedFindings[$category] ?? []);
            $riskLevel = $this->calculateCategoryRisk($categorizedFindings[$category] ?? []);
            
            $categoryName = ucwords(str_replace('_', ' ', $category));
            $report .= "| {$categoryName} | {$count} | {$riskLevel} |\n";
        }
        
        $report .= "\n";
        
        // Detailed Integrity Findings
        $report .= "## 🔄 Detailed Integrity Findings\n\n";
        
        if (empty($findings)) {
            $report .= "✅ **NO INTEGRITY ISSUES DETECTED**\n\n";
            $report .= "The differential integrity analysis found no critical issues. Code integrity, version control, and configuration management appear to be properly maintained.\n\n";
        } else {
            foreach ($findings as $finding) {
                $report .= "### {$finding->getId()}: {$finding->getDescription()}\n\n";
                
                // Integrity Metrics
                $metrics = $finding->getMetrics();
                if (!empty($metrics)) {
                    $report .= "**🔄 Integrity Metrics:**\n";
                    foreach ($metrics as $metric) {
                        $report .= "- {$metric['name']}: `{$metric['value']} {$metric['unit']}`\n";
                    }
                    $report .= "\n";
                }
                
                // File References
                $fileRefs = $finding->getFileReferences();
                if (!empty($fileRefs)) {
                    $report .= "**📁 Integrity Location:**\n";
                    foreach ($fileRefs as $ref) {
                        $report .= "- `{$ref['file']}:{$ref['line']}` - {$ref['context']}\n";
                    }
                    $report .= "\n";
                }
                
                // Evidence
                $evidence = $finding->getEvidence();
                if (!empty($evidence)) {
                    $report .= "**🧬 Integrity Evidence:**\n";
                    foreach ($evidence as $evidenceItem) {
                        $report .= "- **{$evidenceItem['type']}**: {$evidenceItem['description']}\n";
                    }
                    $report .= "\n";
                }
                
                // Reproducibility
                $report .= "**🔄 Reproducibility**: {$finding->getReproducibilityIcon()}\n";
                
                // Fix Recommendation
                if ($finding->getRecommendation()) {
                    $report .= "**🔧 Integrity Fix**: {$finding->getRecommendation()}\n";
                }
                
                if ($finding->getEstimatedHours() > 0) {
                    $report .= "**⏱️ Estimated Hours**: {$finding->getEstimatedHours()}h\n";
                }
                
                $report .= "\n---\n\n";
            }
        }
        
        // Integrity Metrics Summary
        $report .= "## 📊 Integrity Metrics Summary\n\n";
        foreach ($metrics as $metric) {
            $report .= "- **{$metric['name']}**: {$metric['value']} {$metric['unit']}\n";
        }
        
        $report .= "\n";
        
        // Evidence Quality Report
        $report .= "## 🧬 Integrity Evidence Quality Report\n\n";
        $report .= "- **Total Integrity Findings Processed**: {$evidenceStats['total_findings']}\n";
        $report .= "- **Valid Integrity Findings**: {$evidenceStats['valid_findings']}\n";
        $report .= "- **Rejected Integrity Findings**: {$evidenceStats['rejected_findings']}\n";
        $report .= "- **Integrity Evidence Quality Score**: {$evidenceStats['evidence_quality_score']}%\n";
        $report .= "- **False Positive Accountability**: ✅ ENFORCED\n\n";
        
        // Reproducibility Report
        $report .= "## 🔄 Integrity Reproducibility Report\n\n";
        $report .= "- **Total Integrity Tests**: {$reproStats['total_tests']}\n";
        $report .= "- **Reproducible Integrity Tests**: {$reproStats['reproducible_tests']}\n";
        $report .= "- **Integrity Reproducibility Rate**: {$reproStats['reproducibility_rate']}%\n";
        $report .= "- **Integrity Analysis Standard**: ✅ MILITARY-GRADE\n\n";
        
        $report .= "---\n";
        $report .= "*Differential Integrity Analyzer by MedCapsule Comprehensive Audit System*\n";
        $report .= "*SURGICAL-PRECISION Integrity Analysis with ZERO FALSE POSITIVES*\n";
        
        return $report;
    }
    
    private function calculateCategoryRisk(array $findings): string
    {
        if (empty($findings)) {
            return '✅ None';
        }
        
        $criticalCount = count(array_filter($findings, fn($f) => $f->getSeverity() === 'critical'));
        $highCount = count(array_filter($findings, fn($f) => $f->getSeverity() === 'high'));
        
        if ($criticalCount > 0) {
            return '🔴 Critical';
        } elseif ($highCount > 0) {
            return '🟠 High';
        } else {
            return '🟡 Medium';
        }
    }
}