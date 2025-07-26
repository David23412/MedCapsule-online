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
        if (!$user || (!$user->isAdmin() && !$user->isTutor())) {
            abort(403, 'You do not have permission to manage topics.');
        }
        return $user;
    }
    
    /**
     * Display the topic management index page
     */
    public function index()
    {
        Log::info('Method index called', ['user_id' => auth()->id(), 'timestamp' => now()]);
        $cacheKey = QuizCacheService::generateCacheKey('topic_locking_index', ['user_id' => auth()->id() ?? 'guest']);
        return Cache::remember($cacheKey, 600, function () {
            $user = $this->checkPermission();
            
            if ($user->isAdmin()) {
                $courses = \App\Models\Course::with(['topics' => function($query) {
                    $query->orderBy('name');
                }])->orderBy('name')->get();
            } else {
                $courses = $user->courses()->with(['topics' => function($query) {
                    $query->orderBy('name');
                }])->orderBy('name')->get();
            }
            
            return view('topic-locking.index', compact('courses'));
        });
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