<?php

/**
 * 🔥 COMPREHENSIVE COURSE ACCESS VALIDATION SCRIPT
 * 
 * This script tests ALL course access scenarios to identify inconsistencies
 * Run with: php artisan tinker < tests/course_access_validation.php
 */

echo "\n🔍 STARTING COMPREHENSIVE COURSE ACCESS VALIDATION\n";
echo str_repeat("=", 80) . "\n\n";

// Helper function to display results
function displayResult($testName, $expected, $actual, $passed) {
    $icon = $passed ? "✅" : "❌";
    echo "{$icon} {$testName}\n";
    echo "   Expected: {$expected}\n";
    echo "   Actual: {$actual}\n";
    if (!$passed) {
        echo "   ⚠️  MISMATCH DETECTED!\n";
    }
    echo "\n";
    return $passed;
}

$allPassed = true;

try {
    // Create test users
    echo "📝 Creating test users...\n";
    $admin = \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)->first();
    $tutor = \App\Models\User::where('role', \App\Models\User::ROLE_TUTOR)->first();
    $student = \App\Models\User::where('role', \App\Models\User::ROLE_STUDENT)->first();
    
    if (!$admin || !$tutor || !$student) {
        echo "❌ ERROR: Missing required user roles in database\n";
        echo "   Admin: " . ($admin ? "✅" : "❌") . "\n";
        echo "   Tutor: " . ($tutor ? "✅" : "❌") . "\n";
        echo "   Student: " . ($student ? "✅" : "❌") . "\n";
        exit(1);
    }
    
    echo "✅ Test users loaded\n";
    echo "   Admin ID: {$admin->id}\n";
    echo "   Tutor ID: {$tutor->id}\n";
    echo "   Student ID: {$student->id}\n\n";
    
    // Test 1: Check if access_type column exists
    echo "🔍 TEST 1: Database Schema Validation\n";
    echo str_repeat("-", 80) . "\n";
    
    $hasAccessTypeColumn = \Illuminate\Support\Facades\Schema::hasColumn('courses', 'access_type');
    $allPassed &= displayResult(
        "access_type column exists",
        "true",
        $hasAccessTypeColumn ? "true" : "false",
        $hasAccessTypeColumn
    );
    
    // Test 2: Check for courses with each access type
    echo "🔍 TEST 2: Access Type Distribution\n";
    echo str_repeat("-", 80) . "\n";
    
    $freeAccessCount = \App\Models\Course::where('access_type', 'free')->count();
    $previewAccessCount = \App\Models\Course::where('access_type', 'preview')->count();
    $paidAccessCount = \App\Models\Course::where('access_type', 'paid')->count();
    $nullAccessCount = \App\Models\Course::whereNull('access_type')->count();
    
    echo "   Free access courses: {$freeAccessCount}\n";
    echo "   Preview access courses: {$previewAccessCount}\n";
    echo "   Paid access courses: {$paidAccessCount}\n";
    echo "   NULL access_type: {$nullAccessCount}\n\n";
    
    if ($nullAccessCount > 0) {
        echo "⚠️  WARNING: {$nullAccessCount} courses have NULL access_type!\n";
        echo "   These courses may behave unpredictably.\n\n";
        $allPassed = false;
    }
    
    // Test 3: Get sample courses for testing
    echo "🔍 TEST 3: Loading Sample Courses\n";
    echo str_repeat("-", 80) . "\n";
    
    $freeCourse = \App\Models\Course::where('access_type', 'free')->first();
    $previewCourse = \App\Models\Course::where('access_type', 'preview')->first();
    $paidCourse = \App\Models\Course::where('access_type', 'paid')->first();
    
    echo "   Free course: " . ($freeCourse ? "✅ ID {$freeCourse->id}" : "❌ NONE") . "\n";
    echo "   Preview course: " . ($previewCourse ? "✅ ID {$previewCourse->id}" : "❌ NONE") . "\n";
    echo "   Paid course: " . ($paidCourse ? "✅ ID {$paidCourse->id}" : "❌ NONE") . "\n\n";
    
    // Test 4: Admin Access (should always be FULL)
    if ($freeCourse) {
        echo "🔍 TEST 4: Admin Access to Free Course\n";
        echo str_repeat("-", 80) . "\n";
        
        $adminHasAccess = $admin->hasAccessToCourse($freeCourse->id);
        $adminAccessLevel = $admin->getCourseAccessLevel($freeCourse->id);
        $adminHasFullAccess = $admin->hasFullAccessToCourse($freeCourse);
        
        $allPassed &= displayResult(
            "Admin hasAccessToCourse()",
            "true",
            $adminHasAccess ? "true" : "false",
            $adminHasAccess === true
        );
        
        $allPassed &= displayResult(
            "Admin getCourseAccessLevel()",
            "full",
            $adminAccessLevel ?? "null",
            $adminAccessLevel === 'full'
        );
        
        $allPassed &= displayResult(
            "Admin hasFullAccessToCourse()",
            "true",
            $adminHasFullAccess ? "true" : "false",
            $adminHasFullAccess === true
        );
    }
    
    // Test 5: Tutor Access (should always be FULL, even without enrollment)
    if ($freeCourse) {
        echo "🔍 TEST 5: Tutor Access Without Enrollment\n";
        echo str_repeat("-", 80) . "\n";
        
        // Ensure tutor is NOT enrolled
        \DB::table('course_user')
            ->where('user_id', $tutor->id)
            ->where('course_id', $freeCourse->id)
            ->delete();
        
        $tutorHasAccess = $tutor->hasAccessToCourse($freeCourse->id);
        $tutorAccessLevel = $tutor->getCourseAccessLevel($freeCourse->id);
        $tutorHasFullAccess = $tutor->hasFullAccessToCourse($freeCourse);
        
        $allPassed &= displayResult(
            "Tutor hasAccessToCourse() (not enrolled)",
            "true",
            $tutorHasAccess ? "true" : "false",
            $tutorHasAccess === true
        );
        
        $allPassed &= displayResult(
            "Tutor getCourseAccessLevel() (not enrolled)",
            "full",
            $tutorAccessLevel ?? "null",
            $tutorAccessLevel === 'full'
        );
        
        $allPassed &= displayResult(
            "Tutor hasFullAccessToCourse() (not enrolled)",
            "true",
            $tutorHasFullAccess ? "true" : "false",
            $tutorHasFullAccess === true
        );
    }
    
    // Test 6: Student Access to Free Course (with survey)
    if ($freeCourse) {
        echo "🔍 TEST 6: Student Access to Free Course (With Survey)\n";
        echo str_repeat("-", 80) . "\n";
        
        // Enroll student
        \DB::table('course_user')->updateOrInsert(
            ['user_id' => $student->id, 'course_id' => $freeCourse->id],
            ['is_enrolled' => true, 'access_level' => 'full', 'updated_at' => now()]
        );
        
        // Mark survey as completed (check schema first)
        $surveyColumns = \Illuminate\Support\Facades\Schema::getColumnListing('survey_responses');
        $surveyData = ['answers' => json_encode([]), 'updated_at' => now()];
        if (in_array('completed_at', $surveyColumns)) {
            $surveyData['completed_at'] = now();
        }
        \DB::table('survey_responses')->updateOrInsert(
            ['user_id' => $student->id, 'survey_key' => 'free_course_unlock_v1'],
            $surveyData
        );
        
        // Clear cache
        \Cache::forget("user_course_optimization_data_{$student->id}");
        
        $studentHasAccess = $student->hasAccessToCourse($freeCourse->id);
        $studentAccessLevel = $student->getCourseAccessLevel($freeCourse->id);
        $studentHasFullAccess = $student->hasFullAccessToCourse($freeCourse);
        
        $allPassed &= displayResult(
            "Student hasAccessToCourse() (enrolled + survey)",
            "true",
            $studentHasAccess ? "true" : "false",
            $studentHasAccess === true
        );
        
        $allPassed &= displayResult(
            "Student getCourseAccessLevel() (enrolled + survey)",
            "full",
            $studentAccessLevel ?? "null",
            $studentAccessLevel === 'full'
        );
        
        $allPassed &= displayResult(
            "Student hasFullAccessToCourse() (enrolled + survey)",
            "true",
            $studentHasFullAccess ? "true" : "false",
            $studentHasFullAccess === true
        );
    }
    
    // Test 7: Student Access to Preview Course (with survey)
    if ($previewCourse) {
        echo "🔍 TEST 7: Student Access to Preview Course (With Survey)\n";
        echo str_repeat("-", 80) . "\n";
        
        // Enroll student
        \DB::table('course_user')->updateOrInsert(
            ['user_id' => $student->id, 'course_id' => $previewCourse->id],
            ['is_enrolled' => true, 'access_level' => 'preview', 'updated_at' => now()]
        );
        
        // Clear cache
        \Cache::forget("user_course_optimization_data_{$student->id}");
        
        $studentHasAccess = $student->hasAccessToCourse($previewCourse->id);
        $studentAccessLevel = $student->getCourseAccessLevel($previewCourse->id);
        $studentHasFullAccess = $student->hasFullAccessToCourse($previewCourse);
        $previewLimit = $student->getPreviewTopicLimitForCourse($previewCourse);
        
        $allPassed &= displayResult(
            "Student hasAccessToCourse() (preview + survey)",
            "true",
            $studentHasAccess ? "true" : "false",
            $studentHasAccess === true
        );
        
        $allPassed &= displayResult(
            "Student getCourseAccessLevel() (preview + survey)",
            "preview",
            $studentAccessLevel ?? "null",
            $studentAccessLevel === 'preview'
        );
        
        $allPassed &= displayResult(
            "Student hasFullAccessToCourse() (preview + survey)",
            "false",
            $studentHasFullAccess ? "true" : "false",
            $studentHasFullAccess === false
        );
        
        $allPassed &= displayResult(
            "Student getPreviewTopicLimitForCourse()",
            "5",
            (string)$previewLimit,
            $previewLimit === 5
        );
    }
    
    // Test 8: Student Access to Paid Course (without payment)
    if ($paidCourse) {
        echo "🔍 TEST 8: Student Access to Paid Course (No Payment)\n";
        echo str_repeat("-", 80) . "\n";
        
        // Enroll student but don't add payment
        \DB::table('course_user')->updateOrInsert(
            ['user_id' => $student->id, 'course_id' => $paidCourse->id],
            ['is_enrolled' => true, 'access_level' => null, 'updated_at' => now()]
        );
        
        // Clear cache
        \Cache::forget("user_course_optimization_data_{$student->id}");
        
        $studentHasAccess = $student->hasAccessToCourse($paidCourse->id);
        $studentAccessLevel = $student->getCourseAccessLevel($paidCourse->id);
        $studentHasFullAccess = $student->hasFullAccessToCourse($paidCourse);
        
        $allPassed &= displayResult(
            "Student hasAccessToCourse() (enrolled, no payment)",
            "false",
            $studentHasAccess ? "true" : "false",
            $studentHasAccess === false
        );
        
        $allPassed &= displayResult(
            "Student getCourseAccessLevel() (enrolled, no payment)",
            "null",
            $studentAccessLevel ?? "null",
            $studentAccessLevel === null
        );
        
        $allPassed &= displayResult(
            "Student hasFullAccessToCourse() (enrolled, no payment)",
            "false",
            $studentHasFullAccess ? "true" : "false",
            $studentHasFullAccess === false
        );
    }
    
    // Test 9: Check Course Model Methods
    if ($freeCourse && $previewCourse && $paidCourse) {
        echo "🔍 TEST 9: Course Model Helper Methods\n";
        echo str_repeat("-", 80) . "\n";
        
        $allPassed &= displayResult(
            "Free course isFreeAccess()",
            "true",
            $freeCourse->isFreeAccess() ? "true" : "false",
            $freeCourse->isFreeAccess() === true
        );
        
        $allPassed &= displayResult(
            "Preview course isPreviewAccess()",
            "true",
            $previewCourse->isPreviewAccess() ? "true" : "false",
            $previewCourse->isPreviewAccess() === true
        );
        
        $allPassed &= displayResult(
            "Paid course isPaidAccess()",
            "true",
            $paidCourse->isPaidAccess() ? "true" : "false",
            $paidCourse->isPaidAccess() === true
        );
    }
    
    // Test 10: Consistency Check - Course vs User methods
    if ($previewCourse) {
        echo "🔍 TEST 10: Consistency Check (Course vs User Methods)\n";
        echo str_repeat("-", 80) . "\n";
        
        // Clear cache first
        \Cache::forget("user_course_optimization_data_{$student->id}");
        
        $courseMethod = $previewCourse->getAccessTypeForUser($student);
        $userMethod = $student->getCourseAccessLevel($previewCourse->id);
        
        echo "   Course::getAccessTypeForUser(): {$courseMethod}\n";
        echo "   User::getCourseAccessLevel(): " . ($userMethod ?? "null") . "\n";
        
        // Map between the two systems
        $courseToUserMap = [
            'full' => 'full',
            'preview' => 'preview',
            'locked' => null
        ];
        
        $expectedUserMethod = $courseToUserMap[$courseMethod] ?? null;
        $consistent = $userMethod === $expectedUserMethod;
        
        if (!$consistent) {
            echo "   ❌ INCONSISTENCY DETECTED!\n";
            echo "   Course method says: {$courseMethod}\n";
            echo "   User method says: " . ($userMethod ?? "null") . "\n";
            echo "   These should match!\n\n";
            $allPassed = false;
        } else {
            echo "   ✅ Methods are consistent\n\n";
        }
    }
    
    // Test 11: Topic Access Control
    if ($previewCourse) {
        echo "🔍 TEST 11: Topic Access Control (Preview Limits)\n";
        echo str_repeat("-", 80) . "\n";
        
        $topics = \App\Models\Topic::where('course_id', $previewCourse->id)
            ->orderBy('display_order')
            ->take(10)
            ->get();
        
        if ($topics->count() > 0) {
            foreach ($topics as $index => $topic) {
                $canAccess = $previewCourse->canUserAccessTopic($student, $topic);
                $shouldAccess = ($topic->display_order ?? 0) <= 5;
                $passed = $canAccess === $shouldAccess;
                
                $allPassed &= displayResult(
                    "Topic {$topic->display_order} access (preview limit = 5)",
                    $shouldAccess ? "true" : "false",
                    $canAccess ? "true" : "false",
                    $passed
                );
                
                if ($index >= 2) break; // Only test first 3 topics
            }
        } else {
            echo "   ⚠️  No topics found for preview course\n\n";
        }
    }
    
    // Test 12: Enrollment + Access Level Consistency
    echo "🔍 TEST 12: Enrollment + Access Level Consistency\n";
    echo str_repeat("-", 80) . "\n";
    
    $enrollmentData = \DB::table('course_user')
        ->where('user_id', $student->id)
        ->get();
    
    foreach ($enrollmentData as $enrollment) {
        $course = \App\Models\Course::find($enrollment->course_id);
        if (!$course) continue;
        
        $isEnrolled = (bool) $enrollment->is_enrolled;
        $storedAccessLevel = $enrollment->access_level;
        $computedAccessLevel = $student->getCourseAccessLevel($course->id);
        
        echo "   Course ID {$course->id} ({$course->access_type}):\n";
        echo "      is_enrolled: " . ($isEnrolled ? "true" : "false") . "\n";
        echo "      stored access_level: " . ($storedAccessLevel ?? "null") . "\n";
        echo "      computed access_level: " . ($computedAccessLevel ?? "null") . "\n";
        
        if ($isEnrolled && $computedAccessLevel === null) {
            echo "      ❌ ISSUE: Enrolled but no access level!\n";
            $allPassed = false;
        } else {
            echo "      ✅ Consistent\n";
        }
        echo "\n";
    }
    
    // Final Summary
    echo str_repeat("=", 80) . "\n";
    if ($allPassed) {
        echo "🎉 ALL TESTS PASSED! Course access logic is ROBUST and CONSISTENT!\n";
    } else {
        echo "❌ SOME TESTS FAILED! Review the issues above.\n";
        echo "\n📋 RECOMMENDED ACTIONS:\n";
        echo "   1. Fix User::getCourseAccessLevels() to use access_type column\n";
        echo "   2. Move tutor check before enrollment check\n";
        echo "   3. Ensure all courses have access_type set (not NULL)\n";
        echo "   4. Remove Course::getAccessTypeForUser() to avoid dual logic\n";
    }
    echo str_repeat("=", 80) . "\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

exit($allPassed ? 0 : 1);
