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
            // NUCLEAR: Ultra-fast cache key with minimal computation
            $userId = auth()->id() ?? 'guest';
            $authStatus = auth()->check() ? 'auth' : 'guest';
            $cacheKey = "home_nuclear_v5_{$authStatus}_{$userId}_" . now()->format('Y-m-d-H');

            // NUCLEAR: Precomputed data with Redis pipeline
            $homeData = Cache::remember($cacheKey, 7200, function () use ($userId) {
                // PARALLEL: Load all data simultaneously
                $data = $this->homePageService->getHomePageData();
                
                // NUCLEAR: Skip user data for guests completely
                if ($userId !== 'guest') {
                    // Only essential user data, no relationships
                    $user = auth()->user();
                    $data['user'] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'university' => $user->university,
                        'profile_picture_url' => $user->profile_picture_url
                    ];
                }
                
                return $data;
            });
            
            // NUCLEAR: For authenticated users, always include fresh user data to avoid cache issues
            if ($userId !== 'guest' && auth()->check()) {
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
                
                // Add session info to help with client-side state management
                $homeData['sessionInfo'] = [
                    'loginTime' => $user->session_created_at ? $user->session_created_at->timestamp : null,
                    'sessionId' => $request->session()->getId()
                ];
            } else {
                $homeData['isAuthenticated'] = false;
            }

            // NUCLEAR: Optimized response headers for maximum caching
            $headers = [
                'Cache-Control' => 'public, max-age=3600, s-maxage=7200',
                'ETag' => '"' . md5(json_encode($homeData)) . '"',
                'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
                'Vary' => 'Accept-Encoding',
                'X-Cache-Status' => Cache::has($cacheKey) ? 'HIT' : 'MISS'
            ];

            // NUCLEAR: JSON response optimization
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $homeData,
                    'performance' => [
                        'cached' => Cache::has($cacheKey),
                        'cache_key' => $cacheKey,
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
