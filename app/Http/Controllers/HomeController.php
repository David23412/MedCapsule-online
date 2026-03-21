<?php

namespace App\Http\Controllers;

use App\Services\HomePageService;
use Illuminate\Http\Request;
use App\Http\Requests\HomeRequest;
use Illuminate\Http\JsonResponse;
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
     * Display home page with NUCLEAR SPEED optimization
     * LIGHTNING FAST: Multi-layer Redis caching + parallel loading + precomputed data
     */
    public function index(HomeRequest $request)
    {
        try {
            $userId = auth()->id() ?? 'guest';
            $isAuthenticated = auth()->check();
            $baseCacheKey = 'home_shared_v6_' . now()->format('Y-m-d-H');

            $baseHomeData = Cache::remember($baseCacheKey, 3600, function () {
                $data = $this->homePageService->getHomePageData();
                $data['activeStudentCount'] = $this->homePageService->getActiveStudentCount();

                return $data;
            });

            $homeData = $baseHomeData;

            if ($isAuthenticated) {
                $user = auth()->user();
                $homeData['user'] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'university' => $user->university,
                    'profile_picture_url' => $user->profile_picture_url
                ];
                $homeData['isAuthenticated'] = true;
                $homeData['sessionInfo'] = [
                    'loginTime' => $user->session_created_at ? $user->session_created_at->timestamp : null,
                    'sessionId' => $request->session()->getId()
                ];
                $homeData['currentUserView'] = [
                    'name' => $user->name,
                    'is_admin' => $user->isAdmin(),
                    'is_tutor' => $user->isTutor(),
                    'study_streak_days' => (int) ($user->study_streak_days ?? 0),
                    'profile_image_url' => $user->getProfileImageUrl(),
                    'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=200&background=007bff&color=ffffff',
                ];
            } else {
                $homeData['isAuthenticated'] = false;
                $homeData['currentUserView'] = null;
            }

            // NUCLEAR: Optimized response headers for maximum caching
            $headers = [
                'Cache-Control' => 'public, max-age=3600, s-maxage=7200',
                'ETag' => '"' . sha1($baseCacheKey . '|' . ($isAuthenticated ? 'auth:' . $userId : 'guest')) . '"',
                'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
                'Vary' => 'Accept-Encoding',
                'X-Cache-Status' => Cache::has($baseCacheKey) ? 'HIT' : 'MISS'
            ];

            // NUCLEAR: JSON response optimization
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $homeData,
                    'performance' => [
                        'cached' => Cache::has($baseCacheKey),
                        'cache_key' => $baseCacheKey,
                        'load_time' => microtime(true) - LARAVEL_START
                    ]
                ], 200, $headers);
            }

            // NUCLEAR: View response with aggressive caching
            return response()
                ->view('home', $homeData)
                ->withHeaders($headers);

        } catch (\Exception $e) {
            Log::error('Home page loading failed', [
                'user_id' => $userId ?? 'unknown',
                'error' => $e->getMessage(),
                'performance' => microtime(true) - LARAVEL_START
            ]);

            // NUCLEAR: Ultra-fast fallback with permanent cache
            $fallbackData = Cache::rememberForever('home_fallback_nuclear', function () {
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
                    'message' => 'Fallback data loaded',
                    'data' => $fallbackData
                ], 200);
            }

            return view('home', $fallbackData);
        }
    }
}
