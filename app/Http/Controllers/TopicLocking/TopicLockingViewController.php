<?php

namespace App\Http\Controllers\TopicLocking;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Services\TopicLockingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Log, Cache, Auth};
use App\Services\QuizCacheService;

class TopicLockingViewController extends Controller
{
    protected $lockingService;
    
    public function __construct(TopicLockingService $lockingService)
    {
        $this->middleware(['auth']);
        $this->lockingService = $lockingService;
    }
    
    /**
     * Check if user has permission for topic management
     * Uses integer role constants for proper role-based access control
     */
    private function checkPermission()
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'You must be logged in to manage topics.');
        }

        // Use integer constants instead of strings for proper role checking
        $allowedRoles = [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_TUTOR];
        if (!in_array((int)$user->role, $allowedRoles)) {
            abort(403, 'You do not have permission to manage topics.');
        }
        return $user;
    }
    
    /**
     * Display the topic management index page
     */
    public function index()
    {
        if (app()->environment('local', 'testing')) {
            Log::info('Method index called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        }
        
        $user = $this->checkPermission();
        
        // LETHAL FIX: Course-based access control
        if ($user->isAdmin()) {
            // Admins can see all courses
            $courses = \App\Models\Course::query()
                ->select('id', 'name')
                ->with(['topics' => function($query) {
                    $query->select('id', 'course_id', 'name', 'is_locked')
                        ->orderBy('name');
                }])
                ->orderBy('name')
                ->get();
            
            if (app()->environment('local', 'testing')) {
                Log::info('TopicLocking - Admin sees all courses', [
                    'course_count' => $courses->count(),
                    'course_names' => $courses->pluck('name')->toArray()
                ]);
            }
        } else {
            // Tutors can only see courses they're enrolled in
            $courses = $user->courses()
                ->select('courses.id', 'courses.name')
                ->with(['topics' => function($query) {
                    $query->select('id', 'course_id', 'name', 'is_locked')
                          ->orderBy('name');
                }])
                ->orderBy('name')
                ->get();
                
            if (app()->environment('local', 'testing')) {
                Log::info('TopicLocking - Tutor enrolled courses', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'course_count' => $courses->count(),
                    'course_names' => $courses->pluck('name')->toArray()
                ]);
            }
        }
        
        return view('topic-locking.index', compact('courses'));
    }
    
    /**
     * Display topics for a specific course
     */
    public function courseTopics(\App\Models\Course $course)
    {
        if (app()->environment('local', 'testing')) {
            Log::info('Method courseTopics called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        }
        $user = $this->checkPermission();
        
        // LETHAL FIX: Proper course access validation
        if (!$user->isAdmin()) {
            // Check if tutor is enrolled in this course
            $hasAccess = $user->courses()->where('courses.id', $course->id)->exists();
            if (!$hasAccess) {
                abort(403, 'You do not have access to this course.');
            }
        }
        
        $topics = $course->topics()
            ->select('id', 'course_id', 'name', 'is_locked')
            ->orderBy('name')
            ->get();
        
        return view('topic-locking.course', compact('course', 'topics'));
    }
}
