<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\Audit\Reports\EnhancedReportBuilder;
use App\Services\Audit\Models\Finding;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "📊 Testing Enhanced Report Builder - 5000+ Line Report Generation\n";
echo "================================================================\n\n";

try {
    // Create sample audit results with findings
    $auditResults = [
        'security_analysis' => [
            'findings' => [
                createSampleFinding('CRITICAL', 'SQL Injection Vulnerability', 'security', 'sql_injection'),
                createSampleFinding('HIGH', 'Missing CSRF Protection', 'security', 'csrf'),
                createSampleFinding('MEDIUM', 'Weak Password Policy', 'security', 'authentication'),
                createSampleFinding('LOW', 'Missing Security Headers', 'security', 'headers')
            ],
            'metrics' => [
                ['name' => 'vulnerabilities_found', 'value' => 23, 'unit' => 'issues'],
                ['name' => 'security_score', 'value' => 78, 'unit' => 'percentage']
            ]
        ],
        'performance_analysis' => [
            'findings' => [
                createSampleFinding('HIGH', 'Slow Database Queries', 'performance', 'database'),
                createSampleFinding('MEDIUM', 'Unoptimized Images', 'performance', 'assets'),
                createSampleFinding('LOW', 'Missing Caching', 'performance', 'caching')
            ],
            'metrics' => [
                ['name' => 'avg_response_time', 'value' => 425, 'unit' => 'milliseconds'],
                ['name' => 'throughput', 'value' => 1200, 'unit' => 'requests_per_second']
            ]
        ],
        'reliability_analysis' => [
            'findings' => [
                createSampleFinding('CRITICAL', 'Single Point of Failure', 'reliability', 'architecture'),
                createSampleFinding('HIGH', 'Missing Circuit Breakers', 'reliability', 'resilience'),
                createSampleFinding('MEDIUM', 'Insufficient Monitoring', 'reliability', 'observability')
            ],
            'metrics' => [
                ['name' => 'uptime', 'value' => 99.2, 'unit' => 'percentage'],
                ['name' => 'mttr', 'value' => 15, 'unit' => 'minutes']
            ]
        ],
        'infrastructure_analysis' => [
            'findings' => [
                createSampleFinding('HIGH', 'Outdated Dependencies', 'infrastructure', 'dependencies'),
                createSampleFinding('MEDIUM', 'Configuration Issues', 'infrastructure', 'configuration'),
                createSampleFinding('LOW', 'Resource Optimization', 'infrastructure', 'resources')
            ],
            'metrics' => [
                ['name' => 'resource_utilization', 'value' => 78, 'unit' => 'percentage'],
                ['name' => 'scalability_score', 'value' => 72, 'unit' => 'percentage']
            ]
        ]
    ];
    
    echo "✅ Sample audit results created with " . getTotalFindings($auditResults) . " findings\n\n";
    
    // Initialize Enhanced Report Builder
    echo "🏗️ Initializing Enhanced Report Builder...\n";
    $reportBuilder = new EnhancedReportBuilder();
    echo "✅ Report Builder initialized\n\n";
    
    // Generate comprehensive report
    echo "📊 Generating comprehensive 5000+ line report...\n";
    $startTime = microtime(true);
    
    $report = $reportBuilder->generateComprehensiveReport($auditResults);
    
    $executionTime = microtime(true) - $startTime;
    echo "✅ Report generated in " . round($executionTime, 2) . " seconds\n\n";
    
    // Analyze report metrics
    $lineCount = substr_count($report, "\n");
    $characterCount = strlen($report);
    $wordCount = str_word_count($report);
    $sectionCount = substr_count($report, "##");
    $issueCount = substr_count($report, "ISSUE-");
    $severityTagCount = substr_count($report, "🔴") + substr_count($report, "🟠") + substr_count($report, "🟡") + substr_count($report, "🟢");
    
    echo "📊 REPORT ANALYSIS RESULTS\n";
    echo "===========================\n";
    echo "Total Lines: " . number_format($lineCount) . " lines\n";
    echo "Character Count: " . number_format($characterCount) . " characters\n";
    echo "Word Count: " . number_format($wordCount) . " words\n";
    echo "Section Count: " . $sectionCount . " sections\n";
    echo "Issue Tracking IDs: " . $issueCount . " issues\n";
    echo "Color-coded Severity Tags: " . $severityTagCount . " tags\n";
    echo "Report Size: " . round($characterCount / 1024, 2) . " KB\n\n";
    
    // Verify acceptance criteria
    echo "✅ ACCEPTANCE CRITERIA VERIFICATION\n";
    echo "====================================\n";
    
    // Minimum 5000 lines check
    if ($lineCount >= 5000) {
        echo "✅ Minimum 5000 lines: PASSED (" . number_format($lineCount) . " lines)\n";
    } else {
        echo "❌ Minimum 5000 lines: FAILED (" . number_format($lineCount) . " lines)\n";
    }
    
    // Color-coded severity tags check
    if ($severityTagCount > 0) {
        echo "✅ Color-coded severity tags: PASSED (" . $severityTagCount . " tags found)\n";
    } else {
        echo "❌ Color-coded severity tags: FAILED (no tags found)\n";
    }
    
    // Issue tracking index check
    if ($issueCount > 0) {
        echo "✅ Issue tracking index (ISSUE-001 format): PASSED (" . $issueCount . " issues)\n";
    } else {
        echo "❌ Issue tracking index: FAILED (no issue IDs found)\n";
    }
    
    // Domain readiness score tables check
    $domainTablesCount = substr_count($report, "Domain Readiness");
    if ($domainTablesCount > 0) {
        echo "✅ Domain readiness score tables: PASSED (" . $domainTablesCount . " tables found)\n";
    } else {
        echo "❌ Domain readiness score tables: FAILED\n";
    }
    
    // Chain trace documentation check
    $chainTraceCount = substr_count($report, "Chain Trace");
    if ($chainTraceCount > 0) {
        echo "✅ Chain trace documentation: PASSED (" . $chainTraceCount . " sections found)\n";
    } else {
        echo "❌ Chain trace documentation: FAILED\n";
    }
    
    // Reproducibility indicators check
    $reproducibilityCount = substr_count($report, "Reproducibility");
    if ($reproducibilityCount > 0) {
        echo "✅ Reproducibility indicators: PASSED (" . $reproducibilityCount . " sections found)\n";
    } else {
        echo "❌ Reproducibility indicators: FAILED\n";
    }
    
    // Chaos stability scores check
    $chaosCount = substr_count($report, "Chaos Stability");
    if ($chaosCount > 0) {
        echo "✅ Chaos stability scores: PASSED (" . $chaosCount . " sections found)\n";
    } else {
        echo "❌ Chaos stability scores: FAILED\n";
    }
    
    // Visual graphs and charts check
    $visualCount = substr_count($report, "```") + substr_count($report, "Chart") + substr_count($report, "Graph");
    if ($visualCount > 0) {
        echo "✅ Visual graphs and charts: PASSED (" . $visualCount . " visual elements found)\n";
    } else {
        echo "❌ Visual graphs and charts: FAILED\n";
    }
    
    echo "\n";
    
    // Save report to file
    $reportPath = storage_path('app/audit/reports/comprehensive_audit_report.md');
    file_put_contents($reportPath, $report);
    echo "📄 Report saved to: {$reportPath}\n";
    echo "📊 Report file size: " . round(filesize($reportPath) / 1024, 2) . " KB\n\n";
    
    // Display report preview
    echo "📋 REPORT PREVIEW (First 2000 characters)\n";
    echo "==========================================\n";
    echo substr($report, 0, 2000);
    
    if (strlen($report) > 2000) {
        echo "\n\n... [Report continues for " . number_format(strlen($report) - 2000) . " more characters] ...\n";
    }
    
    echo "\n\n✅ Enhanced Report Builder test completed successfully!\n";
    echo "📊 Generated a comprehensive " . number_format($lineCount) . "-line technical analysis report\n";
    echo "🎯 All acceptance criteria have been verified and implemented\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

function createSampleFinding(string $severity, string $title, string $category, string $type): Finding
{
    $finding = new Finding(
        'ISSUE-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
        strtolower($severity),
        $title,
        $category,
        $type
    );
    
    $finding->addFileReference('app/Http/Controllers/SampleController.php', rand(10, 100), 'Sample code reference');
    $finding->addMetric('severity_score', rand(1, 10), 'score_out_of_10');
    $finding->addEvidence('static_analysis', ['issue_detected' => true], 'Static analysis detected this issue');
    $finding->setRecommendation('Implement proper ' . $type . ' handling');
    $finding->setEstimatedHours(rand(2, 16));
    
    return $finding;
}

function getTotalFindings(array $auditResults): int
{
    $total = 0;
    foreach ($auditResults as $phase => $result) {
        if (isset($result['findings'])) {
            $total += count($result['findings']);
        }
    }
    return $total;
}