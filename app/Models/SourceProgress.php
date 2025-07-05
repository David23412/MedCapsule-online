<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Event;
use App\Events\SourceProgressUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SourceProgress extends Model
{
    use HasFactory;

    protected $table = 'source_progress';

    protected $fillable = [
        'user_id',
        'topic_id',
        'source',
        'mode',
        'correct_answers',
        'total_questions_attempted',
        'total_questions_available',
        'percentage_grade',
        'last_attempt_date'
    ];

    protected $casts = [
        'percentage_grade' => 'float',
        'last_attempt_date' => 'datetime'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get all available sources for a specific topic
     */
    public static function getAvailableSourcesForTopic(int $topicId): array
    {
        // Get unique sources and their question counts from questions table for this topic
        $sourceCounts = Question::where('topic_id', $topicId)
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->get()
            ->mapWithKeys(function ($item) {
                $source = $item->source ?: 'General';
                return [$source => $item->count];
            })
            ->toArray();

        return !empty($sourceCounts) ? $sourceCounts : ['all' => 0];
    }

    /**
     * Update progress for a specific source
     */
    public static function updateProgress(int $userId, int $topicId, string $source, int $correctAnswers, int $totalQuestionsAvailable, string $mode = 'quiz'): self
    {
        try {
            // Validate inputs
            if ($correctAnswers < 0) {
                throw new \InvalidArgumentException('Correct answers cannot be negative');
            }
            if ($totalQuestionsAvailable < 0) {
                throw new \InvalidArgumentException('Total questions cannot be negative');
            }
            if ($correctAnswers > $totalQuestionsAvailable) {
                throw new \InvalidArgumentException('Correct answers cannot exceed total questions');
            }
            if (!in_array($mode, ['quiz', 'tutor'])) {
                throw new \InvalidArgumentException('Invalid mode');
            }

            // Normalize source to 'General' if null or empty
            $source = $source ?: 'General';

            // Lock the row to prevent race conditions
            $progress = DB::transaction(function() use ($userId, $topicId, $source, $correctAnswers, $totalQuestionsAvailable, $mode) {
                $progress = self::lockForUpdate()->firstOrNew([
                    'user_id' => $userId,
                    'topic_id' => $topicId,
                    'source' => $source,
                    'mode' => $mode,
                ]);

                // Ensure we never decrease progress
                $newPercentage = $totalQuestionsAvailable > 0 ? min(100, ($correctAnswers / $totalQuestionsAvailable) * 100) : 0;
                if ($progress->exists && $newPercentage < $progress->percentage_grade) {
                    Log::warning('Attempted to decrease progress', [
                        'user_id' => $userId,
                        'topic_id' => $topicId,
                        'source' => $source,
                        'old_percentage' => $progress->percentage_grade,
                        'new_percentage' => $newPercentage
                    ]);
                    return $progress; // Keep existing progress if new is lower
                }

                $progress->fill([
                    'correct_answers' => $correctAnswers,
                    'total_questions_attempted' => $totalQuestionsAvailable,
                    'total_questions_available' => $totalQuestionsAvailable,
                    'percentage_grade' => $newPercentage,
                    'last_attempt_date' => now(),
                ]);

                $progress->save();
                return $progress;
            });

            // Get all source progress for this topic and mode
            $allProgress = self::where([
                'user_id' => $userId,
                'topic_id' => $topicId,
                'mode' => $mode
            ])->get();

            // Calculate if all sources are mastered (>= 90%)
            $allSourcesMastered = $allProgress->every(function ($p) {
                return $p->percentage_grade >= 90;
            });

            // Create source progress data with error handling
            try {
                $sourceProgress = $allProgress->mapWithKeys(function ($p) {
                    return [$p->source => [
                        'score' => $p->correct_answers,
                        'total' => $p->total_questions_available,
                        'percentage' => $p->percentage_grade
                    ]];
                })->toArray();

                // Broadcast the source progress update
                broadcast(new SourceProgressUpdated(
                    $topicId,
                    $sourceProgress,
                    $allSourcesMastered
                ));
            } catch (\Exception $e) {
                Log::error('Failed to broadcast progress update', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'topic_id' => $topicId,
                    'source' => $source
                ]);
                // Don't throw - broadcasting failure shouldn't break progress update
            }

            return $progress;
        } catch (\Exception $e) {
            Log::error('Error updating source progress', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'topic_id' => $topicId,
                'source' => $source,
                'correct_answers' => $correctAnswers,
                'total_questions' => $totalQuestionsAvailable,
                'mode' => $mode
            ]);
            throw $e; // Re-throw to be handled by caller
        }
    }

    /**
     * Get progress for quiz mode only
     */
    public static function getQuizModeProgress($userId, $topicId)
    {
        return self::where([
            'user_id' => $userId,
            'topic_id' => $topicId,
            'mode' => 'quiz'
        ])->get();
    }
} 