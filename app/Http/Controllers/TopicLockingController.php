<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Topic;
use App\Models\Course;
use App\Services\TopicLockingService;

class TopicLockingController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // CRITICAL FIX: Use integer role constants instead of string comparison
        // User model defines ROLE_ADMIN = 1, not 'admin'
        $isAdmin = $user->isAdmin();

        // Debug: Log user info with proper role checking
        \Log::info('TopicLocking - User Info (FIXED)', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'role_type' => gettype($user->role),
            'is_admin' => $isAdmin,
            'admin_check_method' => 'isAdmin()'
        ]);

        // Get courses based on user role using proper role checking
        if ($isAdmin) {
            // Admins can see all courses
            $courses = \App\Models\Course::with(['topics' => function ($query) {
                $query->select('id', 'course_id', 'name', 'is_locked');
            }])->get();

            \Log::info('TopicLocking - Admin sees all courses (FIXED)', [
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

            \Log::info('TopicLocking - Tutor enrolled courses (FIXED)', [
                'course_count' => $courses->count(),
                'course_names' => $courses->pluck('name')->toArray()
            ]);

            // Debug: Check raw enrollment data
            $enrollments = \DB::table('course_user')
                ->where('user_id', $user->id)
                ->whereNotNull('enrolled_at')
                ->get();

            \Log::info('TopicLocking - Raw enrollments (FIXED)', [
                'enrollments' => $enrollments->toArray()
            ]);
        }

        return view('topic-locking.index', compact('courses'));
    }

    public function courseTopics($courseId)
    {
        $user = auth()->user();
        $course = \App\Models\Course::findOrFail($courseId);

        // CRITICAL FIX: Use consistent admin checking with isAdmin() method
        // This ensures admin users (role = 1) are properly recognized
        if (!$user->isAdmin() && !$user->courses()->where('courses.id', $courseId)->exists()) {
            \Log::warning('TopicLocking - Access denied to course', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'course_id' => $courseId,
                'is_admin' => $user->isAdmin()
            ]);
            abort(403, 'You do not have permission to manage topics in this course.');
        }

        \Log::info('TopicLocking - Course access granted', [
            'user_id' => $user->id,
            'course_id' => $courseId,
            'is_admin' => $user->isAdmin()
        ]);

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
            
            if ($topic->is_locked) {
                $success = $lockingService->unlock($topic, $user);
                $message = 'Topic unlocked successfully';
            } else {
                $success = $lockingService->lock($topic, $user);
                $message = 'Topic locked successfully';
            }
            
            return response()->json([
                'success' => $success,
                'message' => $message,
                'is_locked' => $topic->fresh()->is_locked
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

            // CRITICAL FIX: Use consistent isAdmin() method for proper admin recognition
            if (!$user->isAdmin() && !$user->courses()->where('courses.id', $courseId)->exists()) {
                \Log::warning('TopicLocking - Course lock access denied', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'course_id' => $courseId,
                    'is_admin' => $user->isAdmin()
                ]);
                throw new \InvalidArgumentException('You do not have permission to manage topics in this course.');
            }

            $topicIds = $course->topics()->pluck('id')->toArray();
            $lockingService = app(\App\Services\TopicLockingService::class);
            $results = $lockingService->bulkLock($topicIds, $user);

            \Log::info('TopicLocking - Course locked successfully', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'topic_count' => count($topicIds)
            ]);

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

            // CRITICAL FIX: Use consistent isAdmin() method for proper admin recognition
            if (!$user->isAdmin() && !$user->courses()->where('courses.id', $courseId)->exists()) {
                \Log::warning('TopicLocking - Course unlock access denied', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'course_id' => $courseId,
                    'is_admin' => $user->isAdmin()
                ]);
                throw new \InvalidArgumentException('You do not have permission to manage topics in this course.');
            }

            $topicIds = $course->topics()->pluck('id')->toArray();
            $lockingService = app(\App\Services\TopicLockingService::class);
            $results = $lockingService->bulkUnlock($topicIds, $user);

            \Log::info('TopicLocking - Course unlocked successfully', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'topic_count' => count($topicIds)
            ]);

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
