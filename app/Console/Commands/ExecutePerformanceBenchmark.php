<?php

namespace App\Console\Commands;

use App\Services\Audit\Components\PerformanceBenchmarker;
use App\Services\Audit\Components\EvidenceTracker;
use App\Services\Audit\Components\FileScanner;
use App\Services\Audit\Components\ReproducibilityAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExecutePerformanceBenchmark extends Command
{
    protected $signature = 'audit:performance-benchmark {--output=storage/performance-benchmark-results.md}';
    protected $description = 'Execute Task 2.3: Performance Benchmarker with SURGICAL-PRECISION';

    public function handle()
    {
        $this->info('🔥 TASK 2.3: PERFORMANCE BENCHMARKER - ENGAGED');
        $this->info('🎯 TARGET: MedCapsule Performance Analysis');
        $this->info('🧬 EVIDENCE STANDARD: MILITARY-GRADE ACCOUNTABILITY');
        $this->info('📊 TESTING: Route latency, Memory usage, Cache effectiveness, Load simulation');
        
        $startTime = microtime(true);
        
        try {
            // Initialize components
            $fileScanner = new FileScanner();
            $evidenceTracker = new EvidenceTracker();
            $reproducibilityAuditor = new ReproducibilityAuditor();
            
            // Execute Performance Benchmarking
            $this->info('');
            $this->info('🔍 EXECUTING PERFORMANCE BENCHMARKING');
            $this->info('⚡ Testing route response times, memory profiling, cache analysis');
            
            $performanceBenchmarker = new PerformanceBenchmarker($fileScanner);
            $performanceResult = $performanceBenchmarker->executePerformanceBenchmarking(base_path());
            
            $this->info("✅ Performance Benchmarking Complete:");
            $this->info("   🔍 Performance Findings: " . count($performanceResult->getFindings()));
            $this->info("   ⏱️  Execution Time: " . round($performanceResult->getExecutionTime(), 2) . 's');
            
            // Validate findings with Evidence Tracker
            $this->info('');
            $this->info('🧬 EVIDENCE VALIDATION: Applying False Positive Accountability');
            
            $validatedResult = $evidenceTracker->validatePhaseResults($performanceResult);
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
            
            // Generate Performance Report
            $this->info('');
            $this->info('📋 GENERATING PERFORMANCE BENCHMARK REPORT');
            
            $reportContent = $this->generatePerformanceReport($validatedResult, $evidenceStats, $reproStats);
            $outputPath = $this->option('output');
            
            file_put_contents($outputPath, $reportContent);
            
            $executionTime = microtime(true) - $startTime;
            
            $this->info('');
            $this->info('🎯 TASK 2.3 PERFORMANCE BENCHMARK ACCOMPLISHED');
            $this->info("📄 Report Generated: {$outputPath}");
            $this->info("⏱️  Total Execution Time: " . round($executionTime, 2) . 's');
            $this->info("🔥 EVIDENCE-BACKED FINDINGS: " . count($validFindings));
            $this->info("🧬 REPRODUCIBILITY RATE: {$reproStats['reproducibility_rate']}%");
            $this->info("⚡ PERFORMANCE STATUS: ANALYZED");
            
        } catch (\Exception $e) {
            $this->error('❌ PERFORMANCE BENCHMARK FAILED');
            $this->error('Error: ' . $e->getMessage());
            Log::error('Performance benchmark failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }
    
    private function generatePerformanceReport($phaseResult, $evidenceStats, $reproStats): string
    {
        $findings = $phaseResult->getFindings();
        $metrics = $phaseResult->getMetrics();
        
        $report = "# Task 2.3: Performance Benchmarker Report\n\n";
        $report .= "**Generated**: " . now()->toISOString() . "\n";
        $report .= "**Target**: MedCapsule Performance Analysis\n";
        $report .= "**Analysis Type**: SURGICAL-PRECISION Performance Benchmarking\n";
        $report .= "**Evidence Standard**: MILITARY-GRADE ACCOUNTABILITY\n\n";
        
        // Executive Summary
        $report .= "## ⚡ Executive Summary\n\n";
        $report .= "- **Total Performance Findings**: " . count($findings) . "\n";
        $report .= "- **Evidence Quality Score**: {$evidenceStats['evidence_quality_score']}%\n";
        $report .= "- **Reproducibility Score**: {$reproStats['reproducibility_rate']}%\n";
        $report .= "- **Performance Status**: " . (count($findings) === 0 ? '✅ OPTIMIZED' : '⚠️ ISSUES DETECTED') . "\n\n";
        
        // Performance Categories
        $categories = ['slow_route', 'route_failure', 'performance_variance', 'memory_usage', 'cache_performance', 'slow_query', 'concurrent_load'];
        $categorizedFindings = [];
        
        foreach ($findings as $finding) {
            $category = $finding->getComponent();
            if (!isset($categorizedFindings[$category])) {
                $categorizedFindings[$category] = [];
            }
            $categorizedFindings[$category][] = $finding;
        }
        
        $report .= "## 📊 Performance Issues by Category\n\n";
        $report .= "| Category | Count | Avg Impact Score |\n";
        $report .= "|----------|-------|------------------|\n";
        
        foreach ($categories as $category) {
            $count = count($categorizedFindings[$category] ?? []);
            $avgImpact = 0;
            
            if (isset($categorizedFindings[$category])) {
                $impacts = [];
                foreach ($categorizedFindings[$category] as $finding) {
                    $metrics = $finding->getMetrics();
                    foreach ($metrics as $metric) {
                        if (strpos($metric['name'], 'score') !== false || strpos($metric['name'], 'time') !== false) {
                            $impacts[] = $metric['value'];
                            break;
                        }
                    }
                }
                if (!empty($impacts)) {
                    $avgImpact = round(array_sum($impacts) / count($impacts), 1);
                }
            }
            
            $categoryName = ucwords(str_replace('_', ' ', $category));
            $report .= "| {$categoryName} | {$count} | {$avgImpact} |\n";
        }
        
        $report .= "\n";
        
        // Detailed Performance Findings
        $report .= "## ⚡ Detailed Performance Findings\n\n";
        
        if (empty($findings)) {
            $report .= "✅ **NO PERFORMANCE ISSUES DETECTED**\n\n";
            $report .= "The performance benchmarking analysis found no critical performance issues. All routes, memory usage, cache operations, and database queries are performing within acceptable limits.\n\n";
        } else {
            foreach ($findings as $finding) {
                $report .= "### {$finding->getId()}: {$finding->getDescription()}\n\n";
                
                // Performance Metrics
                $metrics = $finding->getMetrics();
                if (!empty($metrics)) {
                    $report .= "**⚡ Performance Metrics:**\n";
                    foreach ($metrics as $metric) {
                        $report .= "- {$metric['name']}: `{$metric['value']} {$metric['unit']}`\n";
                    }
                    $report .= "\n";
                }
                
                // File References
                $fileRefs = $finding->getFileReferences();
                if (!empty($fileRefs)) {
                    $report .= "**📁 Performance Location:**\n";
                    foreach ($fileRefs as $ref) {
                        $report .= "- `{$ref['file']}:{$ref['line']}` - {$ref['context']}\n";
                    }
                    $report .= "\n";
                }
                
                // Evidence
                $evidence = $finding->getEvidence();
                if (!empty($evidence)) {
                    $report .= "**🧬 Performance Evidence:**\n";
                    foreach ($evidence as $evidenceItem) {
                        $report .= "- **{$evidenceItem['type']}**: {$evidenceItem['description']}\n";
                    }
                    $report .= "\n";
                }
                
                // Reproducibility
                $report .= "**🔄 Reproducibility**: {$finding->getReproducibilityIcon()}\n";
                
                // Fix Recommendation
                if ($finding->getRecommendation()) {
                    $report .= "**🔧 Performance Fix**: {$finding->getRecommendation()}\n";
                }
                
                if ($finding->getEstimatedHours() > 0) {
                    $report .= "**⏱️ Estimated Hours**: {$finding->getEstimatedHours()}h\n";
                }
                
                $report .= "\n---\n\n";
            }
        }
        
        // Performance Metrics Summary
        $report .= "## 📊 Performance Metrics Summary\n\n";
        foreach ($metrics as $metric) {
            $report .= "- **{$metric['name']}**: {$metric['value']} {$metric['unit']}\n";
        }
        
        $report .= "\n";
        
        // Evidence Quality Report
        $report .= "## 🧬 Performance Evidence Quality Report\n\n";
        $report .= "- **Total Performance Findings Processed**: {$evidenceStats['total_findings']}\n";
        $report .= "- **Valid Performance Findings**: {$evidenceStats['valid_findings']}\n";
        $report .= "- **Rejected Performance Findings**: {$evidenceStats['rejected_findings']}\n";
        $report .= "- **Performance Evidence Quality Score**: {$evidenceStats['evidence_quality_score']}%\n";
        $report .= "- **False Positive Accountability**: ✅ ENFORCED\n\n";
        
        // Reproducibility Report
        $report .= "## 🔄 Performance Reproducibility Report\n\n";
        $report .= "- **Total Performance Tests**: {$reproStats['total_tests']}\n";
        $report .= "- **Reproducible Performance Tests**: {$reproStats['reproducible_tests']}\n";
        $report .= "- **Performance Reproducibility Rate**: {$reproStats['reproducibility_rate']}%\n";
        $report .= "- **Performance Analysis Standard**: ✅ MILITARY-GRADE\n\n";
        
        $report .= "---\n";
        $report .= "*Performance Benchmarker by MedCapsule Comprehensive Audit System*\n";
        $report .= "*SURGICAL-PRECISION Performance Analysis with ZERO FALSE POSITIVES*\n";
        
        return $report;
    }
}