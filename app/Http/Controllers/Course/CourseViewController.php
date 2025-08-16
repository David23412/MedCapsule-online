<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomeRequest;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Payment;
use App\Services\QuizCacheService;
use App\Services\CourseFilterService;
use App\Support\State\EnrollmentService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Routing\ResponseFactory;
use Psr\Log\LoggerInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * LETHAL OPTIMIZED COURSE VIEW CONTROLLER
 * Handles course display and listing operations
 * FIXED: Eliminated SQL injection vulnerabilities
 */
class CourseViewController extends Controller
{
    private CacheRepository $cache;
    private Auth $auth;
    private ResponseFactory $responseFactory;
    private LoggerInterface $logger;
    private EnrollmentService $enrollmentService;

    public function __construct(
        CacheRepository $cache,
        Auth $auth,
        ResponseFactory $responseFactory,
        LoggerInterface $logger,
        EnrollmentService $enrollmentService
    ) {
        $this->middleware([]);
        $this->middleware(['auth'])->except(['index']);
        $this->cache = $cache;
        $this->auth = $auth;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * Show detailed course list with metadata for authenticated users - COMPLEXITY REDUCED
     * FIXED: Eliminated SQL injection vulnerabilities
     */
    public function index(HomeRequest $request)
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $isLoggedIn = $this->isUserLoggedIn();
            $selectedYear = $this->getValidatedAcademicYear($request);

            // ULTRA-FAST CACHE: Reduced TTL to 30 seconds for instant updates
            $cacheVersion = $this->cache->get('courses_cache_version', 1);
            $cacheKey = $this->generateCacheKey($userId, $selectedYear, $cacheVersion);
            
            $courses = $this->cache->remember($cacheKey, 30, function () use ($userId, $isLoggedIn, $selectedYear) {
                // COMPLEXITY REDUCTION: Extract query building
                $query = $this->buildCourseQuery($userId, $isLoggedIn);

                // Apply academic year filter if provided
                if ($selectedYear && CourseFilterService::isValidAcademicYear($selectedYear)) {
                    $query = CourseFilterService::filterByAcademicYear($query, $selectedYear);
                }

                return $query->get();
            });

            // COMPLEXITY REDUCTION: Extract course formatting
            $this->formatCoursesForDisplay($courses);

            // Get available academic years for filter dropdown
            $availableYears = CourseFilterService::getAvailableAcademicYears();

            $this->logCourseOperation('courses_listed', $userId, count($courses));

            return view('courses', compact('courses', 'availableYears', 'selectedYear'));

        } catch (\Exception $e) {
            $this->logError('Failed to load courses', $e, $userId ?? null);
            return $this->handleError('Failed to load courses');
        }
    }

    /**
     * Show individual course detail and enrollment logic
     * FIXED: Added proper error handling and validation
     */
    public function show($id)
    {
        try {
            $courseId = $this->validateCourseId($id);
            
            // Use eager loading to prevent N+1 queries
            $course = $this->getCourseWithTopics($courseId);
            
            $user = $this->getAuthenticatedUser();
            $isEnrolled = $this->checkEnrollmentStatus($user, $course);

            $this->logCourseOperation('course_viewed', $user->id ?? null, $courseId);

            return view('course', compact('course', 'isEnrolled'));

        } catch (\Exception $e) {
            $this->logError('Failed to load course', $e, $user->id ?? null);
            return $this->handleError('Course not found');
        }
    }

    /**
     * FIXED: Extract authentication logic to eliminate duplication
     */
    private function getAuthenticatedUserId(): ?int
    {
        return $this->auth->id();
    }

    /**
     * FIXED: Extract authentication check to eliminate duplication
     */
    private function isUserLoggedIn(): bool
    {
        return $this->auth->check();
    }

    /**
     * FIXED: Extract user retrieval to eliminate duplication
     */
    private function getAuthenticatedUser()
    {
        return $this->auth->user();
    }

    /**
     * FIXED: Extract academic year validation to eliminate duplication
     */
    private function getValidatedAcademicYear(HomeRequest $request): ?string
    {
        $year = $request->get('academic_year');
        return CourseFilterService::isValidAcademicYear($year) ? $year : null;
    }

    /**
     * FIXED: Extract cache key generation to eliminate duplication
     */
    private function generateCacheKey(?int $userId, ?string $selectedYear, int $cacheVersion): string
    {
        $userIdPart = $userId ?? 'guest';
        $yearPart = $selectedYear ?? 'all';
        return "courses_metadata_{$userIdPart}_{$yearPart}_v{$cacheVersion}";
    }

    /**
     * FIXED: Extract course ID validation to eliminate duplication
     */
    private function validateCourseId($id): int
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new \InvalidArgumentException('Invalid course ID');
        }
        return (int) $id;
    }

    /**
     * FIXED: Extract course retrieval to eliminate duplication
     */
    private function getCourseWithTopics(int $courseId): Course
    {
        return Course::with([
                'topics' => function($query) {
                    $query->select('id', 'name', 'course_id', 'display_order')
                        ->orderBy('display_order');
                }
            ])
            ->findOrFail($courseId);
    }

    /**
     * FIXED: Extract enrollment check to eliminate duplication
     */
    private function checkEnrollmentStatus($user, Course $course): bool
    {
        if (!$user) {
            return false;
        }

        return $this->enrollmentService->isEnrolled($user->id, $course->id);
    }

    /**
     * COMPLEXITY REDUCTION: Format courses for display
     * FIXED: Eliminated SQL injection vulnerabilities
     */
    private function formatCoursesForDisplay($courses): void
    {
        // Eager load all relationships at once to prevent N+1 queries
        $courses->load(['enrolledUsers:id,name,avatar', 'topics:id,course_id']);
        
        $userId = $this->getAuthenticatedUserId();
        
        foreach ($courses as $course) {
            // SSOT: Use EnrollmentService instead of direct field access
            if ($userId) {
                $course->is_enrolled = $this->enrollmentService->isEnrolled($userId, $course->id);
            } else {
                $course->is_enrolled = false;
            }
            
            $course->completion_percentage = 0;
            $course->mastered_topics_count = 0;
            
            // Format enrolled users for display - use eager loaded enrolledUsers
            $enrolledUsers = $course->enrolledUsers ?? collect();
            $course->enrolled_preview = $this->formatEnrolledUsersPreview($enrolledUsers);
            $course->has_more = $enrolledUsers->count() > 3;
        }
    }

    /**
     * FIXED: Extract enrolled users formatting to eliminate duplication
     */
    private function formatEnrolledUsersPreview($enrolledUsers): \Illuminate\Support\Collection
    {
        return $enrolledUsers->take(3)->map(function($user) {
            return [
                'name' => $user->name,
                'profile_picture_url' => $user->profile_picture_url,
                'initial' => strtoupper(substr($user->name, 0, 1))
            ];
        });
    }

    /**
     * COMPLEXITY REDUCTION: Build course query with subqueries
     * FIXED: Eliminated SQL injection vulnerabilities by using safe Eloquent methods
     */
    private function buildCourseQuery($userId, $isLoggedIn): Builder
    {
        // FIXED: Use safe Eloquent methods instead of raw SQL
        $query = Course::select('courses.*')
            ->withCount([
                'topics as topics_count' => function($query) {
                    $query->where('name', 'NOT LIKE', '%cases%');
                },
                'topics as cases_count' => function($query) {
                    $query->where('name', 'LIKE', '%cases%');
                }
            ])
            ->withCount([
                'payments as enrolled_users_count' => function($query) {
                    $query->where('status', 'completed');
                }
            ]);

        // FIXED: Use safe Eloquent relationships instead of raw SQL
        $query->with(['enrolledUsers' => function($query) {
            $query->select('users.id', 'users.name', 'users.email', 'users.profile_picture_url')
                ->whereHas('payments', function($paymentQuery) {
                    $paymentQuery->where('status', 'completed');
                })
                ->distinct()
                ->limit(4);
        }]);

        return $query;
    }

    /**
     * FIXED: Extract error handling to eliminate duplication
     */
    private function handleError(string $message)
    {
        return $this->responseFactory->view('error', ['message' => $message], 500);
    }

    /**
     * FIXED: Extract logging logic to eliminate duplication
     */
    private function logCourseOperation(string $operation, ?int $userId, $courseId = null, array $context = []): void
    {
        $this->logger->info("Course operation: {$operation}", array_merge([
            'user_id' => $userId,
            'course_id' => $courseId,
            'timestamp' => now()->toISOString()
        ], $context));
    }

    /**
     * FIXED: Extract error logging logic to eliminate duplication
     */
    private function logError(string $message, \Exception $exception, ?int $userId = null): void
    {
        $this->logger->error($message, [
            'error' => $exception->getMessage(),
            'user_id' => $userId,
            'trace' => $exception->getTraceAsString()
        ]);
    }
}