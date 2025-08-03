<?php

/**
 * DEBUG CHAOS INJECTION - SIMPLE TEST
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "🔍 DEBUG: Testing Chaos Injection Core Functionality\n";
echo "==================================================\n\n";

try {
    // Test basic class instantiation
    echo "1. Testing ChaosInjector instantiation...\n";
    
    // Mock Laravel environment for testing
    if (!function_exists('env')) {
        function env($key, $default = null) {
            return $default;
        }
    }
    
    if (!function_exists('storage_path')) {
        function storage_path($path = '') {
            return __DIR__ . '/storage/' . $path;
        }
    }
    
    if (!class_exists('Log')) {
        class Log {
            public static function info($message) {
                echo "LOG: $message\n";
            }
            public static function error($message) {
                echo "ERROR: $message\n";
            }
            public static function warning($message) {
                echo "WARNING: $message\n";
            }
        }
    }
    
    // Test the class
    $chaosInjector = new \App\Services\Audit\ChaosInjector(false);
    echo "✅ ChaosInjector instantiated successfully\n";
    
    // Test basic method calls
    echo "\n2. Testing basic method accessibility...\n";
    
    $reflection = new ReflectionClass($chaosInjector);
    $methods = $reflection->getMethods();
    
    $publicMethods = array_filter($methods, function($method) {
        return $method->isPublic();
    });
    
    echo "✅ Found " . count($publicMethods) . " public methods\n";
    
    foreach ($publicMethods as $method) {
        echo "  - " . $method->getName() . "\n";
    }
    
    echo "\n3. Testing executeChaosTests method...\n";
    
    // This should work without errors
    if (method_exists($chaosInjector, 'executeChaosTests')) {
        echo "✅ executeChaosTests method exists\n";
        
        // Try to call it (this might fail but we'll catch the error)
        try {
            echo "🔥 Attempting to execute chaos tests...\n";
            $result = $chaosInjector->executeChaosTests();
            echo "✅ Chaos tests executed successfully!\n";
            echo "📊 Result keys: " . implode(', ', array_keys($result)) . "\n";
        } catch (Exception $e) {
            echo "❌ Error during execution: " . $e->getMessage() . "\n";
            echo "📍 Error location: " . $e->getFile() . ":" . $e->getLine() . "\n";
            
            // Show the stack trace for debugging
            echo "\n🔍 Stack trace:\n";
            echo $e->getTraceAsString() . "\n";
        }
    } else {
        echo "❌ executeChaosTests method not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "📍 Location: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n🔍 Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n🏁 Debug test completed.\n";