<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CourseService;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\Auth\Factory as Auth;
use Psr\Log\LoggerInterface;

class CourseViewController extends Controller
{
    private CourseService $courseService;
    private ViewFactory $viewFactory;
    private Auth $auth;
    private LoggerInterface $logger;

    public function __construct(
        CourseService $courseService,
        ViewFactory $viewFactory,
        Auth $auth,
        LoggerInterface $logger
    ) {
        $this->middleware('auth');
        $this->courseService = $courseService;
        $this->viewFactory = $viewFactory;
        $this->auth = $auth;
        $this->logger = $logger;
    }

    public function show(Request $request, $courseId)
    {
        try {
            $userId = $this->auth->id();
            $courseData = $this->courseService->getCourseData($courseId, $userId);

            return $this->viewFactory->make('course.show', $courseData);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load course view', [
                'course_id' => $courseId,
                'user_id' => $this->auth->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->viewFactory->make('errors.500');
        }
    }
}
