<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use App\Services\QuizPerformanceService;
use App\Services\QuizCacheService;
use App\Services\ResilientCacheService;
use App\Http\Requests\OptimizedRandomQuizRequest;
use App\Models\Quiz;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Optimized Random Quiz Controller
 * Handles random quiz operations with proper dependency injection
 */
class OptimizedRandomQuizController extends Controller
{
    private QuizPerformanceService $performanceService;
    private QuizCacheService $cacheService;
    private CacheRepository $cache;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;
    private Quiz $quizModel;

    public function __construct(
        QuizPerformanceService $performanceService, 
        QuizCacheService $cacheService,
        CacheRepository $cache,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        Quiz $quizModel
    ) {
        $this->middleware('auth');
        $this->performanceService = $performanceService;
        $this->cacheService = $cacheService;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->quizModel = $quizModel;
    }

    public function index(OptimizedRandomQuizRequest $request): JsonResponse
    {
        try {
            $data = $this->cache->remember("random_quizzes", 300, function() {
                return $this->getData();
            });
            
            $this->performanceService->track("random_quiz_index", microtime(true));
            
            $validated = $request->validated();
            
            $this->logger->info("Random quiz index operation completed");
            
            return $this->responseFactory->json(['success' => true, 'data' => $data]);
            
        } catch (\Exception $e) {
            $this->logger->error("Random quiz index failed", ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to retrieve random quizzes'], 500);
        }
    }

    public function show(OptimizedRandomQuizRequest $request, $id): JsonResponse
    {
        try {
            $data = $this->cache->remember("random_quiz_{$id}", 300, function() use ($id) {
                return $this->quizModel->with(['questions'])->select(['id', 'title', 'description'])->find($id);
            });
            
            $this->performanceService->track("random_quiz_show", microtime(true));
            
            $this->logger->info("Random quiz show completed", ['id' => $id]);
            
            $validated = $request->validated();
            
            return $this->responseFactory->json(['data' => $data]);
        } catch (\Exception $e) {
            $this->logger->error("Random quiz show failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to retrieve random quiz'], 500);
        }
    }

    public function store(OptimizedRandomQuizRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $result = $this->quizModel->create($validated);
            
            // Clear related cache
            $this->cache->forget("random_quizzes");
            
            $this->performanceService->track("random_quiz_store", microtime(true));
            
            $this->logger->info("Random quiz store completed", ['id' => $result->id]);
            
            return $this->responseFactory->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->logger->error("Random quiz store failed", ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to create random quiz'], 500);
        }
    }

    public function update(OptimizedRandomQuizRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $quiz = $this->quizModel->findOrFail($id);
            $quiz->update($validated);
            
            // Clear related cache
            $this->cache->forget("random_quiz_{$id}");
            $this->cache->forget("random_quizzes");
            
            $this->performanceService->track("random_quiz_update", microtime(true));
            $this->logger->info("Random quiz update completed", ['id' => $id]);
            
            return $this->responseFactory->json(['success' => true, 'data' => $quiz]);
        } catch (\Exception $e) {
            $this->logger->error("Random quiz update failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to update random quiz'], 500);
        }
    }

    public function destroy(OptimizedRandomQuizRequest $request, $id): JsonResponse
    {
        try {
            $quiz = $this->quizModel->findOrFail($id);
            $quiz->delete();
            
            // Clear related cache
            $this->cache->forget("random_quiz_{$id}");
            $this->cache->forget("random_quizzes");
            
            $this->performanceService->track("random_quiz_destroy", microtime(true));
            $this->logger->info("Random quiz destroy completed", ['id' => $id]);
            
            return $this->responseFactory->json(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error("Random quiz destroy failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to delete random quiz'], 500);
        }
    }

    private function getData()
    {
        return $this->quizModel->with(['questions'])->select(['id', 'title', 'description'])->get();
    }
}