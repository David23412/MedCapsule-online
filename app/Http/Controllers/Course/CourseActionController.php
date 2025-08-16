<?php

namespace App\Http\Controllers\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Services\NotificationService;
use App\Services\CourseEnrollmentService;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Cache\Repository as Cache;

class CourseActionController extends Controller
{
    private NotificationService $notificationService;
    private CourseEnrollmentService $enrollmentService;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;
    private Auth $auth;
    private Cache $cache;

    public function __construct(
        NotificationService $notificationService,
        CourseEnrollmentService $enrollmentService,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        Auth $auth,
        Cache $cache
    ) {
        $this->middleware(['auth']);
        $this->middleware('admin')->only('addCourse');
        $this->notificationService = $notificationService;
        $this->enrollmentService = $enrollmentService;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->auth = $auth;
        $this->cache = $cache;
    }

    public function enroll(Request $request, $courseId)
    {
        try {
            $this->enrollmentService->enroll($this->auth->user(), $courseId);
            $course = Course::findOrFail($courseId);
            return $this->responseFactory->redirectToRoute('topics.forCourse', $courseId)
                ->with('success', "Successfully enrolled in {$course->name}!");
        } catch (\Exception $e) {
            $this->logger->error('Failed to enroll in course', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'user_id' => $this->auth->id()
            ]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function addCourse(CourseRequest $request)
    {
        try {
            $course = $this->createCourse($request->validated());
            $this->clearCourseCaches();
            $this->logger->info('New course created', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'created_by' => $this->auth->id()
            ]);
            return $this->responseFactory->redirectToRoute('admin.courses')
                ->with('success', 'Course "' . $course->name . '" created successfully!');
        } catch (\Exception $e) {
            $this->logger->error('Failed to create course', [
                'error' => $e->getMessage(),
                'validated_data' => $request->validated(),
                'user_id' => $this->auth->id()
            ]);
            return back()->withInput()->with('error', 'Failed to create course. Please try again.');
        }
    }

    private function createCourse(array $validatedData): Course
    {
        return Course::create(array_merge($validatedData, [
            'created_by' => $this->auth->id(),
            'is_active' => $validatedData['is_active'] ?? true,
        ]));
    }

    private function clearCourseCaches(): void
    {
        $this->cache->forget('courses_cache_version');
        $this->cache->increment('courses_cache_version');
        $this->cache->tags(['courses'])->flush();
    }
}
