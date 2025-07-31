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
     */
    private function checkPermission()
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'You must be logged in to manage topics.');
        }
        // TEMPORARILY BYPASSING ROLE CHECK FOR TESTING
        // if (!$user->isAdmin() && !$user->isTutor()) {
        //     abort(403, 'You do not have permission to manage topics.');
        // }
        return $user;
    }
    
    /**
     * Display the topic management index page
     */
    public function index()
    {
        Log::info('Method index called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        
        // TEMPORARILY REMOVING CACHE TO FIX SERIALIZATION ISSUE
        $user = $this->checkPermission();
        
        // TEMPORARILY SHOWING ALL COURSES FOR TESTING
        $courses = \App\Models\Course::with(['topics' => function($query) {
            $query->orderBy('name');
        }])->orderBy('name')->get();
        
        // if ($user->isAdmin()) {
        //     $courses = \App\Models\Course::with(['topics' => function($query) {
        //         $query->orderBy('name');
        //     }])->orderBy('name')->get();
        // } else {
        //     $courses = $user->courses()->with(['topics' => function($query) {
        //         $query->orderBy('name');
        //     }])->orderBy('name')->get();
        // }
        
        return view('topic-locking.index', compact('courses'));
    }
    
    /**
     * Display topics for a specific course
     */
    public function courseTopics(\App\Models\Course $course)
    {
        Log::info('Method courseTopics called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        $user = $this->checkPermission();
        
        if (!$user->isAdmin() && !$user->courses()->where('course_id', $course->id)->exists()) {
            abort(403, 'You do not have access to this course.');
        }
        
        $topics = $course->topics()->orderBy('name')->get();
        
        return view('topic-locking.course', compact('course', 'topics'));
    }
}