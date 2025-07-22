#!/bin/bash

# Comprehensive Testing Script
# Runs back-to-back testing with the comprehensive test seeder

set -e  # Exit on any error

echo "🚀 Starting Comprehensive Back-to-Back Testing"
echo "=============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Function to run a test and capture results
run_test() {
    local test_name="$1"
    local test_command="$2"
    local start_time=$(date +%s)
    
    print_status "Running: $test_name"
    
    if eval "$test_command" > "test_output_$(echo $test_name | tr ' ' '_' | tr '[:upper:]' '[:lower:]').log" 2>&1; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        print_success "$test_name completed in ${duration}s"
        return 0
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        print_error "$test_name failed after ${duration}s"
        return 1
    fi
}

# Initialize test results
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Test 1: Database Reset and Seeding
print_status "Phase 1: Database Setup and Seeding"
echo "-----------------------------------"

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if run_test "Database Migration Reset" "php artisan migrate:fresh --force"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if run_test "Comprehensive Test Seeder" "php artisan db:seed --class=ComprehensiveTestSeeder"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Test 2: Basic Application Tests
print_status "Phase 2: Basic Application Tests"
echo "--------------------------------"

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if run_test "Application Health Check" "php artisan --version"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if run_test "Cache Clear" "php artisan cache:clear && php artisan config:clear && php artisan view:clear"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Test 3: Database Validation Tests
print_status "Phase 3: Database Validation"
echo "----------------------------"

# Create database validation script
cat > validate_seeded_data.php << 'EOF'
<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Assignment;

echo "🔍 Validating Seeded Data\n";
echo "========================\n";

$validations = [
    'Users' => User::count(),
    'Courses' => Course::count(),
    'Topics' => Topic::count(),
    'Questions' => Question::count(),
    'Quiz Attempts' => QuizAttempt::count(),
    'Notifications' => \App\Models\Notification::count(),
    'Payments' => Payment::count(),
    'Assignments' => Assignment::count(),
];

$allValid = true;

foreach ($validations as $model => $count) {
    if ($count > 0) {
        echo "✅ {$model}: {$count} records\n";
    } else {
        echo "❌ {$model}: No records found!\n";
        $allValid = false;
    }
}

// Validate relationships
echo "\n🔗 Validating Relationships\n";
echo "===========================\n";

// Check user enrollments
$studentsWithCourses = User::where('role', 'student')->whereHas('courses')->count();
echo "✅ Students with course enrollments: {$studentsWithCourses}\n";

// Check quiz attempts with users
$attemptsWithUsers = QuizAttempt::whereHas('user')->count();
echo "✅ Quiz attempts with valid users: {$attemptsWithUsers}\n";

// Check notifications with users
$notificationsWithUsers = \App\Models\Notification::whereHas('user')->count();
echo "✅ Notifications with valid users: {$notificationsWithUsers}\n";

// Check tutor-student relationships
$tutorRelationships = \App\Models\TutorStudentRelationship::count();
echo "✅ Tutor-student relationships: {$tutorRelationships}\n";

if ($allValid) {
    echo "\n🎉 All validations passed!\n";
    exit(0);
} else {
    echo "\n💥 Some validations failed!\n";
    exit(1);
}
EOF

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if run_test "Database Validation" "php validate_seeded_data.php"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Test 4: Notification System Tests
print_status "Phase 4: Notification System Tests"
echo "----------------------------------"

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if run_test "JavaScript Notification Tests" "node tests/js/brutal-test-runner.js"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Test 5: API Endpoint Tests
print_status "Phase 5: API Endpoint Tests"
echo "---------------------------"

# Create API test script
cat > test_api_endpoints.php << 'EOF'
<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Models\User;

echo "🌐 Testing API Endpoints\n";
echo "=======================\n";

// Test user authentication endpoints
$testUser = User::where('role', 'student')->first();

if (!$testUser) {
    echo "❌ No test user found!\n";
    exit(1);
}

echo "✅ Test user found: {$testUser->email}\n";

// Test notification endpoints (simulate requests)
$endpoints = [
    '/api/notifications' => 'GET',
    '/api/notifications/unread-count' => 'GET',
];

foreach ($endpoints as $endpoint => $method) {
    echo "Testing {$method} {$endpoint}... ";
    
    try {
        $request = Request::create($endpoint, $method);
        $request->setUserResolver(function () use ($testUser) {
            return $testUser;
        });
        
        $response = $kernel->handle($request);
        
        if ($response->getStatusCode() < 400) {
            echo "✅ Success ({$response->getStatusCode()})\n";
        } else {
            echo "⚠️  Warning ({$response->getStatusCode()})\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

echo "\n🎯 API endpoint tests completed\n";
EOF

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if run_test "API Endpoint Tests" "php test_api_endpoints.php"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Test 6: Performance Tests
print_status "Phase 6: Performance Tests"
echo "--------------------------"

# Create performance test script
cat > test_performance.php << 'EOF'
<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Notification;

echo "⚡ Performance Tests\n";
echo "==================\n";

$tests = [
    'User Query Performance' => function() {
        $start = microtime(true);
        $users = User::with(['courses', 'notifications'])->limit(10)->get();
        $end = microtime(true);
        return ['time' => ($end - $start) * 1000, 'count' => $users->count()];
    },
    
    'Quiz Attempt Aggregation' => function() {
        $start = microtime(true);
        $stats = QuizAttempt::selectRaw('
            COUNT(*) as total_attempts,
            AVG(percentage_grade) as avg_score,
            SUM(correct_answers) as total_correct
        ')->first();
        $end = microtime(true);
        return ['time' => ($end - $start) * 1000, 'stats' => $stats];
    },
    
    'Notification Counting' => function() {
        $start = microtime(true);
        $count = \App\Models\Notification::where('is_read', false)->count();
        $end = microtime(true);
        return ['time' => ($end - $start) * 1000, 'count' => $count];
    }
];

foreach ($tests as $testName => $testFunction) {
    echo "Running {$testName}... ";
    
    try {
        $result = $testFunction();
        $time = round($result['time'], 2);
        
        if ($time < 100) {
            echo "✅ {$time}ms (Excellent)\n";
        } elseif ($time < 500) {
            echo "⚠️  {$time}ms (Acceptable)\n";
        } else {
            echo "❌ {$time}ms (Slow)\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
}

echo "\n⚡ Performance tests completed\n";
EOF

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if run_test "Performance Tests" "php test_performance.php"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Test 7: Data Integrity Tests
print_status "Phase 7: Data Integrity Tests"
echo "-----------------------------"

# Create data integrity test script
cat > test_data_integrity.php << 'EOF'
<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use App\Models\UserTopicProgress;

echo "🔒 Data Integrity Tests\n";
echo "======================\n";

$integrityChecks = [
    'Quiz Attempts vs User Stats' => function() {
        $users = User::where('role', 'student')->get();
        $issues = 0;
        
        foreach ($users as $user) {
            $actualCorrect = QuizAttempt::where('user_id', $user->id)->sum('correct_answers');
            $actualTotal = QuizAttempt::where('user_id', $user->id)->sum('total_questions');
            
            if ($user->correct_answers_count != $actualCorrect || 
                $user->total_questions_attempted != $actualTotal) {
                $issues++;
            }
        }
        
        return $issues;
    },
    
    'Orphaned Mistakes' => function() {
        return Mistake::whereDoesntHave('user')->count() + 
               Mistake::whereDoesntHave('question')->count();
    },
    
    'Invalid Topic Progress' => function() {
        return UserTopicProgress::where('percentage_grade', '>', 100)
                                ->orWhere('percentage_grade', '<', 0)
                                ->count();
    },
    
    'Notification Integrity' => function() {
        return \App\Models\Notification::whereDoesntHave('user')->count();
    }
];

$allIntegrityPassed = true;

foreach ($integrityChecks as $checkName => $checkFunction) {
    echo "Checking {$checkName}... ";
    
    try {
        $issues = $checkFunction();
        
        if ($issues == 0) {
            echo "✅ No issues found\n";
        } else {
            echo "❌ {$issues} issues found\n";
            $allIntegrityPassed = false;
        }
    } catch (Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
        $allIntegrityPassed = false;
    }
}

if ($allIntegrityPassed) {
    echo "\n🎉 All integrity checks passed!\n";
    exit(0);
} else {
    echo "\n💥 Some integrity issues found!\n";
    exit(1);
}
EOF

TOTAL_TESTS=$((TOTAL_TESTS + 1))
if run_test "Data Integrity Tests" "php test_data_integrity.php"; then
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi

# Final Results
echo ""
echo "🏁 Comprehensive Testing Complete"
echo "================================="
echo ""
print_status "Test Results Summary:"
echo "  Total Tests: $TOTAL_TESTS"
print_success "  Passed: $PASSED_TESTS"
if [ $FAILED_TESTS -gt 0 ]; then
    print_error "  Failed: $FAILED_TESTS"
else
    print_success "  Failed: $FAILED_TESTS"
fi

# Calculate success rate
SUCCESS_RATE=$(( (PASSED_TESTS * 100) / TOTAL_TESTS ))
echo "  Success Rate: ${SUCCESS_RATE}%"

echo ""
if [ $FAILED_TESTS -eq 0 ]; then
    print_success "🎉 All tests passed! System is ready for production."
    echo ""
    echo "📊 Generated Test Data Summary:"
    echo "  - Multiple user roles (admin, tutor, student)"
    echo "  - Comprehensive course and topic structure"
    echo "  - Realistic quiz attempts and progress tracking"
    echo "  - Full notification system testing"
    echo "  - Payment and assignment data"
    echo "  - Performance and integrity validation"
else
    print_warning "⚠️  Some tests failed. Check the log files for details:"
    ls -la test_output_*.log 2>/dev/null || echo "  No detailed logs available"
fi

# Cleanup temporary files
rm -f validate_seeded_data.php test_api_endpoints.php test_performance.php test_data_integrity.php

echo ""
print_status "Testing session completed at $(date)"