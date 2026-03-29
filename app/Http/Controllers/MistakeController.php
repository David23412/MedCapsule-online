<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMistakeRequest;
use App\Services\MistakeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MistakeController extends Controller
{
    protected $mistakeService;

    public function __construct(MistakeService $mistakeService)
    {
        $this->middleware(['auth']);
        $this->mistakeService = $mistakeService;
    }

    public function index(Request $request)
    {
        $userId = Auth::id();
        $courseFilter = $request->input('course');
        if ($courseFilter === 'skipped') {
            $courseFilter = null;
        }

        $data = $this->mistakeService->getMistakesForUser($userId, $courseFilter);

        // SURGICAL OPTIMIZATION: Only log in development for performance
        if (app()->environment('local', 'testing')) {
        }

        return view('mistakes', [
            'mistakes' => $data['mistakes'],
            'courses' => $data['courses'],
            'selectedCourse' => $courseFilter,
            'currentUserView' => [
                'profile_image_url' => Auth::user()->getProfileImageUrl(),
                'profile_fallback_url' => 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&size=200&background=007bff&color=ffffff',
            ],
        ]);
    }

    /**
     * Store mistake if answer is incorrect - COMPLEXITY REDUCED
     */
    public function store(StoreMistakeRequest $request)
    {
        $userId = Auth::id();
        $validated = $request->validated();

        $mistake = $this->mistakeService->recordMistake(
            $userId,
            $validated['question_id'],
            $validated['submitted_answer']
        );

        if ($mistake) {
            // SURGICAL OPTIMIZATION: Only log in development
            if (app()->environment('local', 'testing')) {
            }
            return response()->json(['message' => 'Mistake recorded.']);
        } else {
            // SURGICAL OPTIMIZATION: Only log in development
            if (app()->environment('local', 'testing')) {
            }
            return response()->json(['message' => 'Correct answer, no mistake recorded.']);
        }
    }

    /**
     * COMPLEXITY REDUCTION: Check if answer is correct
     */

    /**
     * COMPLEXITY REDUCTION: Process mistake creation/update
     */

    /**
     * COMPLEXITY REDUCTION: Find existing mistake or create new one
     */

    /**
     * Remove mistake from the list.
     */
    public function destroy($questionId)
    {
        $userId = Auth::id();
        $deleted = $this->mistakeService->deleteMistake($userId, $questionId);

        if ($deleted) {
            return redirect()->route('review.index')->with('success', 'Mistake removed.');
        } else {
            Log::warning('Attempted to delete a non-existent mistake.', ['user_id' => $userId, 'question_id' => $questionId]);
            return redirect()->route('review.index')->with('error', 'Mistake not found.');
        }
    }

    /**
     * Remove mistake via AJAX request.
     */
    public function removeMistake($question_id)
    {
        $userId = Auth::id();
        $deleted = $this->mistakeService->deleteMistake($userId, $question_id);

        if ($deleted) {
            return response()->json(['success' => true]);
        } else {
            Log::warning('Attempted to remove a non-existent mistake via AJAX.', ['user_id' => $userId, 'question_id' => $question_id]);
            return response()->json(['success' => false, 'message' => 'Mistake not found.'], 404);
        }
    }
    
    /**
     * 🚀 CRITICAL FIX: NUCLEAR CACHE INVALIDATION - CLEARS ALL MISTAKE CACHES
     */

    /**
     * Skip a mistake question.
     */
    public function skipMistake(\Illuminate\Http\Request $request, $question_id)
    {
        $userId = Auth::id();
        $mode = $request->validate([
            'mode' => 'nullable|string|in:quiz,practice,spaced,quick',
        ])['mode'] ?? 'quiz';
        $mistake = $this->mistakeService->skipMistake($userId, $question_id, $mode);

        if ($mistake) {
            return response()->json([
                'success' => true,
                'message' => 'Question skipped for now.',
                'skip_until' => optional($mistake->skip_until)->toIso8601String(),
                'skip_count' => (int) ($mistake->skip_count ?? 0),
            ]);
        } else {
            Log::warning('Attempted to skip a non-existent mistake.', ['user_id' => $userId, 'question_id' => $question_id]);
            return response()->json(['success' => false, 'message' => 'Mistake not found.'], 404);
        }
    }

    /**
     * Handle Anki rating for a mistake.
     */
    public function rateMistake(Request $request, $questionId)
    {
        $userId = Auth::id();
        $rating = $request->input('rating');
        $mode = $request->input('mode', 'classic');

        try {
            $mistake = $this->mistakeService->rateMistake($userId, $questionId, $rating, $mode);
            return response()->json([
                'success' => true,
                'message' => 'Mistake rated.',
                'next_review_at' => optional($mistake->next_review_at)->toIso8601String(),
                'interval_days' => (int) ($mistake->interval_days ?? 0),
                'stability' => (float) ($mistake->stability ?? 0),
                'difficulty' => (float) ($mistake->difficulty ?? 0),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to rate mistake.', [
                'user_id' => $userId,
                'question_id' => $questionId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to rate mistake.'], 500);
        }
    }
}
