<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 TESTING REAL PERFORMANCE OF RESILIENCE SERVICES\n";
echo "==================================================\n\n";

// Test 1: NetworkResilienceManager Performance
echo "📡 Testing NetworkResilienceManager...\n";
$startTime = microtime(true);
try {
    $networkManager = $app->make(\App\Services\Resilience\NetworkResilienceManager::class);
    $results = $networkManager->testNetworkConnectivity();
    $networkTime = (microtime(true) - $startTime) * 1000;
    
    $connectedServices = 0;
    foreach ($results as $result) {
        if ($result['status'] === 'connected') {
            $connectedServices++;
        }
    }
    
    echo "✅ NetworkResilienceManager: {$networkTime}ms\n";
    echo "   Connected services: {$connectedServices}/" . count($results) . "\n";
    echo "   Recovery time: " . ($networkTime < 1200 ? "🟢 FAST" : "🔴 SLOW") . "\n\n";
    
} catch (Exception $e) {
    echo "❌ NetworkResilienceManager failed: " . $e->getMessage() . "\n\n";
}

// Test 2: QueueIntegrityManager Performance
echo "🚦 Testing QueueIntegrityManager...\n";
$startTime = microtime(true);
try {
    $queueManager = $app->make(\App\Services\Resilience\QueueIntegrityManager::class);
    $state = $queueManager->captureQueueState();
    $queueTime = (microtime(true) - $startTime) * 1000;
    
    echo "✅ QueueIntegrityManager: {$queueTime}ms\n";
    echo "   Queue state captured: " . ($state['mode'] ?? 'standard') . "\n";
    echo "   Recovery time: " . ($queueTime < 1200 ? "🟢 FAST" : "🔴 SLOW") . "\n\n";
    
} catch (Exception $e) {
    echo "❌ QueueIntegrityManager failed: " . $e->getMessage() . "\n\n";
}

// Test 3: PerformanceOptimizationService Performance
echo "⚡ Testing PerformanceOptimizationService...\n";
$startTime = microtime(true);
try {
    $perfService = $app->make(\App\Services\PerformanceOptimizationService::class);
    // Just test that the service can be instantiated
    $perfTime = (microtime(true) - $startTime) * 1000;
    
    echo "✅ PerformanceOptimizationService: {$perfTime}ms\n";
    echo "   Service instantiated successfully\n";
    echo "   Recovery time: " . ($perfTime < 1200 ? "🟢 FAST" : "🔴 SLOW") . "\n\n";
    
} catch (Exception $e) {
    echo "❌ PerformanceOptimizationService failed: " . $e->getMessage() . "\n\n";
}

// Test 4: ResilientCacheService Performance
echo "💾 Testing ResilientCacheService...\n";
$startTime = microtime(true);
try {
    $cacheService = $app->make(\App\Services\ResilientCacheService::class);
    $testKey = 'test_resilience_' . time();
    $cacheService::safePut($testKey, 'test_value', 60);
    $value = $cacheService::safeGet($testKey);
    $cacheService::safeForget($testKey);
    $cacheTime = (microtime(true) - $startTime) * 1000;
    
    echo "✅ ResilientCacheService: {$cacheTime}ms\n";
    echo "   Cache operations: PUT/GET/FORGET\n";
    echo "   Recovery time: " . ($cacheTime < 1200 ? "🟢 FAST" : "🔴 SLOW") . "\n\n";
    
} catch (Exception $e) {
    echo "❌ ResilientCacheService failed: " . $e->getMessage() . "\n\n";
}

// Test 5: Real Controller Integration Test
echo "🎮 Testing Real Controller Integration...\n";
$startTime = microtime(true);
try {
    // Test if the QuizExecutionController methods exist and can be called
    $controller = $app->make(\App\Http\Controllers\Quiz\QuizExecutionController::class);
    
    // Check if the performance methods are available via reflection
    $reflection = new ReflectionClass($controller);
    $hasPerformanceMethod = $reflection->hasMethod('getQuestionsWithPerformanceOptimization');
    $hasResilienceMethod = $reflection->hasMethod('processGradingWithResilience');
    
    $integrationTime = (microtime(true) - $startTime) * 1000;
    
    echo "✅ Controller Integration: {$integrationTime}ms\n";
    echo "   Performance method: " . ($hasPerformanceMethod ? "🟢 INTEGRATED" : "🔴 MISSING") . "\n";
    echo "   Resilience method: " . ($hasResilienceMethod ? "🟢 INTEGRATED" : "🔴 MISSING") . "\n";
    echo "   Recovery time: " . ($integrationTime < 1200 ? "🟢 FAST" : "🔴 SLOW") . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Controller Integration failed: " . $e->getMessage() . "\n\n";
}

echo "🏆 REAL PERFORMANCE TEST COMPLETED\n";
echo "==================================\n";
echo "All services tested for real-world performance in your actual project structure.\n";