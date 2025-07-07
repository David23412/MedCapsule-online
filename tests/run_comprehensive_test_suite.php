<?php

/**
 * Comprehensive Test Suite Runner
 * 
 * This script runs all tests and provides detailed reporting on:
 * - System Stability
 * - Security Vulnerabilities
 * - HTTPS Compliance
 * - Database Integrity
 * - Performance Metrics
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 MEDCAPSULE COMPREHENSIVE TEST SUITE\n";
echo "=====================================\n\n";

// Test categories
$testCategories = [
    'Security' => [
        'tests/Unit/SecurityTest.php',
        'tests/Feature/HTTPSecurityTest.php'
    ],
    'System Stability' => [
        'tests/Feature/SystemStabilityTest.php'
    ],
    'Database Integrity' => [
        'tests/Feature/DatabaseIntegrityTest.php'
    ],
    'Services' => [
        'tests/Unit/QuizServiceTest.php',
        'tests/Unit/GradingServiceTest.php',
        'tests/Unit/WrittenAnswerEvaluationServiceTest.php'
    ],
    'Integration' => [
        'tests/Feature/QuizControllerIntegrationTest.php'
    ]
];

$results = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$startTime = microtime(true);

foreach ($testCategories as $category => $testFiles) {
    echo "📋 Testing: $category\n";
    echo str_repeat('-', strlen($category) + 10) . "\n";
    
    $categoryResults = [];
    
    foreach ($testFiles as $testFile) {
        if (!file_exists($testFile)) {
            echo "⚠️  Test file not found: $testFile\n";
            continue;
        }
        
        $className = basename($testFile, '.php');
        echo "  Running: $className... ";
        
        try {
            // Run the test using Artisan
            $output = shell_exec("cd " . __DIR__ . "/.. && php artisan test $testFile --stop-on-failure 2>&1");
            
            if (strpos($output, 'PASS') !== false || strpos($output, 'OK') !== false) {
                echo "✅ PASSED\n";
                $categoryResults[] = ['file' => $testFile, 'status' => 'PASSED'];
                $passedTests++;
            } else {
                echo "❌ FAILED\n";
                $categoryResults[] = ['file' => $testFile, 'status' => 'FAILED', 'output' => $output];
                $failedTests++;
            }
            
            $totalTests++;
            
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            $categoryResults[] = ['file' => $testFile, 'status' => 'ERROR', 'error' => $e->getMessage()];
            $failedTests++;
            $totalTests++;
        }
    }
    
    $results[$category] = $categoryResults;
    echo "\n";
}

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

// Generate comprehensive report
echo "📊 COMPREHENSIVE TEST REPORT\n";
echo "============================\n\n";

echo "⏱️  Execution Time: {$executionTime}s\n";
echo "📈 Total Tests: $totalTests\n";
echo "✅ Passed: $passedTests\n";
echo "❌ Failed: $failedTests\n";
echo "📊 Success Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";

// Detailed results by category
foreach ($results as $category => $categoryResults) {
    echo "🔍 $category Results:\n";
    echo str_repeat('-', strlen($category) + 9) . "\n";
    
    $categoryPassed = 0;
    $categoryFailed = 0;
    
    foreach ($categoryResults as $result) {
        $status = $result['status'];
        $file = basename($result['file']);
        
        if ($status === 'PASSED') {
            echo "  ✅ $file\n";
            $categoryPassed++;
        } else {
            echo "  ❌ $file\n";
            $categoryFailed++;
            
            if (isset($result['output'])) {
                echo "     Error: " . substr($result['output'], 0, 200) . "...\n";
            }
        }
    }
    
    $categoryTotal = $categoryPassed + $categoryFailed;
    if ($categoryTotal > 0) {
        echo "  📊 $categoryPassed/$categoryTotal passed (" . round(($categoryPassed / $categoryTotal) * 100, 1) . "%)\n";
    }
    echo "\n";
}

// Security assessment
echo "🔒 SECURITY ASSESSMENT\n";
echo "=====================\n";

$securityTests = $results['Security'] ?? [];
$securityPassed = count(array_filter($securityTests, fn($t) => $t['status'] === 'PASSED'));
$securityTotal = count($securityTests);

if ($securityTotal > 0) {
    $securityScore = round(($securityPassed / $securityTotal) * 100, 1);
    
    if ($securityScore >= 90) {
        echo "✅ EXCELLENT: $securityScore% security compliance\n";
        echo "   Your system is highly secure!\n";
    } elseif ($securityScore >= 75) {
        echo "⚠️  GOOD: $securityScore% security compliance\n";
        echo "   Minor security improvements recommended.\n";
    } else {
        echo "❌ CRITICAL: $securityScore% security compliance\n";
        echo "   Immediate security fixes required!\n";
    }
} else {
    echo "⚠️  No security tests found\n";
}

echo "\n";

// System stability assessment
echo "🏗️  SYSTEM STABILITY ASSESSMENT\n";
echo "==============================\n";

$stabilityTests = $results['System Stability'] ?? [];
$stabilityPassed = count(array_filter($stabilityTests, fn($t) => $t['status'] === 'PASSED'));
$stabilityTotal = count($stabilityTests);

if ($stabilityTotal > 0) {
    $stabilityScore = round(($stabilityPassed / $stabilityTotal) * 100, 1);
    
    if ($stabilityScore >= 90) {
        echo "✅ EXCELLENT: $stabilityScore% system stability\n";
        echo "   Your system is production-ready!\n";
    } elseif ($stabilityScore >= 75) {
        echo "⚠️  GOOD: $stabilityScore% system stability\n";
        echo "   Minor stability improvements recommended.\n";
    } else {
        echo "❌ CRITICAL: $stabilityScore% system stability\n";
        echo "   System stability issues detected!\n";
    }
} else {
    echo "⚠️  No stability tests found\n";
}

echo "\n";

// Database integrity assessment
echo "🗄️  DATABASE INTEGRITY ASSESSMENT\n";
echo "================================\n";

$dbTests = $results['Database Integrity'] ?? [];
$dbPassed = count(array_filter($dbTests, fn($t) => $t['status'] === 'PASSED'));
$dbTotal = count($dbTests);

if ($dbTotal > 0) {
    $dbScore = round(($dbPassed / $dbTotal) * 100, 1);
    
    if ($dbScore >= 90) {
        echo "✅ EXCELLENT: $dbScore% database integrity\n";
        echo "   Database is robust and reliable!\n";
    } elseif ($dbScore >= 75) {
        echo "⚠️  GOOD: $dbScore% database integrity\n";
        echo "   Minor database improvements recommended.\n";
    } else {
        echo "❌ CRITICAL: $dbScore% database integrity\n";
        echo "   Database integrity issues detected!\n";
    }
} else {
    echo "⚠️  No database tests found\n";
}

echo "\n";

// Performance assessment
echo "⚡ PERFORMANCE ASSESSMENT\n";
echo "========================\n";

if ($executionTime < 30) {
    echo "✅ EXCELLENT: Tests completed in {$executionTime}s\n";
    echo "   System performance is optimal!\n";
} elseif ($executionTime < 60) {
    echo "⚠️  GOOD: Tests completed in {$executionTime}s\n";
    echo "   Performance is acceptable but could be improved.\n";
} else {
    echo "❌ SLOW: Tests completed in {$executionTime}s\n";
    echo "   Performance optimization recommended!\n";
}

echo "\n";

// Overall assessment
echo "🎯 OVERALL ASSESSMENT\n";
echo "====================\n";

$overallScore = round(($passedTests / $totalTests) * 100, 1);

if ($overallScore >= 95) {
    echo "🏆 EXCELLENT: $overallScore% overall score\n";
    echo "   Your system is BULLETPROOF and production-ready!\n";
    echo "   🚀 Ready for deployment!\n";
} elseif ($overallScore >= 85) {
    echo "✅ GOOD: $overallScore% overall score\n";
    echo "   Your system is stable with minor improvements needed.\n";
    echo "   🔧 Address failed tests before production.\n";
} elseif ($overallScore >= 70) {
    echo "⚠️  FAIR: $overallScore% overall score\n";
    echo "   Your system needs attention before production.\n";
    echo "   🛠️  Fix critical issues first.\n";
} else {
    echo "❌ POOR: $overallScore% overall score\n";
    echo "   Your system has significant issues.\n";
    echo "   🚨 Immediate fixes required before any deployment!\n";
}

echo "\n";

// Recommendations
echo "💡 RECOMMENDATIONS\n";
echo "==================\n";

if ($failedTests > 0) {
    echo "🔧 Fix failed tests before production deployment\n";
    echo "📋 Review error messages and address root causes\n";
    echo "🧪 Re-run tests after fixes to verify resolution\n";
} else {
    echo "🎉 All tests passed! Your system is ready for production.\n";
    echo "📊 Consider adding more tests for edge cases\n";
    echo "🔍 Regular security audits recommended\n";
}

echo "\n";

// Next steps
echo "📋 NEXT STEPS\n";
echo "=============\n";

if ($overallScore >= 90) {
    echo "1. ✅ Deploy to production\n";
    echo "2. 📊 Monitor system performance\n";
    echo "3. 🔒 Schedule regular security audits\n";
    echo "4. 📈 Plan for scaling and optimization\n";
} else {
    echo "1. 🛠️  Fix failed tests\n";
    echo "2. 🔍 Investigate root causes\n";
    echo "3. 🧪 Re-run test suite\n";
    echo "4. ✅ Deploy only after all tests pass\n";
}

echo "\n";
echo "🏁 Test suite completed at " . date('Y-m-d H:i:s') . "\n";
echo "=====================================\n";

// Exit with appropriate code
if ($failedTests > 0) {
    exit(1); // Exit with error code
} else {
    exit(0); // Exit successfully
} 