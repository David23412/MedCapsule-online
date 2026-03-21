<?php

namespace App\Services\Learning;

use App\Models\Mistake;
use App\Models\Question;
use App\Models\User;
use App\Models\UserMistakeMastery;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

class UserMistakeMasteryService
{
    public function recordAttempt(
        User|int $user,
        Question $question,
        bool $isCorrect,
        int $requiredStreak,
        ?int $courseId = null,
        ?CarbonInterface $attemptedAt = null,
        bool $syncMistakeMirror = true
    ): UserMistakeMastery {
        $userId = $user instanceof User ? (int) $user->id : (int) $user;
        $attemptedAt ??= now();
        $courseId ??= (int) ($question->topic?->course_id ?? 0);

        if ($courseId <= 0) {
            $courseId = (int) $question->topic()->value('course_id');
        }

        if (!UserMistakeMastery::hasTable()) {
            return $this->recordAttemptUsingLegacyMistakeMirror(
                $userId,
                $question,
                $isCorrect,
                $requiredStreak,
                $courseId,
                $attemptedAt
            );
        }

        $mastery = UserMistakeMastery::query()->firstOrNew([
            'user_id' => $userId,
            'question_id' => (int) $question->id,
        ]);

        $mastery->course_id = $courseId > 0 ? $courseId : null;
        $mastery->total_attempts = max(0, (int) ($mastery->total_attempts ?? 0)) + 1;
        $mastery->last_attempt_at = $attemptedAt;
        $mastery->last_attempt_result = $isCorrect ? 'correct' : 'incorrect';

        if ($isCorrect) {
            $mastery->consecutive_correct = max(0, (int) ($mastery->consecutive_correct ?? 0)) + 1;
            $mastery->graduated_at = $mastery->consecutive_correct >= max(1, $requiredStreak)
                ? $attemptedAt
                : null;
        } else {
            $mastery->consecutive_correct = 0;
            $mastery->graduated_at = null;
        }

        $mastery->save();

        if ($syncMistakeMirror) {
            $this->syncMistakeMirror(
                $userId,
                (int) $question->id,
                $mastery->consecutive_correct,
                $mastery->graduated_at,
                $attemptedAt
            );
        }

        return $mastery;
    }

    public function requiredStreakForMode(string $mode): ?int
    {
        return match ($mode) {
            'quick', 'practice' => 2,
            'quiz', 'random', 'tutor', 'study', 'assignment', 'review', 'mistake_review' => 3,
            default => $mode === 'spaced' ? null : 3,
        };
    }

    public function requiredStreakForDifficulty(?string $difficulty): int
    {
        return $difficulty === 'hard' ? 3 : 2;
    }

    public function requiredStreakForUser(User $user): int
    {
        return $this->requiredStreakForDifficulty($user->classic_repair_difficulty ?? 'easy');
    }

    private function recordAttemptUsingLegacyMistakeMirror(
        int $userId,
        Question $question,
        bool $isCorrect,
        int $requiredStreak,
        int $courseId,
        CarbonInterface $attemptedAt
    ): UserMistakeMastery {
        $legacyState = $this->legacyMistakeState($userId, (int) $question->id, $attemptedAt);
        $usingFreshMirrorState = (bool) ($legacyState['updated_recently'] ?? false);
        $consecutiveCorrect = $usingFreshMirrorState
            ? (int) ($legacyState['consecutive_correct'] ?? 0)
            : ($isCorrect ? ($legacyState['consecutive_correct'] + 1) : 0);
        $graduatedAt = $usingFreshMirrorState
            ? ((bool) ($legacyState['mastered'] ?? false) ? $attemptedAt : null)
            : ($isCorrect && $consecutiveCorrect >= max(1, $requiredStreak) ? $attemptedAt : null);

        $this->syncMistakeMirror(
            $userId,
            (int) $question->id,
            $consecutiveCorrect,
            $graduatedAt,
            $attemptedAt
        );

        return tap(new UserMistakeMastery(), function (UserMistakeMastery $mastery) use (
            $userId,
            $question,
            $courseId,
            $consecutiveCorrect,
            $graduatedAt,
            $attemptedAt,
            $isCorrect
        ) {
            $mastery->forceFill([
                'user_id' => $userId,
                'question_id' => (int) $question->id,
                'course_id' => $courseId > 0 ? $courseId : null,
                'consecutive_correct' => $consecutiveCorrect,
                'graduated_at' => $graduatedAt,
                'last_attempt_at' => $attemptedAt,
                'last_attempt_result' => $isCorrect ? 'correct' : 'incorrect',
                'total_attempts' => 1,
            ]);
        });
    }

    private function legacyMistakeState(int $userId, int $questionId, CarbonInterface $attemptedAt): array
    {
        if (!Schema::hasTable('mistakes')) {
            return [
                'consecutive_correct' => 0,
                'mastered' => false,
                'updated_recently' => false,
            ];
        }

        $mistake = Mistake::query()
            ->where('user_id', $userId)
            ->where('question_id', $questionId)
            ->first(['correct_streak', 'mastered', 'last_attempt_date', 'updated_at']);

        $lastMutationAt = $mistake?->last_attempt_date ?? $mistake?->updated_at;
        $updatedRecently = $lastMutationAt instanceof CarbonInterface
            ? $lastMutationAt->diffInSeconds($attemptedAt) <= 5
            : false;

        return [
            'consecutive_correct' => max(0, (int) ($mistake?->correct_streak ?? 0)),
            'mastered' => (bool) ($mistake?->mastered ?? false),
            'updated_recently' => $updatedRecently,
        ];
    }

    private function syncMistakeMirror(
        int $userId,
        int $questionId,
        int $consecutiveCorrect,
        mixed $graduatedAt,
        CarbonInterface $attemptedAt
    ): void {
        if (!Schema::hasTable('mistakes')) {
            return;
        }

        $attributes = [
            'correct_streak' => $consecutiveCorrect,
            'mastered' => $graduatedAt !== null,
            'last_attempt_date' => $attemptedAt,
            'updated_at' => $attemptedAt,
        ];

        if (Mistake::hasColumn('mastered_at')) {
            $attributes['mastered_at'] = $graduatedAt;
        }

        Mistake::query()
            ->where('user_id', $userId)
            ->where('question_id', $questionId)
            ->update($attributes);
    }
}
