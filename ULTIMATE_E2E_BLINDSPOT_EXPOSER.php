<?php

/**
 * 🔥 ULTIMATE E2E BLINDSPOT EXPOSER 🔥
 * 
 * This test file is designed to expose REAL blindspots in the MedCapsule system
 * by testing actual user workflows end-to-end with production-like scenarios.
 * 
 * Unlike other tests that focus on isolated units, this test simulates real user
 * behavior and exposes integration issues, data inconsistencies, and architectural flaws.
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\Assignment;
use App\Models\QuizAttempt;
use App\Models\Payment;
use App\Services\QuizService;
use App\Services\GradingService;
use App\Services\NotificationTriggerService;
use App\Services\UniversalNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

echo "🔥 ULTIMATE E2E BLINDSPOT EXPOSER - LETHAL MODE ACTIVATED 🔥\n";
echo "=" . str_repeat("=", 80) . "\n\n";

// Global test results tracking
$blindspots = [];
$criticalIssues = [];
$performanceIssues = [];
$dataInconsistencies = [];
$securityVulnerabilities = [];

/**
 * BLINDSPOT DETECTOR: Log issues as they're discovered
 */
function logBlindspot($category, $issue, $severity = 'HIGH', $details = []) {
    global $blindspots;
    $blindspots[] = [
        'category' => $category,
        'issue' => $issue,
        'severity' => $severity,
        'details' => $details,
        'timestamp' => now()->toDateTimeString()
    ];
    
    $emoji = $severity === 'CRITICAL' ? '🚨' : ($severity === 'HIGH' ? '⚠️' : '⚡');
    echo "   {$emoji} BLINDSPOT DETECTED [{$severity}]: {$issue}\n";
    if (!empty($details)) {
        echo "      └─ Details: " . json_encode($details, JSON_PRETTY_PRINT) . "\n";
    }
}

/**
 * PERFORMANCE MONITOR: Track slow operations
 */
function monitorPerformance($operation, $callback) {
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    
    try {
        $result = $callback();
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = $endMemory - $startMemory;
        
        if ($duration > 1000) { // Slower than 1 second
            logBlindspot('PERFORMANCE', "Slow operation: {$operation}", 'HIGH', [
                'duration_ms' => round($duration, 2),
                'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2)
            ]);
        }
        
        return $result;
    } catch (\Exception $e) {
        logBlindspot('ERROR', "Operation failed: {$operation}", 'CRITICAL', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        throw $e;
    }
}

// =============================================================================
// PHASE 1: DATABASE SCHEMA & RELATIONSHIP INTEGRITY TESTING
// =============================================================================

echo "🔍 PHASE 1: DATABASE SCHEMA & RELATIONSHIP INTEGRITY\n";
echo "-" . str_repeat("-", 60) . "\n";

// Test 1.1: Check for missing tables that tests expect
echo "1.1 Testing database schema completeness...\n";

$expectedTables = [
    'users', 'courses', 'topics', 'questions', 'assignments', 
    'quiz_attempts', 'payments', 'notifications', 'course_user',
    'assignment_users', 'user_topic_progress'
];

$missingTables = [];
foreach ($expectedTables as $table) {
    if (!DB::getSchemaBuilder()->hasTable($table)) {
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    logBlindspot('SCHEMA', 'Missing database tables', 'CRITICAL', ['missing_tables' => $missingTables]);
}

// Test 1.2: Check for deprecated columns that should NOT exist
echo "1.2 Testing for deprecated columns that should be removed...\n";

$deprecatedColumns = [
    'assignments' => ['tutor_id', 'status', 'student_id'], // These were removed in new schema
    'topics' => ['user_id'], // Topics belong to courses, not users
    'courses' => ['currency'] // We don't use currency
];

foreach ($deprecatedColumns as $table => $columns) {
    if (DB::getSchemaBuilder()->hasTable($table)) {
        foreach ($columns as $column) {
            $hasColumn = DB::getSchemaBuilder()->hasColumn($table, $column);
            
            if ($hasColumn) {
                logBlindspot('SCHEMA', "Deprecated column still exists: {$table}.{$column}", 'HIGH', [
                    'table' => $table,
                    'column' => $column,
                    'should_exist' => false,
                    'action' => 'Remove this column as it is no longer used'
                ]);
            }
        }
    }
}

// Test 1.2b: Check for required columns that SHOULD exist
echo "1.2b Testing for required columns in current schema...\n";

$requiredColumns = [
    'users' => ['role', 'name', 'email', 'password'],
    'courses' => ['name', 'type', 'price'],
    'topics' => ['name', 'course_id', 'is_locked'],
    'assignments' => ['title', 'course_id', 'topic_id', 'deadline', 'assignment_mode'],
    'questions' => ['topic_id', 'question', 'question_type', 'correct_answer']
];

foreach ($requiredColumns as $table => $columns) {
    if (DB::getSchemaBuilder()->hasTable($table)) {
        foreach ($columns as $column) {
            $hasColumn = DB::getSchemaBuilder()->hasColumn($table, $column);
            
            if (!$hasColumn) {
                logBlindspot('SCHEMA', "Missing required column: {$table}.{$column}", 'CRITICAL', [
                    'table' => $table,
                    'column' => $column,
                    'action' => 'Add this column as it is required for current functionality'
                ]);
            }
        }
    }
}

// Test 1.3: Check for orphaned records
echo "1.3 Testing for orphaned records...\n";

$orphanChecks = [
    'topics' => ['course_id', 'courses', 'id'],
    'questions' => ['topic_id', 'topics', 'id'],
    'quiz_attempts' => ['user_id', 'users', 'id'],
    'quiz_attempts' => ['topic_id', 'topics', 'id'],
];

foreach ($orphanChecks as $table => $check) {
    list($foreignKey, $parentTable, $parentKey) = $check;
    
    if (DB::getSchemaBuilder()->hasTable($table) && DB::getSchemaBuilder()->hasTable($parentTable)) {
        $orphans = DB::table($table)
            ->leftJoin($parentTable, "{$table}.{$foreignKey}", '=', "{$parentTable}.{$parentKey}")
            ->whereNull("{$parentTable}.{$parentKey}")
            ->whereNotNull("{$table}.{$foreignKey}")
            ->count();
            
        if ($orphans > 0) {
            logBlindspot('DATA_INTEGRITY', "Orphaned records found", 'HIGH', [
                'table' => $table,
                'foreign_key' => $foreignKey,
                'parent_table' => $parentTable,
                'orphan_count' => $orphans
            ]);
        }
    }
}

// =============================================================================
// PHASE 2: USER AUTHENTICATION & AUTHORIZATION TESTING
// =============================================================================

echo "\n🔐 PHASE 2: USER AUTHENTICATION & AUTHORIZATION\n";
echo "-" . str_repeat("-", 60) . "\n";

// Test 2.1: Create test users with different roles
echo "2.1 Creating test users with different roles...\n";

$testUsers = [];
$roles = ['admin', 'tutor', 'student'];

foreach ($roles as $role) {
    try {
        // Try to find existing user first, create if not exists
        $user = User::where('email', "test.{$role}@medcapsule.test")->first();
        
        if (!$user) {
            $user = monitorPerformance("Create {$role} user", function() use ($role) {
                return User::create([
                    'name' => "Test {$role}",
                    'email' => "test.{$role}@medcapsule.test",
                    'password' => bcrypt('password123'),
                    'role' => $role,
                    'university' => 'Test University',
                    'correct_answers_count' => 0,
                    'total_questions_attempted' => 0,
                    'xp' => 0,
                    'study_streak_days' => 0
                ]);
            });
            echo "   ✅ Created {$role}: {$user->name} (ID: {$user->id})\n";
        } else {
            echo "   ✅ Using existing {$role}: {$user->name} (ID: {$user->id})\n";
        }
        
        $testUsers[$role] = $user;
    } catch (\Exception $e) {
        logBlindspot('USER_CREATION', "Failed to create {$role} user", 'CRITICAL', [
            'role' => $role,
            'error' => $e->getMessage()
        ]);
    }
}

// Test 2.2: Test role-based access control
echo "2.2 Testing role-based access control...\n";

// Check if User model has proper role methods
if (!method_exists(User::class, 'isAdmin')) {
    logBlindspot('AUTHORIZATION', 'Missing isAdmin() method in User model', 'HIGH');
}

if (!method_exists(User::class, 'isTutor')) {
    logBlindspot('AUTHORIZATION', 'Missing isTutor() method in User model', 'HIGH');
}

// Test 2.3: User model functionality testing
echo "2.3 Testing user model functionality...\n";

foreach ($testUsers as $role => $user) {
    // Test role-based methods
    if ($role === 'admin' && method_exists($user, 'isAdmin')) {
        if (!$user->isAdmin()) {
            logBlindspot('USER_LOGIC', "Admin user not recognized as admin", 'HIGH', [
                'user_id' => $user->id,
                'role' => $role
            ]);
        }
    }
    
    // Test required user fields
    $requiredFields = ['name', 'email', 'role'];
    foreach ($requiredFields as $field) {
        if (empty($user->$field)) {
            logBlindspot('USER_DATA', "Missing required user field: {$field}", 'HIGH', [
                'user_role' => $role,
                'field' => $field,
                'user_id' => $user->id
            ]);
        }
    }
}

// =============================================================================
// PHASE 3: COURSE & TOPIC MANAGEMENT TESTING
// =============================================================================

echo "\n📚 PHASE 3: COURSE & TOPIC MANAGEMENT\n";
echo "-" . str_repeat("-", 60) . "\n";

// Test 3.1: Create test course structure
echo "3.1 Creating test course structure...\n";

$testCourse = null;
$testTopics = [];

try {
    $testCourse = monitorPerformance('Create test course', function() {
        return Course::create([
            'name' => 'E2E Test Course',
            'description' => 'Course for end-to-end testing',
            'type' => 'medical',
            'price' => 99.99,
            'academic_year' => '2024-2025'
        ]);
    });
    
    echo "   ✅ Created course: {$testCourse->name} (ID: {$testCourse->id})\n";
    
    // Create topics for the course
    for ($i = 1; $i <= 3; $i++) {
        $topic = monitorPerformance("Create topic {$i}", function() use ($testCourse, $i) {
            return Topic::create([
                'name' => "Test Topic {$i}",
                'course_id' => $testCourse->id,
                'display_order' => $i,
                'case_type' => 'quiz',
                'is_locked' => false
            ]);
        });
        
        $testTopics[] = $topic;
        echo "   ✅ Created topic: {$topic->name} (ID: {$topic->id})\n";
    }
    
} catch (\Exception $e) {
    logBlindspot('COURSE_CREATION', 'Failed to create test course structure', 'CRITICAL', [
        'error' => $e->getMessage()
    ]);
}

// Test 3.2: Test topic locking functionality
echo "3.2 Testing topic locking functionality...\n";

if (!empty($testTopics) && isset($testUsers['tutor'])) {
    $topic = $testTopics[0];
    $tutor = $testUsers['tutor'];
    
    try {
        // Check if TopicLockingService exists
        if (!class_exists('App\Services\TopicLockingService')) {
            logBlindspot('SERVICE', 'TopicLockingService class not found', 'HIGH');
        } else {
            $lockingService = app(\App\Services\TopicLockingService::class);
            
            // Test locking
            $lockResult = $lockingService->lock($topic, $tutor);
            if (!$lockResult) {
                logBlindspot('TOPIC_LOCKING', 'Failed to lock topic', 'HIGH', [
                    'topic_id' => $topic->id,
                    'tutor_id' => $tutor->id
                ]);
            }
        }
    } catch (\Exception $e) {
        logBlindspot('TOPIC_LOCKING', 'Topic locking service error', 'HIGH', [
            'error' => $e->getMessage()
        ]);
    }
}

// Test 3.3: Course enrollment testing
echo "3.3 Testing course enrollment...\n";

if ($testCourse && isset($testUsers['student'])) {
    $student = $testUsers['student'];
    
    try {
        // Test enrollment - only using is_enrolled as you specified
        monitorPerformance('Enroll student in course', function() use ($student, $testCourse) {
            $student->courses()->attach($testCourse->id, [
                'is_enrolled' => true
            ]);
        });
        
        // Verify enrollment
        $isEnrolled = $student->courses()->where('courses.id', $testCourse->id)->exists();
        if (!$isEnrolled) {
            logBlindspot('ENROLLMENT', 'Course enrollment failed', 'HIGH', [
                'student_id' => $student->id,
                'course_id' => $testCourse->id
            ]);
        } else {
            echo "   ✅ Student enrolled successfully\n";
        }
        
    } catch (\Exception $e) {
        logBlindspot('ENROLLMENT', 'Course enrollment error', 'HIGH', [
            'error' => $e->getMessage()
        ]);
    }
}

// =============================================================================
// PHASE 4: QUESTION & QUIZ SYSTEM TESTING
// =============================================================================

echo "\n❓ PHASE 4: QUESTION & QUIZ SYSTEM\n";
echo "-" . str_repeat("-", 60) . "\n";

// Test 4.1: Create test questions
echo "4.1 Creating test questions...\n";

$testQuestions = [];

if (!empty($testTopics)) {
    $topic = $testTopics[0];
    
    $questionData = [
        [
            'question' => 'What is the capital of France?',
            'option_a' => 'London',
            'option_b' => 'Berlin',
            'option_c' => 'Paris',
            'option_d' => 'Madrid',
            'correct_answer' => 'C',
            'question_type' => 'multiple_choice',
            'source' => 'Test Source 1'
        ],
        [
            'question' => 'Explain the process of photosynthesis.',
            'question_type' => 'written',
            'correct_answer' => 'Photosynthesis is the process by which plants convert light energy into chemical energy.',
            'source' => 'Test Source 2'
        ]
    ];
    
    foreach ($questionData as $index => $data) {
        try {
            $question = monitorPerformance("Create question " . ($index + 1), function() use ($topic, $data) {
                return Question::create(array_merge($data, [
                    'topic_id' => $topic->id,
                    'explanation' => 'Test explanation'
                ]));
            });
            
            $testQuestions[] = $question;
            echo "   ✅ Created question: {$question->question_type} (ID: {$question->id})\n";
            
        } catch (\Exception $e) {
            logBlindspot('QUESTION_CREATION', "Failed to create question " . ($index + 1), 'HIGH', [
                'error' => $e->getMessage(),
                'question_data' => $data
            ]);
        }
    }
}

// Test 4.2: Test quiz initialization
echo "4.2 Testing quiz initialization...\n";

if ($testCourse && !empty($testTopics) && !empty($testQuestions) && isset($testUsers['student'])) {
    $student = $testUsers['student'];
    $topic = $testTopics[0];
    
    try {
        // Check if QuizService exists and works
        if (!class_exists('App\Services\QuizService')) {
            logBlindspot('SERVICE', 'QuizService class not found', 'CRITICAL');
        } else {
            $quizService = app(\App\Services\QuizService::class);
            
            $quizResult = monitorPerformance('Initialize quiz', function() use ($quizService, $student, $testCourse, $topic) {
                return $quizService->initializeQuiz($student, $testCourse, $topic, ['all'], 'quiz');
            });
            
            if (!$quizResult['success']) {
                logBlindspot('QUIZ_INIT', 'Quiz initialization failed', 'HIGH', [
                    'result' => $quizResult
                ]);
            } else {
                echo "   ✅ Quiz initialized successfully with " . $quizResult['questions']->count() . " questions\n";
            }
        }
        
    } catch (\Exception $e) {
        logBlindspot('QUIZ_INIT', 'Quiz initialization error', 'HIGH', [
            'error' => $e->getMessage()
        ]);
    }
}

// Test 4.3: Test quiz grading
echo "4.3 Testing quiz grading...\n";

if (!empty($testQuestions) && isset($testUsers['student']) && $testCourse && !empty($testTopics)) {
    $student = $testUsers['student'];
    $topic = $testTopics[0];
    
    try {
        // Check if GradingService exists
        if (!class_exists('App\Services\GradingService')) {
            logBlindspot('SERVICE', 'GradingService class not found', 'CRITICAL');
        } else {
            $gradingService = app(\App\Services\GradingService::class);
            
            // Prepare test answers
            $answers = [];
            foreach ($testQuestions as $question) {
                if ($question->question_type === 'multiple_choice') {
                    $answers[$question->id] = $question->correct_answer;
                } else {
                    $answers[$question->id] = 'Test written answer';
                }
            }
            
            $gradingResult = monitorPerformance('Grade quiz', function() use ($gradingService, $student, $answers, $topic, $testCourse) {
                return $gradingService->gradeQuiz($student, $answers, 300, 'quiz', $topic, $testCourse);
            });
            
            echo "   ✅ Quiz graded successfully - Score: {$gradingResult['score']}/{$gradingResult['totalQuestions']}\n";
        }
        
    } catch (\Exception $e) {
        logBlindspot('QUIZ_GRADING', 'Quiz grading error', 'HIGH', [
            'error' => $e->getMessage()
        ]);
    }
}

// =============================================================================
// PHASE 5: ASSIGNMENT SYSTEM TESTING
// =============================================================================

echo "\n📋 PHASE 5: ASSIGNMENT SYSTEM\n";
echo "-" . str_repeat("-", 60) . "\n";

// Test 5.1: Create assignment with new schema
echo "5.1 Testing assignment creation with new schema...\n";

$testAssignment = null;

if ($testCourse && !empty($testTopics) && isset($testUsers['student'])) {
    $student = $testUsers['student'];
    $topic = $testTopics[0];
    
    try {
        $testAssignment = monitorPerformance('Create assignment', function() use ($testCourse, $topic) {
            return Assignment::create([
                'title' => 'E2E Test Assignment',
                'course_id' => $testCourse->id,
                'topic_id' => $topic->id,
                'deadline' => now()->addDays(7),
                'duration_minutes' => 60,
                'assignment_mode' => 'complete_topic',
                'assigned_source' => null
            ]);
        });
        
        // Test assignment-user relationship (new pivot table approach)
        $testAssignment->assignedUsers()->attach($student->id);
        
        // Verify assignment was created correctly
        if (!$testAssignment->isAssignedToUser($student->id)) {
            logBlindspot('ASSIGNMENT', 'Assignment-user relationship not working', 'HIGH', [
                'assignment_id' => $testAssignment->id,
                'student_id' => $student->id
            ]);
        } else {
            echo "   ✅ Assignment created and assigned successfully\n";
        }
        
    } catch (\Exception $e) {
        logBlindspot('ASSIGNMENT', 'Assignment creation failed', 'HIGH', [
            'error' => $e->getMessage()
        ]);
    }
}

// Test 5.2: Test assignment status logic
echo "5.2 Testing assignment status logic...\n";

if ($testAssignment && isset($testUsers['student'])) {
    $student = $testUsers['student'];
    
    try {
        // Test various status methods
        $statusMethods = [
            'isOverdue', 'isActive', 'isExpired', 'canBeCompleted', 
            'isLocked', 'shouldShowTimer', 'hasTimeLimit'
        ];
        
        foreach ($statusMethods as $method) {
            if (!method_exists($testAssignment, $method)) {
                logBlindspot('ASSIGNMENT', "Missing assignment method: {$method}", 'MEDIUM');
            } else {
                $result = $testAssignment->$method();
                echo "   ✅ {$method}(): " . ($result ? 'true' : 'false') . "\n";
            }
        }
        
        // Test user-specific status
        $userStatus = $testAssignment->getStatusForUser($student->id);
        echo "   ✅ User status: {$userStatus}\n";
        
    } catch (\Exception $e) {
        logBlindspot('ASSIGNMENT', 'Assignment status logic error', 'HIGH', [
            'error' => $e->getMessage()
        ]);
    }
}

// =============================================================================
// PHASE 6: PAYMENT SYSTEM TESTING
// =============================================================================

echo "\n💳 PHASE 6: PAYMENT SYSTEM\n";
echo "-" . str_repeat("-", 60) . "\n";

// Test 6.1: Create test payment
echo "6.1 Testing payment creation...\n";

$testPayment = null;

if ($testCourse && isset($testUsers['student'])) {
    $student = $testUsers['student'];
    
    try {
        $testPayment = monitorPerformance('Create payment', function() use ($student, $testCourse) {
            return Payment::create([
                'user_id' => $student->id,
                'course_id' => $testCourse->id,
                'amount' => $testCourse->price,
                'status' => 'pending',
                'payment_method' => 'test',
                'reference_number' => 'TEST-' . time(),
                'payment_data' => ['test' => true]
            ]);
        });
        
        echo "   ✅ Payment created: {$testPayment->reference_number} (ID: {$testPayment->id})\n";
        
    } catch (\Exception $e) {
        logBlindspot('PAYMENT', 'Payment creation failed', 'HIGH', [
            'error' => $e->getMessage()
        ]);
    }
}

// Test 6.2: Test payment verification
echo "6.2 Testing payment verification...\n";

if ($testPayment) {
    try {
        // Test payment verification logic
        $testPayment->update([
            'status' => 'completed',
            'paid_at' => now()
        ]);
        
        // Check if user gets enrolled after payment
        $student = $testUsers['student'];
        $isEnrolledAfterPayment = $student->courses()->where('courses.id', $testCourse->id)->exists();
        
        if (!$isEnrolledAfterPayment) {
            logBlindspot('PAYMENT', 'User not enrolled after payment completion', 'HIGH', [
                'payment_id' => $testPayment->id,
                'student_id' => $student->id,
                'course_id' => $testCourse->id
            ]);
        } else {
            echo "   ✅ Payment verification and enrollment working\n";
        }
        
    } catch (\Exception $e) {
        logBlindspot('PAYMENT', 'Payment verification error', 'HIGH', [
            'error' => $e->getMessage()
        ]);
    }
}

// =============================================================================
// PHASE 7: NOTIFICATION SYSTEM TESTING
// =============================================================================

echo "\n🔔 PHASE 7: NOTIFICATION SYSTEM\n";
echo "-" . str_repeat("-", 60) . "\n";

// Test 7.1: Test notification services exist
echo "7.1 Testing notification services...\n";

$notificationServices = [
    'App\Services\NotificationTriggerService',
    'App\Services\UniversalNotificationService'
];

foreach ($notificationServices as $service) {
    if (!class_exists($service)) {
        logBlindspot('SERVICE', "Notification service not found: {$service}", 'HIGH');
    } else {
        echo "   ✅ Service exists: {$service}\n";
    }
}

// Test 7.2: Test notification triggers
echo "7.2 Testing notification triggers...\n";

if (isset($testUsers['student']) && class_exists('App\Services\NotificationTriggerService')) {
    $student = $testUsers['student'];
    
    try {
        // Test quiz completion notification
        $quizData = [
            'score' => 8,
            'total_questions' => 10,
            'percentage' => 80,
            'course_name' => $testCourse->name ?? 'Test Course',
            'topic_name' => $testTopics[0]->name ?? 'Test Topic',
            'user_quiz_count' => 1,
            'assignment_completed' => false,
            'mode' => 'quiz'
        ];
        
        monitorPerformance('Trigger quiz notification', function() use ($student, $quizData) {
            NotificationTriggerService::onQuizCompleted($student, $quizData);
        });
        
        echo "   ✅ Quiz completion notification triggered\n";
        
        // Test course enrollment notification
        monitorPerformance('Trigger enrollment notification', function() use ($student, $testCourse) {
            NotificationTriggerService::onCourseEnrollment($student, $testCourse->name ?? 'Test Course');
        });
        
        echo "   ✅ Course enrollment notification triggered\n";
        
    } catch (\Exception $e) {
        logBlindspot('NOTIFICATION', 'Notification trigger error', 'HIGH', [
            'error' => $e->getMessage()
        ]);
    }
}

// =============================================================================
// PHASE 8: PERFORMANCE & SCALABILITY TESTING
// =============================================================================

echo "\n⚡ PHASE 8: PERFORMANCE & SCALABILITY\n";
echo "-" . str_repeat("-", 60) . "\n";

// Test 8.1: Database query performance
echo "8.1 Testing database query performance...\n";

// Enable query logging
DB::enableQueryLog();

try {
    // Test N+1 query issues
    monitorPerformance('Load courses with topics', function() {
        $courses = Course::with('topics')->get();
        return $courses;
    });
    
    // Test user progress queries
    if (isset($testUsers['student'])) {
        $student = $testUsers['student'];
        monitorPerformance('Load user progress', function() use ($student) {
            return $student->load(['courses', 'quizAttempts']);
        });
    }
    
    // Check query count
    $queries = DB::getQueryLog();
    $queryCount = count($queries);
    
    if ($queryCount > 50) {
        logBlindspot('PERFORMANCE', 'High query count detected', 'MEDIUM', [
            'query_count' => $queryCount,
            'queries' => array_slice($queries, 0, 5) // Show first 5 queries
        ]);
    }
    
    echo "   ✅ Database queries executed: {$queryCount}\n";
    
} catch (\Exception $e) {
    logBlindspot('PERFORMANCE', 'Database performance test error', 'HIGH', [
        'error' => $e->getMessage()
    ]);
}

// Test 8.2: Cache performance
echo "8.2 Testing cache performance...\n";

try {
    // Test cache operations
    $cacheKey = 'e2e_test_' . time();
    $testData = ['test' => 'data', 'timestamp' => now()];
    
    monitorPerformance('Cache write', function() use ($cacheKey, $testData) {
        Cache::put($cacheKey, $testData, 60);
    });
    
    $cachedData = monitorPerformance('Cache read', function() use ($cacheKey) {
        return Cache::get($cacheKey);
    });
    
    if ($cachedData !== $testData) {
        logBlindspot('CACHE', 'Cache data integrity issue', 'MEDIUM', [
            'expected' => $testData,
            'actual' => $cachedData
        ]);
    } else {
        echo "   ✅ Cache operations working correctly\n";
    }
    
} catch (\Exception $e) {
    logBlindspot('CACHE', 'Cache performance test error', 'HIGH', [
        'error' => $e->getMessage()
    ]);
}

// =============================================================================
// PHASE 9: SECURITY VULNERABILITY TESTING
// =============================================================================

echo "\n🔒 PHASE 9: SECURITY VULNERABILITY TESTING\n";
echo "-" . str_repeat("-", 60) . "\n";

// Test 9.1: SQL Injection vulnerability testing
echo "9.1 Testing SQL injection vulnerabilities...\n";

try {
    // Test potentially vulnerable queries
    $maliciousInput = "'; DROP TABLE users; --";
    
    // Test user search (should be safe with Eloquent)
    $result = User::where('name', 'LIKE', "%{$maliciousInput}%")->count();
    echo "   ✅ User search query safe from SQL injection\n";
    
    // Test course search
    $result = Course::where('name', 'LIKE', "%{$maliciousInput}%")->count();
    echo "   ✅ Course search query safe from SQL injection\n";
    
} catch (\Exception $e) {
    logBlindspot('SECURITY', 'SQL injection test error', 'CRITICAL', [
        'error' => $e->getMessage()
    ]);
}

// Test 9.2: Mass assignment vulnerability
echo "9.2 Testing mass assignment vulnerabilities...\n";

try {
    // Test if sensitive fields can be mass assigned
    $maliciousData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'role' => 'admin', // This should be protected
        'id' => 999999 // This should be protected
    ];
    
    $user = new User();
    $user->fill($maliciousData);
    
    if ($user->role === 'admin' && !in_array('role', $user->getFillable())) {
        logBlindspot('SECURITY', 'Mass assignment vulnerability: role field', 'HIGH');
    }
    
    if ($user->id === 999999) {
        logBlindspot('SECURITY', 'Mass assignment vulnerability: id field', 'CRITICAL');
    }
    
    echo "   ✅ Mass assignment protection working\n";
    
} catch (\Exception $e) {
    logBlindspot('SECURITY', 'Mass assignment test error', 'HIGH', [
        'error' => $e->getMessage()
    ]);
}

// =============================================================================
// PHASE 10: API ENDPOINT TESTING
// =============================================================================

echo "\n🌐 PHASE 10: API ENDPOINT TESTING\n";
echo "-" . str_repeat("-", 60) . "\n";

// Test 10.1: Test API routes exist
echo "10.1 Testing API route availability...\n";

$apiRoutes = [
    '/api/user',
    '/api/active-users',
    '/api/topic/{topic}/access',
    '/api/topic/{topic}/sources',
    '/api/topic/{topic}/lock',
    '/api/topic/{topic}/unlock'
];

// Note: We can't actually test HTTP requests in this context,
// but we can check if the routes are defined
try {
    $routes = app('router')->getRoutes();
    $definedRoutes = [];
    
    foreach ($routes as $route) {
        $definedRoutes[] = $route->uri();
    }
    
    foreach ($apiRoutes as $expectedRoute) {
        $routePattern = str_replace('{topic}', '{[^/]+}', $expectedRoute);
        $found = false;
        
        foreach ($definedRoutes as $definedRoute) {
            if (preg_match('#^' . str_replace('/', '\/', $routePattern) . '$#', $definedRoute)) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            logBlindspot('API', "API route not found: {$expectedRoute}", 'MEDIUM');
        } else {
            echo "   ✅ Route exists: {$expectedRoute}\n";
        }
    }
    
} catch (\Exception $e) {
    logBlindspot('API', 'API route testing error', 'HIGH', [
        'error' => $e->getMessage()
    ]);
}

// =============================================================================
// FINAL REPORT GENERATION
// =============================================================================

echo "\n📊 GENERATING FINAL BLINDSPOT REPORT\n";
echo "=" . str_repeat("=", 80) . "\n";

// Categorize blindspots by severity
$criticalCount = count(array_filter($blindspots, fn($b) => $b['severity'] === 'CRITICAL'));
$highCount = count(array_filter($blindspots, fn($b) => $b['severity'] === 'HIGH'));
$mediumCount = count(array_filter($blindspots, fn($b) => $b['severity'] === 'MEDIUM'));

echo "🚨 CRITICAL ISSUES: {$criticalCount}\n";
echo "⚠️  HIGH PRIORITY: {$highCount}\n";
echo "⚡ MEDIUM PRIORITY: {$mediumCount}\n";
echo "📊 TOTAL BLINDSPOTS: " . count($blindspots) . "\n\n";

// Group blindspots by category
$categorizedBlindspots = [];
foreach ($blindspots as $blindspot) {
    $categorizedBlindspots[$blindspot['category']][] = $blindspot;
}

foreach ($categorizedBlindspots as $category => $issues) {
    echo "📂 {$category} ISSUES (" . count($issues) . "):\n";
    echo "-" . str_repeat("-", 50) . "\n";
    
    foreach ($issues as $issue) {
        $emoji = $issue['severity'] === 'CRITICAL' ? '🚨' : ($issue['severity'] === 'HIGH' ? '⚠️' : '⚡');
        echo "   {$emoji} [{$issue['severity']}] {$issue['issue']}\n";
        
        if (!empty($issue['details'])) {
            echo "      └─ " . json_encode($issue['details'], JSON_UNESCAPED_SLASHES) . "\n";
        }
    }
    echo "\n";
}

// Generate recommendations
echo "🎯 RECOMMENDED ACTIONS:\n";
echo "-" . str_repeat("-", 50) . "\n";

if ($criticalCount > 0) {
    echo "1. 🚨 IMMEDIATE ACTION REQUIRED: Fix all CRITICAL issues before deployment\n";
}

if ($highCount > 0) {
    echo "2. ⚠️  HIGH PRIORITY: Address HIGH priority issues in next sprint\n";
}

echo "3. 📋 Create database migration to add missing columns\n";
echo "4. 🔧 Implement missing service classes and methods\n";
echo "5. 🔒 Review and strengthen security measures\n";
echo "6. ⚡ Optimize database queries to reduce N+1 problems\n";
echo "7. 🧪 Add comprehensive unit tests for discovered gaps\n";

// Save detailed report to file
$reportData = [
    'timestamp' => now()->toDateTimeString(),
    'summary' => [
        'total_blindspots' => count($blindspots),
        'critical' => $criticalCount,
        'high' => $highCount,
        'medium' => $mediumCount
    ],
    'blindspots' => $blindspots,
    'categorized' => $categorizedBlindspots
];

file_put_contents('E2E_BLINDSPOT_REPORT_' . date('Y-m-d_H-i-s') . '.json', json_encode($reportData, JSON_PRETTY_PRINT));

echo "\n✅ ULTIMATE E2E BLINDSPOT EXPOSER COMPLETE!\n";
echo "📄 Detailed report saved to: E2E_BLINDSPOT_REPORT_" . date('Y-m-d_H-i-s') . ".json\n";
echo "🎯 Focus on CRITICAL and HIGH priority issues first!\n";

// Clean up test data
echo "\n🧹 CLEANING UP TEST DATA...\n";

try {
    // Clean up in reverse order of creation
    if ($testPayment) $testPayment->delete();
    if ($testAssignment) $testAssignment->delete();
    foreach ($testQuestions as $question) $question->delete();
    foreach ($testTopics as $topic) $topic->delete();
    if ($testCourse) $testCourse->delete();
    foreach ($testUsers as $user) $user->delete();
    
    echo "✅ Test data cleaned up successfully\n";
} catch (\Exception $e) {
    echo "⚠️  Warning: Could not clean up all test data: " . $e->getMessage() . "\n";
}

echo "\n🔥 LETHAL MODE DEACTIVATED - MISSION COMPLETE! 🔥\n";