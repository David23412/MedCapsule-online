<?php

namespace App\Http\Controllers\Topic\Locking;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

/**
 * 🚀 PERFORMANCE BEAST: Locking Display Controller
 * Handles: Display and status information for topic locking
 * Lines: ~70 (well under 200 limit)
 */
class LockingDisplayController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Display topic locking interface
     */
    public function index()
    {
        $courses = Course::with(['topics' => function($query) {
            $query->select('id', 'name', 'course_id', 'is_locked', 'display_order')
                  ->orderBy('display_order');
        }])->get();

        return view('admin.topic-locking.index', compact('courses'));
    }

    /**
     * Get topics for a specific course
     */
    public function courseTopics($courseId)
    {
        try {
            $topics = Topic::where('course_id', $courseId)
                ->select('id', 'name', 'is_locked', 'display_order')
                ->orderBy('display_order')
                ->get();

            return response()->json([
                'success' => true,
                'topics' => $topics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load topics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get topic status
     */
    public function getTopicStatus($topic)
    {
        return $topic->is_locked ? 'locked' : 'unlocked';
    }

    /**
     * Check if student has access to topic
     */
    public function checkStudentAccess($topic)
    {
        $user = auth()->user();
        
        if ($user->isAdmin()) {
            return true;
        }
        
        return !$topic->is_locked;
    }

    /**
     * Get accessible topics for current user
     */
    public function getAccessibleTopics()
    {
        $user = auth()->user();
        
        $query = Topic::query();
        
        if (!$user->isAdmin()) {
            $query->where('is_locked', false);
        }
        
        return $query->get();
    }

    /**
     * Get locking statistics
     */
    public function getStatistics()
    {
        $stats = [
            'total_topics' => Topic::count(),
            'locked_topics' => Topic::where('is_locked', true)->count(),
            'unlocked_topics' => Topic::where('is_locked', false)->count()
        ];
        
        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    /**
     * Get locked topics
     */
    public function getLockedTopics()
    {
        $lockedTopics = Topic::where('is_locked', true)
            ->with('course:id,name')
            ->get();
            
        return response()->json([
            'success' => true,
            'locked_topics' => $lockedTopics
        ]);
    }
}