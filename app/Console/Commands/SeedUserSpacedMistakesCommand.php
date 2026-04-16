<?php

namespace App\Console\Commands;

use App\Models\Mistake;
use App\Models\MistakeReviewEvent;
use App\Models\Question;
use App\Models\User;
use App\Models\UserSchedulerCalibrationProfile;
use App\Services\Learning\MistakeManagementService;
use App\Services\Learning\SchedulerCalibrationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedUserSpacedMistakesCommand extends Command
{
    protected $signature = 'mistakes:seed-user-deck
                            {email : User email to seed mistakes for}
                            {--count=120 : Number of unique question mistakes to seed}
                            {--reset : Clear existing mistakes, review events, and user calibration for this account first}
                            {--recalibrate : Rebuild spaced calibration after seeding}';

    protected $description = 'Seed a large spaced-repetition mistake deck for a specific user.';

    public function handle(
        MistakeManagementService $mistakeService,
        SchedulerCalibrationService $calibrationService
    ): int {
        $email = (string) $this->argument('email');
        $requestedCount = max(1, (int) $this->option('count'));

        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            $this->error("User not found for email [{$email}].");
            return self::FAILURE;
        }

        $questions = $this->resolveQuestionBank($requestedCount);
        if ($questions->isEmpty()) {
            $this->error('No suitable multiple-choice questions were found to seed mistakes.');
            return self::FAILURE;
        }

        if ($this->option('reset')) {
            $this->resetUserState($user->id);
        }

        $this->enrollInQuestionCourses($user->id, $questions);

        $this->info(sprintf(
            'Seeding %d spaced mistakes for %s...',
            $questions->count(),
            $user->email
        ));

        $seededCount = 0;

        foreach ($questions->values() as $index => $question) {
            $this->seedScenario($mistakeService, $user, $question, $index);
            $seededCount++;

            if ($seededCount % 25 === 0) {
                $this->line("  Seeded {$seededCount} mistakes...");
            }
        }

        Carbon::setTestNow();

        $userProfile = null;
        $modeProfile = null;

        if ($this->option('recalibrate')) {
            $userProfile = $calibrationService->recalibrateUserConfidence('spaced', $user->id, 365);
            $modeProfile = $calibrationService->recalibrateMode('spaced', 365);
        }

        $mistakeQuery = Mistake::query()->where('user_id', $user->id);
        $mistakeCount = (clone $mistakeQuery)->count();
        $dueCount = (clone $mistakeQuery)->dueForReview('spaced')->count();
        $futureCount = (clone $mistakeQuery)
            ->whereNotNull('next_review_at')
            ->where('next_review_at', '>', now())
            ->count();
        $skippedCount = (clone $mistakeQuery)->where('skipped', true)->count();
        $reviewEventCount = MistakeReviewEvent::query()->where('user_id', $user->id)->count();

        $this->newLine();
        $this->info('Spaced mistake deck ready.');
        $this->line("User: {$user->email}");
        $this->line("Mistakes: {$mistakeCount}");
        $this->line("Due now: {$dueCount}");
        $this->line("Scheduled for later: {$futureCount}");
        $this->line("Currently skipped: {$skippedCount}");
        $this->line("Review events: {$reviewEventCount}");

        if ($userProfile !== null) {
            $this->line(sprintf(
                'User calibration: reviews=%d, confidence_reliability=%.4f, alignment=%.4f',
                (int) ($userProfile->calibration_reviews ?? 0),
                (float) ($userProfile->confidence_reliability ?? 0.0),
                (float) ($userProfile->confidence_alignment ?? 0.0)
            ));
        }

        if ($modeProfile !== null) {
            $this->line(sprintf(
                'Mode calibration: reviews=%d, confidence_sensitivity=%.4f, overconfidence_penalty=%.4f',
                (int) ($modeProfile->calibration_reviews ?? 0),
                (float) ($modeProfile->confidence_sensitivity ?? 0.0),
                (float) ($modeProfile->overconfidence_penalty ?? 0.0)
            ));
        }

        return self::SUCCESS;
    }

    private function resolveQuestionBank(int $requestedCount): Collection
    {
        $questions = Question::query()
            ->with('topic.course')
            ->where('question_type', Question::TYPE_MULTIPLE_CHOICE)
            ->whereNotNull('option_a')
            ->whereNotNull('option_b')
            ->whereNotNull('option_c')
            ->whereNotNull('option_d')
            ->orderBy('id')
            ->get();

        if ($questions->count() < $requestedCount) {
            $this->warn(sprintf(
                'Requested %d mistakes but only %d unique questions are available. Using all available questions.',
                $requestedCount,
                $questions->count()
            ));
        }

        return $questions->take($requestedCount)->values();
    }

    private function resetUserState(int $userId): void
    {
        $this->line('Resetting existing mistake state for the user...');

        MistakeReviewEvent::query()->where('user_id', $userId)->delete();
        Mistake::query()->where('user_id', $userId)->delete();

        if (Schema::hasTable('user_scheduler_calibration_profiles')) {
            UserSchedulerCalibrationProfile::query()->where('user_id', $userId)->delete();
        }
    }

    private function enrollInQuestionCourses(int $userId, Collection $questions): void
    {
        $courseIds = $questions
            ->map(fn (Question $question) => $question->topic?->course_id)
            ->filter()
            ->unique()
            ->values();

        foreach ($courseIds as $courseId) {
            DB::table('course_user')->updateOrInsert(
                ['user_id' => $userId, 'course_id' => $courseId],
                [
                    'is_enrolled' => true,
                    'access_level' => null,
                    'enrolled_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function seedScenario(
        MistakeManagementService $mistakeService,
        User $user,
        Question $question,
        int $index
    ): void {
        $variant = $index % 8;

        match ($variant) {
            0 => $this->seedFreshFailure($user, $question),
            1 => $this->seedOverconfidentLapse($mistakeService, $user, $question),
            2 => $this->seedFragileDue($mistakeService, $user, $question),
            3 => $this->seedStableFuture($mistakeService, $user, $question),
            4 => $this->seedLowConfidenceDue($mistakeService, $user, $question),
            5 => $this->seedRelearnCycle($mistakeService, $user, $question),
            6 => $this->seedActiveSkip($user, $question),
            default => $this->seedExpiredSkip($user, $question),
        };
    }

    private function seedFreshFailure(User $user, Question $question): void
    {
        $this->recordFailureAt($user, $question, now()->subHours(18));
    }

    private function seedOverconfidentLapse(
        MistakeManagementService $mistakeService,
        User $user,
        Question $question
    ): void {
        $this->recordFailureAt($user, $question, now()->subDays(14));
        $this->reviewAt($mistakeService, $user, $question, 5, now()->subDays(11), 10, 'knew');
        $this->reviewAt($mistakeService, $user, $question, 5, now()->subDays(5), 8, 'knew');
        $this->reviewAt($mistakeService, $user, $question, 1, now()->subHours(12), 14, 'knew');
    }

    private function seedFragileDue(
        MistakeManagementService $mistakeService,
        User $user,
        Question $question
    ): void {
        $this->recordFailureAt($user, $question, now()->subDays(21));
        $this->reviewAt($mistakeService, $user, $question, 3, now()->subDays(16), 34, 'guessed');
        $this->reviewAt($mistakeService, $user, $question, 4, now()->subDays(8), 29, 'unsure');
    }

    private function seedStableFuture(
        MistakeManagementService $mistakeService,
        User $user,
        Question $question
    ): void {
        $this->recordFailureAt($user, $question, now()->subDays(28));
        $this->reviewAt($mistakeService, $user, $question, 5, now()->subDays(24), 9, 'knew');
        $this->reviewAt($mistakeService, $user, $question, 5, now()->subDays(15), 8, 'knew');
        $this->reviewAt($mistakeService, $user, $question, 4, now()->subDays(2), 11, 'knew');
    }

    private function seedLowConfidenceDue(
        MistakeManagementService $mistakeService,
        User $user,
        Question $question
    ): void {
        $this->recordFailureAt($user, $question, now()->subDays(12));
        $this->reviewAt($mistakeService, $user, $question, 3, now()->subDays(9), 42, 'guessed');
        $this->reviewAt($mistakeService, $user, $question, 3, now()->subDays(3), 38, 'guessed');
    }

    private function seedRelearnCycle(
        MistakeManagementService $mistakeService,
        User $user,
        Question $question
    ): void {
        $this->recordFailureAt($user, $question, now()->subDays(9));
        $this->reviewAt($mistakeService, $user, $question, 4, now()->subDays(6), 22, 'unsure');
        $this->reviewAt($mistakeService, $user, $question, 1, now()->subHours(9), 27, 'guessed');
        $this->reviewAt($mistakeService, $user, $question, 4, now()->subHours(2), 25, 'unsure');
    }

    private function seedActiveSkip(User $user, Question $question): void
    {
        $mistake = $this->recordFailureAt($user, $question, now()->subHours(6));

        Carbon::setTestNow(now()->subMinutes(8));
        $mistake->applySkip('spaced');
        $mistake->save();
        Carbon::setTestNow();
    }

    private function seedExpiredSkip(User $user, Question $question): void
    {
        $mistake = $this->recordFailureAt($user, $question, now()->subDays(2));

        Carbon::setTestNow(now()->subHours(8));
        $mistake->applySkip('spaced');
        $mistake->save();
        Carbon::setTestNow();
    }

    private function recordFailureAt(User $user, Question $question, Carbon $at): Mistake
    {
        Carbon::setTestNow($at);
        $mistake = Mistake::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'question_id' => $question->id,
            ],
            [
                'submitted_answer' => $this->wrongAnswerFor($question),
            ]
        );

        $mistake->submitted_answer = $this->wrongAnswerFor($question);
        $mistake->setRelation('question', $question);
        $mistake->markFailedAttempt($mistake->submitted_answer);
        $mistake->save();
        Carbon::setTestNow();

        return $mistake;
    }

    private function reviewAt(
        MistakeManagementService $mistakeService,
        User $user,
        Question $question,
        int $rating,
        Carbon $at,
        int $responseSeconds,
        ?string $confidence = null,
        array $interactionSignals = []
    ): void {
        Carbon::setTestNow($at);
        $mistakeService->reviewMistake(
            $user->id,
            $question->id,
            $rating,
            $responseSeconds,
            'spaced',
            $confidence,
            $interactionSignals
        );
        Carbon::setTestNow();
    }

    private function wrongAnswerFor(Question $question): string
    {
        foreach (['A', 'B', 'C', 'D'] as $answer) {
            if (strtoupper((string) $question->correct_answer) !== $answer) {
                return $answer;
            }
        }

        return 'A';
    }
}
