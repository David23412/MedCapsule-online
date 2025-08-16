<?php

namespace App\Http\Controllers\Topic\Progress;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * 🚀 PERFORMANCE BEAST: Topic Progress Controller
 * Handles: Topic progress tracking and source progress
 * Lines: ~80 (well under 200 limit)
 */
class TopicProgressController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Get source progress for a topic
     */
    public function getSourceProgress(Topic $topic)
    {
        $user = auth()->user();
        $mode = request()->get('mode', 'quiz');
        
        $cacheKey = "topic_source_progress_{$topic->id}_{$user->id}_{$mode}";
        
        $sourceProgress = Cache::remember($cacheKey, 1800, function () use ($topic, $user, $mode) {
            return $this->calculateSourceProgress($topic, $user->id, $mode);
        });

        return response()->json([
            'success' => true,
            'source_progress' => $sourceProgress,
            'topic_id' => $topic->id,
            'mode' => $mode
        ]);
    }

    /**
     * Calculate source progress for a topic
     */
    private function calculateSourceProgress($topic, $userId, $mode): array
    {
        // Get all sources for this topic
        $sources = DB::table('questions')
            ->where('topic_id', $topic->id)
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->pluck('source')
            ->toArray();

        $sourceProgress = [];

        foreach ($sources as $source) {
            // Get total questions for this source
            $totalQuestions = DB::table('questions')
                ->where('topic_id', $topic->id)
                ->where('source', $source)
                ->count();

            // Get attempted questions for this source
            $attemptedQuestions = DB::table('quiz_attempts')
                ->join('questions', 'quiz_attempts.question_id', '=', 'questions.id')
                ->where('quiz_attempts.user_id', $userId)
                ->where('questions.topic_id', $topic->id)
                ->where('questions.source', $source)
                ->where('quiz_attempts.study_mode', $mode)
                ->distinct('questions.id')
                ->count('questions.id');

            // Get correct answers for this source
            $correctAnswers = DB::table('quiz_attempts')
                ->join('questions', 'quiz_attempts.question_id', '=', 'questions.id')
                ->where('quiz_attempts.user_id', $userId)
                ->where('questions.topic_id', $topic->id)
                ->where('questions.source', $source)
                ->where('quiz_attempts.study_mode', $mode)
                ->where('quiz_attempts.is_correct', true)
                ->distinct('questions.id')
                ->count('questions.id');

            $progressPercentage = $totalQuestions > 0 ? round(($attemptedQuestions / $totalQuestions) * 100, 1) : 0;
            $accuracyPercentage = $attemptedQuestions > 0 ? round(($correctAnswers / $attemptedQuestions) * 100, 1) : 0;

            $sourceProgress[] = [
                'source' => $source,
                'total_questions' => $totalQuestions,
                'attempted_questions' => $attemptedQuestions,
                'correct_answers' => $correctAnswers,
                'progress_percentage' => $progressPercentage,
                'accuracy_percentage' => $accuracyPercentage,
                'status' => $this->getSourceStatus($progressPercentage, $accuracyPercentage)
            ];
        }

        // Sort by progress percentage descending
        usort($sourceProgress, function($a, $b) {
            return $b['progress_percentage'] <=> $a['progress_percentage'];
        });

        return $sourceProgress;
    }

    /**
     * Get source status based on progress and accuracy
     */
    private function getSourceStatus($progressPercentage, $accuracyPercentage): string
    {
        if ($progressPercentage >= 100 && $accuracyPercentage >= 80) {
            return 'mastered';
        }
        
        if ($progressPercentage >= 100) {
            return 'completed';
        }
        
        if ($progressPercentage > 0) {
            return 'in_progress';
        }
        
        return 'not_started';
    }
}