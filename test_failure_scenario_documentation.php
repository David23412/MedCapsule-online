<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\Audit\Components\FailSafeSimulator;
use App\Services\Audit\Components\FailSafeScenarioDocumenter;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔥 Testing Failure Scenario Documentation System\n";
echo "================================================\n\n";

try {
    // Initialize components
    $failSafeSimulator = new FailSafeSimulator();
    $scenarioDocumenter = new FailSafeScenarioDocumenter($failSafeSimulator);
    
    echo "✅ Components initialized successfully\n\n";
    
    // Test individual scenario documentation
    echo "📸 Testing individual failure scenarios...\n";
    
    $scenarios = $failSafeSimulator->getFailureScenarios();
    foreach ($scenarios as $scenarioKey => $scenarioConfig) {
        echo "  - Testing {$scenarioKey}... ";
        
        try {
            // This would normally be called internally, but we'll test the simulator directly
            switch ($scenarioKey) {
                case 'queue_overload':
                    $result = $failSafeSimulator->simulateQueueOverload();
                    break;
                case 'database_lock':
                    $result = $failSafeSimulator->simulateDatabaseLock();
                    break;
                case 'disk_exhaustion':
                    $result = $failSafeSimulator->simulateDiskExhaustion();
                    break;
                case 'memory_breach':
                    $result = $failSafeSimulator->simulateMemoryBreach();
                    break;
                case 'cascade_failure':
                    $result = $failSafeSimulator->simulateCascadeFailure();
                    break;
                default:
                    throw new Exception("Unknown scenario: {$scenarioKey}");
            }
            
            $status = $result['success'] ? '✅ SUCCESS' : '❌ FAILED';
            $recoveryTime = $result['recovery_time_ms'] ?? 0;
            echo "{$status} (Recovery: {$recoveryTime}ms)\n";
            
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📸 Testing comprehensive scenario documentation...\n";
    
    // Test comprehensive documentation
    $startTime = microtime(true);
    $documentation = $scenarioDocumenter->documentAllFailureScenarios();
    $executionTime = microtime(true) - $startTime;
    
    echo "✅ Documentation completed in " . round($executionTime, 2) . " seconds\n\n";
    
    // Display results
    echo "📊 DOCUMENTATION RESULTS\n";
    echo "========================\n";
    
    $summary = $documentation['executive_summary'] ?? [];
    echo "Total Scenarios Tested: " . ($summary['total_scenarios_tested'] ?? 0) . "\n";
    echo "Successful Recoveries: " . ($summary['successful_recoveries'] ?? 0) . "\n";
    echo "Average Recovery Time: " . ($summary['average_recovery_time_ms'] ?? 0) . "ms\n";
    echo "Resilience Score: " . ($summary['resilience_score'] ?? 0) . "%\n";
    echo "Screenshots Captured: " . ($documentation['documentation_metadata']['total_screenshots'] ?? 0) . "\n";
    
    if (!empty($summary['critical_findings'])) {
        echo "\n🔴 CRITICAL FINDINGS:\n";
        foreach ($summary['critical_findings'] as $finding) {
            echo "  - {$finding}\n";
        }
    }
    
    // Display scenario details
    echo "\n📋 SCENARIO DETAILS\n";
    echo "===================\n";
    
    $documentedScenarios = $documentation['documented_scenarios'] ?? [];
    foreach ($documentedScenarios as $scenarioKey => $scenarioDoc) {
        echo "\n🔥 {$scenarioKey}:\n";
        
        if (isset($scenarioDoc['simulation_result'])) {
            $result = $scenarioDoc['simulation_result'];
            echo "  Status: " . ($result['success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n";
            echo "  Recovery Time: " . ($result['recovery_time_ms'] ?? 0) . "ms\n";
            
            if (isset($result['error'])) {
                echo "  Error: " . $result['error'] . "\n";
            }
        }
        
        if (isset($scenarioDoc['screenshots'])) {
            echo "  Screenshots: " . count($scenarioDoc['screenshots']) . " captured\n";
            foreach ($scenarioDoc['screenshots'] as $type => $filename) {
                echo "    - {$type}: {$filename}\n";
            }
        }
        
        if (isset($scenarioDoc['recovery_documentation'])) {
            $recovery = $scenarioDoc['recovery_documentation'];
            echo "  Recovery Steps: " . count($recovery['recovery_steps'] ?? []) . "\n";
            echo "  Recovery Successful: " . ($recovery['recovery_successful'] ? 'YES' : 'NO') . "\n";
        }
    }
    
    // Display failure patterns
    echo "\n🔍 FAILURE PATTERNS\n";
    echo "===================\n";
    
    $patterns = $documentation['failure_patterns'] ?? [];
    if (!empty($patterns['common_failure_points'])) {
        echo "Common Failure Points:\n";
        foreach ($patterns['common_failure_points'] as $failure) {
            echo "  - {$failure['scenario']}: {$failure['failure_reason']}\n";
        }
    }
    
    if (!empty($patterns['resilience_patterns'])) {
        $resilience = $patterns['resilience_patterns'];
        echo "\nResilience Patterns:\n";
        echo "  Best Recovery: " . ($resilience['best_recovery_scenario'] ?? 'N/A') . "\n";
        echo "  Worst Recovery: " . ($resilience['worst_recovery_scenario'] ?? 'N/A') . "\n";
        echo "  Successful Scenarios: " . implode(', ', $resilience['successful_scenarios'] ?? []) . "\n";
    }
    
    // Display improvement recommendations
    echo "\n💡 IMPROVEMENT RECOMMENDATIONS\n";
    echo "==============================\n";
    
    $recommendations = $documentation['improvement_recommendations'] ?? [];
    foreach ($recommendations as $recommendation) {
        echo "  - {$recommendation}\n";
    }
    
    // Check screenshot directory
    $screenshotPath = storage_path('app/audit/screenshots');
    if (is_dir($screenshotPath)) {
        $screenshots = glob($screenshotPath . '/*');
        echo "\n📸 SCREENSHOTS DIRECTORY\n";
        echo "========================\n";
        echo "Path: {$screenshotPath}\n";
        echo "Files: " . count($screenshots) . "\n";
        
        if (!empty($screenshots)) {
            echo "Recent screenshots:\n";
            $recentScreenshots = array_slice($screenshots, -5);
            foreach ($recentScreenshots as $screenshot) {
                echo "  - " . basename($screenshot) . "\n";
            }
        }
    }
    
    echo "\n✅ Failure scenario documentation test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}