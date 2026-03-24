<?php

namespace App\Services\Core;

use App\Models\User;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Events\QuizGraded;
use App\Events\QuizStarted;
use App\Events\QuizCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Event-Driven Grading Service
 * 
 * This service focuses ONLY on grading logic.
 * All side effects (notifications, cache clearing, stats updates) 
 * are handled by event listeners to prevent coupling.
 */
class EventDrivenGradingService
{
    /**
     * Grade a quiz with minimal transaction scope
     * 
     * @param User $user
     * @param array $answers
     * @param int $duration
     * @param string $mode
     * @param Topic $topic
     * @param Course $course
     * @param int|null $assignmentId
     * @return array
     */
    public function gradeQuiz(
        User $user, 
        array $answers, 
        int $duration, 
        string $mode, 
        Topic $topic, 
        Course $course, 
        ?int $assignmentId = null
    ): array {
        
        // Fire quiz started event
        event(new QuizStarted($user, $topic, $mode, count($answers)));
        
        try {
            // MICRO-TRANSACTION: Only essential grading logic
            $gradingResult = DB::transaction(function() use ($user, $answers, $topic, $mode) {
                return $this->performCoreGrading($user, $answers, $topic, $mode);
            });
            
            // MICRO-TRANSACTION: Store quiz attempt
            $quizAttempt = DB::transaction(function() use ($user, $gradingResult, $duration, $mode, $topic, $assignmentId) {
                return $this->storeQuizAttempt($user, $gradingResult, $duration, $mode, $topic, $assignmentId);
            });
            
            // Fire quiz completed event (async side effects)
            event(new QuizCompleted($user, $topic, $quizAttempt, $gradingResult));
            
            return $this->formatResponse($gradingResult, $quizAttempt);
            
        } catch (\Exception $e) {
            Log::error('Quiz grading failed', [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Core grading logic - minimal and fast
     * 
     * @param User $user
     * @param array $answers
     * @param Topic $topic
     * @param string $mode
     * @return array
     */
    private function performCoreGrading(User $user, array $answers, Topic $topic, string $mode): array
    {
        // Get questions efficiently
        $questions = $this->getQuestionsEfficiently(array_keys($answers));
        
        $score = 0;
        $totalQuestions = $questions->count();
        $results = [];
        
        // Process answers with minimal memory footprint
        foreach ($questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;
            $isCorrect = $this->evaluateAnswer($question, $submittedAnswer);
            
            if ($isCorrect) {
                $score++;
            }
            
            $results[] = [
                'question_id' => $question->id,
                'submitted_answer' => $submittedAnswer,
                'is_correct' => $isCorrect,
                'source' => $question->source ?? 'General'
            ];
        }
        
        $percentageGrade = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;
        
        return [
            'score' => $score,
            'total_questions' => $totalQuestions,
            'percentage_grade' => $percentageGrade,
            'results' => $results,
            'questions' => $questions
        ];
    }
    
    /**
     * Get questions with optimized query
     * 
     * @param array $questionIds
     * @return Collection
     */
    private function getQuestionsEfficiently(array $questionIds): Collection
    {
        return Question::select([
                'id', 'question_type', 'correct_answer', 
                'option_a', 'option_b', 'option_c', 'option_d',
                'topic_id', 'source', 'alternative_answers'
            ])
            ->whereIn('id', $questionIds)
            ->get()
            ->keyBy('id');
    }
    
    /**
     * Evaluate a single answer
     * 
     * @param Question $question
     * @param string|null $submittedAnswer
     * @return bool
     */
    private function evaluateAnswer(Question $question, ?string $submittedAnswer): bool
    {
        if ($submittedAnswer === null || $submittedAnswer === '') {
            return false;
        }
        
        if ($question->question_type === 'written') {
            return $this->evaluateWrittenAnswer($question, $submittedAnswer);
        }
        
        return $question->isAnswerCorrect($submittedAnswer);
    }
    
    /**
     * Evaluate written answer with fallback
     * 
     * @param Question $question
     * @param string $submittedAnswer
     * @return bool
     */
    private function evaluateWrittenAnswer(Question $question, string $submittedAnswer): bool
    {
        try {
            // Try AI evaluation if available
            if (app()->bound('App\Services\WrittenAnswerEvaluationService')) {
                $evaluationService = app('App\Services\WrittenAnswerEvaluationService');
                $evaluation = $evaluationService->evaluateAnswer(
                    $submittedAnswer,
                    $question->correct_answer,
                    $question->alternative_answers ?? [],
                    0.8
                );
                return $evaluation['isCorrect'] ?? false;
            }
        } catch (\Exception $e) {
            Log::warning('AI evaluation failed, using fallback', [
                'question_id' => $question->id,
                'error' => $e->getMessage()
            ]);
        }
        
        // Fallback to exact match
        return strtolower(trim($submittedAnswer)) === strtolower(trim($question->correct_answer));
    }
    
    /**
     * Store quiz attempt with minimal data
     * 
     * @param User $user
     * @param array $gradingResult
     * @param int $duration
     * @param string $mode
     * @param Topic $topic
     * @param int|null $assignmentId
     * @return QuizAttempt
     */
    private function storeQuizAttempt(
        User $user, 
        array $gradingResult, 
        int $duration, 
        string $mode, 
        Topic $topic, 
        ?int $assignmentId
    ): QuizAttempt {
        
        return QuizAttempt::create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'assignment_id' => $assignmentId,
            'quiz_type' => $assignmentId ? 'assignment' : 'normal',
            'study_mode' => $mode,
            'source' => null, // Will be processed by event listeners
            'score' => $gradingResult['score'],
            'total_questions' => $gradingResult['total_questions'],
            'percentage_grade' => $gradingResult['percentage_grade'],
            'duration_seconds' => $duration,
            'question_data' => $gradingResult['results'],
            'correct_answers' => $gradingResult['score']
        ]);
    }
    
    /**
     * Format response for API
     * 
     * @param array $gradingResult
     * @param QuizAttempt $quizAttempt
     * @return array
     */
    private function formatResponse(array $gradingResult, QuizAttempt $quizAttempt): array
    {
        return [
            'success' => true,
            'quiz_attempt_id' => $quizAttempt->id,
            'score' => $gradingResult['score'],
            'total_questions' => $gradingResult['total_questions'],
            'percentage_grade' => $gradingResult['percentage_grade'],
            'encouragement' => $this->getEncouragementMessage($gradingResult['percentage_grade']),
            'timestamp' => now()->toISOString()
        ];
    }
    
    /**
     * Get encouragement message based on score
     * 
     * @param float $percentage
     * @return string
     */
    private function getEncouragementMessage(float $percentage): string
    {
        if ($percentage >= 90) {
            return "Excellent work! You're mastering this topic!";
        } elseif ($percentage >= 80) {
            return "Great job! You're doing very well!";
        } elseif ($percentage >= 70) {
            return "Good work! Keep practicing to improve further!";
        } elseif ($percentage >= 60) {
            return "You're making progress! Review the topics you missed.";
        } else {
            return "Keep studying! Focus on understanding the key concepts.";
        }
    }
}