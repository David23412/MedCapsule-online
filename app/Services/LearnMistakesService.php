<?php

namespace App\Services;

use App\Models\SkippedQuestion;
use App\Models\Question;
use App\Models\User;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LearnMistakesService
{
    /**
     * Skip a question during learn mistakes session
     */
    public function skipQuestion(int $userId, int $questionId, string $sessionType = SkippedQuestion::SESSION_LEARN_MISTAKES): bool
    {
        try {
            return SkippedQuestion::markAsSkipped($userId, $questionId, $sessionType);
        } catch (\Exception $e) {
            Log::error('Failed to skip question in learn mistakes', [
                'user_id' => $userId,
                'question_id' => $questionId,
                'session_type' => $sessionType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove skip from a question
     */
    public function removeSkip(int $userId, int $questionId, string $sessionType = SkippedQuestion::SESSION_LEARN_MISTAKES): bool
    {
        try {
            return SkippedQuestion::removeSkip($userId, $questionId, $sessionType);
        } catch (\Exception $e) {
            Log::error('Failed to remove skip from question', [
                'user_id' => $userId,
                'question_id' => $questionId,
                'session_type' => $sessionType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get skipped questions for a user
     */
    public function getSkippedQuestions(int $userId, string $sessionType = null): Collection
    {
        return SkippedQuestion::getSkippedQuestions($userId, $sessionType);
    }

    /**
     * Get skipped questions for a specific topic
     */
    public function getSkippedQuestionsForTopic(int $userId, int $topicId, string $sessionType = null): Collection
    {
        return SkippedQuestion::getSkippedQuestionsForTopic($userId, $topicId, $sessionType);
    }

    /**
     * Get questions for learn mistakes session, excluding skipped ones
     */
    public function getQuestionsForSession(int $userId, int $topicId, bool $excludeSkipped = true): Collection
    {
        $query = Question::where('topic_id', $topicId)
                        ->where('deleted', false);

        if ($excludeSkipped) {
            // Exclude questions that have been skipped in learn mistakes sessions
            $skippedQuestionIds = SkippedQuestion::where('user_id', $userId)
                                                ->where('session_type', SkippedQuestion::SESSION_LEARN_MISTAKES)
                                                ->whereHas('question', function($q) use ($topicId) {
                                                    $q->where('topic_id', $topicId);
                                                })
                                                ->pluck('question_id')
                                                ->toArray();

            if (!empty($skippedQuestionIds)) {
                $query->whereNotIn('id', $skippedQuestionIds);
            }
        }

        return $query->orderBy('id')->get();
    }

    /**
     * Get all questions for a topic (including skipped ones for review)
     */
    public function getAllQuestionsForTopic(int $userId, int $topicId): array
    {
        $allQuestions = Question::where('topic_id', $topicId)
                              ->where('deleted', false)
                              ->orderBy('id')
                              ->get();

        $skippedQuestionIds = SkippedQuestion::where('user_id', $userId)
                                            ->where('session_type', SkippedQuestion::SESSION_LEARN_MISTAKES)
                                            ->whereHas('question', function($q) use ($topicId) {
                                                $q->where('topic_id', $topicId);
                                            })
                                            ->pluck('question_id')
                                            ->toArray();

        return [
            'all_questions' => $allQuestions,
            'skipped_question_ids' => $skippedQuestionIds,
            'available_questions' => $allQuestions->whereNotIn('id', $skippedQuestionIds),
            'skipped_questions' => $allQuestions->whereIn('id', $skippedQuestionIds)
        ];
    }

    /**
     * Check if a question is skipped
     */
    public function isQuestionSkipped(int $userId, int $questionId, string $sessionType = SkippedQuestion::SESSION_LEARN_MISTAKES): bool
    {
        return SkippedQuestion::isSkipped($userId, $questionId, $sessionType);
    }

    /**
     * Clear all skips for a user in a specific session type
     */
    public function clearAllSkips(int $userId, string $sessionType = SkippedQuestion::SESSION_LEARN_MISTAKES): int
    {
        try {
            return SkippedQuestion::clearAllSkips($userId, $sessionType);
        } catch (\Exception $e) {
            Log::error('Failed to clear all skips for user', [
                'user_id' => $userId,
                'session_type' => $sessionType,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Clear skips for a specific topic
     */
    public function clearTopicSkips(int $userId, int $topicId, string $sessionType = SkippedQuestion::SESSION_LEARN_MISTAKES): int
    {
        try {
            return SkippedQuestion::clearTopicSkips($userId, $topicId, $sessionType);
        } catch (\Exception $e) {
            Log::error('Failed to clear topic skips', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'session_type' => $sessionType,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get skip statistics for a user
     */
    public function getSkipStatistics(int $userId): array
    {
        return SkippedQuestion::getSkipStatistics($userId);
    }

    /**
     * Get questions with skip status for mistakes review
     */
    public function getQuestionsWithSkipStatus(int $userId, int $topicId): Collection
    {
        $questions = Question::where('topic_id', $topicId)
                           ->where('deleted', false)
                           ->orderBy('id')
                           ->get();

        $skippedQuestionIds = SkippedQuestion::where('user_id', $userId)
                                            ->where('session_type', SkippedQuestion::SESSION_LEARN_MISTAKES)
                                            ->whereHas('question', function($q) use ($topicId) {
                                                $q->where('topic_id', $topicId);
                                            })
                                            ->pluck('question_id')
                                            ->toArray();

        // Add skip status to each question
        $questions->each(function($question) use ($skippedQuestionIds) {
            $question->is_skipped = in_array($question->id, $skippedQuestionIds);
        });

        return $questions;
    }

    /**
     * Get session progress including skip information
     */
    public function getSessionProgress(int $userId, int $topicId): array
    {
        $totalQuestions = Question::where('topic_id', $topicId)
                                ->where('deleted', false)
                                ->count();

        $skippedCount = SkippedQuestion::where('user_id', $userId)
                                      ->where('session_type', SkippedQuestion::SESSION_LEARN_MISTAKES)
                                      ->whereHas('question', function($q) use ($topicId) {
                                          $q->where('topic_id', $topicId);
                                      })
                                      ->count();

        $availableQuestions = $totalQuestions - $skippedCount;

        return [
            'total_questions' => $totalQuestions,
            'skipped_questions' => $skippedCount,
            'available_questions' => $availableQuestions,
            'skip_percentage' => $totalQuestions > 0 ? round(($skippedCount / $totalQuestions) * 100, 2) : 0
        ];
    }

    /**
     * Reset learn mistakes session (clear all skips for topic)
     */
    public function resetSession(int $userId, int $topicId): bool
    {
        try {
            $cleared = $this->clearTopicSkips($userId, $topicId, SkippedQuestion::SESSION_LEARN_MISTAKES);
            
            Log::info('Learn mistakes session reset', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'cleared_skips' => $cleared
            ]);

            return $cleared > 0;
        } catch (\Exception $e) {
            Log::error('Failed to reset learn mistakes session', [
                'user_id' => $userId,
                'topic_id' => $topicId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get recommended questions for next session
     */
    public function getRecommendedQuestions(int $userId, int $topicId, int $limit = 10): Collection
    {
        // Get questions that haven't been skipped recently
        $recentlySkipped = SkippedQuestion::where('user_id', $userId)
                                         ->where('session_type', SkippedQuestion::SESSION_LEARN_MISTAKES)
                                         ->where('skipped_at', '>=', now()->subDays(1))
                                         ->whereHas('question', function($q) use ($topicId) {
                                             $q->where('topic_id', $topicId);
                                         })
                                         ->pluck('question_id')
                                         ->toArray();

        return Question::where('topic_id', $topicId)
                      ->where('deleted', false)
                      ->whereNotIn('id', $recentlySkipped)
                      ->orderBy('id')
                      ->limit($limit)
                      ->get();
    }
}