<?php

namespace App\Services;

use App\Models\Mistake;
use App\Models\Question;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MistakeService
{
    const CACHE_TTL = 900; // 15 minutes

    /**
     * Get mistakes for a user, with optional filtering.
     *
     * @param int $userId
     * @param string|null $courseFilter
     * @return array
     */
    public function getMistakesForUser(int $userId, ?string $courseFilter): array
    {
        $cacheKey = "mistakes.user.{$userId}.filter." . ($courseFilter ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $courseFilter) {
            $mistakesQuery = Mistake::where('user_id', $userId)
                ->where('mastered', false)
                ->with([
                    'question:id,question,question_type,topic_id,image_url,option_a,option_b,option_c,option_d,correct_answer,explanation',
                    'question.topic:id,name,course_id',
                    'question.topic.course:id,name'
                ]);

            if ($courseFilter) {
                if ($courseFilter === 'skipped') {
                    $mistakesQuery->where('skipped', true);
                } else {
                    $mistakesQuery->whereHas('question.topic.course', function ($query) use ($courseFilter) {
                        $query->where('id', $courseFilter);
                    });
                }
            }

            $mistakes = $mistakesQuery->orderBy('last_attempt_date', 'desc')->get();

            // Batch update null skipped values
            $mistakeIdsToUpdate = $mistakes->where('skipped', null)->pluck('id');
            if ($mistakeIdsToUpdate->isNotEmpty()) {
                Mistake::whereIn('id', $mistakeIdsToUpdate)->update(['skipped' => false]);
                // Refresh the collection to reflect the change
                $mistakes = $mistakesQuery->get(); 
            }

            return [
                'mistakes' => $mistakes,
                'courses' => $this->getCoursesForMistakeFilter($userId),
            ];
        });
    }

    /**
     * Get courses that have mistakes for the user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCoursesForMistakeFilter(int $userId)
    {
        $cacheKey = "mistake_courses.user.{$userId}";
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return Course::select('courses.id', 'courses.name')
                ->join('topics', 'courses.id', '=', 'topics.course_id')
                ->join('questions', 'topics.id', '=', 'questions.topic_id')
                ->join('mistakes', 'questions.id', '=', 'mistakes.question_id')
                ->where('mistakes.user_id', $userId)
                ->where('mistakes.mastered', false)
                ->distinct()
                ->get();
        });
    }

    /**
     * Record a mistake for a user.
     *
     * @param int $userId
     * @param int $questionId
     * @param string $submittedAnswer
     * @return Mistake
     */
    public function recordMistake(int $userId, int $questionId, string $submittedAnswer): Mistake
    {
        return DB::transaction(function () use ($userId, $questionId, $submittedAnswer) {
            $question = Question::findOrFail($questionId);

            if ($submittedAnswer === $question->correct_answer) {
                // This case should ideally be handled in the controller before calling the service
                // but we add a check here for robustness.
                return null;
            }

            $mistake = Mistake::updateOrCreate(
                ['user_id' => $userId, 'question_id' => $questionId],
                [
                    'submitted_answer' => $submittedAnswer,
                    'question_text' => $question->question,
                    'correct_answer' => $question->correct_answer,
                    'image_path' => $question->image_url,
                    'last_attempt_date' => now(),
                    'mastered' => false, // Always un-master when a mistake is made
                    'skipped' => false, // Reset skipped status
                ]
            );

            $this->clearUserMistakeCaches($userId);
            return $mistake;
        });
    }

    /**
     * Delete a mistake for a user.
     *
     * @param int $userId
     * @param int $questionId
     * @return bool
     */
    public function deleteMistake(int $userId, int $questionId): bool
    {
        $deleted = Mistake::where('user_id', $userId)
            ->where('question_id', $questionId)
            ->delete();

        if ($deleted) {
            $this->clearUserMistakeCaches($userId);
        }

        return $deleted > 0;
    }

    /**
     * Skip a mistake for a user.
     *
     * @param int $userId
     * @param int $questionId
     * @return Mistake|null
     */
    public function skipMistake(int $userId, int $questionId): ?Mistake
    {
        $mistake = Mistake::where('user_id', $userId)
            ->where('question_id', $questionId)
            ->first();

        if ($mistake) {
            $mistake->update([
                'skipped' => true,
                'last_attempt_date' => now(),
            ]);
            $this->clearUserMistakeCaches($userId);
            return $mistake;
        }

        return null;
    }

    /**
     * Rate a mistake using an Anki-like algorithm.
     *
     * @param int $userId
     * @param int $questionId
     * @param int $rating
     * @return Mistake|null
     */
    public function rateMistake(int $userId, int $questionId, int $rating): ?Mistake
    {
        $mistake = Mistake::where('user_id', $userId)
            ->where('question_id', $questionId)
            ->firstOrFail();

        // Assuming an `updateAnki` method exists on the Mistake model
        $mistake->updateAnki($rating);
        $mistake->save();

        $this->clearUserMistakeCaches($userId);

        return $mistake;
    }

    /**
     * Clear all mistake-related caches for a user.
     *
     * @param int $userId
     */
    public function clearUserMistakeCaches(int $userId): void
    {
        // Clear mistake list caches
        Cache::forget("mistakes.user.{$userId}.filter.all");
        Cache::forget("mistakes.user.{$userId}.filter.skipped");

        // Clear course filter cache
        Cache::forget("mistake_courses.user.{$userId}");

        // Clear caches for each course
        $courseIds = $this->getCoursesForMistakeFilter($userId)->pluck('id');
        foreach ($courseIds as $courseId) {
            Cache::forget("mistakes.user.{$userId}.filter.{$courseId}");
        }

        Log::info('Cleared mistake caches for user.', ['user_id' => $userId]);
    }
}
