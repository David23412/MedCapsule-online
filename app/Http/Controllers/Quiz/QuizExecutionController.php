<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Topic;
use App\Services\Quiz\QuizExecutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 PERFORMANCE BEAST: Quiz Execution Controller
 * Handles: Quiz execution endpoints
 * Lines: ~80 (well under 200 limit)
 */
class QuizExecutionController extends Controller
{
    private QuizExecutionService $quizService;

    public function __construct(QuizExecutionService $quizService)
    {
        $this->middleware(['auth']);
        $this->quizService = $quizService;
    }

    /**
     * Start a quiz for a topic
     */
    public function startQuiz(Course $course, Topic $topic)
    {
        try {
            $user = auth()->user();
            $params = request()->all();
            
            Log::info('Quiz start requested', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'params' => $params
            ]);

            $quizData = $this->quizService->prepareQuizStart($course, $topic, $user, $params);
            
            return view('solve_quiz', $quizData);

        } catch (\Exception $e) {
            Log::error('Quiz start failed', [
                'user_id' => auth()->id(),
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('topics.forCourse', $course->id)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Grade a completed quiz
     */
    public function grade(Request $request, Course $course, Topic $topic)
    {
        try {
            $user = auth()->user();
            
            $results = $this->quizService->gradeQuiz($request, $course, $topic, $user);
            
            Log::info('Quiz graded successfully', [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'percentage' => $results['grade_percentage']
            ]);

            return response()->json([
                'success' => true,
                'score' => $results['results']['correct_answers'],
                'total' => $results['results']['total_questions'],
                'percentage' => $results['grade_percentage'],
                'message' => 'Quiz completed successfully!',
                'quiz_attempt_ids' => array_map(fn($attempt) => $attempt->id, $results['quiz_attempts']),
                'source_progress' => $results['source_progress'] ?? [],
                'availableSources' => array_keys($results['source_progress'] ?? [])
            ]);

        } catch (\Exception $e) {
            Log::error('Quiz grading failed', [
                'user_id' => auth()->id(),
                'topic_id' => $topic->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}