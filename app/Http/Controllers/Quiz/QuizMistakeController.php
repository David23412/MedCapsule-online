<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Mistake;
use App\Models\Course;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 💀 NUCLEAR OPTIMIZED QUIZ MISTAKE CONTROLLER 💀
 * Handles mistake display with F-16 RAPTOR SPEED
 */
class QuizMistakeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Show mistakes with nuclear optimization - LETHAL ERROR HANDLING
     */
    public function showMistakes(Request $request)
    {
        $startTime = microtime(true);
        $requestId = uniqid('mistakes_');
        
        try {
            $userId = auth()->id();
            $courseFilter = $request->input('course');
            
            Log::info('Loading mistakes for user', [
                'request_id' => $requestId,
                'user_id' => $userId, 
                'course_filter' => $courseFilter
            ]);

            // Optimized single query with aggressive caching
            $cacheKey = "mistakes_{$userId}_{$courseFilter}";
            $mistakes = Cache::remember($cacheKey, 300, function() use ($userId, $courseFilter) {
                return Mistake::select([
                    'mistakes.id',
                    'mistakes.question_id',
                    'mistakes.submitted_answer',
                    'mistakes.last_attempt_date',
                    'mistakes.correct_streak',
                    'mistakes.skipped'
                ])
                ->with([
                    'question' => function($query) {
                        $query->select([
                            'id', 'question', 'topic_id', 'correct_answer', 
                            'image_url', 'explanation', 'question_type',
                            'option_a', 'option_b', 'option_c', 'option_d'
                        ]);
                    },
                    'question.topic' => function($query) {
                        $query->select(['id', 'name', 'course_id']);
                    },
                    'question.topic.course' => function($query) {
                        $query->select(['id', 'name']);
                    }
                ])
                ->where('mistakes.user_id', $userId)
                ->when($courseFilter, function($query) use ($courseFilter) {
                    if ($courseFilter === 'skipped') {
                        return $query->where('mistakes.skipped', true);
                    } else {
                        return $query->whereHas('question.topic.course', function($q) use ($courseFilter) {
                            $q->where('id', $courseFilter);
                        });
                    }
                })
                ->orderByDesc('mistakes.last_attempt_date')
                ->get();
            });

            // Get total mistakes count with caching
            $totalMistakesCount = Cache::remember("total_mistakes_{$userId}", 300, function() use ($userId) {
                return Mistake::where('user_id', $userId)->count();
            });

            // Efficient course availability check with caching
            $availableCourses = Cache::remember("available_courses_{$userId}", 300, function() use ($userId) {
                return Course::select(['id', 'name'])
                    ->whereIn('id', function($query) use ($userId) {
                        $query->select('topics.course_id')
                              ->from('topics')
                              ->join('questions', 'topics.id', '=', 'questions.topic_id')
                              ->join('mistakes', 'questions.id', '=', 'mistakes.question_id')
                              ->where('mistakes.user_id', $userId)
                              ->distinct();
                    })
                    ->orderBy('name')
                    ->get();
            });

            // Smart navigation - get recent course with caching
            $recentCourse = Cache::remember("recent_course_{$userId}", 300, function() use ($userId, $mistakes) {
                $recentQuizAttempt = QuizAttempt::select('id', 'user_id', 'topic_id', 'created_at')
                    ->where('user_id', $userId)
                    ->whereNotNull('topic_id')
                    ->latest('created_at')
                    ->with('topic.course:id,name')
                    ->first();
                
                if ($recentQuizAttempt && $recentQuizAttempt->topic && $recentQuizAttempt->topic->course) {
                    return $recentQuizAttempt->topic->course;
                } elseif ($mistakes->isNotEmpty()) {
                    return $mistakes->first()->question->topic->course;
                }
                return null;
            });

            // Group mistakes by course
            $mistakesByCourse = $mistakes->groupBy('question.topic.course.name');

            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::info('✅ Mistakes loaded successfully', [
                'request_id' => $requestId,
                'execution_time_ms' => round($executionTime, 2),
                'mistakes_count' => $mistakes->count()
            ]);

            return view('mistakes', [
                'mistakes' => $mistakes,
                'mistakesByCourse' => $mistakesByCourse,
                'availableCourses' => $availableCourses,
                'selectedCourse' => $courseFilter,
                'recentCourse' => $recentCourse,
                'totalMistakesCount' => $totalMistakesCount,
                'performance_metrics' => [
                    'execution_time_ms' => round($executionTime, 2),
                    'mistakes_count' => $mistakes->count(),
                    'courses_count' => $availableCourses->count()
                ]
            ]);

        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            Log::error('❌ Error in showMistakes:', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'execution_time_ms' => round($executionTime, 2)
            ]);

            return view('mistakes', [
                'mistakes' => collect([]),
                'mistakesByCourse' => collect([]),
                'availableCourses' => collect([]),
                'selectedCourse' => null,
                'recentCourse' => null,
                'totalMistakesCount' => 0,
                'error' => 'An error occurred while loading your mistakes. Please try again.'
            ]);
        }
    }
}