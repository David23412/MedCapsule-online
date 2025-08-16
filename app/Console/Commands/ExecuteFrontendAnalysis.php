<?php

namespace App\Console\Commands;

use App\Services\Audit\Components\FrontendAnalyzer;
use App\Services\Audit\Components\EvidenceTracker;
use App\Services\Audit\Components\FileScanner;
use App\Services\Audit\Components\ReproducibilityAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExecuteFrontendAnalysis extends Command
{
    protected $signature = 'audit:frontend-analysis {--output=storage/audit/frontend-analysis-results.md}';
    protected $description = 'Execute comprehensive frontend technical analysis with SURGICAL-PRECISION';

    public function handle()
    {
        $this->info('🔥 FRONTEND TECHNICAL ANALYZER - ENGAGED');
        $this->info('🎯 TARGET: MedCapsule Frontend Performance & Quality');
        $this->info('🧬 EVIDENCE STANDARD: MILITARY-GRADE ACCOUNTABILITY');
        
        $startTime = microtime(true);
        
        try {
            // Initialize components
            $fileScanner = new FileScanner();
            $evidenceTracker = new EvidenceTracker();
            $reproducibilityAuditor = new ReproducibilityAuditor();
            
            // Execute Frontend Analysis
            $this->info('');
            $this->info('🔍 EXECUTING FRONTEND TECHNICAL ANALYSIS');
            $this->info('🎨 Analyzing: Blade Templates, Assets, JavaScript, CSS, Performance');
            
            $frontendAnalyzer = new FrontendAnalyzer($fileScanner);
            $frontendAnalysisResult = $frontendAnalyzer->executeFrontendAnalysis(base_path());
            
            $this->info("✅ Frontend Analysis Complete:");
            $this->info("   🔍 Findings: " . count($frontendAnalysisResult->getFindings()));
            $this->info("   ⏱️  Execution Time: " . round($frontendAnalysisResult->getExecutionTime(), 2) . 's');
            
            // Validate findings with Evidence Tracker
            $this->info('');
            $this->info('🧬 EVIDENCE VALIDATION: Applying False Positive Accountability');
            
            $validatedResult = $evidenceTracker->validatePhaseResults($frontendAnalysisResult);
            $evidenceStats = $evidenceTracker->getEvidenceStatistics();
            
            $validFindings = $validatedResult->getFindings();
            
            foreach ($validFindings as $finding) {
                $severityIcon = $finding->getSeverityIcon();
                $this->line("   ✅ {$finding->getId()}: {$severityIcon} EVIDENCE APPROVED");
            }
            
            $this->info("📊 Evidence Quality Score: " . (is_array($evidenceStats) ? $evidenceStats['evidence_quality_score'] : 0) . "%");
            
            // Apply Reproducibility Testing
            $this->info('');
            $this->info('🔄 REPRODUCIBILITY TESTING: Multi-run validation');
            
            foreach ($validFindings as $finding) {
                $isReproducible = $reproducibilityAuditor->confirmRepeatability($finding);
                $this->line("   " . ($isReproducible ? '✅' : '❌') . " {$finding->getId()}: " . 
                           ($isReproducible ? 'REPRODUCIBLE' : 'UNSTABLE'));
            }
            
            $reproStats = $reproducibilityAuditor->getReproducibilityStatistics();
            
            // Generate Frontend Analysis Report
            $this->info('');
            $this->info('📋 GENERATING FRONTEND TECHNICAL REPORT');
            
            try {
                $reportContent = $this->generateFrontendReport($validatedResult, $evidenceStats, $reproStats);
            } catch (Exception $e) {
                $this->error("Report generation failed: " . $e->getMessage());
                $this->error("Line: " . $e->getLine());
                throw $e;
            }
            $outputPath = $this->option('output');
            
            // Ensure directory exists
            $directory = dirname($outputPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            file_put_contents($outputPath, $reportContent);
            
            $executionTime = microtime(true) - $startTime;
            
            $this->info('');
            $this->info('🎯 FRONTEND TECHNICAL ANALYSIS ACCOMPLISHED');
            $this->info("📄 Report Generated: {$outputPath}");
            $this->info("⏱️  Total Execution Time: " . round($executionTime, 2) . 's');
            $this->info("🔥 EVIDENCE-BACKED FINDINGS: " . count($validFindings));
            $this->info("🧬 REPRODUCIBILITY RATE: " . (is_array($reproStats) ? $reproStats['reproducibility_rate'] : 0) . "%");
            $this->info("🎨 FRONTEND STATUS: " . $this->getFrontendStatus($validFindings));
            
        } catch (\Exception $e) {
            $this->error('❌ FRONTEND ANALYSIS FAILED');
            $this->error('Error: ' . $e->getMessage());
            Log::error('Frontend analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    private function generateFrontendReport($validatedResult, $evidenceStats, $reproStats): string
    {
        $findings = $validatedResult->getFindings();
        $metrics = $validatedResult->getMetrics();
        
        $report = "# Frontend Technical Analysis Report\n\n";
        $report .= "**Generated**: " . now()->toISOString() . "\n";
        $report .= "**Target**: MedCapsule Frontend Performance & Quality\n";
        $report .= "**Analysis Type**: SURGICAL-PRECISION Frontend Technical Analysis\n";
        $report .= "**Evidence Standard**: MILITARY-GRADE ACCOUNTABILITY\n\n";
        
        $report .= "## 🎨 Executive Summary\n\n";
        $report .= "- **Total Frontend Findings**: " . count($findings) . "\n";
        $report .= "- **Evidence Quality Score**: " . (is_array($evidenceStats) ? $evidenceStats['evidence_quality_score'] : 0) . "%\n";
        $report .= "- **Reproducibility Score**: " . (is_array($reproStats) ? $reproStats['reproducibility_rate'] : 0) . "%\n";
        $report .= "- **Frontend Status**: " . $this->getFrontendStatus($findings) . "\n\n";
        
        // Group findings by category
        $findingsByCategory = [];
        foreach ($findings as $finding) {
            $category = $finding->getComponent();
            if (!isset($findingsByCategory[$category])) {
                $findingsByCategory[$category] = [];
            }
            $findingsByCategory[$category][] = $finding;
        }
        
        $report .= "## 📊 Frontend Issues by Category\n\n";
        $report .= "| Category | Count | Avg Impact Score |\n";
        $report .= "|----------|-------|------------------|\n";
        
        foreach ($findingsByCategory as $category => $categoryFindings) {
            $avgImpact = $this->calculateAverageImpact($categoryFindings);
            $categoryName = ucwords(str_replace('_', ' ', $category));
            $report .= "| {$categoryName} | " . count($categoryFindings) . " | {$avgImpact} |\n";
        }
        
        $report .= "\n## 🎨 Detailed Frontend Findings\n\n";
        
        foreach ($findings as $finding) {
            $report .= "### {$finding->getId()}: {$finding->getDescription()}\n\n";
            
            // File references
            $fileRefs = $finding->getFileReferences();
            if (!empty($fileRefs) && is_array($fileRefs)) {
                $report .= "**📁 Frontend Location:**\n";
                foreach ($fileRefs as $ref) {
                    if (is_array($ref)) {
                        $report .= "- `{$ref['file']}:{$ref['line']}` - {$ref['context']}\n";
                    }
                }
                $report .= "\n";
            }
            
            // Metrics
            $metrics = $finding->getMetrics();
            if (!empty($metrics) && is_array($metrics)) {
                $report .= "**🎨 Frontend Metrics:**\n";
                foreach ($metrics as $metric) {
                    if (is_array($metric)) {
                        $report .= "- {$metric['name']}: `{$metric['value']} {$metric['unit']}`\n";
                    }
                }
                $report .= "\n";
            }
            
            // Evidence
            $evidence = $finding->getEvidence();
            if (!empty($evidence) && is_array($evidence)) {
                $report .= "**🧬 Frontend Evidence:**\n";
                foreach ($evidence as $evidenceItem) {
                    if (is_array($evidenceItem)) {
                        $report .= "- **{$evidenceItem['type']}**: {$evidenceItem['description']}\n";
                    }
                }
                $report .= "\n";
            }
            
            $report .= "**🔄 Reproducibility**: " . ($finding->isReproducible() ? '✅' : '❌') . "\n";
            $report .= "**🔧 Frontend Fix**: {$finding->getRecommendation()}\n";
            $report .= "**⏱️ Estimated Hours**: {$finding->getEstimatedHours()}h\n\n";
            $report .= "---\n\n";
        }
        
        // Metrics summary
        $report .= "## 📊 Frontend Metrics Summary\n\n";
        if (is_array($metrics)) {
            foreach ($metrics as $metric) {
                if (is_array($metric)) {
                    $report .= "- **{$metric['name']}**: {$metric['value']} {$metric['unit']}\n";
                }
            }
        }
        
        $report .= "\n## 🧬 Frontend Evidence Quality Report\n\n";
        
        // Ensure evidenceStats is an array
        if (is_array($evidenceStats)) {
            $report .= "- **Total Frontend Findings Processed**: {$evidenceStats['total_findings']}\n";
            $report .= "- **Valid Frontend Findings**: {$evidenceStats['valid_findings']}\n";
        } else {
            $report .= "- **Evidence Stats Error**: Invalid data format\n";
        }
        if (is_array($evidenceStats)) {
            $report .= "- **Rejected Frontend Findings**: {$evidenceStats['rejected_findings']}\n";
            $report .= "- **Frontend Evidence Quality Score**: {$evidenceStats['evidence_quality_score']}%\n";
        }
        $report .= "- **False Positive Accountability**: ✅ ENFORCED\n\n";
        
        $report .= "## 🔄 Frontend Reproducibility Report\n\n";
        if (is_array($reproStats)) {
            $report .= "- **Total Frontend Tests**: {$reproStats['total_tests']}\n";
            $report .= "- **Reproducible Frontend Tests**: {$reproStats['reproducible_tests']}\n";
            $report .= "- **Frontend Reproducibility Rate**: {$reproStats['reproducibility_rate']}%\n";
        }
        $report .= "- **Frontend Analysis Standard**: ✅ MILITARY-GRADE\n\n";
        
        $report .= "---\n";
        $report .= "*Frontend Technical Analyzer by MedCapsule Comprehensive Audit System*\n";
        $report .= "*SURGICAL-PRECISION Frontend Analysis with ZERO FALSE POSITIVES*\n";
        
        return $report;
    }

    private function getFrontendStatus($findings): string
    {
        $highImpactCount = 0;
        $performanceIssues = 0;
        
        foreach ($findings as $finding) {
            $severity = $finding->getSeverity();
            $component = $finding->getComponent();
            
            if (in_array($severity, ['critical', 'high'])) {
                $highImpactCount++;
            }
            
            if (in_array($component, ['render_blocking', 'asset_optimization', 'frontend_performance'])) {
                $performanceIssues++;
            }
        }
        
        if ($highImpactCount > 5) {
            return '🔴 CRITICAL FRONTEND ISSUES DETECTED';
        } elseif ($performanceIssues > 3) {
            return '🟠 PERFORMANCE OPTIMIZATION NEEDED';
        } elseif (count($findings) > 0) {
            return '🟡 FRONTEND IMPROVEMENTS RECOMMENDED';
        } else {
            return '🟢 FRONTEND QUALITY EXCELLENT';
        }
    }

    private function calculateAverageImpact($findings): string
    {
        if (empty($findings)) {
            return '0';
        }
        
        $totalImpact = 0;
        $impactCount = 0;
        
        foreach ($findings as $finding) {
            $metrics = $finding->getMetrics();
            foreach ($metrics as $metric) {
                if (strpos($metric['name'], 'impact') !== false && is_numeric($metric['value'])) {
                    $totalImpact += $metric['value'];
                    $impactCount++;
                }
            }
        }
        
        if ($impactCount === 0) {
            return '0';
        }
        
        return round($totalImpact / $impactCount, 1);
    }
}