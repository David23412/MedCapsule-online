<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Topic;
use App\Models\Course;
use App\Services\TopicLockingService;
use App\Support\State\TopicLockService;

class TopicLockingController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Debug: Log user info
        \Log::info('TopicLocking - User Info', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'is_admin' => $user->role === 'admin'
        ]);
        
        // Get courses based on user role
        $topicLockService = app(TopicLockService::class);
        
        if ($user->role === 'admin') {
            // Admins can see all courses
            $courses = \App\Models\Course::with(['topics' => function ($query) {
                $query->select('id', 'course_id', 'name', 'is_locked');
            }])->get();
            
            // Enhance topics with SSOT lock status
            foreach ($courses as $course) {
                foreach ($course->topics as $topic) {
                    $lockStatus = $topicLockService->getTopicLockStatus($topic);
                    $topic->ssot_is_locked = $lockStatus['is_locked'];
                    $topic->lock_reason = $lockStatus['lock_reason'];
                }
            }
            
            \Log::info('TopicLocking - Admin sees all courses', [
                'course_count' => $courses->count(),
                'course_names' => $courses->pluck('name')->toArray()
            ]);
        } else {
            // Tutors can only see courses they're enrolled in
            $courses = $user->courses()
                ->with(['topics' => function ($query) {
                    $query->select('id', 'course_id', 'name', 'is_locked');
                }])
                ->get();
                
            // Enhance topics with SSOT lock status
            foreach ($courses as $course) {
                foreach ($course->topics as $topic) {
                    $lockStatus = $topicLockService->getTopicLockStatus($topic);
                    $topic->ssot_is_locked = $lockStatus['is_locked'];
                    $topic->lock_reason = $lockStatus['lock_reason'];
                }
            }
                
            \Log::info('TopicLocking - Tutor enrolled courses', [
                'course_count' => $courses->count(),
                'course_names' => $courses->pluck('name')->toArray()
            ]);
            
            // Debug: Check raw enrollment data
            $enrollments = \DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('is_enrolled', true)
                ->get();
                
            \Log::info('TopicLocking - Raw enrollments', [
                'enrollments' => $enrollments->toArray()
            ]);
        }
        
        return view('topic-locking.index', compact('courses'));
    }

    public function courseTopics($courseId)
    {
        $user = auth()->user();
        $course = \App\Models\Course::findOrFail($courseId);
        
        // Check if user has access to this course
        if (!$user->isAdmin() && !$user->courses()->where('courses.id', $courseId)->exists()) {
            abort(403, 'You do not have permission to manage topics in this course.');
        }
        
        return view('topic-locking.course', compact('course'));
    }

    public function lock($topicId)
    {
        try {
            $topic = \App\Models\Topic::findOrFail($topicId);
            $user = auth()->user();
            
            $lockingService = app(\App\Services\TopicLockingService::class);
            $success = $lockingService->lock($topic, $user);
            
            return response()->json([
                'success' => $success,
                'message' => 'Topic locked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function unlock($topicId)
    {
        try {
            $topic = \App\Models\Topic::findOrFail($topicId);
            $user = auth()->user();
            
            $lockingService = app(\App\Services\TopicLockingService::class);
            $success = $lockingService->unlock($topic, $user);
            
            return response()->json([
                'success' => $success,
                'message' => 'Topic unlocked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function toggle($topicId)
    {
        try {
            $topic = \App\Models\Topic::findOrFail($topicId);
            $user = auth()->user();
            
            $lockingService = app(\App\Services\TopicLockingService::class);
            $topicLockService = app(TopicLockService::class);
            
            // Use SSOT service to check current lock status
            if ($topicLockService->isTopicLocked($topic)) {
                $success = $lockingService->unlock($topic, $user);
                $message = 'Topic unlocked successfully';
            } else {
                $success = $lockingService->lock($topic, $user);
                $message = 'Topic locked successfully';
            }
            
            // Get updated lock status from SSOT service
            $updatedLockStatus = $topicLockService->getTopicLockStatus($topic->fresh());
            
            return response()->json([
                'success' => $success,
                'message' => $message,
                'is_locked' => $updatedLockStatus['is_locked']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function bulkLock(Request $request)
    {
        try {
            $topicIds = $request->input('topic_ids', []);
            $user = auth()->user();
            
            $lockingService = app(\App\Services\TopicLockingService::class);
            $results = $lockingService->bulkLock($topicIds, $user);
            
            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function bulkUnlock(Request $request)
    {
        try {
            $topicIds = $request->input('topic_ids', []);
            $user = auth()->user();
            
            $lockingService = app(\App\Services\TopicLockingService::class);
            $results = $lockingService->bulkUnlock($topicIds, $user);
            
            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function lockCourse($courseId)
    {
        try {
            $user = auth()->user();
            $course = \App\Models\Course::findOrFail($courseId);
            
            // Check if user has access to this course
            if (!$user->isAdmin() && !$user->courses()->where('courses.id', $courseId)->exists()) {
                throw new \InvalidArgumentException('You do not have permission to manage topics in this course.');
            }
            
            $topicIds = $course->topics()->pluck('id')->toArray();
            $lockingService = app(\App\Services\TopicLockingService::class);
            $results = $lockingService->bulkLock($topicIds, $user);
            
            return response()->json([
                'success' => true,
                'message' => 'All topics in course locked successfully',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function unlockCourse($courseId)
    {
        try {
            $user = auth()->user();
            $course = \App\Models\Course::findOrFail($courseId);
            
            // Check if user has access to this course
            if (!$user->isAdmin() && !$user->courses()->where('courses.id', $courseId)->exists()) {
                throw new \InvalidArgumentException('You do not have permission to manage topics in this course.');
            }
            
            $topicIds = $course->topics()->pluck('id')->toArray();
            $lockingService = app(\App\Services\TopicLockingService::class);
            $results = $lockingService->bulkUnlock($topicIds, $user);
            
            return response()->json([
                'success' => true,
                'message' => 'All topics in course unlocked successfully',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getTopicStatus($topic)
    {
        return response()->json(['locked' => false]);
    }

    public function checkStudentAccess($topic)
    {
        return response()->json(['access' => true]);
    }

    public function getAccessibleTopics()
    {
        return response()->json([]);
    }

    public function getStatistics()
    {
        return response()->json(['stats' => []]);
    }

    public function getLockedTopics()
    {
        return response()->json([]);
    }
}