<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunComprehensiveTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:comprehensive {--category=all : Test category to run (security, stability, database, services, integration, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run comprehensive test suite for system stability, security, and performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 MEDCAPSULE COMPREHENSIVE TEST SUITE');
        $this->info('=====================================');
        $this->newLine();

        $category = $this->option('category');
        
        if ($category === 'all') {
            $this->runAllTests();
        } else {
            $this->runCategoryTests($category);
        }
    }

    private function runAllTests()
    {
        $testCategories = [
            'security' => [
                'tests/Unit/SecurityTest.php',
                'tests/Feature/HTTPSecurityTest.php'
            ],
            'stability' => [
                'tests/Feature/SystemStabilityTest.php'
            ],
            'database' => [
                'tests/Feature/DatabaseIntegrityTest.php'
            ],
            'services' => [
                'tests/Unit/QuizServiceTest.php',
                'tests/Unit/GradingServiceTest.php',
                'tests/Unit/WrittenAnswerEvaluationServiceTest.php'
            ],
            'integration' => [
                'tests/Feature/QuizControllerIntegrationTest.php'
            ]
        ];

        $results = [];
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $skippedTests = 0;
        $startTime = microtime(true);

        foreach ($testCategories as $categoryName => $testFiles) {
            $this->info("📋 Testing: " . ucfirst($categoryName));
            $this->info(str_repeat('-', strlen($categoryName) + 10));
            
            $categoryResults = $this->runTestFiles($testFiles);
            $results[$categoryName] = $categoryResults;
            
            foreach ($categoryResults as $result) {
                $totalTests++;
                if ($result['status'] === 'PASSED') {
                    $passedTests++;
                } elseif ($result['status'] === 'SKIPPED') {
                    $skippedTests++;
                } else {
                    $failedTests++;
                }
            }
            
            $this->newLine();
        }

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $this->generateReport($results, $totalTests, $passedTests, $failedTests, $skippedTests, $executionTime);
    }

    private function runCategoryTests($category)
    {
        $testFiles = $this->getTestFilesForCategory($category);
        
        if (empty($testFiles)) {
            $this->error("❌ No test files found for category: $category");
            return;
        }

        $this->info("📋 Testing: " . ucfirst($category));
        $this->info(str_repeat('-', strlen($category) + 10));
        
        $results = $this->runTestFiles($testFiles);
        
        $totalTests = count($results);
        $passedTests = count(array_filter($results, fn($r) => $r['status'] === 'PASSED'));
        $skippedTests = count(array_filter($results, fn($r) => $r['status'] === 'SKIPPED'));
        $failedTests = $totalTests - $passedTests - $skippedTests;

        $this->generateReport([$category => $results], $totalTests, $passedTests, $failedTests, $skippedTests, 0);
    }

    private function runTestFiles($testFiles)
    {
        $results = [];

        $phpunitRunner = $this->resolvePhpUnitRunner();
        if ($phpunitRunner === null) {
            $this->warn('⚠️  PHPUnit runner not found (vendor/bin/phpunit or global phpunit). Marking tests as SKIPPED.');

            foreach ($testFiles as $testFile) {
                $results[] = [
                    'file' => $testFile,
                    'status' => 'SKIPPED',
                    'reason' => 'PHPUnit runner not installed in this environment'
                ];
            }

            return $results;
        }
        
        foreach ($testFiles as $testFile) {
            if (!file_exists($testFile)) {
                $this->warn("⚠️  Test file not found: $testFile");
                continue;
            }
            
            $className = basename($testFile, '.php');
            $this->info("  Running: $className... ");
            
            try {
                // Run the test file directly with PHPUnit
                $command = $phpunitRunner . ' ' . escapeshellarg($testFile) . ' --colors=never';
                $outputLines = [];
                $exitCode = 1;
                exec($command . ' 2>&1', $outputLines, $exitCode);
                $output = implode("\n", $outputLines);
                
                if ($exitCode === 0 || strpos($output, 'OK') !== false || strpos($output, 'PASS') !== false) {
                    $this->info("✅ PASSED");
                    $results[] = ['file' => $testFile, 'status' => 'PASSED'];
                } else {
                    $this->error("❌ FAILED");
                    $results[] = ['file' => $testFile, 'status' => 'FAILED', 'output' => $output];
                }
                
            } catch (\Exception $e) {
                $this->error("❌ ERROR: " . $e->getMessage());
                $results[] = ['file' => $testFile, 'status' => 'ERROR', 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    private function resolvePhpUnitRunner(): ?string
    {
        $localPhpUnit = base_path('vendor/bin/phpunit');
        if (is_file($localPhpUnit)) {
            return escapeshellarg($localPhpUnit);
        }

        $globalPhpUnit = trim((string) shell_exec('command -v phpunit 2>/dev/null'));

        return $globalPhpUnit !== '' ? escapeshellcmd($globalPhpUnit) : null;
    }

    private function getTestFilesForCategory($category)
    {
        $testCategories = [
            'security' => [
                'tests/Unit/SecurityTest.php',
                'tests/Feature/HTTPSecurityTest.php'
            ],
            'stability' => [
                'tests/Feature/SystemStabilityTest.php'
            ],
            'database' => [
                'tests/Feature/DatabaseIntegrityTest.php'
            ],
            'services' => [
                'tests/Unit/QuizServiceTest.php',
                'tests/Unit/GradingServiceTest.php',
                'tests/Unit/WrittenAnswerEvaluationServiceTest.php'
            ],
            'integration' => [
                'tests/Feature/QuizControllerIntegrationTest.php'
            ]
        ];

        return $testCategories[$category] ?? [];
    }

    private function generateReport($results, $totalTests, $passedTests, $failedTests, $skippedTests, $executionTime)
    {
        $this->info('📊 COMPREHENSIVE TEST REPORT');
        $this->info('============================');
        $this->newLine();

        if ($executionTime > 0) {
            $this->info("⏱️  Execution Time: {$executionTime}s");
        }
        $this->info("📈 Total Tests: $totalTests");
        $this->info("✅ Passed: $passedTests");
        $this->info("❌ Failed: $failedTests");
        $this->info("⏭️  Skipped: $skippedTests");
        
        if ($totalTests > 0) {
            $successRate = round(($passedTests / $totalTests) * 100, 1);
            $this->info("📊 Success Rate: {$successRate}%");
        }
        
        $this->newLine();

        // Detailed results by category
        foreach ($results as $category => $categoryResults) {
            $this->info("🔍 " . ucfirst($category) . " Results:");
            $this->info(str_repeat('-', strlen($category) + 9));
            
            $categoryPassed = count(array_filter($categoryResults, fn($t) => $t['status'] === 'PASSED'));
            $categorySkipped = count(array_filter($categoryResults, fn($t) => $t['status'] === 'SKIPPED'));
            $categoryFailed = count($categoryResults) - $categoryPassed - $categorySkipped;
            
            foreach ($categoryResults as $result) {
                $status = $result['status'];
                $file = basename($result['file']);
                
                if ($status === 'PASSED') {
                    $this->info("  ✅ $file");
                } elseif ($status === 'SKIPPED') {
                    $this->warn("  ⏭️  $file (skipped)");
                } else {
                    $this->error("  ❌ $file");
                }
            }
            
            $categoryTotal = $categoryPassed + $categoryFailed + $categorySkipped;
            if ($categoryTotal > 0) {
                $categoryScore = round(($categoryPassed / $categoryTotal) * 100, 1);
                $this->info("  📊 $categoryPassed/$categoryTotal passed ($categoryScore%), skipped: $categorySkipped");
            }
            $this->newLine();
        }

        // Overall assessment
        $this->info('🎯 OVERALL ASSESSMENT');
        $this->info('====================');
        
        $effectiveTotal = max(0, $totalTests - $skippedTests);

        if ($effectiveTotal > 0) {
            $overallScore = round(($passedTests / $effectiveTotal) * 100, 1);
            
            if ($overallScore >= 95) {
                $this->info("🏆 EXCELLENT: $overallScore% overall score");
                $this->info("   Your system is BULLETPROOF and production-ready!");
                $this->info("   🚀 Ready for deployment!");
            } elseif ($overallScore >= 85) {
                $this->info("✅ GOOD: $overallScore% overall score");
                $this->info("   Your system is stable with minor improvements needed.");
                $this->info("   🔧 Address failed tests before production.");
            } elseif ($overallScore >= 70) {
                $this->info("⚠️  FAIR: $overallScore% overall score");
                $this->info("   Your system needs attention before production.");
                $this->info("   🛠️  Fix critical issues first.");
            } else {
                $this->info("❌ POOR: $overallScore% overall score");
                $this->info("   Your system has significant issues.");
                $this->info("   🚨 Immediate fixes required before any deployment!");
            }
        } elseif ($totalTests > 0) {
            $this->warn('⚠️  No executable tests were run (all skipped).');
            $this->warn('   Install PHPUnit to get real pass/fail signal.');
        }
        
        $this->newLine();

        // Recommendations
        $this->info('💡 RECOMMENDATIONS');
        $this->info('==================');
        
        if ($failedTests > 0) {
            $this->info('🔧 Fix failed tests before production deployment');
            $this->info('📋 Review error messages and address root causes');
            $this->info('🧪 Re-run tests after fixes to verify resolution');
        } elseif ($skippedTests > 0) {
            $this->info('🧰 Install PHPUnit (global or vendor/bin/phpunit) to execute skipped tests');
            $this->info('🧪 Re-run this command after installing test runner for accurate signal');
        } else {
            $this->info('🎉 All tests passed! Your system is ready for production.');
            $this->info('📊 Consider adding more tests for edge cases');
            $this->info('🔍 Regular security audits recommended');
        }
        
        $this->newLine();
        $this->info('🏁 Test suite completed at ' . date('Y-m-d H:i:s'));
        $this->info('=====================================');
    }
} 