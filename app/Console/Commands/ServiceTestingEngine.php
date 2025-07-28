<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class ServiceTestingEngine extends Command
{
    protected $signature = 'service:test-all {--mode=full : Testing mode (quick|full|stress)} {--output=console : Output format}';
    protected $description = 'Run comprehensive service testing suite';
    
    public function handle(): int
    {
        $mode = $this->option('mode');
        $output = $this->option('output');
        
        $this->displayBanner();
        
        switch ($mode) {
            case 'quick':
                return $this->runQuickTests($output);
            case 'stress':
                return $this->runStressTests($output);
            case 'full':
            default:
                return $this->runFullTestSuite($output);
        }
    }
    
    private function displayBanner(): void
    {
        $this->line("");
        $this->line("🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥");
        $this->line("🔥                                                                    🔥");
        $this->line("🔥           LETHAL SERVICE TESTING ENGINE ACTIVATED                  🔥");
        $this->line("🔥                                                                    🔥");
        $this->line("🔥    MISSION: DESTROY ALL SERVICES UNDER EXTREME LOAD               🔥");
        $this->line("🔥    TARGET: IDENTIFY EVERY PERFORMANCE BOTTLENECK                  🔥");
        $this->line("🔥    GOAL: ACHIEVE 100% SERVICE OPTIMIZATION                        🔥");
        $this->line("🔥                                                                    🔥");
        $this->line("🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥");
        $this->line("");
        $this->info("Starting at: " . Carbon::now()->format('Y-m-d H:i:s'));
        $this->line("");
    }
    
    private function runQuickTests(string $output): int
    {
        $this->info("🚀 RUNNING QUICK SERVICE TESTS");
        
        $tests = [
            'Service Audit' => 'service:audit',
            'Memory Leak Detection (Quick)' => 'service:detect-leaks --iterations=100',
            'Performance Monitor (30s)' => 'service:monitor --duration=30 --interval=5'
        ];
        
        return $this->executeTests($tests, $output);
    }
    
    private function runStressTests(string $output): int
    {
        $this->info("💥 RUNNING STRESS TESTS - PREPARE FOR DESTRUCTION");
        
        $tests = [
            'Service Stress Test Seeder' => 'db:seed --class=ServiceStressTestSeeder',
            'Memory Leak Detection (Stress)' => 'service:detect-leaks --iterations=5000 --threshold=5',
            'Performance Monitor (300s)' => 'service:monitor --duration=300 --interval=10'
        ];
        
        return $this->executeTests($tests, $output);
    }
    
    private function runFullTestSuite(string $output): int
    {
        $this->info("🎯 RUNNING FULL SERVICE TEST SUITE");
        
        $tests = [
            'Service Audit Engine' => 'service:audit --output=' . $output,
            'Service Stress Testing' => 'db:seed --class=ServiceStressTestSeeder',
            'Memory Leak Detection' => 'service:detect-leaks --iterations=2000 --threshold=10',
            'Performance Monitoring' => 'service:monitor --duration=120 --interval=5'
        ];
        
        return $this->executeTests($tests, $output);
    }
    
    private function executeTests(array $tests, string $output): int
    {
        $startTime = microtime(true);
        $results = [];
        
        foreach ($tests as $testName => $command) {
            $this->line("");
            $this->info("🔥 EXECUTING: {$testName}");
            $this->line("Command: {$command}");
            $this->line("");
            
            $testStartTime = microtime(true);
            
            try {
                $exitCode = Artisan::call($command);
                $testEndTime = microtime(true);
                $testDuration = $testEndTime - $testStartTime;
                
                if ($exitCode === 0) {
                    $this->info("✅ {$testName} completed successfully in " . round($testDuration, 2) . " seconds");
                    $results[$testName] = [
                        'status' => 'success',
                        'duration' => $testDuration,
                        'exit_code' => $exitCode
                    ];
                } else {
                    $this->error("❌ {$testName} failed with exit code {$exitCode}");
                    $results[$testName] = [
                        'status' => 'failed',
                        'duration' => $testDuration,
                        'exit_code' => $exitCode
                    ];
                }
                
                // Display command output
                $commandOutput = Artisan::output();
                if (!empty(trim($commandOutput))) {
                    $this->line("Output:");
                    $this->line($commandOutput);
                }
                
            } catch (\Exception $e) {
                $testEndTime = microtime(true);
                $testDuration = $testEndTime - $testStartTime;
                
                $this->error("💥 {$testName} crashed: " . $e->getMessage());
                $results[$testName] = [
                    'status' => 'crashed',
                    'duration' => $testDuration,
                    'error' => $e->getMessage()
                ];
            }
            
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        }
        
        $endTime = microtime(true);
        $totalDuration = $endTime - $startTime;
        
        // Generate final summary
        $this->generateFinalSummary($results, $totalDuration, $output);
        
        // Return appropriate exit code
        $failedTests = array_filter($results, fn($result) => $result['status'] !== 'success');
        return empty($failedTests) ? 0 : 1;
    }
    
    private function generateFinalSummary(array $results, float $totalDuration, string $output): void
    {
        $this->line("");
        $this->line("🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯");
        $this->line("🎯                                                                    🎯");
        $this->line("🎯                    FINAL TEST EXECUTION SUMMARY                    🎯");
        $this->line("🎯                                                                    🎯");
        $this->line("🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯🎯");
        $this->line("");
        
        $totalTests = count($results);
        $successfulTests = count(array_filter($results, fn($result) => $result['status'] === 'success'));
        $failedTests = count(array_filter($results, fn($result) => $result['status'] === 'failed'));
        $crashedTests = count(array_filter($results, fn($result) => $result['status'] === 'crashed'));
        
        $this->info("📊 EXECUTION STATISTICS:");
        $this->line("Total Tests: {$totalTests}");
        $this->line("Successful: {$successfulTests}");
        $this->line("Failed: {$failedTests}");
        $this->line("Crashed: {$crashedTests}");
        $this->line("Success Rate: " . round(($successfulTests / $totalTests) * 100, 1) . "%");
        $this->line("Total Duration: " . round($totalDuration, 2) . " seconds");
        
        $this->line("");
        $this->info("📋 DETAILED RESULTS:");
        
        foreach ($results as $testName => $result) {
            $statusIcon = match($result['status']) {
                'success' => '✅',
                'failed' => '❌',
                'crashed' => '💥',
                default => '❓'
            };
            
            $duration = round($result['duration'], 2);
            $this->line("{$statusIcon} {$testName}: {$result['status']} ({$duration}s)");
            
            if (isset($result['error'])) {
                $this->line("   Error: {$result['error']}");
            }
        }
        
        // Generate recommendations
        $this->line("");
        $this->info("🔧 RECOMMENDATIONS:");
        
        if ($failedTests > 0 || $crashedTests > 0) {
            $this->line("- Review failed/crashed tests and fix underlying issues");
            $this->line("- Check service implementations for stability problems");
            $this->line("- Consider reducing test intensity if system resources are limited");
        }
        
        if ($successfulTests === $totalTests) {
            $this->line("🎉 ALL TESTS PASSED! Your services are performing excellently!");
            $this->line("- Consider running stress tests to push limits further");
            $this->line("- Review performance reports for optimization opportunities");
        }
        
        // Save summary report
        $summaryData = [
            'execution_timestamp' => Carbon::now()->toISOString(),
            'total_duration' => $totalDuration,
            'test_results' => $results,
            'statistics' => [
                'total_tests' => $totalTests,
                'successful_tests' => $successfulTests,
                'failed_tests' => $failedTests,
                'crashed_tests' => $crashedTests,
                'success_rate' => ($successfulTests / $totalTests) * 100
            ]
        ];
        
        $reportPath = storage_path('logs/service_testing_summary_' . date('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($summaryData, JSON_PRETTY_PRINT));
        
        $this->line("");
        $this->info("📄 Complete test summary saved to: {$reportPath}");
        
        $this->line("");
        $this->line("🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥");
        $this->line("🔥                                                                    🔥");
        $this->line("🔥              SERVICE TESTING ENGINE EXECUTION COMPLETE            🔥");
        $this->line("🔥                                                                    🔥");
        $this->line("🔥    \"If our services don't break under stress testing,             🔥");
        $this->line("🔥     we're not testing hard enough!\"                              🔥");
        $this->line("🔥                                                                    🔥");
        $this->line("🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥🔥");
        $this->line("");
    }
}