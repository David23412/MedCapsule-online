<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\StudyPlan;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicProgress;
use App\Services\Planner\PlannerContextFactory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPlannerStageMarkersCommand extends Command
{
    protected $signature = 'planner:backfill-stage-markers
        {--user= : Limit backfill to a single user ID}
        {--course= : Limit backfill to a single course ID}
        {--dry-run : Show what would change without writing markers}';

    protected $description = 'Seed planner stage marker columns from inferred historical tutor/quiz progress.';

    public function handle(PlannerContextFactory $plannerContextFactory): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $userId = $this->option('user');
        $courseId = $this->option('course');

        $enrollments = DB::table('course_user')
            ->select('user_id', 'course_id')
            ->when($userId !== null, fn ($query) => $query->where('user_id', (int) $userId))
            ->when($courseId !== null, fn ($query) => $query->where('course_id', (int) $courseId))
            ->orderBy('user_id')
            ->orderBy('course_id')
            ->get();

        if ($enrollments->isEmpty()) {
            $this->info('No enrolled user/course pairs matched the requested scope.');

            return self::SUCCESS;
        }

        $pairsProcessed = 0;
        $rowsUpdated = 0;
        $rowsSkipped = 0;

        foreach ($enrollments as $enrollment) {
            $user = User::query()->find((int) $enrollment->user_id);
            $course = Course::query()->find((int) $enrollment->course_id);

            if (!$user || !$course) {
                $rowsSkipped++;
                continue;
            }

            $topics = Topic::query()
                ->where('course_id', $course->id)
                ->orderBy('display_order')
                ->with(['userProgress' => fn ($query) => $query->where('user_id', $user->id)])
                ->get();

            if ($topics->isEmpty()) {
                $rowsSkipped++;
                continue;
            }

            $pairsProcessed++;
            $plan = new StudyPlan([
                'user_id' => $user->id,
                'plan_date' => now()->toDateString(),
                'daily_goal' => 0,
                'questions_completed' => 0,
                'tasks' => [],
                'topics' => [],
                'is_committed' => true,
            ]);

            $context = $plannerContextFactory->buildForCourse($user, $course, $topics, $plan);

            foreach ($topics as $topic) {
                $profile = data_get($context->topicSignals, 'profiles.' . $topic->id, []);
                $resolvedTutorClearedAt = $this->normalizeTimestamp($profile['resolved_tutor_cleared_at'] ?? null);
                $resolvedQuizClearedAt = $this->normalizeTimestamp($profile['resolved_quiz_cleared_at'] ?? null);
                $resolvedClearedAt = $this->normalizeTimestamp($profile['resolved_cleared_at'] ?? null);
                $existingProgress = $topic->userProgress->first();

                if (!$existingProgress
                    && $resolvedTutorClearedAt === null
                    && $resolvedQuizClearedAt === null
                    && $resolvedClearedAt === null) {
                    continue;
                }

                $progress = $existingProgress ?? new UserTopicProgress([
                    'user_id' => $user->id,
                    'topic_id' => $topic->id,
                    'percentage_grade' => (float) ($topic->percentage_grade ?? 0.0),
                    'last_attempt_date' => $topic->last_attempt_date,
                ]);

                if ($progress->planner_tutor_cleared_at === null && $resolvedTutorClearedAt !== null) {
                    $progress->planner_tutor_cleared_at = $resolvedTutorClearedAt;
                }

                if ($progress->planner_quiz_cleared_at === null && $resolvedQuizClearedAt !== null) {
                    $progress->planner_quiz_cleared_at = $resolvedQuizClearedAt;
                }

                if ($progress->planner_cleared_at === null && $resolvedClearedAt !== null) {
                    $progress->planner_cleared_at = $resolvedClearedAt;
                }

                if (!$progress->isDirty([
                    'planner_tutor_cleared_at',
                    'planner_quiz_cleared_at',
                    'planner_cleared_at',
                ])) {
                    continue;
                }

                $rowsUpdated++;

                if (!$dryRun) {
                    $progress->save();
                }
            }
        }

        $summary = sprintf(
            'Processed %d user/course pairs, updated %d topic progress rows, skipped %d pairs.',
            $pairsProcessed,
            $rowsUpdated,
            $rowsSkipped
        );

        $dryRun ? $this->warn('[dry-run] ' . $summary) : $this->info($summary);

        return self::SUCCESS;
    }

    private function normalizeTimestamp(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }

        return null;
    }
}
