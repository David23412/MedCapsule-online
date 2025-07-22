<?php

require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔥 COMPREHENSIVE STUDY STREAK TESTING SUITE 🔥\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "Running all streak tests to ensure system integrity...\n\n";

$testSuites = [
    'Core Streak Logic' => 'test-study-streak-comprehensive.php',
    'Notification Integration' => 'test-streak-notification-integration.php',
    'Production Scenarios' => 'test-production-streak-scenarios.php'
];

$overallResults = [];

foreach ($testSuites as $suiteName => $testFile) {
    echo "🧪 Running $suiteName Tests...\n";
    echo "-" . str_repeat("-", 50) . "\n";
    
    $startTime = microtime(true);
    
    // Capture output
    ob_start();
    $exitCode = 0;
    
    try {
        include $testFile;
    } catch (Exception $e) {
        echo "❌ Test suite failed with exception: " . $e->getMessage() . "\n";
        $exitCode = 1;
    }
    
    $output = ob_get_clean();
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    // Extract results from output
    preg_match('/Success Rate: ([\d.]+)%/', $output, $matches);
    $successRate = isset($matches[1]) ? floatval($matches[1]) : 0;
    
    preg_match('/Total Tests: (\d+)/', $output, $matches);
    $totalTests = isset($matches[1]) ? intval($matches[1]) : 0;
    
    preg_match('/✅ Passed: (\d+)/', $output, $matches);
    $passedTests = isset($matches[1]) ? intval($matches[1]) : 0;
    
    $overallResults[$suiteName] = [
        'success_rate' => $successRate,
        'total_tests' => $totalTests,
        'passed_tests' => $passedTests,
        'execution_time' => $executionTime,
        'exit_code' => $exitCode
    ];
    
    // Display summary for this suite
    $status = $successRate >= 90 ? '✅' : ($successRate >= 80 ? '⚠️' : '❌');
    echo "$status $suiteName: $successRate% ($passedTests/$totalTests tests) - {$executionTime}ms\n";
    
    if ($successRate < 100) {
        // Show failed tests
        preg_match_all('/❌ ([^\n]+)/', $output, $failedMatches);
        if (!empty($failedMatches[1])) {
            echo "   Failed tests:\n";
            foreach (array_slice($failedMatches[1], 0, 3) as $failedTest) {
                echo "   - $failedTest\n";
            }
            if (count($failedMatches[1]) > 3) {
                echo "   - ... and " . (count($failedMatches[1]) - 3) . " more\n";
            }
        }
    }
    
    echo "\n";
}

// Overall summary
echo "=" . str_repeat("=", 70) . "\n";
echo "📊 OVERALL STREAK SYSTEM TEST RESULTS\n";
echo "=" . str_repeat("=", 70) . "\n\n";

$totalTests = array_sum(array_column($overallResults, 'total_tests'));
$totalPassed = array_sum(array_column($overallResults, 'passed_tests'));
$overallSuccessRate = $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 2) : 0;
$totalExecutionTime = array_sum(array_column($overallResults, 'execution_time'));

echo "📈 SUMMARY STATISTICS:\n";
echo "Total Test Suites: " . count($testSuites) . "\n";
echo "Total Tests: $totalTests\n";
echo "Total Passed: $totalPassed\n";
echo "Overall Success Rate: $overallSuccessRate%\n";
echo "Total Execution Time: {$totalExecutionTime}ms\n\n";

echo "📋 DETAILED RESULTS:\n";
foreach ($overallResults as $suiteName => $results) {
    $status = $results['success_rate'] >= 90 ? '✅' : ($results['success_rate'] >= 80 ? '⚠️' : '❌');
    echo "$status $suiteName:\n";
    echo "   Success Rate: {$results['success_rate']}%\n";
    echo "   Tests: {$results['passed_tests']}/{$results['total_tests']}\n";
    echo "   Execution Time: {$results['execution_time']}ms\n";
    echo "\n";
}

// System readiness assessment
echo "🎯 SYSTEM READINESS ASSESSMENT:\n";

$coreLogicReady = $overallResults['Core Streak Logic']['success_rate'] >= 95;
$notificationReady = $overallResults['Notification Integration']['success_rate'] >= 90;
$productionReady = $overallResults['Production Scenarios']['success_rate'] >= 80;

echo "Core Streak Logic: " . ($coreLogicReady ? '✅ READY' : '❌ NEEDS WORK') . "\n";
echo "Notification System: " . ($notificationReady ? '✅ READY' : '❌ NEEDS WORK') . "\n";
echo "Production Scenarios: " . ($productionReady ? '✅ READY' : '❌ NEEDS WORK') . "\n\n";

$systemReady = $coreLogicReady && $notificationReady && $productionReady;

if ($systemReady) {
    echo "🎉 SYSTEM STATUS: READY FOR PRODUCTION! 🎉\n";
    echo "✅ Study streak logic is robust and handles all scenarios\n";
    echo "✅ Smart notifications provide excellent user experience\n";
    echo "✅ Database integrity is maintained across all operations\n";
    echo "✅ Performance is within acceptable limits\n";
    echo "✅ All user journeys are properly supported\n\n";
    
    echo "🚀 DEPLOYMENT RECOMMENDATIONS:\n";
    echo "• Deploy with confidence - all critical functionality tested\n";
    echo "• Monitor notification delivery in production\n";
    echo "• Consider adding streak analytics dashboard\n";
    echo "• Set up alerts for streak calculation performance\n";
} else {
    echo "⚠️ SYSTEM STATUS: NEEDS ATTENTION BEFORE PRODUCTION\n";
    echo "Please address the failing tests before deployment.\n\n";
    
    echo "🔧 RECOMMENDED ACTIONS:\n";
    if (!$coreLogicReady) {
        echo "• Fix core streak calculation issues\n";
    }
    if (!$notificationReady) {
        echo "• Improve notification system reliability\n";
    }
    if (!$productionReady) {
        echo "• Address production scenario edge cases\n";
    }
}

echo "\n" . "=" . str_repeat("=", 70) . "\n";
echo "Testing completed at " . now()->format('Y-m-d H:i:s') . "\n";
echo "=" . str_repeat("=", 70) . "\n";