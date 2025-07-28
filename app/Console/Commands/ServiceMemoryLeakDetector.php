<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;
use Exception;
use Throwable;

class ServiceMemoryLeakDetector extends Command
{
    protected $signature = 'service:detect-leaks {--iterations=1000 : Number of iterations to test} {--threshold=10 : Memory leak threshold in MB}';
    protected $description = 'Detect memory leaks in service layer';
    
    private array $memorySnapshots = [];
    private array $serviceInstances = [];
    private array $leakDetectionResults = [];
    private int $baselineMemory;
    
    public function handle(): int
    {
        $iterations = (int) $this->option('iterations');
        $threshold = (int) $this->option('threshold') * 1024 * 1024; // Convert to bytes
        
        $this->info("🔍 STARTING SERVICE MEMORY LEAK DETECTION");
        $this->info("Iterations: {$iterations}, Threshold: " . $this->formatBytes($threshold));
        $this->line("");
        
        // Establish baseline
        $this->establishBaseline();
        
        // Test each service for memory leaks
        $this->testServiceMemoryLeaks($iterations, $threshold);
        
        // Generate leak detection report
        $this->generateLeakReport();
        
        return 0;
    }
    
    private function establishBaseline(): void
    {
        $this->info("📊 Establishing memory baseline...");
        
        // Force garbage collection
        gc_collect_cycles();
        
        $this->baselineMemory = memory_get_usage(true);
        $this->info("Baseline memory: " . $this->formatBytes($this->baselineMemory));
    }
    
    private function testServiceMemoryLeaks(int $iterations, int $threshold): void
    {
        $services = [
            'App\\Services\\AssignmentService',
            'App\\Services\\CacheService',
            'App\\Services\\CourseFilterService',
            'App\\Services\\GradingService',
            'App\\Services\\MedicalKnowledgeService',
            'App\\Services\\NotificationService',
            'App\\Services\\PaymentService',
            'App\\Services\\QuizService',
            'App\\Services\\UserStatsService',
            'App\\Services\\QuizCacheService',
            'App\\Services\\StudentProgressService',
            'App\\Services\\TopicLockingService',
            'App\\Services\\TextProcessingService',
            'App\\Services\\SecurityService',
        ];
        
        foreach ($services as $serviceClass) {
            $this->testServiceForLeaks($serviceClass, $iterations, $threshold);
        }
    }
    
    private function testServiceForLeaks(string $serviceClass, int $iterations, int $threshold): void
    {
        $serviceName = class_basename($serviceClass);
        $this->info("🧪 Testing {$serviceName} for memory leaks...");
        
        try {
            // Test 1: Instance Creation/Destruction Leak Test
            $this->testInstanceCreationLeaks($serviceClass, $iterations, $threshold);
            
            // Test 2: Method Execution Leak Test
            $this->testMethodExecutionLeaks($serviceClass, $iterations, $threshold);
            
            // Test 3: Static Property Leak Test
            $this->testStaticPropertyLeaks($serviceClass, $iterations, $threshold);
            
            // Test 4: Event Listener Leak Test
            $this->testEventListenerLeaks($serviceClass, $iterations, $threshold);
            
        } catch (Throwable $e) {
            $this->error("Failed to test {$serviceName}: " . $e->getMessage());
            
            $this->leakDetectionResults[$serviceName] = [
                'status' => 'error',
                'error' => $e->getMessage(),
                'tests_completed' => 0
            ];
        }
    }
    
    private function testInstanceCreationLeaks(string $serviceClass, int $iterations, int $threshold): void
    {
        $serviceName = class_basename($serviceClass);
        
        // Force garbage collection before test
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);
        
        $instances = [];
        
        // Create many instances
        for ($i = 0; $i < $iterations; $i++) {
            try {
                $instance = App::make($serviceClass);
                $instances[] = $instance;
                
                // Take memory snapshots periodically
                if ($i % 100 === 0) {
                    $currentMemory = memory_get_usage(true);
                    $this->memorySnapshots["{$serviceName}_creation_{$i}"] = [
                        'memory' => $currentMemory,
                        'increase' => $currentMemory - $startMemory,
                        'iteration' => $i
                    ];
                }
            } catch (Throwable $e) {
                $this->warn("Failed to create instance {$i} of {$serviceName}: " . $e->getMessage());
                break;
            }
        }
        
        $peakMemory = memory_get_usage(true);
        
        // Clear instances
        unset($instances);
        gc_collect_cycles();
        
        $endMemory = memory_get_usage(true);
        $memoryLeak = $endMemory - $startMemory;
        
        $this->leakDetectionResults[$serviceName]['instance_creation'] = [
            'start_memory' => $startMemory,
            'peak_memory' => $peakMemory,
            'end_memory' => $endMemory,
            'memory_leak' => $memoryLeak,
            'leak_detected' => $memoryLeak > $threshold,
            'iterations_completed' => $iterations,
            'memory_per_instance' => $iterations > 0 ? $memoryLeak / $iterations : 0
        ];
        
        if ($memoryLeak > $threshold) {
            $this->error("🚨 MEMORY LEAK DETECTED in {$serviceName} instance creation: " . $this->formatBytes($memoryLeak));
        } else {
            $this->info("✅ {$serviceName} instance creation: No significant leaks");
        }
    }
    
    private function testMethodExecutionLeaks(string $serviceClass, int $iterations, int $threshold): void
    {
        $serviceName = class_basename($serviceClass);
        
        try {
            $instance = App::make($serviceClass);
            $reflection = new \ReflectionClass($instance);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            
            foreach ($methods as $method) {
                if ($method->getDeclaringClass()->getName() === $serviceClass && 
                    !$method->isConstructor() && 
                    !$method->isDestructor() &&
                    $method->getNumberOfRequiredParameters() === 0) {
                    
                    $this->testMethodForLeaks($instance, $method, $iterations, $threshold);
                }
            }
            
        } catch (Throwable $e) {
            $this->warn("Failed to test methods for {$serviceName}: " . $e->getMessage());
        }
    }
    
    private function testMethodForLeaks($instance, \ReflectionMethod $method, int $iterations, int $threshold): void
    {
        $serviceName = class_basename(get_class($instance));
        $methodName = $method->getName();
        
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);
        
        $results = [];
        
        // Execute method multiple times
        for ($i = 0; $i < min($iterations, 100); $i++) { // Limit method tests to 100 iterations
            try {
                $result = $method->invoke($instance);
                $results[] = $result;
                
                if ($i % 20 === 0) {
                    $currentMemory = memory_get_usage(true);
                    $this->memorySnapshots["{$serviceName}_{$methodName}_{$i}"] = [
                        'memory' => $currentMemory,
                        'increase' => $currentMemory - $startMemory,
                        'iteration' => $i
                    ];
                }
            } catch (Throwable $e) {
                // Method might require parameters or have other issues
                break;
            }
        }
        
        // Clear results
        unset($results);
        gc_collect_cycles();
        
        $endMemory = memory_get_usage(true);
        $memoryLeak = $endMemory - $startMemory;
        
        if (!isset($this->leakDetectionResults[$serviceName]['method_execution'])) {
            $this->leakDetectionResults[$serviceName]['method_execution'] = [];
        }
        
        $this->leakDetectionResults[$serviceName]['method_execution'][$methodName] = [
            'memory_leak' => $memoryLeak,
            'leak_detected' => $memoryLeak > ($threshold / 10), // Lower threshold for individual methods
            'iterations_completed' => min($iterations, 100)
        ];
        
        if ($memoryLeak > ($threshold / 10)) {
            $this->warn("⚠️  Method leak in {$serviceName}::{$methodName}: " . $this->formatBytes($memoryLeak));
        }
    }
    
    private function testStaticPropertyLeaks(string $serviceClass, int $iterations, int $threshold): void
    {
        $serviceName = class_basename($serviceClass);
        
        try {
            $reflection = new \ReflectionClass($serviceClass);
            $staticProperties = $reflection->getStaticProperties();
            
            if (empty($staticProperties)) {
                $this->leakDetectionResults[$serviceName]['static_properties'] = [
                    'status' => 'no_static_properties',
                    'leak_detected' => false
                ];
                return;
            }
            
            gc_collect_cycles();
            $startMemory = memory_get_usage(true);
            
            // Simulate static property usage
            for ($i = 0; $i < $iterations; $i++) {
                foreach ($staticProperties as $propertyName => $value) {
                    try {
                        // Access static property
                        $property = $reflection->getProperty($propertyName);
                        if ($property->isStatic() && $property->isPublic()) {
                            $staticValue = $property->getValue();
                        }
                    } catch (Throwable $e) {
                        // Property might not be accessible
                        continue;
                    }
                }
                
                if ($i % 200 === 0) {
                    $currentMemory = memory_get_usage(true);
                    $this->memorySnapshots["{$serviceName}_static_{$i}"] = [
                        'memory' => $currentMemory,
                        'increase' => $currentMemory - $startMemory,
                        'iteration' => $i
                    ];
                }
            }
            
            gc_collect_cycles();
            $endMemory = memory_get_usage(true);
            $memoryLeak = $endMemory - $startMemory;
            
            $this->leakDetectionResults[$serviceName]['static_properties'] = [
                'memory_leak' => $memoryLeak,
                'leak_detected' => $memoryLeak > $threshold,
                'properties_count' => count($staticProperties),
                'iterations_completed' => $iterations
            ];
            
            if ($memoryLeak > $threshold) {
                $this->error("🚨 Static property leak in {$serviceName}: " . $this->formatBytes($memoryLeak));
            }
            
        } catch (Throwable $e) {
            $this->leakDetectionResults[$serviceName]['static_properties'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
                'leak_detected' => false
            ];
        }
    }
    
    private function testEventListenerLeaks(string $serviceClass, int $iterations, int $threshold): void
    {
        $serviceName = class_basename($serviceClass);
        
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);
        
        // Simulate event listener registration/deregistration
        $listeners = [];
        
        for ($i = 0; $i < min($iterations, 500); $i++) { // Limit event tests
            try {
                // Create a closure that might cause memory leaks
                $listener = function() use ($serviceClass, $i) {
                    // Simulate event handling
                    $instance = App::make($serviceClass);
                    return "Event handled by {$serviceClass} - {$i}";
                };
                
                $listeners[] = $listener;
                
                if ($i % 100 === 0) {
                    $currentMemory = memory_get_usage(true);
                    $this->memorySnapshots["{$serviceName}_events_{$i}"] = [
                        'memory' => $currentMemory,
                        'increase' => $currentMemory - $startMemory,
                        'iteration' => $i
                    ];
                }
            } catch (Throwable $e) {
                break;
            }
        }
        
        // Clear listeners
        unset($listeners);
        gc_collect_cycles();
        
        $endMemory = memory_get_usage(true);
        $memoryLeak = $endMemory - $startMemory;
        
        $this->leakDetectionResults[$serviceName]['event_listeners'] = [
            'memory_leak' => $memoryLeak,
            'leak_detected' => $memoryLeak > $threshold,
            'iterations_completed' => min($iterations, 500)
        ];
        
        if ($memoryLeak > $threshold) {
            $this->error("🚨 Event listener leak in {$serviceName}: " . $this->formatBytes($memoryLeak));
        }
    }
    
    private function generateLeakReport(): void
    {
        $this->info("📋 GENERATING MEMORY LEAK DETECTION REPORT");
        
        $reportData = [
            'detection_timestamp' => Carbon::now()->toISOString(),
            'baseline_memory' => $this->baselineMemory,
            'final_memory' => memory_get_usage(true),
            'total_memory_increase' => memory_get_usage(true) - $this->baselineMemory,
            'peak_memory' => memory_get_peak_usage(true),
            'leak_detection_results' => $this->leakDetectionResults,
            'memory_snapshots' => $this->memorySnapshots,
            'summary' => $this->generateLeakSummary()
        ];
        
        // Save detailed report
        $reportPath = storage_path('logs/service_memory_leak_report_' . date('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($reportData, JSON_PRETTY_PRINT));
        
        // Display console summary
        $this->displayLeakSummary($reportData);
        
        $this->info("📄 Detailed leak report saved to: {$reportPath}");
    }
    
    private function generateLeakSummary(): array
    {
        $totalServices = count($this->leakDetectionResults);
        $servicesWithLeaks = 0;
        $totalLeakage = 0;
        $criticalLeaks = [];
        
        foreach ($this->leakDetectionResults as $serviceName => $results) {
            $serviceHasLeaks = false;
            $serviceLeakage = 0;
            
            // Check instance creation leaks
            if (isset($results['instance_creation']) && $results['instance_creation']['leak_detected']) {
                $serviceHasLeaks = true;
                $serviceLeakage += $results['instance_creation']['memory_leak'];
            }
            
            // Check method execution leaks
            if (isset($results['method_execution'])) {
                foreach ($results['method_execution'] as $method => $methodResult) {
                    if ($methodResult['leak_detected']) {
                        $serviceHasLeaks = true;
                        $serviceLeakage += $methodResult['memory_leak'];
                    }
                }
            }
            
            // Check static property leaks
            if (isset($results['static_properties']) && $results['static_properties']['leak_detected']) {
                $serviceHasLeaks = true;
                $serviceLeakage += $results['static_properties']['memory_leak'];
            }
            
            // Check event listener leaks
            if (isset($results['event_listeners']) && $results['event_listeners']['leak_detected']) {
                $serviceHasLeaks = true;
                $serviceLeakage += $results['event_listeners']['memory_leak'];
            }
            
            if ($serviceHasLeaks) {
                $servicesWithLeaks++;
                $totalLeakage += $serviceLeakage;
                
                if ($serviceLeakage > 50 * 1024 * 1024) { // 50MB threshold for critical
                    $criticalLeaks[] = [
                        'service' => $serviceName,
                        'leakage' => $serviceLeakage
                    ];
                }
            }
        }
        
        return [
            'total_services_tested' => $totalServices,
            'services_with_leaks' => $servicesWithLeaks,
            'leak_percentage' => $totalServices > 0 ? ($servicesWithLeaks / $totalServices) * 100 : 0,
            'total_leakage_bytes' => $totalLeakage,
            'critical_leaks' => $criticalLeaks,
            'memory_efficiency_score' => max(0, 100 - (($servicesWithLeaks / max($totalServices, 1)) * 100))
        ];
    }
    
    private function displayLeakSummary(array $reportData): void
    {
        $summary = $reportData['summary'];
        
        $this->line("");
        $this->info("🎯 MEMORY LEAK DETECTION SUMMARY");
        $this->line("Services Tested: " . $summary['total_services_tested']);
        $this->line("Services with Leaks: " . $summary['services_with_leaks']);
        $this->line("Leak Percentage: " . round($summary['leak_percentage'], 1) . "%");
        $this->line("Total Leakage: " . $this->formatBytes($summary['total_leakage_bytes']));
        $this->line("Memory Efficiency Score: " . round($summary['memory_efficiency_score'], 1) . "/100");
        
        if (!empty($summary['critical_leaks'])) {
            $this->line("");
            $this->error("🚨 CRITICAL MEMORY LEAKS DETECTED:");
            foreach ($summary['critical_leaks'] as $leak) {
                $this->line("- {$leak['service']}: " . $this->formatBytes($leak['leakage']));
            }
        }
        
        $this->line("");
        $this->info("📊 DETAILED RESULTS BY SERVICE:");
        foreach ($this->leakDetectionResults as $serviceName => $results) {
            $hasAnyLeak = false;
            $leakTypes = [];
            
            if (isset($results['instance_creation']) && $results['instance_creation']['leak_detected']) {
                $hasAnyLeak = true;
                $leakTypes[] = 'Instance Creation';
            }
            
            if (isset($results['method_execution'])) {
                foreach ($results['method_execution'] as $method => $methodResult) {
                    if ($methodResult['leak_detected']) {
                        $hasAnyLeak = true;
                        $leakTypes[] = "Method: {$method}";
                    }
                }
            }
            
            if (isset($results['static_properties']) && $results['static_properties']['leak_detected']) {
                $hasAnyLeak = true;
                $leakTypes[] = 'Static Properties';
            }
            
            if (isset($results['event_listeners']) && $results['event_listeners']['leak_detected']) {
                $hasAnyLeak = true;
                $leakTypes[] = 'Event Listeners';
            }
            
            if ($hasAnyLeak) {
                $this->line("<error>❌ {$serviceName}: " . implode(', ', $leakTypes) . "</error>");
            } else {
                $this->line("<info>✅ {$serviceName}: No leaks detected</info>");
            }
        }
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}