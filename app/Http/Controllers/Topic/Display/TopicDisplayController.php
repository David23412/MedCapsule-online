<?php

namespace App\Http\Controllers\Topic\Display;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 PERFORMANCE BEAST: Topic Display Controller
 * Handles: Topic display for courses
 * Lines: ~90 (well under 200 limit)
 */
class TopicDisplayController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Show topics for a specific course with ultra-fast performance
     */
    public function forCourse(Course $course)
    {
        $user = auth()->user();
        
        // Ultra-aggressive caching for topics
        $cacheKey = "course_topics_{$course->id}_{$user->id}_" . now()->format('Y-m-d-H');
        
        $data = Cache::remember($cacheKey, 3600, function () use ($course, $user) {
            // Check if user has access to the course
            $hasAccess = $user->isAdmin() || 
                        $user->hasAccessToCourse($course->id) || 
                        (!$course->price || $course->price <= 0);

            if (!$hasAccess) {
                return ['error' => 'Access denied to this course'];
            }

            // 🚀 CRITICAL N+1 FIX: Optimized eager loading to prevent N+1 queries
            $topics = $course->topics()
                ->select('id', 'name', 'course_id', 'display_order', 'is_active')
                ->with([
                    'questions:id,topic_id,difficulty',
                    'userProgress' => function($query) use ($user) {
                        $query->where('user_id', $user->id)
                              ->select('id', 'user_id', 'topic_id', 'progress_percentage', 'last_accessed');
                    }
                ])
                ->orderBy('display_order')
                ->get();

            // Calculate progress efficiently
            $progressData = $this->calculateUserProgress($topics, $user);

            return [
                'course' => $course,
                'topics' => $topics,
                'progress' => $progressData,
                'user_access' => $hasAccess
            ];
        });

        if (isset($data['error'])) {
            return redirect()->route('courses.index')->with('error', $data['error']);
        }

        return view('topics.index', $data);
    }

    /**
     * Calculate user progress for topics
     * 🚀 CRITICAL N+1 FIX: Use already loaded relationships to avoid N+1 queries
     */
    private function calculateUserProgress($topics, $user): array
    {
        $progressData = [];
        
        // 🚀 CRITICAL FIX: Relationships are already loaded via eager loading above
        // No additional database queries will be made in this loop
        foreach ($topics as $topic) {
            // Use already loaded relationships - no N+1 queries
            $userProgress = $topic->userProgress->first();
            $questionCount = $topic->questions->count(); // Uses loaded collection
            
            $progressData[$topic->id] = [
                'percentage' => $userProgress ? $userProgress->progress_percentage : 0,
                'question_count' => $questionCount,
                'last_accessed' => $userProgress ? $userProgress->last_accessed : null,
                'status' => $this->getTopicStatus($userProgress, $questionCount)
            ];
        }
        
        return $progressData;
    }

    /**
     * Get topic status based on progress
     */
    private function getTopicStatus($userProgress, $questionCount): string
    {
        if (!$userProgress) {
            return 'not_started';
        }
        
        if ($userProgress->progress_percentage >= 100) {
            return 'completed';
        }
        
        if ($userProgress->progress_percentage > 0) {
            return 'in_progress';
        }
        
        return 'not_started';
    }
}