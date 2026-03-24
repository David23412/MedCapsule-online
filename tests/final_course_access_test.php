<?php

/**
 * 🎯 FINAL COMPREHENSIVE COURSE ACCESS TEST
 * 
 * Tests all edge cases and security scenarios
 */

echo "\n🎯 FINAL COURSE ACCESS VALIDATION\n";
echo str_repeat("=", 80) . "\n\n";

$allPassed = true;
$testResults = [];

function test($name, $condition, &$allPassed, &$testResults) {
    $passed = (bool) $condition;
    $icon = $passed ? "✅" : "❌";
    echo "{$icon} {$name}\n";
    $testResults[] = ['name' => $name, 'passed' => $passed];
    if (!$passed) {
        $allPassed = false;
    }
    return $passed;
}

try {
    // Load users
    $admin = \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)->first();
    $tutor = \App\Models\User::where('role', \App\Models\User::ROLE_TUTOR)->first();
    $student = \App\Models\User::where('role', \App\Models\User::ROLE_STUDENT)->first();
    
    // Load courses
    $freeCourse = \App\Models\Course::where('access_type', 'free')->first();
    $previewCourse = \App\Models\Course::where('access_type', 'preview')->first();
    $paidCourse = \App\Models\Course::where('access_type', 'paid')->first();
    
    echo "📊 TEST SUITE 1: ADMIN ACCESS (Should bypass all restrictions)\n";
    echo str_repeat("-", 80) . "\n";
    
    test("Admin has access to free course", 
        $admin->hasAccessToCourse($freeCourse->id), $allPassed, $testResults);
    test("Admin has access to preview course", 
        $admin->hasAccessToCourse($previewCourse->id), $allPassed, $testResults);
    test("Admin has access to paid course", 
        $admin->hasAccessToCourse($paidCourse->id), $allPassed, $testResults);
    test("Admin has FULL access to all courses", 
        $admin->hasFullAccessToCourse($freeCourse) && 
        $admin->hasFullAccessToCourse($previewCourse) && 
        $admin->hasFullAccessToCourse($paidCourse), $allPassed, $testResults);
    
    echo "\n📊 TEST SUITE 2: TUTOR ACCESS (Should bypass enrollment requirement)\n";
    echo str_repeat("-", 80) . "\n";
    
    // Ensure tutor is NOT enrolled in any course
    \DB::table('course_user')->where('user_id', $tutor->id)->delete();
    \Cache::forget("user_course_optimization_data_{$tutor->id}");
    
    test("Tutor has access to free course (not enrolled)", 
        $tutor->hasAccessToCourse($freeCourse->id), $allPassed, $testResults);
    test("Tutor has access to preview course (not enrolled)", 
        $tutor->hasAccessToCourse($previewCourse->id), $allPassed, $testResults);
    test("Tutor has access to paid course (not enrolled)", 
        $tutor->hasAccessToCourse($paidCourse->id), $allPassed, $testResults);
    test("Tutor has FULL access to all courses (not enrolled)", 
        $tutor->hasFullAccessToCourse($freeCourse) && 
        $tutor->hasFullAccessToCourse($previewCourse) && 
        $tutor->hasFullAccessToCourse($paidCourse), $allPassed, $testResults);
    
    echo "\n📊 TEST SUITE 3: FREE ACCESS COURSES\n";
    echo str_repeat("-", 80) . "\n";
    
    // Enroll student in free course
    \DB::table('course_user')->updateOrInsert(
        ['user_id' => $student->id, 'course_id' => $freeCourse->id],
        ['is_enrolled' => true, 'access_level' => 'full', 'updated_at' => now()]
    );
    \Cache::forget("user_course_optimization_data_{$student->id}");
    
    test("Student enrolled in free course has access", 
        $student->hasAccessToCourse($freeCourse->id), $allPassed, $testResults);
    test("Student enrolled in free course has FULL access", 
        $student->hasFullAccessToCourse($freeCourse), $allPassed, $testResults);
    test("Student enrolled in free course gets 'full' access level", 
        $student->getCourseAccessLevel($freeCourse->id) === 'full', $allPassed, $testResults);
    
    echo "\n📊 TEST SUITE 4: PREVIEW ACCESS COURSES\n";
    echo str_repeat("-", 80) . "\n";
    
    // Enroll student in preview course
    \DB::table('course_user')->updateOrInsert(
        ['user_id' => $student->id, 'course_id' => $previewCourse->id],
        ['is_enrolled' => true, 'access_level' => 'preview', 'updated_at' => now()]
    );
    \Cache::forget("user_course_optimization_data_{$student->id}");
    
    test("Student enrolled in preview course has access", 
        $student->hasAccessToCourse($previewCourse->id), $allPassed, $testResults);
    test("Student enrolled in preview course has LIMITED access (not full)", 
        !$student->hasFullAccessToCourse($previewCourse), $allPassed, $testResults);
    test("Student enrolled in preview course gets 'preview' access level", 
        $student->getCourseAccessLevel($previewCourse->id) === 'preview', $allPassed, $testResults);
    test("Student preview limit is 5 topics", 
        $student->getPreviewTopicLimitForCourse($previewCourse) === 5, $allPassed, $testResults);
    
    // Test topic access limits
    $previewTopics = \App\Models\Topic::where('course_id', $previewCourse->id)
        ->orderBy('display_order')
        ->get();
    
    if ($previewTopics->count() >= 6) {
        $topic1 = $previewTopics->where('display_order', 1)->first();
        $topic5 = $previewTopics->where('display_order', 5)->first();
        $topic6 = $previewTopics->where('display_order', 6)->first();
        
        if ($topic1) {
            test("Student can access topic 1 (within preview limit)", 
                $previewCourse->canUserAccess