<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class CourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index']);
        
        // Admin-only routes
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->is_admin) {
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
        $courses = Course::paginate(6);
        return view('courses', compact('courses'));
    }

    /**
     * Show detailed course list with metadata for authenticated users.
     */
    public function index()
    {
        $userId = auth()->id();
        $isLoggedIn = auth()->check();

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
        }

        $courses = Course::select('courses.*', ...$subqueries)
            ->with(['users' => function($query) {
                $query->select('users.id', 'users.name', 'users.email', 'users.profile_picture_url')
                    ->where('course_user.enrollment_status', 'active')
                    ->take(4);
            }])
            ->get();

        // Format the results
        foreach ($courses as $course) {
            $course->is_enrolled = isset($course->is_enrolled) ? (bool)$course->is_enrolled : false;
            $course->completion_percentage = 0; // This will be handled by the Course model's accessor
            $course->mastered_topics_count = 0; // placeholder
            
            // Add pending payment check
            $course->has_pending_payment = $course->hasUserPendingPayment();
            
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

        return view('courses', compact('courses'));
    }

    /**
     * Show individual course detail and enrollment logic.
     */
    public function show($id)
    {
        $course = Course::findOrFail($id);
        $topics = $course->topics()->orderBy('display_order')->get();
        $user = auth()->user();
        $isEnrolled = false;

        if ($user) {
            if ($user->is_admin) {
                $isEnrolled = true;
            } else {
                $isEnrolled = $course->isUserEnrolled($user->id);
            }
        }

        return view('courses.show', compact('course', 'topics', 'isEnrolled'));
    }

    /**
     * Enroll the current user in the selected course.
     */
    public function enroll(Request $request, $courseId)
    {
        $user = Auth::user();
        $course = Course::findOrFail($courseId);
        
        // Check if user is already enrolled
        if ($user->courses()->where('course_id', $courseId)->exists()) {
            return redirect()->back()->with('error', 'You are already enrolled in this course.');
        }
        
        // Enroll user
        $user->courses()->attach($courseId);
        
        // Send welcome notification
        app(NotificationService::class)->createCourseWelcomeNotification(
            $user,
            $course->name
        );
        
        return redirect()->back()->with('success', 'You have been enrolled in the course successfully.');
    }

    public function showAddCourseForm()
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }
        return view('add_course');
    }

    public function addCourse(Request $request)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:courses',
            'description' => 'required|string',
            'image' => 'nullable|url',
            'color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'title_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'is_paid' => 'sometimes|boolean',
            'price' => 'required_if:is_paid,1|numeric|min:0',
            'currency' => 'required_if:is_paid,1|string|max:3'
        ]);

        Course::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'image' => $validated['image'] ?? null,
            'color' => $validated['color'],
            'title_color' => $validated['title_color'],
            'is_paid' => $request->has('is_paid'),
            'price' => $request->has('is_paid') ? $validated['price'] : 0,
            'currency' => $request->has('is_paid') ? $validated['currency'] : 'EGP'
        ]);

        return redirect()->route('courses.index')->with('success', 'Course created successfully!');
    }
}
