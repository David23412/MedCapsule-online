<?php

// Test N+1 Pattern for DatabaseProbe Detection
class TestController extends Controller
{
    public function testN1Pattern()
    {
        // CRITICAL N+1 PATTERN - Should be detected
        $users = User::all();
        foreach ($users as $user) {
            $courses = $user->courses; // N+1 query pattern!
            foreach ($courses as $course) {
                $topics = $course->topics; // Another N+1!
            }
        }
        
        // Another N+1 pattern
        $courses = Course::all();
        foreach ($courses as $course) {
            $enrolledCount = $course->users()->count(); // N+1 query!
        }
        
        return view('test');
    }
}