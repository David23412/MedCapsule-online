<?php

namespace App\Http\Controllers\Topic;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * LETHAL OPTIMIZED TOPIC VIEW CONTROLLER
 * Handles topic display and progress operations
 */
class TopicViewController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Show topics for a specific course with ultra-fast performance
     */
    public function forCourse(Course $course)
    {
        $user = auth()->user();
        
        // Ultra-aggressive caching for topics
        $cacheKey = "course_topics_{$course->id}_{$user->id}_" . now()->format('Y-m-d-H');
        
        $data = Cache::remember($cacheKey, 3600, function () use ($course, $user) {
            // Check if user has access to the course
            $hasAccess = $user->isAdmin() || 
                        $user->hasAccessToCourse($course->id) || 
                        (!$course->price || $course->price <= 0);

            if (!$hasAccess) {
                return ['error' => 'Access denied to this course'];
            }

            // Load topics with comprehensive data
            $topics = $course->topics()
                ->select('id', 'name', 'course_id', 'display_order', 'is_active')
                ->with([
                    'questions:id,topic_id,difficulty',
                    'userProgress' => function($query) use ($user) {
                        $query->where('user_id', $user->id)
                              ->select('id', 'user_id', 'topic_id', 'progress_percentage', 'last_accessed');
                    }
                ])
                ->orderBy('display_order')
                ->get();

            return [
                'course' => $course,
                'topics' => $topics,
                'user_progress' => $this->calculateUserProgress($topics, $user)
            ];
        });

        if (isset($data['error'])) {
            return redirect()->route('courses')->with('error', $data['error']);
        }

        return view('topics', $data);
    }

    /**
     * Get source progress for a topic
     */
    public function getSourceProgress(Topic $topic)
    {
        try {
            $userId = auth()->id();
            $mode = request()->input('mode', 'quiz');
            
            // Ultra-aggressive caching for source progress
            $cacheKey = "source_progress_{$topic->id}_{$userId}_{$mode}_" . now()->format('Y-m-d-H');
            
            $progressData = Cache::remember($cacheKey, 1800, function () use ($topic, $userId, $mode) {
                return $this->calculateSourceProgress($topic, $userId, $mode);
            });

            return response()->json([
                'success' => true,
                'availableSources' => $progressData['availableSources'],
                'progress' => $progressData['progress'],
                'topic_id' => $topic->id,
                'topic_name' => $progressData['topic_name'],
                'mode' => $mode
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in getSourceProgress:', [
                'error' => $e->getMessage(),
                'topic_id' => $topic->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get source progress'
            ], 500);
        }
    }

    /**
     * Calculate user progress across topics
     */
    private function calculateUserProgress($topics, $user): array
    {
        $totalTopics = $topics->count();
        $completedTopics = 0;
        $totalQuestions = 0;
        $answeredQuestions = 0;

        // Eager load all relationships at once to prevent N+1 queries
        $topics->load(['questions:id,topic_id', 'userProgress:id,topic_id,user_id,progress_percentage']);
        
        foreach ($topics as $topic) {
            // LETHAL OPTIMIZATION: Cache question count to avoid repeated calls
            $questionCount = $topic->questions->count();
            $totalQuestions += $questionCount;
            
            if ($topic->userProgress->isNotEmpty()) {
                $progress = $topic->userProgress->first();
                if ($progress->progress_percentage >= 80) {
                    $completedTopics++;
                }
                // Use cached count instead of calling count() again
                $answeredQuestions += ($progress->progress_percentage / 100) * $questionCount;
            }
        }

        return [
            'total_topics' => $totalTopics,
            'completed_topics' => $completedTopics,
            'completion_percentage' => $totalTopics > 0 ? round(($completedTopics / $totalTopics) * 100, 1) : 0,
            'total_questions' => $totalQuestions,
            'answered_questions' => round($answeredQuestions),
            'questions_percentage' => $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100, 1) : 0
        ];
    }

    /**
     * Calculate source progress for a topic
     */
    private function calculateSourceProgress($topic, $userId, $mode): array
    {
        // Get all available sources for this topic
        $availableSources = \App\Models\Question::where('topic_id', $topic->id)
            ->whereNotNull('source')
            ->distinct('source')
            ->pluck('source')
            ->filter()
            ->values()
            ->toArray();

        // LETHAL FIX: Batch load all data to eliminate N+1 queries
        $questionCounts = \App\Models\Question::where('topic_id', $topic->id)
            ->whereIn('source', $availableSources)
            ->groupBy('source')
            ->selectRaw('source, COUNT(*) as count')
            ->pluck('count', 'source')
            ->toArray();
            
        $latestAttempts = \App\Models\QuizAttempt::where([
            'user_id' => $userId,
            'topic_id' => $topic->id,
            'study_mode' => $mode
        ])
        ->whereIn('source', $availableSources)
        ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('source')
        ->map(function($attempts) {
            return $attempts->first(); // Get latest attempt per source
        });

        $progressData = [];
        
        foreach ($availableSources as $source) {
            $totalQuestionsAvailable = $questionCounts[$source] ?? 0;
            $latestAttempt = $latestAttempts[$source] ?? null;

            if ($latestAttempt) {
                // Use correct_answers or fall back to score (they should be the same)
                $correctAnswers = $latestAttempt->correct_answers ?? $latestAttempt->score;
                
                Log::info('🔥 SOURCE PROGRESS: Latest attempt found', [
                    'source' => $source,
                    'attempt_id' => $latestAttempt->id,
                    'correct_answers' => $latestAttempt->correct_answers,
                    'score' => $latestAttempt->score,
                    'used_correct_answers' => $correctAnswers,
                    'percentage_grade' => $latestAttempt->percentage_grade,
                    'mode' => $mode
                ]);
                
                $progressData[$source] = [
                    'completed' => $correctAnswers,
                    'total_questions_available' => $totalQuestionsAvailable,
                    'percentage' => $latestAttempt->percentage_grade,
                    'last_attempt' => $latestAttempt->created_at->toISOString()
                ];
            } else {
                Log::info('🔥 SOURCE PROGRESS: No attempt found', [
                    'source' => $source,
                    'mode' => $mode,
                    'user_id' => $userId
                ]);
                
                $progressData[$source] = [
                    'completed' => 0,
                    'total_questions_available' => $totalQuestionsAvailable,
                    'percentage' => 0,
                    'last_attempt' => null
                ];
            }
        }

        return [
            'availableSources' => $availableSources,
            'progress' => $progressData,
            'topic_name' => $topic->name,
            'mode' => $mode
        ];
    }
}