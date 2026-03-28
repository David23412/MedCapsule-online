<?php

namespace App\Services;

use App\Models\Mistake;
use App\Models\Question;
use App\Services\Learning\ConceptMemoryService;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MistakeService
{
    public function __construct(
        private ConceptMemoryService $conceptMemoryService,
    ) {
    }

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
        $this->reactivateExpiredSkippedMistakes($userId);
        $cacheKey = "mistakes.user.{$userId}.filter." . ($courseFilter ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $courseFilter) {
            $mistakesQuery = Mistake::where('user_id', $userId)
                ->with([
                    'question:id,question,question_type,topic_id,image_url,option_a,option_b,option_c,option_d,correct_answer,explanation',
                    'question.topic:id,name,course_id',
                    'question.topic.course:id,name'
                ]);

            if ($courseFilter) {
                $mistakesQuery->whereHas('question.topic.course', function ($query) use ($courseFilter) {
                    $query->where('id', $courseFilter);
                });
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
     * @return Mistake|null
     */
    public function recordMistake(int $userId, int $questionId, string $submittedAnswer): ?Mistake
    {
        return DB::transaction(function () use ($userId, $questionId, $submittedAnswer) {
            $question = Question::findOrFail($questionId);

            if ($question->isAnswerCorrect($submittedAnswer)) {
                // This case should ideally be handled in the controller before calling the service
                // but we add a check here for robustness.
                return null;
            }

            $mistake = Mistake::recordFailure($userId, $question, $submittedAnswer);

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
    public function skipMistake(int $userId, int $questionId, string $mode = 'quiz'): ?Mistake
    {
        $mistake = Mistake::where('user_id', $userId)
            ->where('question_id', $questionId)
            ->first();

        if ($mistake) {
            $mistake->applySkip($mode);
            $mistake->save();
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
    public function rateMistake(int $userId, int $questionId, int $rating, string $mode = 'classic'): ?Mistake
    {
        $mistake = Mistake::where('user_id', $userId)
            ->where('question_id', $questionId)
            ->firstOrFail();

        $mistake->applyRatedReview($rating, $mode);
        $mistake->save();
        $this->conceptMemoryService->syncReview($mistake, $rating, 'quiz');

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
        // SURGICAL OPTIMIZATION: Only clear specific user caches, not all courses
        $cacheKeys = [
            "mistakes.user.{$userId}.filter.all",
            "mistake_courses.user.{$userId}",
            "mistakes_smart_{$userId}_v3",
            "mistakes_support_smart_{$userId}"
        ];

        // Clear only the specific user's cache keys (surgical precision)
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // SURGICAL OPTIMIZATION: Only clear course caches for courses user actually has mistakes in
        try {
            // Get only courses with mistakes for this user (not all courses!)
            $userCourseIds = DB::table('mistakes')
                ->join('questions', 'mistakes.question_id', '=', 'questions.id')
                ->join('topics', 'questions.topic_id', '=', 'topics.id')
                ->where('mistakes.user_id', $userId)
                ->distinct()
                ->pluck('topics.course_id')
                ->toArray();

            // Clear only relevant course caches (surgical!)
            foreach ($userCourseIds as $courseId) {
                Cache::forget("mistakes.user.{$userId}.filter.{$courseId}");
                Cache::forget("mistakes_smart_{$userId}_{$courseId}_v3");
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear specific course caches', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }

        // SURGICAL OPTIMIZATION: Reduced logging frequency for production
        if (app()->environment('local', 'testing')) {
            Log::info('Cleared surgical mistake caches for user', [
                'user_id' => $userId,
                'cache_keys_cleared' => count($cacheKeys) + count($userCourseIds ?? [])
            ]);
        }
    }

    private function reactivateExpiredSkippedMistakes(int $userId): void
    {
        if (!Mistake::hasColumn('skip_until')) {
            return;
        }

        Mistake::query()
            ->where('user_id', $userId)
            ->where('skipped', true)
            ->whereNotNull('skip_until')
            ->where('skip_until', '<=', now())
            ->update([
                'skipped' => false,
                'skip_until' => null,
            ]);
    }
}
