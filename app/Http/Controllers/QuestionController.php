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
        $cacheKey = QuizUltraConsolidatedCacheService::generateCacheKey('admin_topics_all', ['timestamp' => now()->format('Y-m-d-H')]);
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

        // SECURITY FIX: Enhanced file upload security
        $imageUrl = null;
        if ($request->hasFile('question_image')) {
            $file = $request->file('question_image');

            // SECURITY FIX: Validate file integrity
            if (!$file->isValid()) {
                throw new \Exception('Invalid file upload');
            }

            // SECURITY FIX: Check for malicious filename patterns
            $originalName = $file->getClientOriginalName();
            if (preg_match('/[<>\"\'\/\\\\]/', $originalName)) {
                throw new \Exception('Invalid filename. Please use only alphanumeric characters, dots, and underscores.');
            }

            // SECURITY FIX: Check for double extensions
            $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
            $extension = strtolower($file->getClientOriginalExtension());
            if (strpos($nameWithoutExt, '.') !== false) {
                throw new \Exception('Double extensions are not allowed for security reasons.');
            }

            // SECURITY FIX: Sanitize filename
            $sanitizedFilename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $originalName);
            $path = $file->storeAs('questions', $sanitizedFilename, 'public');
            $imageUrl = 'storage/' . $path;

        } elseif ($request->filled('image_url')) {
            // SECURITY FIX: Validate URL format and prevent malicious URLs
            $url = $request->image_url;
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \Exception('Invalid image URL format');
            }

            // SECURITY FIX: Only allow HTTP/HTTPS URLs
            if (!preg_match('/^https?:\/\//', $url)) {
                throw new \Exception('Only HTTP and HTTPS URLs are allowed');
            }

            $imageUrl = $url;
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
            $topics = Topic::with(['course', 'questions'])->get();
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
            'question' => $request->question_text,
            'option_a' => $request->option_a ?? '',
            'option_b' => $request->option_b ?? '',
            'option_c' => $request->option_c ?? '',
            'option_d' => $request->option_d ?? '',
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
