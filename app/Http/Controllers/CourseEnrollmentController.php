<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use App\Services\QuizPerformanceService;
use App\Services\QuizCacheService;
use App\Services\ResilientCacheService;
use App\Http\Requests\CourseEnrollmentRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Course Enrollment Controller
 * Handles course enrollment operations with proper dependency injection
 */
class CourseEnrollmentController extends Controller
{
    private QuizPerformanceService $performanceService;
    private QuizCacheService $cacheService;
    private CacheRepository $cache;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;
    private Course $courseModel;

    public function __construct(
        QuizPerformanceService $performanceService,
        QuizCacheService $cacheService,
        CacheRepository $cache,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        Course $courseModel
    ) {
        $this->middleware('auth');
        $this->performanceService = $performanceService;
        $this->cacheService = $cacheService;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->courseModel = $courseModel;
    }

    public function index(CourseEnrollmentRequest $request): JsonResponse
    {
        try {
            $data = $this->cache->remember("course_enrollments", 300, function() {
                return $this->getData();
            });
            
            $this->performanceService->track("course_enrollment_index", microtime(true));
            
            $validated = $request->validated();
            
            $this->logger->info("Course enrollment index operation completed");
            
            return $this->responseFactory->json(['success' => true, 'data' => $data]);
            
        } catch (\Exception $e) {
            $this->logger->error("Course enrollment index failed", ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to retrieve course enrollments'], 500);
        }
    }

    public function show(CourseEnrollmentRequest $request, $id): JsonResponse
    {
        try {
            $data = $this->cache->remember("course_enrollment_{$id}", 300, function() use ($id) {
                return $this->courseModel->with(['user'])->select(['id', 'name', 'description'])->find($id);
            });
            
            $this->performanceService->track("course_enrollment_show", microtime(true));
            
            $this->logger->info("Course enrollment show completed", ['id' => $id]);
            
            $validated = $request->validated();
            
            return $this->responseFactory->json(['data' => $data]);
        } catch (\Exception $e) {
            $this->logger->error("Course enrollment show failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to retrieve course enrollment'], 500);
        }
    }

    public function store(CourseEnrollmentRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $result = $this->courseModel->create($validated);
            
            // Clear related cache
            $this->cache->forget("course_enrollments");
            
            $this->performanceService->track("course_enrollment_store", microtime(true));
            
            $this->logger->info("Course enrollment store completed", ['id' => $result->id]);
            
            return $this->responseFactory->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->logger->error("Course enrollment store failed", ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to create course enrollment'], 500);
        }
    }

    public function update(CourseEnrollmentRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $course = $this->courseModel->findOrFail($id);
            $course->update($validated);
            
            // Clear related cache
            $this->cache->forget("course_enrollment_{$id}");
            $this->cache->forget("course_enrollments");
            
            $this->performanceService->track("course_enrollment_update", microtime(true));
            $this->logger->info("Course enrollment update completed", ['id' => $id]);
            
            return $this->responseFactory->json(['success' => true, 'data' => $course]);
        } catch (\Exception $e) {
            $this->logger->error("Course enrollment update failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to update course enrollment'], 500);
        }
    }

    public function destroy(CourseEnrollmentRequest $request, $id): JsonResponse
    {
        try {
            $course = $this->courseModel->findOrFail($id);
            $course->delete();
            
            // Clear related cache
            $this->cache->forget("course_enrollment_{$id}");
            $this->cache->forget("course_enrollments");
            
            $this->performanceService->track("course_enrollment_destroy", microtime(true));
            $this->logger->info("Course enrollment destroy completed", ['id' => $id]);
            
            return $this->responseFactory->json(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error("Course enrollment destroy failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to delete course enrollment'], 500);
        }
    }

    private function getData()
    {
        return $this->courseModel->with(['user'])->select(['id', 'name', 'description'])->get();
    }
}