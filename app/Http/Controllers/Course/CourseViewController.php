<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomeRequest;
use App\Models\Course;
use App\Services\QuizCacheService;
use App\Services\CourseFilterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * LETHAL OPTIMIZED COURSE VIEW CONTROLLER
 * Handles course display and listing operations
 */
class CourseViewController extends Controller
{
    public function __construct()
    {
        $this->middleware([]);
        $this->middleware(['auth'])->except(['index']);
    }

    /**
     * Show detailed course list with metadata for authenticated users - COMPLEXITY REDUCED
     */
    public function index(HomeRequest $request)
    {
        $userId = auth()->id();
        $isLoggedIn = auth()->check();
        $selectedYear = $request->get('academic_year');

        // ULTRA-FAST CACHE: Reduced TTL to 30 seconds for instant updates
        $cacheVersion = Cache::get('courses_cache_version', 1);
        $cacheKey = "courses_metadata_" . ($userId ?? 'guest') . "_" . ($selectedYear ?? 'all') . "_v{$cacheVersion}";
        
        $courses = Cache::remember($cacheKey, 30, function () use ($userId, $isLoggedIn, $selectedYear) {
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

        return view('courses', compact('courses', 'availableYears', 'selectedYear'));
    }

    /**
     * Show individual course detail and enrollment logic
     */
    public function show($id)
    {
        // Use eager loading to prevent N+1 queries
        $course = Course::with([
                'topics' => function($query) {
                    $query->select('id', 'name', 'course_id', 'display_order')
                        ->orderBy('display_order');
                }
            ])
            ->findOrFail($id);
            
        $user = auth()->user();
        $isEnrolled = false;

        if ($user) {
            $isEnrolled = $user->courses()
                ->where('course_id', $course->id)
                ->whereNotNull('enrolled_at')
                ->exists();
        }

        return view('course', compact('course', 'isEnrolled'));
    }

    /**
     * COMPLEXITY REDUCTION: Format courses for display
     */
    private function formatCoursesForDisplay($courses): void
    {
        $user = auth()->user();
        
        // ✅ FIXED: Eager load all relationships at once to prevent N+1 queries
        $courses->load(['enrolledUsers:id,name,avatar', 'topics:id,course_id']);
        
        // ✅ FIXED: Pre-calculate user access and payment status in bulk to prevent N+1 queries
        $userAccessMap = [];
        $userPaymentMap = [];
        
        if ($user) {
            // Get all course IDs for bulk access checking
            $courseIds = $courses->pluck('id');
            
            // Bulk check course access
            if ($user->role === 'admin') {
                $userAccessMap = array_fill_keys($courseIds->toArray(), true);
            } else {
                $userAccessMap = $user->hasAccessToCourses($courseIds);
            }
            
            // Bulk check pending payments
            $userPaymentMap = $user->hasPendingPaymentsForCourses($courseIds);
        }
        
        foreach ($courses as $course) {
            // Check if current user is enrolled in this course using pre-calculated map
            if ($user) {
                $course->is_enrolled = $userAccessMap[$course->id] ?? false;
                $course->has_pending_payment = $userPaymentMap[$course->id] ?? false;
            } else {
                $course->is_enrolled = false;
                $course->has_pending_payment = false;
            }
            
            $course->completion_percentage = 0;
            $course->mastered_topics_count = 0;
            
            // Format enrolled users for display - use fake or real data
            if ($course->fake_enrolled_enabled && $course->fake_enrolled_count > 0) {
                // Generate fake enrolled users
                $fakeUsers = $this->generateFakeEnrolledUsers($course->fake_enrolled_count);
                $course->enrolled_preview = collect($fakeUsers)->take(3);
                $course->has_more = $course->fake_enrolled_count > 3;
                $course->enrolled_users_count = $course->fake_enrolled_count;
            } else {
                // Use real enrolled users
                $enrolledUsers = $course->enrolledUsers ?? collect();
                $course->enrolled_preview = $enrolledUsers->take(3)->map(function($user) {
                    return [
                        'name' => $user->name,
                        'profile_picture_url' => $user->profile_picture_url,
                        'initial' => strtoupper(substr($user->name, 0, 1))
                    ];
                });
                $course->has_more = $enrolledUsers->count() > 3;
                $course->enrolled_users_count = $enrolledUsers->count();
            }
        }
    }

    /**
     * Generate fake enrolled users with realistic names and initials
     */
    private function generateFakeEnrolledUsers(int $count): array
    {
        $firstNames = ['Ahmed', 'Mohamed', 'Fatima', 'Aisha', 'Omar', 'Sara', 'Ali', 'Nour', 'Hassan', 'Layla', 
                       'Youssef', 'Mariam', 'Khaled', 'Hana', 'Mahmoud', 'Zainab', 'Amr', 'Dina', 'Karim', 'Salma',
                       'Yasmin', 'Aya', 'Mostafa', 'Rana', 'Tarek', 'Nada', 'Waleed', 'Mona', 'Sherif', 'Dalia'];
        $lastNames = ['Hassan', 'Ibrahim', 'Mohamed', 'Ali', 'Ahmed', 'Mahmoud', 'Youssef', 'Khaled', 'Omar', 'Said',
                      'Farouk', 'Mostafa', 'Abdel', 'Sayed', 'Rashid', 'Nasser', 'Kamel', 'Fathy', 'Salah', 'Hamdy'];
        
        $fakeUsers = [];
        // Generate only first 3 users for display (view will show +X for the rest)
        for ($i = 0; $i < min($count, 3); $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $fullName = $firstName . ' ' . $lastName;
            
            $fakeUsers[] = [
                'name' => $fullName,
                'profile_picture_url' => null,
                'initial' => strtoupper(substr($firstName, 0, 1))
            ];
        }
        
        return $fakeUsers;
    }

    /**
     * COMPLEXITY REDUCTION: Build course query with subqueries
     */
    private function buildCourseQuery($userId, $isLoggedIn): \Illuminate\Database\Eloquent\Builder
    {
        $subqueries = [
            'topics_count' => Topic::selectRaw('COUNT(*)')
                ->whereColumn('course_id', 'courses.id')
                ->where('name', 'NOT LIKE', '%cases%'),
            'cases_count' => Topic::selectRaw('COUNT(*)')
                ->whereColumn('course_id', 'courses.id')
                ->where('name', 'LIKE', '%cases%')
            // Removed enrolled_users_count subquery - it's now set manually in the loop
            // to support both real and fake enrolled counts
        ];

        if ($isLoggedIn) {
            // Note: Enrollment and payment status will be checked via relationships instead of raw SQL
        }

        // 💀 LETHAL N+1 FIX: Optimized eager loading with proper constraints
        return Course::select('courses.*', ...$subqueries)
            ->with(['enrolledUsers' => function($query) {
                $query->select('users.id', 'users.name', 'users.email', 'users.profile_picture_url')
                    ->whereHas('payments', function($paymentQuery) {
                        $paymentQuery->where('status', 'completed');
                    })
                    ->distinct()
                    ->limit(4);
            }]);
    }
}