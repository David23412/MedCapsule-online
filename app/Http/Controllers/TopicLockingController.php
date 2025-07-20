<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Course;
use App\Services\TopicLockingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TopicLockingController extends Controller
{
    private TopicLockingService $topicLockingService;

    public function __construct(TopicLockingService $topicLockingService)
    {
        $this->topicLockingService = $topicLockingService;
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if ($user->role === 'admin' || $user->role === 'tutor') {
                return $next($request);
            }
            abort(403, 'Unauthorized access');
        });
    }

    /**
     * Display topic locking management interface
     */
    public function index(): View
    {
        $courses = Course::whereHas('users', function($query) {
            $query->whereHas('tutorStudents', function($q) {
                $q->where('tutor_id', auth()->id());
            });
        })->with(['topics' => function($query) {
            $query->orderBy('display_order');
        }])->get();

        $lockedTopics = $this->topicLockingService->getLockedTopicsForTutor(auth()->id());
        $statistics = $this->topicLockingService->getLockingStatistics(auth()->id());

        return view('topic-locking.index', compact('courses', 'lockedTopics', 'statistics'));
    }

    /**
     * Lock a topic
     */
    public function lock(Topic $topic, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $result = $this->topicLockingService->lockTopic(
                $topic->id, 
                auth()->id(), 
                $request->input('reason')
            );

            if ($result) {
                $lockStatus = $this->topicLockingService->getTopicLockStatus($topic->id);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Topic locked successfully!',
                    'topic' => [
                        'id' => $topic->id,
                        'name' => $topic->name,
                        'lock_status' => $lockStatus
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to lock topic. You may not have permission.'
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Unlock a topic
     */
    public function unlock(Topic $topic): JsonResponse
    {
        try {
            $result = $this->topicLockingService->unlockTopic($topic->id, auth()->id());

            if ($result) {
                $lockStatus = $this->topicLockingService->getTopicLockStatus($topic->id);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Topic unlocked successfully!',
                    'topic' => [
                        'id' => $topic->id,
                        'name' => $topic->name,
                        'lock_status' => $lockStatus
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to unlock topic. You may not have permission.'
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Bulk lock multiple topics
     */
    public function bulkLock(Request $request): JsonResponse
    {
        $request->validate([
            'topic_ids' => 'required|array|min:1',
            'topic_ids.*' => 'required|exists:topics,id',
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $results = $this->topicLockingService->bulkLockTopics(
                $request->input('topic_ids'),
                auth()->id(),
                $request->input('reason')
            );

            $successCount = count($results['successful']);
            $failedCount = count($results['failed']);

            return response()->json([
                'success' => true,
                'message' => "Successfully locked {$successCount} topics" . 
                           ($failedCount > 0 ? ", {$failedCount} failed" : ""),
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Bulk unlock multiple topics
     */
    public function bulkUnlock(Request $request): JsonResponse
    {
        $request->validate([
            'topic_ids' => 'required|array|min:1',
            'topic_ids.*' => 'required|exists:topics,id'
        ]);

        try {
            $results = $this->topicLockingService->bulkUnlockTopics(
                $request->input('topic_ids'),
                auth()->id()
            );

            $successCount = count($results['successful']);
            $failedCount = count($results['failed']);

            return response()->json([
                'success' => true,
                'message' => "Successfully unlocked {$successCount} topics" . 
                           ($failedCount > 0 ? ", {$failedCount} failed" : ""),
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display course topic management
     */
    public function courseTopics(Course $course): View
    {
        $topics = Topic::where('course_id', $course->id)
                      ->with('lockedByTutor')
                      ->orderBy('display_order')
                      ->get();

        $lockedTopics = $this->topicLockingService->getLockedTopicsInCourse($course->id);

        return view('topic-locking.course', compact('course', 'topics', 'lockedTopics'));
    }

    /**
     * Lock all topics in a course
     */
    public function lockCourse(Course $course, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $results = $this->topicLockingService->lockCourseTopics(
                $course->id,
                auth()->id(),
                $request->input('reason')
            );

            $successCount = count($results['successful']);
            $failedCount = count($results['failed']);

            return response()->json([
                'success' => true,
                'message' => "Successfully locked {$successCount} topics in {$course->name}" . 
                           ($failedCount > 0 ? ", {$failedCount} failed" : ""),
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Unlock all topics in a course
     */
    public function unlockCourse(Course $course): JsonResponse
    {
        try {
            $results = $this->topicLockingService->unlockCourseTopics($course->id, auth()->id());

            $successCount = count($results['successful']);
            $failedCount = count($results['failed']);

            return response()->json([
                'success' => true,
                'message' => "Successfully unlocked {$successCount} topics in {$course->name}" . 
                           ($failedCount > 0 ? ", {$failedCount} failed" : ""),
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get topic lock status (AJAX)
     */
    public function getTopicStatus(Topic $topic): JsonResponse
    {
        $lockStatus = $this->topicLockingService->getTopicLockStatus($topic->id);
        
        return response()->json($lockStatus);
    }

    /**
     * Check if student can access topic (AJAX)
     */
    public function checkStudentAccess(Topic $topic, Request $request): JsonResponse
    {
        $studentId = $request->input('student_id');
        
        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required'
            ], 422);
        }

        $accessInfo = $this->topicLockingService->canStudentAccessTopic($studentId, $topic->id);
        
        return response()->json($accessInfo);
    }

    /**
     * Get accessible topics for a student (AJAX)
     */
    public function getAccessibleTopics(Request $request): JsonResponse
    {
        $studentId = $request->input('student_id');
        $courseId = $request->input('course_id');
        
        if (!$studentId) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID is required'
            ], 422);
        }

        $topics = $this->topicLockingService->getAccessibleTopicsForStudent($studentId, $courseId);
        
        return response()->json([
            'success' => true,
            'topics' => $topics->map(function($topic) {
                return [
                    'id' => $topic->id,
                    'name' => $topic->name,
                    'course_name' => $topic->course->name,
                    'display_order' => $topic->display_order
                ];
            })
        ]);
    }

    /**
     * Get locking statistics (AJAX)
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = $this->topicLockingService->getLockingStatistics(auth()->id());
        
        return response()->json($statistics);
    }

    /**
     * Get locked topics for tutor (AJAX)
     */
    public function getLockedTopics(): JsonResponse
    {
        $lockedTopics = $this->topicLockingService->getLockedTopicsForTutor(auth()->id());
        
        return response()->json([
            'locked_topics' => $lockedTopics->map(function($topic) {
                return [
                    'id' => $topic->id,
                    'name' => $topic->name,
                    'course_name' => $topic->course->name,
                    'lock_reason' => $topic->lock_reason,
                    'locked_at' => $topic->locked_at->format('M j, Y g:i A'),
                    'locked_by_tutor_name' => $topic->lockedByTutor->name
                ];
            })
        ]);
    }

    /**
     * Toggle topic lock status
     */
    public function toggle(Topic $topic, Request $request): JsonResponse
    {
        $lockStatus = $this->topicLockingService->getTopicLockStatus($topic->id);
        
        if (!$lockStatus['exists']) {
            return response()->json([
                'success' => false,
                'message' => 'Topic not found'
            ], 404);
        }

        try {
            if ($lockStatus['is_locked']) {
                $result = $this->topicLockingService->unlockTopic($topic->id, auth()->id());
                $action = 'unlocked';
            } else {
                $reason = $request->input('reason');
                $result = $this->topicLockingService->lockTopic($topic->id, auth()->id(), $reason);
                $action = 'locked';
            }

            if ($result) {
                $newLockStatus = $this->topicLockingService->getTopicLockStatus($topic->id);
                
                return response()->json([
                    'success' => true,
                    'message' => "Topic {$action} successfully!",
                    'topic' => [
                        'id' => $topic->id,
                        'name' => $topic->name,
                        'lock_status' => $newLockStatus
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Failed to {$action} topic. You may not have permission."
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}