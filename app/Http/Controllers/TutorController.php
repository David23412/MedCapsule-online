<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use App\Services\QuizPerformanceService;
use App\Services\QuizCacheService;
use App\Http\Requests\TutorRequest;
use App\Models\User; // Assuming Tutor is a user
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;

class TutorController extends Controller
{
    private QuizPerformanceService $performanceService;
    private QuizCacheService $cacheService;
    private CacheRepository $cache;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;
    private User $userModel;

    public function __construct(
        QuizPerformanceService $performanceService,
        QuizCacheService $cacheService,
        CacheRepository $cache,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        User $userModel
    ) {
        $this->middleware('auth');
        $this->performanceService = $performanceService;
        $this->cacheService = $cacheService;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->userModel = $userModel;
    }

    public function index(TutorRequest $request): JsonResponse
    {
        try {
            $data = $this->cache->remember("tutors_index", 300, function () {
                return $this->userModel->where('role', 'tutor')->get();
            });

            $this->performanceService->track("tutor_index", microtime(true));
            $this->logger->info("Tutor index operation completed");

            return $this->responseFactory->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->logger->error("Tutor index failed", ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to retrieve tutors'], 500);
        }
    }

    public function show(TutorRequest $request, $id): JsonResponse
    {
        try {
            $data = $this->cache->remember("tutor_{$id}", 300, function () use ($id) {
                return $this->userModel->where('role', 'tutor')->findOrFail($id);
            });

            $this->performanceService->track("tutor_show", microtime(true));
            $this->logger->info("Tutor show completed", ['id' => $id]);

            return $this->responseFactory->json(['data' => $data]);
        } catch (\Exception $e) {
            $this->logger->error("Tutor show failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to retrieve tutor'], 500);
        }
    }

    public function store(TutorRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['role'] = 'tutor';

            $result = $this->userModel->create($validated);

            $this->cache->forget("tutors_index");

            $this->performanceService->track("tutor_store", microtime(true));
            $this->logger->info("Tutor store completed", ['id' => $result->id]);

            return $this->responseFactory->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->logger->error("Tutor store failed", ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to create tutor'], 500);
        }
    }

    public function update(TutorRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $tutor = $this->userModel->where('role', 'tutor')->findOrFail($id);
            $tutor->update($validated);

            $this->cache->forget("tutor_{$id}");
            $this->cache->forget("tutors_index");

            $this->performanceService->track("tutor_update", microtime(true));
            $this->logger->info("Tutor update completed", ['id' => $id]);

            return $this->responseFactory->json(['success' => true, 'data' => $tutor]);
        } catch (\Exception $e) {
            $this->logger->error("Tutor update failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to update tutor'], 500);
        }
    }

    public function destroy(TutorRequest $request, $id): JsonResponse
    {
        try {
            $tutor = $this->userModel->where('role', 'tutor')->findOrFail($id);
            $tutor->delete();

            $this->cache->forget("tutor_{$id}");
            $this->cache->forget("tutors_index");

            $this->performanceService->track("tutor_destroy", microtime(true));
            $this->logger->info("Tutor destroy completed", ['id' => $id]);

            return $this->responseFactory->json(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error("Tutor destroy failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to delete tutor'], 500);
        }
    }
}