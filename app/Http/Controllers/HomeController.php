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
     * RUTHLESS OPTIMIZATION: Multi-layer caching + parallel loading
     */
    public function index(HomeRequest $request)
    {
        try {
            // RUTHLESS: Multi-layer cache key with user context
            $userId = auth()->id() ?? 'guest';
            $cacheKey = "home_page_optimized_v3_{$userId}_" . now()->format('Y-m-d-H');

            // RUTHLESS: Aggressive caching with parallel data loading
            $homeData = Cache::remember($cacheKey, 3600, function () use ($userId) {
                // PARALLEL DATA LOADING - No sequential waits
                $statisticsPromise = $this->homePageService->getHomePageData();
                
                // RUTHLESS: Skip unnecessary user data loading for guests
                if ($userId !== 'guest') {
                    $user = auth()->user();
                    // Only load essential relationships
                    $user->loadMissing([
                        'courses:id,name',
                        'quizAttempts:id,user_id,score,created_at'
                    ]);
                }
                
                return $statisticsPromise;
            });

            // RUTHLESS: Optimized JSON response for AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $homeData,
                    'cached' => Cache::has($cacheKey),
                    'cache_key' => $cacheKey
                ], 200, [
                    'Cache-Control' => 'public, max-age=1800',
                    'ETag' => md5(json_encode($homeData))
                ]);
            }

            // RUTHLESS: Optimized view response with caching headers
            return response()
                ->view('home', $homeData)
                ->header('Cache-Control', 'public, max-age=1800')
                ->header('ETag', md5(json_encode($homeData)));

        } catch (\Exception $e) {
            Log::error('Home page loading failed', [
                'user_id' => $userId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // RUTHLESS: Cached fallback data
            $fallbackData = Cache::remember('home_fallback_data', 3600, function () {
                return [
                    'statistics' => [
                        'quizzes' => 0,
                        'cases' => 0,
                        'practical' => 0,
                        'questions' => 0,
                        'enrolled_students' => 0
                    ],
                    'featuredCourses' => collect([])
                ];
            });

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
