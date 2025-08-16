<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use App\Services\QuizPerformanceService;
use App\Services\QuizCacheService;
use App\Http\Requests\PaymentOptionsRequest;
use App\Models\PaymentOption; // Assuming a PaymentOption model exists
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOptionsController extends Controller
{
    private QuizPerformanceService $performanceService;
    private QuizCacheService $cacheService;
    private CacheRepository $cache;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;
    private PaymentOption $paymentOptionModel;

    public function __construct(
        QuizPerformanceService $performanceService,
        QuizCacheService $cacheService,
        CacheRepository $cache,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        PaymentOption $paymentOptionModel
    ) {
        $this->middleware('auth');
        $this->performanceService = $performanceService;
        $this->cacheService = $cacheService;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->paymentOptionModel = $paymentOptionModel;
    }

    public function index(PaymentOptionsRequest $request): JsonResponse
    {
        try {
            $data = $this->cache->remember("payment_options_index", 300, function () {
                return $this->paymentOptionModel->all();
            });

            $this->performanceService->track("payment_options_index", microtime(true));
            $this->logger->info("Payment options index operation completed");

            return $this->responseFactory->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->logger->error("Payment options index failed", ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to retrieve payment options'], 500);
        }
    }

    public function show(PaymentOptionsRequest $request, $id): JsonResponse
    {
        try {
            $data = $this->cache->remember("payment_option_{$id}", 300, function () use ($id) {
                return $this->paymentOptionModel->findOrFail($id);
            });

            $this->performanceService->track("payment_options_show", microtime(true));
            $this->logger->info("Payment options show completed", ['id' => $id]);

            return $this->responseFactory->json(['data' => $data]);
        } catch (\Exception $e) {
            $this->logger->error("Payment options show failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to retrieve payment option'], 500);
        }
    }

    public function store(PaymentOptionsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->paymentOptionModel->create($validated);

            $this->cache->forget("payment_options_index");

            $this->performanceService->track("payment_options_store", microtime(true));
            $this->logger->info("Payment options store completed", ['id' => $result->id]);

            return $this->responseFactory->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            $this->logger->error("Payment options store failed", ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to create payment option'], 500);
        }
    }

    public function update(PaymentOptionsRequest $request, $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $option = $this->paymentOptionModel->findOrFail($id);
            $option->update($validated);

            $this->cache->forget("payment_option_{$id}");
            $this->cache->forget("payment_options_index");

            $this->performanceService->track("payment_options_update", microtime(true));
            $this->logger->info("Payment options update completed", ['id' => $id]);

            return $this->responseFactory->json(['success' => true, 'data' => $option]);
        } catch (\Exception $e) {
            $this->logger->error("Payment options update failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to update payment option'], 500);
        }
    }

    public function destroy(PaymentOptionsRequest $request, $id): JsonResponse
    {
        try {
            $option = $this->paymentOptionModel->findOrFail($id);
            $option->delete();

            $this->cache->forget("payment_option_{$id}");
            $this->cache->forget("payment_options_index");

            $this->performanceService->track("payment_options_destroy", microtime(true));
            $this->logger->info("Payment options destroy completed", ['id' => $id]);

            return $this->responseFactory->json(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error("Payment options destroy failed", ['id' => $id, 'error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Failed to delete payment option'], 500);
        }
    }
}