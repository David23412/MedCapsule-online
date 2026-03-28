<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Mistake;
use App\Models\MistakeReviewEvent;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserSchedulerCalibrationProfile;
use App\Services\Learning\MistakeManagementService;
use App\Services\Learning\SchedulerBacktestService;
use App\Services\Learning\SchedulerCalibrationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SpacedRepetitionFixtureSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding spaced-repetition fixture data...');

        $users = $this->ensureFixtureUsers();
        $questions = $this->ensureQuestionBank();
        $this->attachConceptCoverage($questions);
        $this->resetFixtureState($users);
        $this->enrollFixtureUsers($users, $questions);

        $mistakeService = app(MistakeManagementService::class);
        $calibrationService = app(SchedulerCalibrationService::class);
        $backtestService = app(SchedulerBacktestService::class);

        foreach ($users as $index => $user) {
            $this->seedUserDeck($user, $questions->slice($index * 8, 8)->values(), $mistakeService);
            $calibrationService->recalibrateUserConfidence('spaced', $user->id, 365);
        }

        $profile = $calibrationService->recalibrateMode('spaced', 365);
        $report = $backtestService->backtest('spaced', 365);

        $this->command?->info(sprintf(
            'Spaced fixture ready: %d mistakes, %d review events, MAE=%s, quality=%s',
            Mistake::query()->whereIn('user_id', $users->pluck('id'))->count(),
            MistakeReviewEvent::query()->whereIn('user_id', $users->pluck('id'))->count(),
            $report['mean_abs_error'] !== null ? number_format((float) $report['mean_abs_error'], 4) : 'n/a',
            $report['data_quality']['sufficiency'] ?? 'n/a'
        ));
        $this->command?->line(sprintf(
            'Calibrated spaced profile: reviews=%d, confidence_sensitivity=%.4f, overconfidence_penalty=%.4f',
            (int) ($profile->calibration_reviews ?? 0),
            (float) ($profile->confidence_sensitivity ?? 0.0),
            (float) ($profile->overconfidence_penalty ?? 0.0)
        ));
    }

    private function ensureFixtureUsers(): Collection
    {
        $profiles = [
            ['name' => 'Spaced Alpha', 'email' => 'spaced.alpha@medcapsule.test', 'planner_focus' => 'revision'],
            ['name' => 'Spaced Beta', 'email' => 'spaced.beta@medcapsule.test', 'planner_focus' => 'exam_prep'],
            ['name' => 'Spaced Gamma', 'email' => 'spaced.gamma@medcapsule.test', 'planner_focus' => 'balanced'],
            ['name' => 'Spaced Delta', 'email' => 'spaced.delta@medcapsule.test', 'planner_focus' => 'revision'],
        ];

        return collect($profiles)->map(function (array $profile) {
            return User::query()->updateOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'password' => Hash::make('password'),
                    'role' => 3,
                    'weekly_goal' => 120,
                    'daily_study_minutes' => 120,
                    'planner_focus' => $profile['planner_focus'],
                    'exam_date' => now()->addWeeks(8)->toDateString(),
                    'email_verified_at' => now(),
                ]
            );
        });
    }

    private function ensureQuestionBank(): Collection
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

        if ($questions->count() >= 32) {
            return $questions->take(32)->values();
        }

        $course = Course::query()->firstOrCreate(
            ['name' => 'Spaced Repetition Fixtures'],
            [
                'description' => 'Fixture course for spaced repetition validation.',
                'type' => 'custom',
                'academic_year' => '2026-1',
                'color' => '#0F6CBD',
                'title_color' => '#FFFFFF',
            ]
        );

        $topics = collect([
            'Pulmonary gas exchange',
            'Renal fluid shifts',
            'Cardiac output regulation',
            'Neurophysiology integration',
        ])->map(function (string $name, int $index) use ($course) {
            return Topic::query()->firstOrCreate(
                ['course_id' => $course->id, 'name' => $name],
                [
                    'case_type' => 'quiz',
                    'display_order' => $index + 1,
                    'is_locked' => false,
                ]
            );
        });

        $templates = [
            [
                'stem' => 'What best explains oxygen transfer from alveoli into capillary blood?',
                'options' => ['Partial pressure gradient', 'Active transport', 'Osmotic pressure', 'Facilitated diffusion'],
                'correct' => 'A',
                'explanation' => 'Oxygen diffuses down its partial pressure gradient.',
            ],
            [
                'stem' => 'Which force most strongly draws water into the plasma compartment?',
                'options' => ['Hydrostatic pressure', 'Oncotic pressure', 'Transmembrane pumping', 'Electrical gradient'],
                'correct' => 'B',
                'explanation' => 'Plasma oncotic pressure retains fluid intravascularly.',
            ],
            [
                'stem' => 'Which change most directly increases venous return?',
                'options' => ['Higher intrathoracic pressure', 'Increased venous tone', 'Lower skeletal pump activity', 'Reduced blood volume'],
                'correct' => 'B',
                'explanation' => 'Venoconstriction raises stressed volume and augments venous return.',
            ],
            [
                'stem' => 'Which ion movement drives the rapid depolarization phase of a ventricular myocyte?',
                'options' => ['Calcium influx', 'Potassium efflux', 'Sodium influx', 'Chloride influx'],
                'correct' => 'C',
                'explanation' => 'Fast sodium influx causes phase 0 depolarization.',
            ],
        ];

        $questions = collect();
        foreach ($topics as $topicIndex => $topic) {
            foreach ($templates as $templateIndex => $template) {
                $questions->push(Question::query()->create([
                    'topic_id' => $topic->id,
                    'question' => $template['stem'] . ' #' . ($topicIndex + 1) . '-' . ($templateIndex + 1),
                    'explanation' => $template['explanation'],
                    'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                    'option_a' => $template['options'][0],
                    'option_b' => $template['options'][1],
                    'option_c' => $template['options'][2],
                    'option_d' => $template['options'][3],
                    'correct_answer' => $template['correct'],
                    'source' => 'FixtureSeeder',
                ]));
            }
        }

        return $questions->load('topic.course')->values();
    }

    private function attachConceptCoverage(Collection $questions): void
    {
        $questions
            ->groupBy(fn (Question $question) => $question->topic_id)
            ->each(function (Collection $topicQuestions) {
                $topic = $topicQuestions->first()?->topic;
                if (!$topic) {
                    return;
                }

                $concepts = collect([
                    $topic->name . ' Core Principle',
                    $topic->name . ' Common Trap',
                    $topic->name . ' Clinical Application',
                ])->values();

                foreach ($topicQuestions->values() as $index => $question) {
                    $question->forceFill([
                        'concept' => $concepts[$index % $concepts->count()],
                    ])->save();
                }
            });
    }

    private function resetFixtureState(Collection $users): void
    {
        $userIds = $users->pluck('id');

        MistakeReviewEvent::query()->whereIn('user_id', $userIds)->delete();
        UserSchedulerCalibrationProfile::query()->whereIn('user_id', $userIds)->delete();
        Mistake::query()->whereIn('user_id', $userIds)->delete();
    }

    private function enrollFixtureUsers(Collection $users, Collection $questions): void
    {
        $courseIds = $questions
            ->map(fn (Question $question) => $question->topic?->course_id)
            ->filter()
            ->unique()
            ->values();

        foreach ($users as $user) {
            foreach ($courseIds as $courseId) {
                DB::table('course_user')->updateOrInsert(
                    ['user_id' => $user->id, 'course_id' => $courseId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    private function seedUserDeck(User $user, Collection $questions, MistakeManagementService $mistakeService): void
    {
        if ($questions->count() < 8) {
            return;
        }

        $questionSet = $questions->values();

        $this->seedOverconfidentRelearn($user, $questionSet[0], $mistakeService);
        $this->seedHesitantRelearn($user, $questionSet[1], $mistakeService);
        $this->seedFragileDue($user, $questionSet[2], $mistakeService);
        $this->seedStableFuture($user, $questionSet[3], $mistakeService);
        $this->seedActiveSkip($user, $questionSet[4]);
        $this->seedExpiredSkip($user, $questionSet[5]);
        $this->seedConceptClusterWeakness($user, $questionSet[6], $questionSet[7], $mistakeService);
    }

    private function seedOverconfidentRelearn(User $user, Question $question, MistakeManagementService $mistakeService): void
    {
        $this->recordFailureAt($user, $question, now()->subDays(9));
        $this->reviewAt($mistakeService, $user, $question, 5, now()->subDays(7), 9, 'spaced', 'knew');
        $this->reviewAt($mistakeService, $user, $question, 1, now()->subHours(2), 11, 'spaced', 'knew');
    }

    private function seedHesitantRelearn(User $user, Question $question, MistakeManagementService $mistakeService): void
    {
        $this->recordFailureAt($user, $question, now()->subDays(5));
        $this->reviewAt($mistakeService, $user, $question, 1, now()->subHours(5), 22, 'spaced', 'unsure', [
            'first_selected_answer' => 'A',
            'final_selected_answer' => 'C',
            'answer_change_count' => 2,
            'time_to_first_selection' => 12,
            'first_confidence' => 'guessed',
            'confidence_change_count' => 1,
        ]);
        $this->reviewAt($mistakeService, $user, $question, 4, now()->subHour(), 27, 'spaced', 'unsure', [
            'first_selected_answer' => 'D',
            'final_selected_answer' => $question->correct_answer,
            'answer_change_count' => 1,
            'time_to_first_selection' => 18,
            'first_confidence' => 'guessed',
            'confidence_change_count' => 1,
        ]);
    }

    private function seedFragileDue(User $user, Question $question, MistakeManagementService $mistakeService): void
    {
        $this->recordFailureAt($user, $question, now()->subDays(18));
        $this->reviewAt($mistakeService, $user, $question, 4, now()->subDays(13), 28, 'spaced', 'guessed');
        $this->reviewAt($mistakeService, $user, $question, 4, now()->subDays(6), 24, 'spaced', 'guessed');
    }

    private function seedStableFuture(User $user, Question $question, MistakeManagementService $mistakeService): void
    {
        $this->recordFailureAt($user, $question, now()->subDays(24));
        $this->reviewAt($mistakeService, $user, $question, 5, now()->subDays(19), 7, 'spaced', 'knew');
        $this->reviewAt($mistakeService, $user, $question, 5, now()->subDays(9), 6, 'spaced', 'knew');
        $this->reviewAt($mistakeService, $user, $question, 4, now()->subDays(2), 8, 'spaced', 'knew');
    }

    private function seedActiveSkip(User $user, Question $question): void
    {
        Carbon::setTestNow(now()->subMinutes(20));
        $mistake = Mistake::recordFailure($user->id, $question, 'wrong');
        Carbon::setTestNow(now()->subMinutes(8));
        $mistake->applySkip('spaced');
        $mistake->save();
        Carbon::setTestNow();
    }

    private function seedExpiredSkip(User $user, Question $question): void
    {
        Carbon::setTestNow(now()->subDays(2));
        $mistake = Mistake::recordFailure($user->id, $question, 'wrong');
        Carbon::setTestNow(now()->subHours(8));
        $mistake->applySkip('spaced');
        $mistake->save();
        Carbon::setTestNow();
    }

    private function seedConceptClusterWeakness(User $user, Question $first, Question $second, MistakeManagementService $mistakeService): void
    {
        $sharedConcept = $this->sharedClusterConcept($first);
        $second->forceFill([
            'concept' => $sharedConcept['name'],
        ])->save();

        $this->recordFailureAt($user, $first, now()->subDays(11));
        $this->reviewAt($mistakeService, $user, $first, 1, now()->subDays(4), 14, 'spaced', 'knew');
        $this->reviewAt($mistakeService, $user, $first, 4, now()->subDays(2), 16, 'spaced', 'unsure');

        $this->recordFailureAt($user, $second, now()->subDays(10));
        $this->reviewAt($mistakeService, $user, $second, 1, now()->subDays(3), 13, 'spaced', 'knew');
        $this->reviewAt($mistakeService, $user, $second, 4, now()->subHours(6), 17, 'spaced', 'guessed');
    }

    private function sharedClusterConcept(Question $question): array
    {
        $topic = $question->topic;

        return [
            'name' => $topic->name . ' Failure Cluster',
            'slug' => 'topic-' . $topic->id . '-failure-cluster',
        ];
    }

    private function recordFailureAt(User $user, Question $question, Carbon $at): Mistake
    {
        Carbon::setTestNow($at);
        $mistake = Mistake::recordFailure($user->id, $question, 'wrong');
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
        string $mode,
        ?string $confidence = null,
        array $interactionSignals = []
    ): void {
        Carbon::setTestNow($at);
        $mistakeService->reviewMistake(
            $user->id,
            $question->id,
            $rating,
            $responseSeconds,
            $mode,
            $confidence,
            $interactionSignals
        );
        Carbon::setTestNow();
    }
}
