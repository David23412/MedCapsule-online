<?php

namespace App\Services\Quiz;

use App\Models\Course;
use App\Models\Topic;
use App\Models\User;
use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * 🚀 PERFORMANCE BEAST: Quiz Execution Service
 * Handles: Quiz business logic and execution
 * Extracted from fat QuizExecutionController
 */
class QuizExecutionService
{
    /**
     * Prepare quiz data for starting
     */
    public function prepareQuizStart(Course $course, Topic $topic, User $user, array $params): array
    {
        // Validate topic belongs to course
        if ($topic->course_id !== $course->id) {
            throw new \Exception('Invalid topic for this course.');
        }

        // Get questions based on sources
        $questions = $this->getQuestionsForQuiz($topic, $params);
        
        if ($questions->isEmpty()) {
            throw new \Exception('No questions available for this topic.');
        }

        // Prepare quiz session data
        return [
            'course' => $course,
            'topic' => $topic,
            'questions' => $questions,
            'assignment_id' => $params['assignment_id'] ?? null,
            'sources' => $params['sources'] ?? [],
            'quiz_mode' => $params['mode'] ?? 'quiz'
        ];
    }

    /**
     * Get questions for quiz based on sources
     */
    public function getQuestionsForQuiz(Topic $topic, array $params): \Illuminate\Database\Eloquent\Collection
    {
        $query = Question::where('topic_id', $topic->id);
        
        // Filter by sources if provided
        if (!empty($params['sources'])) {
            $sources = is_array($params['sources']) ? $params['sources'] : [$params['sources']];
            $query->whereIn('source', $sources);
        }
        
        return $query->inRandomOrder()
            ->limit(20) // Default quiz size
            ->get();
    }

    /**
     * Process quiz grading
     */
    public function gradeQuiz(Request $request, Course $course, Topic $topic, User $user): array
    {
        $answers = $request->input('answers', []);
        $startTime = $request->input('start_time');
        $assignmentId = $request->input('assignment_id');
        
        if (empty($answers)) {
            throw new \Exception('No answers provided.');
        }

        $results = $this->calculateQuizResults($answers, $startTime);
        
        // Save separate quiz attempts for each source
        $quizAttempts = $this->saveSourceQuizAttempts($user, $topic, $results, $assignmentId);
        
        // Update user progress
        $this->updateUserProgress($user, $topic, $results);
        
        // Calculate comprehensive source progress from all attempts
        $sourceProgress = $this->calculateComprehensiveSourceProgress($user, $topic);
        
        return [
            'quiz_attempts' => $quizAttempts,
            'results' => $results,
            'grade_percentage' => $results['percentage'],
            'source_progress' => $sourceProgress
        ];
    }

    /**
     * Calculate quiz results
     */
    private function calculateQuizResults(array $answers, ?string $startTime): array
    {
        $totalQuestions = count($answers);
        $correctAnswers = 0;
        $questionResults = [];

        foreach ($answers as $questionId => $submittedAnswer) {
            $question = Question::find($questionId);
            if (!$question) {
                Log::warning('Question not found', ['question_id' => $questionId]);
                continue;
            }

            $isCorrect = $question->correct_answer === $submittedAnswer;
            if ($isCorrect) {
                $correctAnswers++;
            }

            $questionResults[$questionId] = [
                'question' => $question,
                'submitted_answer' => $submittedAnswer,
                'correct_answer' => $question->correct_answer,
                'is_correct' => $isCorrect
            ];
        }

        $percentage = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;
        $duration = $startTime ? (time() - strtotime($startTime)) : 0;

        return [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'percentage' => $percentage,
            'duration_seconds' => $duration,
            'question_results' => $questionResults
        ];
    }

    /**
     * Save separate quiz attempts for each source
     */
    private function saveSourceQuizAttempts(User $user, Topic $topic, array $results, ?int $assignmentId): array
    {
        $attempts = [];
        
        // Group questions by source
        $sourceGroups = [];
        foreach ($results['question_results'] as $questionId => $result) {
            $question = $result['question'];
            $source = $question->source ?? 'Unknown';
            
            if (!isset($sourceGroups[$source])) {
                $sourceGroups[$source] = [];
            }
            $sourceGroups[$source][$questionId] = $result;
        }
        
        // Create separate attempt for each source
        foreach ($sourceGroups as $source => $sourceQuestions) {
            $sourceCorrect = 0;
            $sourceTotal = count($sourceQuestions);
            $sourceQuestionData = [];
            
            foreach ($sourceQuestions as $questionId => $result) {
                if ($result['is_correct']) {
                    $sourceCorrect++;
                }
                
                $sourceQuestionData[] = [
                    'question_id' => $questionId,
                    'user_answer' => $result['submitted_answer'],
                    'is_correct' => $result['is_correct'],
                    'correct_answer' => $result['correct_answer'],
                    'source' => $source
                ];
            }
            
            // Ensure values are properly cast to integers
            $sourceCorrect = (int) $sourceCorrect;
            $sourceTotal = (int) $sourceTotal;
            $sourcePercentage = $sourceTotal > 0 ? round(($sourceCorrect / $sourceTotal) * 100, 2) : 0;
            
            try {
                $attempt = QuizAttempt::create([
                    'user_id' => $user->id,
                    'topic_id' => $topic->id,
                    'assignment_id' => $assignmentId,
                    'source' => $source, // Single source per attempt
                    'total_questions' => $sourceTotal,
                    'correct_answers' => $sourceCorrect,
                    'score' => $sourceCorrect,
                    'percentage_grade' => $sourcePercentage,
                    'duration_seconds' => (int) $results['duration_seconds'],
                    'question_data' => $sourceQuestionData,
                    'quiz_type' => 'normal',
                    'study_mode' => 'quiz'
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to create quiz attempt', [
                    'source' => $source,
                    'user_id' => $user->id,
                    'topic_id' => $topic->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
            
            $attempts[] = $attempt;
        }
        
        return $attempts;
    }

    /**
     * Update user progress after quiz
     */
    private function updateUserProgress(User $user, Topic $topic, array $results): void
    {
        // Update user's total stats
        $user->increment('total_questions_attempted', $results['total_questions']);
        $user->increment('correct_answers_count', $results['correct_answers']);
        
        // Calculate total topic progress from all sources
        $totalProgress = $this->calculateTotalTopicProgress($user, $topic);
        
        // Update topic progress with the total progress
        DB::table('user_topic_progress')->updateOrInsert(
            ['user_id' => $user->id, 'topic_id' => $topic->id],
            [
                'percentage_grade' => $totalProgress,
                'last_attempt_date' => now(),
                'updated_at' => now()
            ]
        );
        
        // Clear all relevant caches to ensure immediate frontend updates
        $this->clearTopicProgressCaches($user->id, $topic->id);
    }
    
    /**
     * Clear all caches related to topic progress
     */
    private function clearTopicProgressCaches(int $userId, int $topicId): void
    {
        try {
            // Clear topic progress cache
            $cacheKey = "topic_progress_{$userId}_{$topicId}_quiz";
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            
            // Clear user topic progress cache
            $userProgressCacheKey = "user_topic_progress_{$userId}_{$topicId}";
            \Illuminate\Support\Facades\Cache::forget($userProgressCacheKey);
            
            // Clear source progress cache
            $sourceProgressCacheKey = "topic_source_progress_{$topicId}_{$userId}_quiz";
            \Illuminate\Support\Facades\Cache::forget($sourceProgressCacheKey);
            
            // Clear course progress cache if it exists
            $courseProgressCacheKey = "course_progress_{$userId}";
            \Illuminate\Support\Facades\Cache::forget($courseProgressCacheKey);
            
            Log::info('Topic progress caches cleared', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'cache_keys_cleared' => [
                    $cacheKey,
                    $userProgressCacheKey,
                    $sourceProgressCacheKey,
                    $courseProgressCacheKey
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::warning('Failed to clear topic progress caches', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate total topic progress from all sources
     */
    private function calculateTotalTopicProgress(User $user, Topic $topic): float
    {
        // Get all available questions for this topic
        $totalQuestions = Question::where('topic_id', $topic->id)->count();
        
        if ($totalQuestions === 0) {
            return 0;
        }
        
        // Get all quiz attempts for this user and topic
        $attempts = QuizAttempt::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->where('study_mode', 'quiz')
            ->get();
        
        $totalCorrect = 0;
        $totalAttempted = 0;
        
        // Calculate total correct answers from all attempts
        foreach ($attempts as $attempt) {
            $totalCorrect += $attempt->correct_answers;
            $totalAttempted += $attempt->total_questions;
        }
        
        // Calculate percentage based on total questions available
        return $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 2) : 0;
    }

    /**
     * Calculate comprehensive source progress from all attempts
     */
    private function calculateComprehensiveSourceProgress(User $user, Topic $topic): array
    {
        // Get all available sources for this topic
        $availableSources = Question::where('topic_id', $topic->id)
            ->whereNotNull('source')
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();
        
        // Get all quiz attempts for this user and topic (quiz mode only)
        $attempts = QuizAttempt::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->where('study_mode', 'quiz')
            ->get();
        
        $sourceProgress = [];
        
        // Initialize progress for all sources
        foreach ($availableSources as $source => $totalAvailable) {
            $sourceProgress[$source] = [
                'completed' => 0,
                'total' => 0,
                'total_questions_available' => $totalAvailable,
                'has_attempts' => false,
                'percentage' => 0
            ];
        }
        
        // Calculate progress from all attempts
        foreach ($attempts as $attempt) {
            $source = $attempt->source;
            if (isset($sourceProgress[$source])) {
                $sourceProgress[$source]['completed'] += $attempt->correct_answers;
                $sourceProgress[$source]['total'] += $attempt->total_questions;
                $sourceProgress[$source]['has_attempts'] = true;
            }
        }
        
        // Calculate percentages
        foreach ($sourceProgress as $source => &$data) {
            $data['percentage'] = $data['total'] > 0 ? round(($data['completed'] / $data['total']) * 100, 2) : 0;
        }
        
        return $sourceProgress;
    }
}