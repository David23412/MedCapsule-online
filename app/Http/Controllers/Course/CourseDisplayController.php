<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Support\State\EnrollmentService;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\Auth\Factory as Auth;

class CourseDisplayController extends Controller
{
    private EnrollmentService $enrollmentService;
    private LoggerInterface $logger;
    private ViewFactory $viewFactory;
    private Auth $auth;

    public function __construct(
        EnrollmentService $enrollmentService,
        LoggerInterface $logger,
        ViewFactory $viewFactory,
        Auth $auth
    ) {
        $this->enrollmentService = $enrollmentService;
        $this->logger = $logger;
        $this->viewFactory = $viewFactory;
        $this->auth = $auth;
    }

    public function showCourses(Request $request)
    {
        return $this->index($request);
    }

    public function index(Request $request)
    {
        try {
            $courses = $this->getCourses();
            $selectedYear = $request->get('year', ''); // Get selected year from request or default to empty
            
            return $this->viewFactory->make('courses.index', [
                'courses' => $courses,
                'selectedYear' => $selectedYear
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error loading courses: ' . $e->getMessage());
            return $this->viewFactory->make('courses.index', [
                'courses' => collect(), 
                'selectedYear' => '',
                'error' => 'Some course data may not be available.'
            ]);
        }
    }

    public function show($id)
    {
        try {
            $course = $this->getCourseDetails($id);
            $enrollmentStatus = $this->getEnrollmentStatus($course);

            return $this->viewFactory->make('courses.show', array_merge(compact('course'), $enrollmentStatus));
        } catch (\Exception $e) {
            $this->logger->error('Error loading course: ' . $e->getMessage());
            return redirect()->route('courses.index')->with('error', 'Course not found.');
        }
    }

    private function getCourses()
    {
        try {
            $courses = Course::withCount('topics')
                ->orderBy('created_at', 'desc')
                ->paginate(12);

            $courses->each(function ($course) {
                $this->formatCourse($course);
            });

            return $courses;
        } catch (\Exception $e) {
            $this->logger->error('Error loading courses with topics count: ' . $e->getMessage());
            
            // Fallback without withCount
            $courses = Course::orderBy('created_at', 'desc')
                ->paginate(12);

            $courses->each(function ($course) {
                $this->formatCourse($course);
            });

            return $courses;
        }
    }

    private function getCourseDetails($id)
    {
        try {
            return Course::with(['topics' => function ($query) {
                $query->select('id', 'course_id', 'name', 'display_order')->orderBy('display_order');
            }])->findOrFail($id);
        } catch (\Exception $e) {
            $this->logger->error('Error loading course details: ' . $e->getMessage(), [
                'course_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback without eager loading
            return Course::findOrFail($id);
        }
    }

    private function getEnrollmentStatus(Course $course): array
    {
        $isEnrolled = false;
        if ($this->auth->check()) {
            $isEnrolled = $this->enrollmentService->isEnrolled($this->auth->id(), $course->id);
        }

        return [
            'isEnrolled' => $isEnrolled,
            'canEnroll' => !$isEnrolled && $this->isCourseFree($course),
        ];
    }

    private function formatCourse(Course $course): void
    {
        // Auto-enroll user in free courses
        if ($this->auth->check() && $this->isCourseFree($course)) {
            $this->autoEnrollInFreeCourse($course);
        }
        
        $enrollmentStatus = $this->getEnrollmentStatus($course);
        $course->is_enrolled = $enrollmentStatus['isEnrolled'];
        $course->can_enroll = $enrollmentStatus['canEnroll'];
        $course->price_display = $this->isCourseFree($course) ? 'Free' : number_format($course->price, 2) . ' EGP';
        $course->is_free = $this->isCourseFree($course);
        // Safely get topics count
        try {
            $course->topics_count = $course->topics_count ?? $course->topics()->count();
        } catch (\Exception $e) {
            $course->topics_count = 0;
            $this->logger->error('Error getting topics count for course: ' . $e->getMessage(), [
                'course_id' => $course->id
            ]);
        }
        
        // Add enrolled preview data
        $course->enrolled_preview = $this->getEnrolledPreview($course);
        
        // Ensure academic_year is set
        $course->academic_year = $course->academic_year ?? '';
        $course->semester = $course->semester ?? '';
    }

    private function isCourseFree(Course $course): bool
    {
        return $course->price === null || $course->price == 0;
    }
    
    private function autoEnrollInFreeCourse(Course $course): void
    {
        try {
            $userId = $this->auth->id();
            
            // Check if already enrolled
            if ($this->enrollmentService->isEnrolled($userId, $course->id)) {
                return; // Already enrolled
            }
            
            // Auto-enroll the user
            $this->enrollmentService->enrollUser($userId, $course->id);
            
            $this->logger->info('User auto-enrolled in free course', [
                'user_id' => $userId,
                'course_id' => $course->id,
                'course_name' => $course->name
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to auto-enroll user in free course', [
                'user_id' => $this->auth->id(),
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function getEnrolledPreview(Course $course): array
    {
        try {
            // Get a few enrolled users for preview with explicit table names
            $enrolledUsers = $course->users()
                ->select('users.id', 'users.name', 'users.profile_picture_url')
                ->limit(5)
                ->get();
            
            return $enrolledUsers->map(function($user) {
                return [
                    'name' => $user->name,
                    'profile_picture_url' => $user->profile_picture_url,
                    'initial' => strtoupper(substr($user->name, 0, 1))
                ];
            })->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Failed to get enrolled preview for course', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
