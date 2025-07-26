<?php

namespace App\Http\Controllers\Learn;

use App\Http\Controllers\Controller;
use App\Models\Mistake;
use Illuminate\Http\Request;
use App\Services\UserStatsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * LETHAL OPTIMIZED LEARN ACTION CONTROLLER
 * Handles mistake review actions and study time tracking
 */
class LearnActionController extends Controller
{
    protected $userStatsService;

    public function __construct(UserStatsService $userStatsService)
    {
        $this->middleware(['auth']);
        $this->userStatsService = $userStatsService;
    }

    /**
     * Handle the review (rating) for a mistake - LETHAL ERROR HANDLING
     */
    public function reviewMistake(Request $request, $questionId)
    {
        try {
            $userId = auth()->id();
            $rating = $request->input('rating');
            
            if (!in_array($rating, [1, 2, 3, 4, 5])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid rating. Must be between 1 and 5.'
                ], 400);
            }

            DB::beginTransaction();

            $mistake = Mistake::where('user_id', $userId)
                ->where('question_id', $questionId)
                ->first();

            if (!$mistake) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Mistake not found.'
                ], 404);
            }

            // Update mistake based on rating
            if ($rating >= 4) {
                $mistake->mastered = true;
                $mistake->mastered_at = now();
            }

            $mistake->last_reviewed = now();
            $mistake->review_count = ($mistake->review_count ?? 0) + 1;
            $mistake->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Review recorded successfully',
                'mastered' => $mistake->mastered
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Review mistake failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'question_id' => $questionId,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to record review'
            ], 500);
        }
    }

    /**
     * Remove a mistake from the user's list - LETHAL ERROR HANDLING
     */
    public function removeMistake($questionId)
    {
        try {
            $userId = auth()->id();

            DB::beginTransaction();

            $mistake = Mistake::where('user_id', $userId)
                ->where('question_id', $questionId)
                ->first();

            if (!$mistake) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Mistake not found.'
                ], 404);
            }

            $mistake->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mistake removed successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Remove mistake failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'question_id' => $questionId,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove mistake'
            ], 500);
        }
    }

    /**
     * Save study time for analytics - LETHAL ERROR HANDLING
     */
    public function saveStudyTime(Request $request)
    {
        try {
            $userId = auth()->id();
            $studyTime = $request->input('study_time', 0);
            $questionId = $request->input('question_id');

            if ($studyTime <= 0 || $studyTime > 3600) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid study time'
                ], 400);
            }

            // Update user stats
            $this->userStatsService->recordStudyTime($userId, $studyTime, $questionId);

            return response()->json([
                'success' => true,
                'message' => 'Study time recorded'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Save study time failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save study time'
            ], 500);
        }
    }
}