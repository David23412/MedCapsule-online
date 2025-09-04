<?php

namespace App\Http\Controllers\Topic;

use App\Http\Controllers\Controller;
use App\Http\Requests\TopicRequest;
use App\Models\Topic;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuizAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * LETHAL OPTIMIZED TOPIC ACTION CONTROLLER
 * Handles topic creation and management operations
 */
class TopicActionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('login')->with('error', 'Please login to continue.');
            }
            if (auth()->user()->role !== 'admin') {
                return redirect()->route('home')->with('error', 'Access denied. Only admins can manage topics.');
            }
            return $next($request);
        });
    }

    /**
     * Handle topic creation
     */
    public function addTopic(TopicRequest $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Access denied. Only admins can manage topics.');
        }

        $validated = $request->validated();

        try {
            $topic = Topic::create([
                'name' => $validated['name'],
                'course_id' => $validated['course_id'],
                'description' => $validated['description'] ?? '',
                'display_order' => $validated['display_order'] ?? 0,
                'created_by' => auth()->id()
            ]);

            Log::info('New topic created', [
                'topic_id' => $topic->id,
                'topic_name' => $topic->name,
                'course_id' => $topic->course_id,
                'created_by' => auth()->id()
            ]);

            return redirect()->back()->with('success', 'Topic added successfully!');
            
        } catch (\Exception $e) {
            Log::error('Failed to create topic', [
                'error' => $e->getMessage(),
                'validated_data' => $validated,
                'user_id' => auth()->id()
            ]);

            return back()->withInput()
                ->with('error', 'Failed to create topic. Please try again.');
        }
    }

    /**
     * Update topic data after quiz attempt - COMPLEXITY REDUCED
     */
    public function updateTopicAfterQuiz($topicId)
    {
        $topic = Topic::findOrFail($topicId);

        // COMPLEXITY REDUCTION: Extract question counting
        $totalQuestions = $this->getTotalQuestions($topicId);
        if ($totalQuestions === 0) {
            return;
        }

        // COMPLEXITY REDUCTION: Extract grade calculation
        $percentageGrade = $this->calculateTopicGrade($topicId, $totalQuestions);

        // Update topic details
        $topic->update([
            'percentage_grade' => $percentageGrade,
            'last_attempt_date' => Carbon::now()
        ]);
    }

    /**
     * Get cases for a course
     */
    public function casesForCourse(Course $course)
    {
        return $course->topics()
            ->where('name', 'like', '% cases')
            ->get();
    }

    /**
     * Get cases count for a course
     */
    public function casesCount(Course $course)
    {
        return $course->topics()
            ->where('name', 'like', '% cases')
            ->count();
    }

    /**
     * COMPLEXITY REDUCTION: Get total questions for topic
     */
    private function getTotalQuestions($topicId): int
    {
        return Question::where('topic_id', $topicId)->count();
    }

    /**
     * COMPLEXITY REDUCTION: Calculate topic grade percentage
     */
    private function calculateTopicGrade($topicId, $totalQuestions): float
    {
        $incorrectAnswers = QuizAttempt::whereHas('question', function ($query) use ($topicId) {
            $query->where('topic_id', $topicId);
        })
        ->whereColumn('submitted_answer', '!=', 'questions.correct_answer')
        ->count();

        $correctAnswers = $totalQuestions - $incorrectAnswers;
        return ($correctAnswers / $totalQuestions) * 100;
    }
}
