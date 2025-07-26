<?php

namespace App\Http\Controllers;

use App\Services\QuizCacheService;
use App\Services\HomePageService;
use Illuminate\Http\Request;
use App\Http\Requests\HomeRequest;
use Illuminate\Http\JsonResponse;
use App\Services\QuizPerformanceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Optimized Home Controller
 * Ultra-fast home page with aggressive caching
 */
class HomeController extends Controller
{
    protected $homePageService;

    public function __construct(HomePageService $homePageService)
    {
        $this->middleware([]);
        // Remove auth middleware to allow public access to home page
        $this->homePageService = $homePageService;
    }

    /**
     * Display home page with ultra-fast performance
     */
    public function index(HomeRequest $request)
    {
        try {
            // Ultra-fast cache key for home page data
            $cacheKey = QuizCacheService::generateCacheKey('home_page', [
                'user_id' => auth()->id() ?? 'guest',
                'timestamp' => now()->format('Y-m-d-H') // Cache per hour
            ]);

            // Aggressive caching for home page data with eager loading
            $homeData = Cache::remember($cacheKey, 3600, function () {
                // Ultra-aggressive eager loading for home page data
                $user = auth()->user();
                if ($user) {
                    $user->load([
                        'courses:id,name,description,price',
                        'courses.topics:id,course_id,name',
                        'quizAttempts:id,user_id,topic_id,score,created_at',
                        'quizAttempts.topic:id,name,course_id',
                        'assignments:id,user_id,title,status,deadline',
                        'mistakes:id,user_id,question_id,mastered'
                    ]);
                }
                
                return $this->homePageService->getHomePageData();
            });

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $homeData,
                    'cache_stats' => QuizCacheService::getCacheStats()
                ]);
            }

            return view('home', $homeData);

        } catch (\Exception $e) {
            Log::error('Home page loading failed', [
                'user_id' => auth()->id() ?? 'guest',
                'error' => $e->getMessage()
            ]);

            // Fallback data for error cases
            $fallbackData = [
                'statistics' => [
                    'quizzes' => 0,
                    'cases' => 0,
                    'practical' => 0,
                    'questions' => 0,
                    'enrolled_students' => 0
                ],
                'featuredCourses' => collect([])
            ];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to load home page data',
                    'data' => $fallbackData
                ], 500);
            }

            return view('home', $fallbackData);
        }
    }
}
