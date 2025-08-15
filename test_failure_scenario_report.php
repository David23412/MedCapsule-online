<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\Audit\Components\FailSafeSimulator;
use App\Services\Audit\Components\FailSafeScenarioDocumenter;
use App\Services\Audit\Reports\FailureScenarioReportGenerator;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "📋 Testing Failure Scenario Report Generation\n";
echo "==============================================\n\n";

try {
    // Initialize components
    $failSafeSimulator = new FailSafeSimulator();
    $scenarioDocumenter = new FailSafeScenarioDocumenter($failSafeSimulator);
    
    echo "✅ Components initialized successfully\n\n";
    
    // Generate scenario documentation
    echo "📸 Generating scenario documentation...\n";
    $documentation = $scenarioDocumenter->documentAllFailureScenarios();
    echo "✅ Documentation generated\n\n";
    
    // Generate report
    echo "📋 Generating comprehensive report...\n";
    $reportGenerator = new FailureScenarioReportGenerator($documentation);
    $reportSection = $reportGenerator->generateReportSection();
    echo "✅ Report generated\n\n";
    
    // Save report to file
    $reportPath = storage_path('app/audit/failure_scenario_report.md');
    file_put_contents($reportPath, $reportSection);
    
    echo "📄 Report saved to: {$reportPath}\n";
    echo "📊 Report size: " . number_format(strlen($reportSection)) . " characters\n";
    echo "📝 Report lines: " . substr_count($reportSection, "\n") . " lines\n\n";
    
    // Display report preview
    echo "📋 REPORT PREVIEW (First 2000 characters)\n";
    echo "==========================================\n";
    echo substr($reportSection, 0, 2000);
    
    if (strlen($reportSection) > 2000) {
        echo "\n\n... [Report continues for " . (strlen($reportSection) - 2000) . " more characters] ...\n";
    }
    
    echo "\n\n✅ Failure scenario report generation test completed successfully!\n";
    echo "📄 Full report available at: {$reportPath}\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}