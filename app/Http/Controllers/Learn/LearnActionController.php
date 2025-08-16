<?php

namespace App\Http\Controllers\Learn;

use App\Http\Controllers\Controller;
use App\Models\Mistake;
use Illuminate\Http\Request;
use App\Services\UserStatsService;
use App\Support\State\MistakeMasteryService;
use Psr\Log\LoggerInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\JsonResponse;

class LearnActionController extends Controller
{
    protected UserStatsService $userStatsService;
    protected MistakeMasteryService $mistakeMasteryService;
    protected LoggerInterface $logger;
    protected DatabaseManager $db;
    protected ResponseFactory $responseFactory;
    protected Auth $auth;

    public function __construct(
        UserStatsService $userStatsService,
        MistakeMasteryService $mistakeMasteryService,
        LoggerInterface $logger,
        DatabaseManager $db,
        ResponseFactory $responseFactory,
        Auth $auth
    ) {
        $this->middleware(['auth']);
        $this->userStatsService = $userStatsService;
        $this->mistakeMasteryService = $mistakeMasteryService;
        $this->logger = $logger;
        $this->db = $db;
        $this->responseFactory = $responseFactory;
        $this->auth = $auth;
    }

    public function reviewMistake(Request $request, $questionId): JsonResponse
    {
        $rating = $request->input('rating');
        
        if (!$this->isValidRating($rating)) {
            return $this->jsonResponse('Invalid rating. Must be between 1 and 5.', 400, false);
        }

        return $this->handleMistakeOperation($questionId, function ($mistake) use ($rating) {
            $this->updateMistakeReview($mistake, $rating);
            
            return [
                'message' => 'Review recorded successfully',
                'mastered' => $this->mistakeMasteryService->isMastered($mistake)
            ];
        });
    }

    public function removeMistake($questionId): JsonResponse
    {
        return $this->handleMistakeOperation($questionId, function ($mistake) {
            $mistake->delete();
            return ['message' => 'Mistake removed successfully'];
        });
    }

    public function saveStudyTime(Request $request): JsonResponse
    {
        try {
            $studyTime = $request->input('study_time', 0);
            
            if (!$this->isValidStudyTime($studyTime)) {
                return $this->jsonResponse('Invalid study time', 400, false);
            }

            $this->userStatsService->recordStudyTime(
                $this->auth->id(), 
                $studyTime, 
                $request->input('question_id')
            );

            return $this->jsonResponse('Study time recorded', 200, true);

        } catch (\Exception $e) {
            $this->logError('Save study time failed', $e->getMessage());
            return $this->jsonResponse('Failed to save study time', 500, false);
        }
    }

    /**
     * FIXED: Extract rating validation to eliminate duplication
     */
    private function isValidRating($rating): bool
    {
        return in_array($rating, [1, 2, 3, 4, 5]);
    }

    /**
     * FIXED: Extract study time validation to eliminate duplication
     */
    private function isValidStudyTime($studyTime): bool
    {
        return $studyTime > 0 && $studyTime <= 3600;
    }

    /**
     * FIXED: Extract mistake review update logic to eliminate duplication
     */
    private function updateMistakeReview(Mistake $mistake, int $rating): void
    {
        if ($rating >= 4) {
            $mistake->correct_streak = config('mistakes.thresholds.quiz', 4);
            $mistake->mastered_at = now();
        }
        $mistake->last_reviewed = now();
        $mistake->review_count = ($mistake->review_count ?? 0) + 1;
        $mistake->save();
    }

    /**
     * FIXED: Extract common mistake operation handling to eliminate duplication
     */
    private function handleMistakeOperation($questionId, callable $operation)
    {
        try {
            return $this->db->transaction(function () use ($questionId, $operation) {
                $mistake = $this->findMistake($questionId);

                if (!$mistake) {
                    return $this->jsonResponse('Mistake not found.', 404, false);
                }

                $data = $operation($mistake);

                return $this->jsonResponse($data['message'], 200, true, $data);
            });
        } catch (\Exception $e) {
            $this->logError('Mistake operation failed', $e->getMessage(), [
                'question_id' => $questionId
            ]);
            return $this->jsonResponse('Failed to process mistake operation', 500, false);
        }
    }

    /**
     * FIXED: Extract common error logging to eliminate duplication
     */
    private function logError(string $message, string $error, array $context = []): void
    {
        $this->logger->error($message, array_merge([
            'error' => $error,
            'user_id' => $this->auth->id()
        ], $context));
    }

    /**
     * FIXED: Extract mistake finding logic to eliminate duplication
     */
    private function findMistake($questionId): ?Mistake
    {
        return Mistake::where('user_id', $this->auth->id())
            ->where('question_id', $questionId)
            ->first();
    }

    /**
     * FIXED: Extract JSON response creation to eliminate duplication
     */
    private function jsonResponse(string $message, int $status, bool $success, array $data = []): JsonResponse
    {
        $responseData = ['success' => $success, 'message' => $message];
        if (!empty($data)) {
            $responseData = array_merge($responseData, $data);
        }
        return $this->responseFactory->json($responseData, $status);
    }
}
