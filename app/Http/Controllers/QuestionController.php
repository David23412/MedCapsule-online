<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Topic;

class QuestionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->is_admin) {
                return redirect()->route('home')->with('error', 'Unauthorized access.');
            }
            return $next($request);
        });
    }

    public function showAddQuestionForm()
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }
        $topics = Topic::all();
        return view('add_question', compact('topics'));
    }

    public function storeQuestion(Request $request)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }

        // Validate basic fields first
        $request->validate([
            'topic_id' => 'required|exists:topics,id',
            'question' => 'required|string',
            'explanation' => 'nullable|string',
            'question_type' => 'required|string|in:multiple_choice,written',
            'image_url' => 'nullable|url',
            'source' => 'required|string|in:Assuit,Cairo,Alexandria,Other'
        ]);

        // Validate based on question type
        if ($request->question_type === 'multiple_choice') {
            $request->validate([
                'option_a' => 'required|string',
                'option_b' => 'required|string',
                'option_c' => 'required|string',
                'option_d' => 'required|string',
                'correct_answer' => 'required|string|in:A,B,C,D',
            ]);
            
            $correctAnswer = $request->correct_answer;
            $alternativeAnswers = null;
            
        } else { // written question
            $request->validate([
                'written_correct_answer' => 'required|string',
                'alternative_answers' => 'nullable|string',
            ]);
            
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
            'image_url' => $request->image_url,
            'source' => $request->source
        ]);

        return redirect()->back()->with('success', 'Question added successfully!');
    }

    public function editQuestion($id)
    {
        $question = Question::findOrFail($id);
        $topics = Topic::all();
        return view('edit_question', compact('question', 'topics'));
    }

    public function updateQuestion(Request $request, $id)
    {
        $request->validate([
            'topic_id' => 'required|exists:topics,id',
            'question_text' => 'required|string',
            'options' => 'required|array|min:4',
            'correct_answer' => 'required|string|in:A,B,C,D',
        ]);

        $question = Question::findOrFail($id);
        $question->update([
            'topic_id' => $request->topic_id,
            'question_text' => $request->question_text,
            'options' => json_encode($request->options),
            'correct_answer' => $request->correct_answer,
        ]);

        return redirect()->back()->with('success', 'Question updated successfully!');
    }

    public function deleteQuestion($id)
    {
        Question::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Question deleted successfully!');
    }
}