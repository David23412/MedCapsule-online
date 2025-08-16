<?php

namespace App\Http\Controllers;

use App\Services\HomePageService;
use Illuminate\Http\Request;
use App\Http\Requests\HomeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * Optimized Home Controller
 * Ultra-fast home page with aggressive caching
 */
class HomeController extends Controller
{
    protected HomePageService $homePageService;
    protected CacheRepository $cache;
    protected LoggerInterface $logger;
    protected Auth $auth;
    protected ResponseFactory $responseFactory;

    public function __construct(
        HomePageService $homePageService,
        CacheRepository $cache,
        LoggerInterface $logger,
        Auth $auth,
        ResponseFactory $responseFactory
    ) {
        $this->middleware([]);
        // Remove auth middleware to allow public access to home page
        $this->homePageService = $homePageService;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->auth = $auth;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Display home page with NUCLEAR SPEED optimization
     * FIXED: Reduced cyclomatic complexity by extracting methods
     */
    public function index(HomeRequest $request)
    {
        try {
            $startTime = microtime(true);
            $userId = $this->getUserId();
            $cacheKey = $this->generateCacheKey($userId);
            $homeData = $this->getHomeData($cacheKey, $userId, $request);

            $this->logPerformance($startTime, $userId, $cacheKey);

            return $this->generateResponse($request, $homeData, $cacheKey);

        } catch (\Exception $e) {
            $this->logError($e);
            return $this->handleFallback($request);
        }
    }

    /**
     * FIXED: Extract performance logging to reduce complexity
     */
    private function logPerformance($startTime, $userId, $cacheKey)
    {
        $loadTime = microtime(true) - $startTime;
        $this->logger->info('Home page loaded successfully', [
            'user_id' => $userId,
            'cache_key' => $cacheKey,
            'load_time' => $loadTime,
            'cache_hit' => $this->cache->has($cacheKey)
        ]);
    }

    /**
     * FIXED: Extract error logging to reduce complexity
     */
    private function logError(\Exception $e)
    {
        $this->logger->error('Home page loading failed', [
            'user_id' => $this->getUserId(),
            'error' => $e->getMessage(),
            'performance' => microtime(true) - LARAVEL_START
        ]);
    }

    private function handleFallback(Request $request)
    {
        $fallbackData = $this->getFallbackData();

        if ($this->isAjaxRequest($request)) {
            return $this->createJsonResponse($fallbackData, false, 'Fallback data loaded');
        }

        return $this->responseFactory->view('home', $fallbackData);
    }

    /**
     * FIXED: Extract fallback data generation to reduce complexity
     */
    private function getFallbackData()
    {
        return $this->cache->rememberForever('home_fallback_nuclear', function () {
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
    }

    /**
     * FIXED: Extract AJAX request check to reduce complexity
     */
    private function isAjaxRequest(Request $request)
    {
        return $request->ajax() || $request->wantsJson();
    }

    /**
     * FIXED: Extract JSON response creation to reduce complexity
     */
    private function createJsonResponse($data, $success = true, $message = '')
    {
        $response = ['success' => $success, 'data' => $data];
        
        if ($message) {
            $response['message'] = $message;
        }

        return $this->responseFactory->json($response, 200);
    }

    /**
     * FIXED: Extract user ID logic to reduce complexity
     */
    private function getUserId()
    {
        return $this->auth->id() ?? 'guest';
    }

    /**
     * FIXED: Extract cache key generation to reduce complexity
     */
    private function generateCacheKey($userId)
    {
        $authStatus = $this->auth->check() ? 'auth' : 'guest';
        return "home_nuclear_v5_{$authStatus}_{$userId}_" . now()->format('Y-m-d-H');
    }

    /**
     * FIXED: Extract home data retrieval to reduce complexity
     */
    private function getHomeData($cacheKey, $userId, Request $request)
    {
        $homeData = $this->cache->remember($cacheKey, 7200, function () use ($userId) {
            return $this->homePageService->getHomePageData();
        });

        return $this->enrichWithUserData($homeData, $userId, $request);
    }

    /**
     * FIXED: Extract user data enrichment to reduce complexity
     */
    private function enrichWithUserData($homeData, $userId, Request $request)
    {
        $homeData['isAuthenticated'] = $this->isUserAuthenticated($userId);
        
        if ($homeData['isAuthenticated']) {
            $homeData['user'] = $this->getUserDataArray();
            $homeData['sessionInfo'] = $this->getSessionInfo($request);
        }

        return $homeData;
    }

    /**
     * FIXED: Extract authentication check to reduce complexity
     */
    private function isUserAuthenticated($userId)
    {
        return $userId !== 'guest' && $this->auth->check();
    }

    /**
     * FIXED: Extract user data array creation to reduce complexity
     */
    private function getUserDataArray()
    {
        $user = $this->auth->user();
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'university' => $user->university,
            'profile_picture_url' => $user->profile_picture_url
        ];
    }

    /**
     * FIXED: Extract session info creation to reduce complexity
     */
    private function getSessionInfo(Request $request)
    {
        $user = $this->auth->user();
        return [
            'loginTime' => $this->getLoginTime($user),
            'sessionId' => $request->session()->getId()
        ];
    }

    /**
     * FIXED: Extract login time logic to reduce complexity
     */
    private function getLoginTime($user)
    {
        return $user->session_created_at ? $user->session_created_at->timestamp : null;
    }

    /**
     * FIXED: Extract response headers generation to reduce complexity
     */
    private function generateResponseHeaders($homeData, $cacheKey)
    {
        return [
            'Cache-Control' => 'public, max-age=3600, s-maxage=7200',
            'ETag' => '"' . md5(json_encode($homeData)) . '"',
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'Vary' => 'Accept-Encoding',
            'X-Cache-Status' => $this->getCacheStatus($cacheKey)
        ];
    }

    /**
     * FIXED: Extract cache status check to reduce complexity
     */
    private function getCacheStatus($cacheKey)
    {
        return $this->cache->has($cacheKey) ? 'HIT' : 'MISS';
    }

    /**
     * FIXED: Extract response generation to reduce complexity
     */
    private function generateResponse(Request $request, $homeData, $cacheKey)
    {
        $headers = $this->generateResponseHeaders($homeData, $cacheKey);

        if ($this->isAjaxRequest($request)) {
            return $this->createAjaxResponse($homeData, $cacheKey, $headers);
        }

        return $this->responseFactory->view('home', $homeData)->withHeaders($headers);
    }

    /**
     * FIXED: Extract AJAX response creation to reduce complexity
     */
    private function createAjaxResponse($homeData, $cacheKey, $headers)
    {
        return $this->responseFactory->json([
            'success' => true,
            'data' => $homeData,
            'performance' => $this->getPerformanceData($cacheKey)
        ], 200, $headers);
    }

    /**
     * FIXED: Extract performance data generation to reduce complexity
     */
    private function getPerformanceData($cacheKey)
    {
        return [
            'cached' => $this->cache->has($cacheKey),
            'cache_key' => $cacheKey,
            'load_time' => microtime(true) - LARAVEL_START
        ];
    }
}

