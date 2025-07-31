<?php

namespace App\Services\Quiz;

use App\Models\Course;
use App\Models\Topic;
use App\Models\Assignment;
use App\Models\Question;
use App\Services\GradingService;
use App\Services\DatabaseService;
use App\Services\ResilientCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizExecutionService
{
    protected $gradingService;

    public function __construct(GradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Get assignment check with caching
     */
    public function getAssignmentCheck(int $userId, Topic $topic, Course $course): ?Assignment
    {
        $cacheKey = "assignment_check_{$userId}_{$topic->id}_{$course->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId, $topic, $course) {
            return Assignment::select('id', 'topic_id', 'status', 'deadline', 'duration_minutes')
                ->where('topic_id', $topic->id)
                ->where('course_id', $course->id)
                ->whereIn('status', [Assignment::STATUS_ACTIVE, Assignment::STATUS_COMPLETED])
                ->first();
        });
    }

    /**
     * Validate user access to course and topic
     */
    public function validateUserAccess(int $userId, Course $course, Topic $topic): bool
    {
        $cacheKey = "user_access_{$userId}_{$course->id}_{$topic->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId, $course) {
            $user = auth()->user();
            return $user->isAdmin() || $user->hasAccessToCourse($course->id) || (!$course->price || $course->price <= 0);
        });
    }

    /**
     * Get questions with smart validation and caching
     */
    public function getQuestionsWithSmartValidation(Topic $topic, array $sources): \Illuminate\Database\Eloquent\Collection
    {
        $questionCount = Question::where('topic_id', $topic->id)
            ->when(!in_array('all', $sources), function ($query) use ($sources) {
                $query->whereIn('source', $sources);
            })
            ->count();
            
        $cacheKey = "solve_quiz_{$topic->id}_" . md5(serialize($sources)) . "_count_{$questionCount}";
        
        $questions = Cache::get($cacheKey);
        
        if ($questions === null) {
            $questions = $this->getQuestionsFromDatabase($topic, $sources);
            Cache::put($cacheKey, $questions, 1800);
        } else {
            $currentCount = Question::where('topic_id', $topic->id)
                ->when(!in_array('all', $sources), function ($query) use ($sources) {
                    $query->whereIn('source', $sources);
                })
                ->count();
                
            if ($currentCount !== $questionCount) {
                Cache::forget($cacheKey);
                $questions = $this->getQuestionsFromDatabase($topic, $sources);
                Cache::put($cacheKey, $questions, 1800);
                
                Log::info('Quiz questions cache refreshed due to deletion detection', [
                    'topic_id' => $topic->id,
                    'old_count' => $questionCount,
                    'new_count' => $currentCount
                ]);
            }
        }
        
        return $questions;
    }

    /**
     * Get questions from database with validation
     */
    private function getQuestionsFromDatabase(Topic $topic, array $sources): \Illuminate\Database\Eloquent\Collection
    {
        $questionsQuery = Question::select([
            'id', 'topic_id', 'question', 'question_type', 
            'option_a', 'option_b', 'option_c', 'option_d',
            'correct_answer', 'explanation', 'image_url', 'source',
            'alternative_answers', 'similarity_threshold'
        ])
        ->where('topic_id', $topic->id)
        ->whereNotNull('question')
        ->where('question', '!=', '')
        ->with(['topic:id,name,course_id']);
        
        if (!in_array('all', $sources)) {
            $questionsQuery->whereIn('source', $sources);
        }
        
        $questions = $questionsQuery->get();
        
        $questionIds = $questions->pluck('id')->toArray();
        $existingIds = Question::whereIn('id', $questionIds)
            ->whereNotNull('question')
            ->where('question', '!=', '')
            ->pluck('id')
            ->toArray();
            
        return $questions->filter(function ($question) use ($existingIds) {
            return in_array($question->id, $existingIds);
        });
    }

    /**
     * Process quiz grading
     */
    public function processQuizGrading(array $validated, Topic $topic, Course $course, int $userId, ?int $assignmentId = null): array
    {
        return DatabaseService::safeTransaction(function () use ($validated, $topic, $course, $userId, $assignmentId) {
            $result = $this->gradingService->gradeQuiz(
                auth()->user(),
                $validated['answers'],
                $validated['duration_seconds'] ?? 0,
                $validated['mode'] ?? 'quiz',
                $topic,
                $course,
                $assignmentId
            );
            
            if ($assignmentId && $result['success']) {
                $this->handleAssignmentCompletion($userId, $topic->id, $result['data']['percentageGrade'] ?? 0, $assignmentId);
            }
            
            return $result;
        }, [
            'user_id' => $userId,
            'topic_id' => $topic->id,
            'action' => 'quiz_grading',
            'is_assignment' => $assignmentId !== null
        ]);
    }

    /**
     * Handle assignment completion
     */
    public function handleAssignmentCompletion(int $userId, int $topicId, float $percentageGrade, ?int $assignmentId = null): void
    {
        if (!$assignmentId) {
            return;
        }

        $assignment = Assignment::find($assignmentId);
        if (!$assignment) {
            return;
        }
        
        $existingAttempt = DB::table('quiz_attempts')
            ->where('assignment_id', $assignmentId)
            ->where('user_id', $userId)
            ->where('percentage_grade', '>=', 50)
            ->exists();
            
        $updated = $existingAttempt ? 0 : 1;

        if ($updated > 0) {
            try {
                event(new \App\Events\AssignmentStatusChanged([
                    'id' => $assignment->id,
                    'user_id' => $userId,
                    'topic_id' => $assignment->topic_id,
                    'status' => 'completed_by_user',
                    'previous_status' => 'active',
                    'score_percentage' => $percentageGrade,
                    'completed_at' => now()->toISOString()
                ]));
            } catch (\Exception $e) {
                Log::error('Failed to dispatch assignment completion event', [
                    'assignment_id' => $assignmentId,
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->clearAssignmentCaches($userId, $assignmentId, $assignment);
    }

    /**
     * Clear assignment-related caches
     */
    private function clearAssignmentCaches(int $userId, int $assignmentId, Assignment $assignment): void
    {
        $cacheKeys = [
            "assignments_index_{$userId}_*",
            "assignment_{$assignmentId}",
            "assignment_counters_{$userId}",
            "bulk_create_data_{$userId}",
            "user_progress_{$userId}_{$assignment->topic_id}",
            "quiz_cache_{$userId}_{$assignment->topic_id}"
        ];
        
        foreach ($cacheKeys as $key) {
            if (str_contains($key, '*')) {
                // Handle wildcard cache clearing
                $pattern = str_replace('*', '', $key);
                $allCacheKeys = Cache::get('assignment_cache_keys_' . $userId, []);
                foreach ($allCacheKeys as $cacheKey) {
                    if (str_starts_with($cacheKey, $pattern)) {
                        Cache::forget($cacheKey);
                    }
                }
            } else {
                Cache::forget($key);
            }
        }
        
        if ($assignment->course_id) {
            Cache::forget("assignments_{$assignment->course_id}_" . now()->format('Y-m-d-H'));
            Cache::forget("assignments_{$assignment->course_id}_" . now()->subHour()->format('Y-m-d-H'));
        }
    }

    /**
     * Get user's total quiz count
     */
    public function getUserQuizCount(int $userId): int
    {
        $cacheKey = "user_quiz_count_{$userId}";
        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            return DB::table('quiz_attempts')
                ->where('user_id', $userId)
                ->count();
        });
    }
}