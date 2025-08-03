<?php

/**
 * Quick Resilience Test Script
 * Tests our fixes without running full chaos injection
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 TESTING RESILIENCE FIXES\n";
echo "============================\n\n";

// Test 1: Database Integrity Check
echo "1. Testing Database Integrity...\n";
try {
    $chaosInjector = new App\Services\Audit\ChaosInjector();
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($chaosInjector);
    $method = $reflection->getMethod('verifyDataIntegrityEnhanced');
    $method->setAccessible(true);
    
    $result = $method->invoke($chaosInjector);
    echo "   Database Integrity: " . ($result ? "✅ PASS" : "❌ FAIL") . "\n";
} catch (Exception $e) {
    echo "   Database Integrity: ❌ ERROR - " . $e->getMessage() . "\n";
}

// Test 2: Network Connectivity
echo "\n2. Testing Network Connectivity...\n";
try {
    $networkManager = app(App\Services\Resilience\NetworkResilienceManager::class);
    $connectivity = $networkManager->testNetworkConnectivity();
    
    foreach ($connectivity as $service => $result) {
        $status = $result['status'] === 'connected' ? '✅' : '❌';
        echo "   {$service}: {$status} {$result['status']}\n";
    }
} catch (Exception $e) {
    echo "   Network Test: ❌ ERROR - " . $e->getMessage() . "\n";
}

// Test 3: Job Capture
echo "\n3. Testing Job Capture...\n";
try {
    $chaosInjector = new App\Services\Audit\ChaosInjector();
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($chaosInjector);
    $method = $reflection->getMethod('captureActiveJobs');
    $method->setAccessible(true);
    
    $subsystems = ['queue', 'scheduler', 'horizon'];
    foreach ($subsystems as $subsystem) {
        $jobs = $method->invoke($chaosInjector, $subsystem);
        echo "   {$subsystem}: " . count($jobs) . " jobs captured\n";
    }
} catch (Exception $e) {
    echo "   Job Capture: ❌ ERROR - " . $e->getMessage() . "\n";
}

// Test 4: Configuration Guard
echo "\n4. Testing Configuration Guard...\n";
try {
    $configGuard = app(App\Services\Resilience\ConfigurationGuard::class);
    $integrity = $configGuard->verifyConfigurationIntegrity();
    
    $validConfigs = array_filter($integrity, function($config) {
        return $config['valid'] && $config['has_value'];
    });
    
    $validCount = count($validConfigs);
    $totalCount = count($integrity);
    
    echo "   Configuration Integrity: {$validCount}/{$totalCount} valid\n";
    
    if ($validCount >= $totalCount * 0.8) {
        echo "   Status: ✅ GOOD (80%+ valid)\n";
    } else {
        echo "   Status: ⚠️ NEEDS ATTENTION\n";
    }
} catch (Exception $e) {
    echo "   Configuration Guard: ❌ ERROR - " . $e->getMessage() . "\n";
}

echo "\n🏁 RESILIENCE TEST COMPLETE\n";
echo "============================\n";