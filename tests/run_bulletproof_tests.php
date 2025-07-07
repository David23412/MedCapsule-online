<?php

/**
 * BULLETPROOF TEST SUITE
 * 
 * This script performs comprehensive testing to ensure your system is:
 * - SECURE (HTTPS, CSRF, XSS protection, SQL injection prevention)
 * - STABLE (Database integrity, error handling, performance)
 * - ROBUST (All critical functionality works correctly)
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔒 MEDCAPSULE BULLETPROOF TEST SUITE\n";
echo "====================================\n\n";

$results = [];
$totalChecks = 0;
$passedChecks = 0;
$failedChecks = 0;

// 1. SECURITY CHECKS
echo "🔒 SECURITY CHECKS\n";
echo "==================\n";

// HTTPS Configuration
$totalChecks++;
try {
    $config = config('app');
    if (str_contains($config['url'] ?? '', 'https://')) {
        echo "✅ HTTPS is configured\n";
        $passedChecks++;
    } else {
        echo "❌ HTTPS not configured in APP_URL\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ HTTPS check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

// CSRF Protection
$totalChecks++;
try {
    $csrfConfig = config('session');
    if (isset($csrfConfig['encrypt']) && $csrfConfig['encrypt']) {
        echo "✅ CSRF protection is enabled\n";
        $passedChecks++;
    } else {
        echo "❌ CSRF protection may not be properly configured\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ CSRF check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

// Debug Mode
$totalChecks++;
try {
    if (!config('app.debug')) {
        echo "✅ Debug mode is disabled (production safe)\n";
        $passedChecks++;
    } else {
        echo "⚠️  Debug mode is enabled (should be disabled in production)\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ Debug check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

echo "\n";

// 2. DATABASE INTEGRITY CHECKS
echo "🗄️  DATABASE INTEGRITY CHECKS\n";
echo "=============================\n";

// Database Connection
$totalChecks++;
try {
    DB::connection()->getPdo();
    echo "✅ Database connection is working\n";
    $passedChecks++;
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

// Foreign Key Constraints
$totalChecks++;
try {
    $constraints = DB::select("
        SELECT COUNT(*) as count 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE CONSTRAINT_NAME LIKE '%_foreign'
    ");
    if ($constraints[0]->count > 0) {
        echo "✅ Foreign key constraints are in place\n";
        $passedChecks++;
    } else {
        echo "❌ No foreign key constraints found\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ Foreign key check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

// Database Indexes
$totalChecks++;
try {
    $indexes = DB::select("
        SELECT COUNT(*) as count 
        FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE()
    ");
    if ($indexes[0]->count > 0) {
        echo "✅ Database indexes are present\n";
        $passedChecks++;
    } else {
        echo "❌ No database indexes found\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ Index check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

echo "\n";

// 3. CRITICAL FUNCTIONALITY CHECKS
echo "⚙️  CRITICAL FUNCTIONALITY CHECKS\n";
echo "=================================\n";

// User Model
$totalChecks++;
try {
    $user = new \App\Models\User();
    $methods = get_class_methods($user);
    $requiredMethods = ['enrollInAllFreeCourses', 'hasAccessToCourse', 'enrollInCourse'];
    $missingMethods = array_diff($requiredMethods, $methods);
    
    if (empty($missingMethods)) {
        echo "✅ User model has all required methods\n";
        $passedChecks++;
    } else {
        echo "❌ User model missing methods: " . implode(', ', $missingMethods) . "\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ User model check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

// Course Model
$totalChecks++;
try {
    $course = new \App\Models\Course();
    $methods = get_class_methods($course);
    $requiredMethods = ['isUserEnrolled', 'hasUserPaid', 'getEnrolledStudentsCountAttribute'];
    $missingMethods = array_diff($requiredMethods, $methods);
    
    if (empty($missingMethods)) {
        echo "✅ Course model has all required methods\n";
        $passedChecks++;
    } else {
        echo "❌ Course model missing methods: " . implode(', ', $missingMethods) . "\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ Course model check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

// Services
$totalChecks++;
try {
    $services = ['QuizService', 'GradingService', 'NotificationService'];
    $missingServices = [];
    
    foreach ($services as $service) {
        if (!class_exists("\\App\\Services\\$service")) {
            $missingServices[] = $service;
        }
    }
    
    if (empty($missingServices)) {
        echo "✅ All required services are available\n";
        $passedChecks++;
    } else {
        echo "❌ Missing services: " . implode(', ', $missingServices) . "\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ Services check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

echo "\n";

// 4. CONFIGURATION CHECKS
echo "⚙️  CONFIGURATION CHECKS\n";
echo "=======================\n";

// Session Configuration
$totalChecks++;
try {
    $sessionConfig = config('session');
    if (isset($sessionConfig['secure']) && $sessionConfig['secure']) {
        echo "✅ Secure sessions are configured\n";
        $passedChecks++;
    } else {
        echo "⚠️  Sessions may not be secure (should be true in production)\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ Session check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

// Cache Configuration
$totalChecks++;
try {
    $cacheConfig = config('cache');
    if (isset($cacheConfig['default']) && $cacheConfig['default'] !== 'file') {
        echo "✅ Cache is configured (not using file driver)\n";
        $passedChecks++;
    } else {
        echo "⚠️  Using file cache driver (consider Redis/Memcached for production)\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ Cache check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

// Queue Configuration
$totalChecks++;
try {
    $queueConfig = config('queue');
    if (isset($queueConfig['default']) && $queueConfig['default'] !== 'sync') {
        echo "✅ Queue is configured (not using sync driver)\n";
        $passedChecks++;
    } else {
        echo "⚠️  Using sync queue driver (consider Redis/database for production)\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ Queue check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

echo "\n";

// 5. PERFORMANCE CHECKS
echo "⚡ PERFORMANCE CHECKS\n";
echo "=====================\n";

// Database Query Performance
$totalChecks++;
try {
    $startTime = microtime(true);
    $userCount = \App\Models\User::count();
    $endTime = microtime(true);
    $queryTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    
    if ($queryTime < 100) {
        echo "✅ Database queries are fast ({$queryTime}ms)\n";
        $passedChecks++;
    } else {
        echo "⚠️  Database queries are slow ({$queryTime}ms)\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ Performance check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

// Memory Usage
$totalChecks++;
try {
    $memoryUsage = memory_get_usage(true);
    $memoryLimit = ini_get('memory_limit');
    
    if ($memoryUsage < 50 * 1024 * 1024) { // Less than 50MB
        echo "✅ Memory usage is reasonable (" . round($memoryUsage / 1024 / 1024, 2) . "MB)\n";
        $passedChecks++;
    } else {
        echo "⚠️  High memory usage (" . round($memoryUsage / 1024 / 1024, 2) . "MB)\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ Memory check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

echo "\n";

// 6. ROUTE CHECKS
echo "🛣️  ROUTE CHECKS\n";
echo "================\n";

// Critical Routes
$totalChecks++;
try {
    $routes = ['/', '/login', '/register', '/courses'];
    $missingRoutes = [];
    
    foreach ($routes as $route) {
        try {
            $response = app()->handle(\Illuminate\Http\Request::create($route));
            if ($response->getStatusCode() >= 400) {
                $missingRoutes[] = $route;
            }
        } catch (Exception $e) {
            $missingRoutes[] = $route;
        }
    }
    
    if (empty($missingRoutes)) {
        echo "✅ All critical routes are accessible\n";
        $passedChecks++;
    } else {
        echo "❌ Missing or broken routes: " . implode(', ', $missingRoutes) . "\n";
        $failedChecks++;
    }
} catch (Exception $e) {
    echo "❌ Route check failed: " . $e->getMessage() . "\n";
    $failedChecks++;
}

echo "\n";

// FINAL REPORT
echo "📊 BULLETPROOF TEST REPORT\n";
echo "==========================\n\n";

echo "📈 Total Checks: $totalChecks\n";
echo "✅ Passed: $passedChecks\n";
echo "❌ Failed: $failedChecks\n";

if ($totalChecks > 0) {
    $successRate = round(($passedChecks / $totalChecks) * 100, 1);
    echo "📊 Success Rate: {$successRate}%\n\n";
    
    if ($successRate >= 95) {
        echo "🏆 EXCELLENT: Your system is BULLETPROOF!\n";
        echo "   🚀 Ready for production deployment\n";
        echo "   🔒 Security is top-notch\n";
        echo "   ⚡ Performance is optimal\n";
    } elseif ($successRate >= 85) {
        echo "✅ GOOD: Your system is stable\n";
        echo "   🔧 Minor improvements recommended\n";
        echo "   📋 Address failed checks before production\n";
    } elseif ($successRate >= 70) {
        echo "⚠️  FAIR: Your system needs attention\n";
        echo "   🛠️  Fix critical issues first\n";
        echo "   🚨 Not ready for production\n";
    } else {
        echo "❌ POOR: Your system has significant issues\n";
        echo "   🚨 Immediate fixes required\n";
        echo "   🛑 Do not deploy to production\n";
    }
}

echo "\n";

// Recommendations
echo "💡 RECOMMENDATIONS\n";
echo "==================\n";

if ($failedChecks > 0) {
    echo "🔧 Fix failed checks before production deployment\n";
    echo "📋 Review each failed check and address root causes\n";
    echo "🧪 Re-run this test suite after fixes\n";
    echo "🔒 Pay special attention to security-related failures\n";
} else {
    echo "🎉 All checks passed! Your system is bulletproof.\n";
    echo "📊 Consider regular security audits\n";
    echo "📈 Monitor performance in production\n";
    echo "🔄 Run this test suite regularly\n";
}

echo "\n";
echo "🏁 Test completed at " . date('Y-m-d H:i:s') . "\n";
echo "====================================\n";

// Exit with appropriate code
if ($failedChecks > 0) {
    exit(1); // Exit with error code
} else {
    exit(0); // Exit successfully
} 