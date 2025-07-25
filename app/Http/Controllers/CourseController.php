<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Services\NotificationService;
use App\Services\CourseFilterService;

class CourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'showCourses']);
        
        // Admin-only routes
        $this->middleware(function ($request, $next) {
            if (!auth()->user() || auth()->user()->role !== 'admin') {
                return redirect()->route('home')->with('error', 'Unauthorized access.');
            }
            return $next($request);
        })->only(['showAddCourseForm', 'addCourse']);
    }

    /**
     * Show paginated list of courses (public).
     */
    public function showCourses()
    {
        $courses = Course::select([
                'id', 'name', 'description', 'image', 'color', 'title_color', 'price', 'currency'
            ])
            ->paginate(6);
        
        return view('courses', compact('courses'));
    }

    /**
     * Show detailed course list with metadata for authenticated users.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $isLoggedIn = auth()->check();
        $selectedYear = $request->get('academic_year');

        // ULTRA-FAST CACHE: Reduced TTL to 30 seconds for instant updates
        $cacheVersion = Cache::get('courses_cache_version', 1);
        $cacheKey = "courses_metadata_" . ($userId ?? 'guest') . "_" . ($selectedYear ?? 'all') . "_v{$cacheVersion}";
        
        $courses = Cache::remember($cacheKey, 30, function () use ($userId, $isLoggedIn, $selectedYear) {
            $subqueries = [
                DB::raw('(SELECT COUNT(*) FROM topics WHERE topics.course_id = courses.id AND name NOT LIKE "%cases%") as topics_count'),
                DB::raw('(SELECT COUNT(*) FROM topics WHERE topics.course_id = courses.id AND name LIKE "%cases%") as cases_count'),
                DB::raw('(
                    SELECT COUNT(*)
                    FROM course_user
                    WHERE course_user.course_id = courses.id
                    AND course_user.enrollment_status = "active"
                ) as enrolled_users_count')
            ];

            if ($isLoggedIn) {
                $subqueries[] = DB::raw("(
                    SELECT enrollment_status = 'active'
                    FROM course_user
                    WHERE course_user.course_id = courses.id
                    AND course_user.user_id = {$userId}
                ) as is_enrolled");
                
                // Add pending payment check as subquery to avoid N+1
                $subqueries[] = DB::raw("(
                    SELECT COUNT(*) > 0
                    FROM payments
                    WHERE payments.course_id = courses.id
                    AND payments.user_id = {$userId}
                    AND payments.status = 'pending'
                ) as has_pending_payment");
            }

            $query = Course::select('courses.*', ...$subqueries)
                ->with(['users' => function($query) {
                    $query->select('users.id', 'users.name', 'users.email', 'users.profile_picture_url')
                        ->where('course_user.enrollment_status', 'active')
                        ->take(4);
                }]);

            // Apply academic year filter if provided
            if ($selectedYear && CourseFilterService::isValidAcademicYear($selectedYear)) {
                $query = CourseFilterService::filterByAcademicYear($query, $selectedYear);
            }

            return $query->get();
        });

        // Format the results (outside cache to ensure fresh data)
        foreach ($courses as $course) {
            $course->is_enrolled = isset($course->is_enrolled) ? (bool)$course->is_enrolled : false;
            $course->completion_percentage = 0; // This will be handled by the Course model's accessor
            $course->mastered_topics_count = 0; // placeholder
            
            // Format enrolled users for display
            $course->enrolled_preview = $course->users->take(3)->map(function($user) {
                return [
                    'name' => $user->name,
                    'profile_picture_url' => $user->profile_picture_url,
                    'initial' => strtoupper(substr($user->name, 0, 1))
                ];
            });
            $course->has_more = $course->users->count() > 3;
        }

        // Get available academic years for filter dropdown
        $availableYears = CourseFilterService::getAvailableAcademicYears();

        return view('courses', compact('courses', 'availableYears', 'selectedYear'));
    }

    /**
     * Show individual course detail and enrollment logic.
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
            if ($user->role === 'admin') {
                $isEnrolled = true;
            } else {
                // Use efficient query instead of model method
                $isEnrolled = DB::table('course_user')
                    ->where('course_id', $id)
                    ->where('user_id', $user->id)
                    ->where('enrollment_status', 'active')
                    ->exists();
            }
        }

        return view('courses.show', compact('course', 'isEnrolled'));
    }

    /**
     * Enroll the current user in the selected course.
     */
    public function enroll(Request $request, $courseId)
    {
        $request->validate([
            'course_id' => 'sometimes|integer|exists:courses,id'
        ]);
        
        $user = Auth::user();
        $course = Course::findOrFail($courseId);
        
        // Check if user is already enrolled using efficient query
        $alreadyEnrolled = DB::table('course_user')
            ->where('course_id', $courseId)
            ->where('user_id', $user->id)
            ->where('enrollment_status', 'active')
            ->exists();
            
        if ($alreadyEnrolled) {
            return redirect()->back()->with('error', 'You are already enrolled in this course.');
        }
        
        // Enroll user with proper pivot data
        $user->courses()->syncWithoutDetaching([
            $courseId => [
                'enrollment_status' => 'active',
                'enrolled_at' => now()
            ]
        ]);
        
        // Clear ALL course caches for instant updates
        $this->clearAllCourseCaches();
        
        // Send welcome notification
        try {
            app(NotificationService::class)->createCourseWelcomeNotification(
                $user,
                $course->name
            );
        } catch (\Exception $e) {
            // Don't fail enrollment if notification fails
            \Log::warning('Failed to send welcome notification', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
        }
        
        return redirect()->back()->with('success', 'You have been enrolled in the course successfully.');
    }

    /**
     * Show form to add a new course (admin only)
     */
    public function showAddCourseForm()
    {
        return view('add_course');
    }

    /**
     * Add a new course (admin only)
     */
    public function addCourse(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name',
            'description' => 'required|string|max:2000',
            'image' => 'nullable|url|max:2048',
            'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'title_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'academic_year' => 'required|string|in:year-1-sem-1,year-1-sem-2,year-2-sem-1,year-2-sem-2,year-3-sem-1,year-3-sem-2,year-4-sem-1,year-4-sem-2,year-5-sem-1,year-5-sem-2,year-6-sem-1,year-6-sem-2,sat-prep,est-prep,act-prep',
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'currency' => 'required_with:price|string|size:3|in:EGP,USD,EUR'
        ], [
            'name.unique' => 'A course with this name already exists.',
            'color.regex' => 'Color must be a valid hex color code (e.g., #FF5733).',
            'title_color.regex' => 'Title color must be a valid hex color code (e.g., #FF5733).',
            'academic_year.required' => 'Academic year is required.',
            'academic_year.in' => 'Please select a valid academic year.',
            'currency.in' => 'Currency must be one of: EGP, USD, EUR.'
        ]);

        try {
            $course = Course::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'image' => $validated['image'] ?? null,
                'color' => $validated['color'],
                'title_color' => $validated['title_color'],
                'academic_year' => $validated['academic_year'],
                'price' => $validated['price'] ?? null,
                'currency' => $validated['price'] ? $validated['currency'] : 'EGP'
            ]);
            
            // Increment cache version to invalidate all course caches
            $currentVersion = Cache::get('courses_cache_version', 1);
            Cache::put('courses_cache_version', $currentVersion + 1, 86400); // 24 hours
            
            \Log::info('Course created successfully', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'admin_id' => auth()->id()
            ]);

            return redirect()->route('courses.index')
                ->with('success', 'Course "' . $course->name . '" created successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Failed to create course', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'data' => $validated
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create course. Please try again.');
        }
    }

    /**
     * Clear all course-related caches for instant updates
     */
    private function clearAllCourseCaches()
    {
        // Increment cache version to invalidate ALL versioned caches
        $currentVersion = Cache::get('courses_cache_version', 1);
        Cache::put('courses_cache_version', $currentVersion + 1, 86400);
        
        // Clear specific cache patterns
        $patterns = [
            'courses_metadata_*',
            'course_*_enrolled_*',
            'course_*_user_*',
            'courses_list',
            'courses_with_stats'
        ];
        
        // For Laravel cache stores that support pattern deletion
        try {
            foreach ($patterns as $pattern) {
                Cache::forget($pattern);
            }
        } catch (\Exception $e) {
            // Fallback: clear entire cache if pattern deletion fails
            Cache::flush();
        }
    }
}
