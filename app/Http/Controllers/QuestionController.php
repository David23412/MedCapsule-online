<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\QuestionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\QuizPerformanceService;
use Illuminate\Support\Facades\Cache;
use App\Services\QuizCacheService;
use App\Models\Question;
use App\Models\Topic;

class QuestionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function showAddQuestionForm()
    {
        // Ultra-aggressive caching for topics
        $cacheKey = QuizCacheService::generateCacheKey('admin_topics_all', ['timestamp' => now()->format('Y-m-d-H')]);
        $topics = Cache::remember($cacheKey, 3600, function () {
            return Topic::select('id', 'name', 'course_id')->with('course:id,name')->get();
        });
        
        return view('add_question', compact('topics'));
    }

    public function storeQuestion(QuestionRequest $request)
    {
        try {
            if (auth()->user()->role !== 'admin') {
                abort(403);
            }

        // Validate basic fields first
        // TODO: Create FormRequest class for validation

        // Handle image upload
        $imageUrl = null;
        if ($request->hasFile('question_image')) {
            $file = $request->file('question_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('questions', $filename, 'public');
            $imageUrl = 'storage/' . $path;
        } elseif ($request->filled('image_url')) {
            $imageUrl = $request->image_url;
        }

        // Validate based on question type
        if ($request->question_type === 'multiple_choice') {
            // TODO: Create FormRequest class for validation
            
            $correctAnswer = $request->correct_answer;
            $alternativeAnswers = null;
            
        } else { // written question
            // TODO: Create FormRequest class for validation
            
            $correctAnswer = $request->written_correct_answer;
            
            // Process alternative answers if provided
            if ($request->filled('alternative_answers')) {
                // Split by new lines and filter out empty lines
                $alternativeAnswers = array_filter(
                    explode("\n", $request->alternative_answers),
                    function($line) {
                        return trim($line) !== '';
                    }
                );
            } else {
                $alternativeAnswers = null;
            }
        }

        // Set empty values for options if it's a written question
        $optionA = $request->question_type === 'multiple_choice' ? $request->option_a : '';
        $optionB = $request->question_type === 'multiple_choice' ? $request->option_b : '';
        $optionC = $request->question_type === 'multiple_choice' ? $request->option_c : '';
        $optionD = $request->question_type === 'multiple_choice' ? $request->option_d : '';

        // Create the question
        Question::create([
            'topic_id' => $request->topic_id,
            'question' => $request->question,
            'explanation' => $request->explanation,
            'question_type' => $request->question_type,
            'option_a' => $optionA,
            'option_b' => $optionB,
            'option_c' => $optionC,
            'option_d' => $optionD,
            'correct_answer' => $correctAnswer,
            'alternative_answers' => $alternativeAnswers,
            'image_url' => $imageUrl,
            'source' => $request->source
        ]);

        return redirect()->back()->with('success', 'Question added successfully!');
        
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }

    public function editQuestion($id)
    {
        try {
            $question = Question::findOrFail($id);
            $topics = Topic::all();
            return view('edit_question', compact('question', 'topics'));
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
    }

    public function updateQuestion(Request $request, $id)
    {
        try {

        // TODO: Create FormRequest class for validation

        $question = Question::findOrFail($id);
        $question->update([
            'topic_id' => $request->topic_id,
            'question_text' => $request->question_text,
            'options' => json_encode($request->options),
            'correct_answer' => $request->correct_answer]);

        return redirect()->back()->with('success', 'Question updated successfully!');
    
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
}

    public function deleteQuestion($id)
    {
        try {

        Question::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Question deleted successfully!');
    
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
}
}