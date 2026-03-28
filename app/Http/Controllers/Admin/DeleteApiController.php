<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Course, Topic, Question, User};

class DeleteApiController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get deletion preview data - LETHAL CACHED
     */
    public function getDeletionPreview(Request $request)
    {
        // CRITICAL SECURITY FIX: Validate input parameters
        $validated = $request->validate([
            'type' => 'required|string|in:user,course,topic,question',
            'id' => 'required|integer|min:1'
        ]);
        
        $type = $validated['type'];
        $id = $validated['id'];

        // LETHAL CACHING: Ultra-aggressive caching for deletion preview
        $cacheKey = "deletion_preview_{$type}_{$id}_" . now()->format('Y-m-d-H');
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($type, $id) {
            switch ($type) {
                case 'course':
                    $course = Course::select('id', 'name', 'description')
                        ->withCount(['topics', 'questions', 'users', 'payments'])
                        ->find($id);
                    return response()->json([
                        'name' => $course->name,
                        'stats' => [
                            'topics' => $course->topics_count,
                            'questions' => $course->questions_count,
                            'enrolled_users' => $course->users_count,
                            'payments' => $course->payments_count
                        ]
                    ]);

                case 'topic':
                    $topic = Topic::select('id', 'name', 'course_id')
                        ->withCount(['questions', 'quizAttempts'])
                        ->with('course:id,name')
                        ->find($id);
                    return response()->json([
                        'name' => $topic->name,
                        'course' => $topic->course->name,
                        'stats' => [
                            'questions' => $topic->questions_count,
                            'quiz_attempts' => $topic->quiz_attempts_count
                        ]
                    ]);

                case 'question':
                    $question = Question::select('id', 'question_text', 'topic_id')
                        ->withCount(['mistakes', 'quizAttempts'])
                        ->with(['topic.course:id,name'])
                        ->find($id);
                    return response()->json([
                    'text' => substr($question->question, 0, 100) . '...',
                    'topic' => $question->topic->name,
                    'course' => $question->topic->course->name,
                    'stats' => [
                        'mistakes' => $question->mistakes_count,
                        'quiz_attempts' => $question->quiz_attempts_count
                    ]
                ]);

            case 'user':
                $user = User::withCount(['quizAttempts', 'mistakes', 'payments'])->find($id);
                return response()->json([
                    'name' => $user->name,
                    'email' => $user->email,
                    'stats' => [
                        'quiz_attempts' => $user->quiz_attempts_count,
                        'mistakes' => $user->mistakes_count,
                        'payments' => $user->payments_count
                    ]
                ]);

            default:
                return response()->json(['error' => 'Invalid type'], 400);
        }
        });
    }
}
