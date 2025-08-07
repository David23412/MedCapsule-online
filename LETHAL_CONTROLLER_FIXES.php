<?php

// LETHAL CONTROLLER FIXES

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Services\QuizService;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuizExecutionController extends Controller
{
    private QuizService $quizService;
    private GradingService $gradingService;
    
    public function __construct(QuizService $quizService, GradingService $gradingService)
    {
        $this->middleware('auth');
        $this->quizService = $quizService;
        $this->gradingService = $gradingService;
    }
    
    /**
     * LETHAL FIX: Submit quiz with proper authentication
     */
    public function submit(Request $request): JsonResponse
    {
        try {
            // Validate user is authenticated
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            $validated = $request->validate([
                'quiz_id' => 'required|integer|exists:quizzes,id',
                'answers' => 'required|array',
                'time_taken' => 'nullable|integer'
            ]);
            
            $result = $this->quizService->submitQuiz(
                Auth::id(),
                $validated['quiz_id'],
                $validated['answers'],
                $validated['time_taken'] ?? null
            );
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Quiz submission error: ' . $e->getMessage());
            return response()->json(['error' => 'Submission failed'], 500);
        }
    }
    
    /**
     * LETHAL FIX: Grade quiz with proper permissions
     */
    public function grade(Request $request): JsonResponse
    {
        try {
            // Check if user can grade (tutor or admin)
            $user = Auth::user();
            if (!$user || (!$user->isTutor() && !$user->isAdmin())) {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }
            
            $validated = $request->validate([
                'submission_id' => 'required|integer|exists:quiz_submissions,id',
                'grade' => 'required|numeric|min:0|max:100',
                'feedback' => 'nullable|string'
            ]);
            
            $result = $this->gradingService->gradeSubmission(
                $validated['submission_id'],
                $validated['grade'],
                $validated['feedback'] ?? null,
                Auth::id()
            );
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Grading error: ' . $e->getMessage());
            return response()->json(['error' => 'Grading failed'], 500);
        }
    }
    
    /**
     * LETHAL FIX: API endpoints for AJAX calls
     */
    public function apiSubmit(Request $request): JsonResponse
    {
        return $this->submit($request);
    }
    
    public function apiGrade(Request $request): JsonResponse
    {
        return $this->grade($request);
    }
}
