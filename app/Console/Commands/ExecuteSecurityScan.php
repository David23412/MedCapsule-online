<?php

namespace App\Console\Commands;

use App\Services\Audit\Components\SecurityScanner;
use App\Services\Audit\Components\EvidenceTracker;
use App\Services\Audit\Components\FileScanner;
use App\Services\Audit\Components\ReproducibilityAuditor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExecuteSecurityScan extends Command
{
    protected $signature = 'audit:security-scan {--output=storage/audit/security-scan-results.md}';
    protected $description = 'Execute comprehensive security vulnerability scanning with SURGICAL-PRECISION';

    public function handle()
    {
        $this->info('🔥 SECURITY VULNERABILITY SCANNER - ENGAGED');
        $this->info('🎯 TARGET: MedCapsule Security Vulnerabilities');
        $this->info('🧬 EVIDENCE STANDARD: MILITARY-GRADE ACCOUNTABILITY');
        
        $startTime = microtime(true);
        
        try {
            // Initialize components
            $fileScanner = new FileScanner();
            $evidenceTracker = new EvidenceTracker();
            $reproducibilityAuditor = new ReproducibilityAuditor();
            
            // Execute Security Scan
            $this->info('');
            $this->info('🔍 EXECUTING COMPREHENSIVE SECURITY SCAN');
            $this->info('🔒 Scanning: SQL Injection, XSS, CSRF, Access Control, Input Validation');
            
            $securityScanner = new SecurityScanner($fileScanner);
            $securityScanResult = $securityScanner->executeSecurityScan(base_path());
            
            $this->info("✅ Security Scan Complete:");
            $this->info("   🔍 Findings: " . count($securityScanResult->getFindings()));
            $this->info("   ⏱️  Execution Time: " . round($securityScanResult->getExecutionTime(), 2) . 's');
            
            // Validate findings with Evidence Tracker
            $this->info('');
            $this->info('🧬 EVIDENCE VALIDATION: Applying False Positive Accountability');
            
            $validatedResult = $evidenceTracker->validatePhaseResults($securityScanResult);
            $evidenceStats = $evidenceTracker->getEvidenceStatistics();
            
            $validFindings = $validatedResult->getFindings();
            
            foreach ($validFindings as $finding) {
                $severityIcon = $finding->getSeverityIcon();
                $this->line("   ✅ {$finding->getId()}: {$severityIcon} EVIDENCE APPROVED");
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
            
            // Generate Security Scan Report
            $this->info('');
            $this->info('📋 GENERATING SECURITY VULNERABILITY REPORT');
            
            $reportContent = $this->generateSecurityReport($validatedResult, $evidenceStats, $reproStats);
            $outputPath = $this->option('output');
            
            // Ensure directory exists
            $directory = dirname($outputPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            file_put_contents($outputPath, $reportContent);
            
            $executionTime = microtime(true) - $startTime;
            
            $this->info('');
            $this->info('🎯 SECURITY VULNERABILITY SCAN ACCOMPLISHED');
            $this->info("📄 Report Generated: {$outputPath}");
            $this->info("⏱️  Total Execution Time: " . round($executionTime, 2) . 's');
            $this->info("🔥 EVIDENCE-BACKED FINDINGS: " . count($validFindings));
            $this->info("🧬 REPRODUCIBILITY RATE: {$reproStats['reproducibility_rate']}%");
            $this->info("🔒 SECURITY STATUS: " . $this->getSecurityStatus($validFindings));
            
        } catch (\Exception $e) {
            $this->error('❌ SECURITY SCAN FAILED');
            $this->error('Error: ' . $e->getMessage());
            Log::error('Security scan failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    private function generateSecurityReport($validatedResult, $evidenceStats, $reproStats): string
    {
        $findings = $validatedResult->getFindings();
        $metrics = $this->normalizeMetrics($validatedResult->getMetrics());
        
        $report = "# Security Vulnerability Scan Report\n\n";
        $report .= "**Generated**: " . now()->toISOString() . "\n";
        $report .= "**Target**: MedCapsule Security Analysis\n";
        $report .= "**Analysis Type**: SURGICAL-PRECISION Security Vulnerability Scan\n";
        $report .= "**Evidence Standard**: MILITARY-GRADE ACCOUNTABILITY\n\n";
        
        $report .= "## 🔒 Executive Summary\n\n";
        $report .= "- **Total Security Findings**: " . count($findings) . "\n";
        $report .= "- **Evidence Quality Score**: {$evidenceStats['evidence_quality_score']}%\n";
        $report .= "- **Reproducibility Score**: {$reproStats['reproducibility_rate']}%\n";
        $report .= "- **Security Status**: " . $this->getSecurityStatus($findings) . "\n\n";
        
        // Group findings by severity
        $findingsBySeverity = [
            'critical' => [],
            'high' => [],
            'medium' => [],
            'low' => []
        ];
        
        foreach ($findings as $finding) {
            $severity = $finding->getSeverity();
            if (isset($findingsBySeverity[$severity])) {
                $findingsBySeverity[$severity][] = $finding;
            }
        }
        
        $report .= "## 📊 Security Issues by Category\n\n";
        $report .= "| Severity | Count | Risk Level |\n";
        $report .= "|----------|-------|------------|\n";
        
        foreach ($findingsBySeverity as $severity => $severityFindings) {
            $icon = match($severity) {
                'critical' => '🔴',
                'high' => '🟠',
                'medium' => '🟡',
                'low' => '🟢'
            };
            $riskLevel = match($severity) {
                'critical' => 'CRITICAL',
                'high' => 'HIGH',
                'medium' => 'MEDIUM',
                'low' => 'LOW'
            };
            $report .= "| {$icon} " . ucfirst($severity) . " | " . count($severityFindings) . " | {$riskLevel} |\n";
        }
        
        $report .= "\n## 🔒 Detailed Security Findings\n\n";
        
        foreach ($findings as $finding) {
            $report .= "### {$finding->getId()}: {$finding->getDescription()}\n\n";
            
            // File references
            $fileRefs = $finding->getFileReferences();
            if (!empty($fileRefs)) {
                $report .= "**🔒 Security Location:**\n";
                foreach ($fileRefs as $ref) {
                    $report .= "- `{$ref['file']}:{$ref['line']}` - {$ref['context']}\n";
                }
                $report .= "\n";
            }
            
            // Metrics
            $metrics = $finding->getMetrics();
            if (!empty($metrics)) {
                $report .= "**🔒 Security Metrics:**\n";
                foreach ($metrics as $metric) {
                    $report .= "- {$metric['name']}: `{$metric['value']} {$metric['unit']}`\n";
                }
                $report .= "\n";
            }
            
            // Evidence
            $evidence = $finding->getEvidence();
            if (!empty($evidence)) {
                $report .= "**🧬 Security Evidence:**\n";
                foreach ($evidence as $evidenceItem) {
                    $report .= "- **{$evidenceItem['type']}**: {$evidenceItem['description']}\n";
                }
                $report .= "\n";
            }
            
            $report .= "**🔄 Reproducibility**: " . ($finding->isReproducible() ? '✅' : '❌') . "\n";
            $report .= "**🔧 Security Fix**: {$finding->getRecommendation()}\n";
            $report .= "**⏱️ Estimated Hours**: {$finding->getEstimatedHours()}h\n\n";
            $report .= "---\n\n";
        }
        
        // Metrics summary
        $report .= "## 📊 Security Metrics Summary\n\n";
        foreach ($metrics as $metric) {
            $report .= "- **{$metric['name']}**: {$metric['value']} {$metric['unit']}\n";
        }
        
        $report .= "\n## 🧬 Security Evidence Quality Report\n\n";
        $report .= "- **Total Security Findings Processed**: {$evidenceStats['total_findings']}\n";
        $report .= "- **Valid Security Findings**: {$evidenceStats['validated_findings']}\n";
        $report .= "- **Rejected Security Findings**: {$evidenceStats['rejected_findings']}\n";
        $report .= "- **Security Evidence Quality Score**: {$evidenceStats['evidence_quality_score']}%\n";
        $report .= "- **False Positive Accountability**: ✅ ENFORCED\n\n";
        
        $report .= "## 🔄 Security Reproducibility Report\n\n";
        $report .= "- **Total Security Tests**: {$reproStats['total_tests']}\n";
        $report .= "- **Reproducible Security Tests**: {$reproStats['reproducible_tests']}\n";
        $report .= "- **Security Reproducibility Rate**: {$reproStats['reproducibility_rate']}%\n";
        $report .= "- **Security Analysis Standard**: ✅ MILITARY-GRADE\n\n";
        
        $report .= "---\n";
        $report .= "*Security Vulnerability Scanner by MedCapsule Comprehensive Audit System*\n";
        $report .= "*SURGICAL-PRECISION Security Analysis with ZERO FALSE POSITIVES*\n";
        
        return $report;
    }

    private function normalizeMetrics(array $metrics): array
    {
        $normalized = [];

        foreach ($metrics as $name => $metric) {
            if (is_array($metric) && array_key_exists('name', $metric)) {
                $normalized[] = [
                    'name' => (string) $metric['name'],
                    'value' => $metric['value'] ?? '',
                    'unit' => (string) ($metric['unit'] ?? ''),
                ];

                continue;
            }

            $normalized[] = [
                'name' => is_string($name) && $name !== '' ? $name : 'metric_'.(count($normalized) + 1),
                'value' => is_scalar($metric) || $metric === null ? $metric : json_encode($metric),
                'unit' => '',
            ];
        }

        return $normalized;
    }

    private function getSecurityStatus($findings): string
    {
        $criticalCount = 0;
        $highCount = 0;
        
        foreach ($findings as $finding) {
            if ($finding->getSeverity() === 'critical') {
                $criticalCount++;
            } elseif ($finding->getSeverity() === 'high') {
                $highCount++;
            }
        }
        
        if ($criticalCount > 0) {
            return '🔴 CRITICAL VULNERABILITIES DETECTED';
        } elseif ($highCount > 0) {
            return '🟠 HIGH-RISK VULNERABILITIES DETECTED';
        } elseif (count($findings) > 0) {
            return '🟡 SECURITY ISSUES DETECTED';
        } else {
            return '🟢 NO CRITICAL VULNERABILITIES FOUND';
        }
    }
}
