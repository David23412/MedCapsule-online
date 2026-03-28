<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationTriggerService;

class AssignmentCompletionService
{
    /**
     * Complete an assignment with score
     */
    public function completeAssignment(Assignment $assignment, array $answers = [], int $score = null): bool
    {
        if (!$assignment->canBeCompleted()) {
            return false;
        }

        // Calculate score if not provided but answers are
        if ($score === null && !empty($answers)) {
            $scoreData = $this->calculateAssignmentScore($assignment, $answers);
            $score = $scoreData['score'];
            $scorePercentage = $scoreData['score_percentage'];
        } else {
            $scorePercentage = null;
        }

        DB::beginTransaction();
        
        try {
            // Update with score percentage if available
            if ($scorePercentage !== null) {
                // Since status column was removed, completion is tracked in quiz_attempts
                $result = true;
            } else {
                $result = $assignment->markAsCompleted($score);
            }
            
            if ($result) {
                try {
                    $assignmentData = [
                        'assignment_id' => $assignment->id,
                        'assignment_title' => $assignment->title,
                        'score' => $scorePercentage ?? $assignment->score,
                        'total_questions' => $assignment->total_questions ?? 1,
                        'completed_at' => $assignment->completed_at?->toISOString()
                    ];
                    
                    NotificationTriggerService::onAssignmentCompleted($assignment->user, $assignmentData);
                } catch (\Exception $e) {
                    \Log::error('Failed to send assignment completion notification', [
                        'assignment_id' => $assignment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    
    /**
     * Calculate assignment score based on answers and assignment mode
     */
    public function calculateAssignmentScore(Assignment $assignment, array $answers): array
    {
        if ($assignment->isSourceSpecific()) {
            return $this->calculateSourceSpecificScore($assignment, $answers);
        }
        
        return $this->calculateCompleteTopicScore($assignment, $answers);
    }
    
    /**
     * Calculate score for source-specific assignment
     */
    private function calculateSourceSpecificScore(Assignment $assignment, array $answers): array
    {
        $topic = $assignment->topic;
        $source = $assignment->assigned_source;
        
        $questions = Question::query()
            ->where('topic_id', $topic->id)
            ->where('source', $source)
            ->get();
            
        $totalQuestions = $questions->count();
        $correctAnswers = 0;
        
        foreach ($questions as $question) {
            if (isset($answers[$question->id]) && $question->isAnswerCorrect((string) $answers[$question->id])) {
                $correctAnswers++;
            }
        }
        
        $scorePercentage = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        $score = round($scorePercentage); // Integer score
        
        return [
            'score' => $score,
            'score_percentage' => $scorePercentage,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'source' => $source
        ];
    }
    
    /**
     * Calculate score for complete-topic assignment
     */
    private function calculateCompleteTopicScore(Assignment $assignment, array $answers): array
    {
        $topic = $assignment->topic;
        
        $questions = Question::query()
            ->where('topic_id', $topic->id)
            ->get();
            
        $totalQuestions = $questions->count();
        $correctAnswers = 0;
        
        foreach ($questions as $question) {
            if (isset($answers[$question->id]) && $question->isAnswerCorrect((string) $answers[$question->id])) {
                $correctAnswers++;
            }
        }
        
        $scorePercentage = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        $score = round($scorePercentage); // Integer score
        
        return [
            'score' => $score,
            'score_percentage' => $scorePercentage,
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'source' => 'all'
        ];
    }

    /**
     * Auto-submit assignment when time limit expires
     */
    public function autoSubmitAssignment(Assignment $assignment, array $answers = []): bool
    {
        if (!$assignment->isActive()) {
            return false;
        }
        
        DB::transaction(function() use ($assignment, $answers) {
            $scoreData = $this->calculateAssignmentScore($assignment, $answers);
            
            DB::table('assignment_attempts')->insert([
                'assignment_id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'source' => $assignment->isSourceSpecific() ? $assignment->assigned_source : 'all',
                'questions_answered' => count($answers),
                'correct_answers' => $scoreData['correct_answers'],
                'score_percentage' => $scoreData['score_percentage'],
                'time_spent_seconds' => $assignment->duration_minutes * 60,
                'started_at' => now()->subMinutes($assignment->duration_minutes),
                'completed_at' => now(),
                'auto_submitted' => true,
                'answers' => json_encode($answers),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            try {
                $assignmentData = [
                    'assignment_id' => $assignment->id,
                    'assignment_title' => $assignment->title,
                    'score' => $scoreData['score_percentage'],
                    'total_questions' => $scoreData['total_questions'],
                    'auto_submitted' => true
                ];
                NotificationTriggerService::onAssignmentCompleted($assignment->user, $assignmentData);
            } catch (\Exception $e) {
                \Log::error('Failed to send assignment auto-submit notification', [
                    'assignment_id' => $assignment->id,
                    'error' => $e->getMessage()
                ]);
            }
        });
        
        return true;
    }
}
