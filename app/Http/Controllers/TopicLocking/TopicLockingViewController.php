<?php

namespace App\Http\Controllers\TopicLocking;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Services\TopicLockingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Log, Cache, Auth};

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
        }
        
        $user = $this->checkPermission();
        
        // LETHAL FIX: Course-based access control
        if ($user->isAdmin()) {
            // Admins can see all courses
            $courses = \App\Models\Course::query()
                ->select('id', 'name')
                ->withCount([
                    'topics',
                    'topics as locked_topics_count' => fn ($query) => $query->where('is_locked', true),
                    'topics as unlocked_topics_count' => fn ($query) => $query->where('is_locked', false),
                ])
                ->with(['topics' => function($query) {
                    $query->select('id', 'course_id', 'name', 'is_locked')
                        ->orderBy('name');
                }])
                ->orderBy('name')
                ->get();
            
            if (app()->environment('local', 'testing')) {
            }
        } else {
            // Tutors can only see courses they're enrolled in
            $courses = $user->courses()
                ->select('courses.id', 'courses.name')
                ->withCount([
                    'topics',
                    'topics as locked_topics_count' => fn ($query) => $query->where('is_locked', true),
                    'topics as unlocked_topics_count' => fn ($query) => $query->where('is_locked', false),
                ])
                ->with(['topics' => function($query) {
                    $query->select('id', 'course_id', 'name', 'is_locked')
                          ->orderBy('name');
                }])
                ->orderBy('name')
                ->get();
                
            if (app()->environment('local', 'testing')) {
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
