<?php

namespace App\Services\Planner;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\StudyPlan;
use App\Models\Topic;
use App\Models\User;
use App\Services\Learning\ConceptAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlannerContextFactory
{
    private const MIN_TASKS = 3;
    private const MAX_TASKS = 6;

    private static ?array $availableCoursePlannerColumns = null;

    public function __construct(
        private GlobalStudyBudgetAllocator $globalStudyBudgetAllocator,
        private ConceptAnalyticsService $conceptAnalyticsService,
        private PlannerSignalService $plannerSignalService,
        private PlannerTaskOutcomeService $plannerTaskOutcomeService,
    ) {
    }

    public function buildForCourse(User $user, Course $course, Collection $topics, StudyPlan $plan): PlannerContext
    {
        $totalDailyStudyMinutes = max(30, (int) ($user->daily_study_minutes ?? 90));
        $globalAllocation = $this->globalStudyBudgetAllocator->allocate($user, $course->id);
        $courseSettings = $this->resolveCoursePlannerSettings($user, $course, $plan, $globalAllocation, $totalDailyStudyMinutes);
        $examDate = $courseSettings['exam_date'];
        $daysToExam = $examDate
            ? now()->startOfDay()->diffInDays($examDate->copy()->startOfDay(), false)
            : null;
        $dailyStudyMinutes = (int) $courseSettings['effective_daily_study_minutes'];
        $plannerFocus = (string) $courseSettings['planner_focus'];
        $tasksLimit = $this->resolveTasksLimit($dailyStudyMinutes);
        $mixedQuestionCount = $this->resolveQuestionCount($dailyStudyMinutes, $daysToExam);
        $mixedDurationMinutes = $this->resolveMixedDuration($dailyStudyMinutes, $daysToExam);
        $carryOverTaskIds = $this->resolveCarryOverTaskIds($user, $course->id);
        $courseTopicIds = $topics->pluck('id')->map(fn ($id) => (int) $id)->all();
        $classicRepairDifficulty = (string) ($courseSettings['classic_repair_difficulty'] ?? 'easy');
        $backlogSignals = $this->plannerSignalService->buildTopicBacklogSignals(
            $user->id,
            $courseTopicIds,
            $this->resolveClassicMasteryThreshold($classicRepairDifficulty)
        );
        $assignmentSignals = $this->plannerSignalService->buildCourseAssignmentSignals($user->id, [$course->id])[$course->id] ?? [
            'nearest_assignment_deadline' => null,
            'nearest_assignment_hours' => null,
            'overdue_assignment_count' => 0,
            'due_within_48h_count' => 0,
            'active_assignment_count' => 0,
            'by_topic' => [],
        ];
        $conceptSignals = $this->buildConceptSignals($user, $courseTopicIds);
        $topicSignals = $this->buildTopicSignals(
            $user,
            $topics,
            $backlogSignals,
            $assignmentSignals,
            $conceptSignals,
            $daysToExam,
            $courseSettings['preferred_source']
        );
        $attemptSignals = $this->buildAttemptSignals($user, $courseTopicIds);
        $behaviorSignals = $this->plannerTaskOutcomeService->summarizeRecentBehavior($user, $course->id, $courseTopicIds);
        $repairMode = (string) ($courseSettings['repair_mode'] ?? 'easy');

        return new PlannerContext(
            examDate: $examDate,
            daysToExam: $daysToExam,
            dailyStudyMinutes: $dailyStudyMinutes,
            totalDailyStudyMinutes: $totalDailyStudyMinutes,
            plannerFocus: $plannerFocus,
            tasksLimit: $tasksLimit,
            mixedQuestionCount: $mixedQuestionCount,
            mixedDurationMinutes: $mixedDurationMinutes,
            carryOverTaskIds: $carryOverTaskIds,
            courseTopicIds: $courseTopicIds,
            openMistakesCount: (int) ($backlogSignals['classic_reviewable_backlog'] ?? $backlogSignals['reviewable_backlog'] ?? 0),
            overallAccuracy: $this->resolveOverallAccuracy($user),
            topicSignals: $topicSignals,
            attemptSignals: $attemptSignals,
            dueMistakeSignals: $this->buildDueMistakeSignals($backlogSignals),
            backlogSignals: $backlogSignals,
            assignmentSignals: $assignmentSignals,
            conceptSignals: $conceptSignals,
            behaviorSignals: $behaviorSignals,
            globalAllocation: $globalAllocation,
            coursePriorityScore: (float) data_get($globalAllocation, 'courses.' . $course->id . '.priority_score', 100.0),
            preferredSource: $courseSettings['preferred_source'],
            repairMode: $repairMode,
            classicRepairDifficulty: $classicRepairDifficulty,
            resolvedSettings: $courseSettings,
        );
    }

    private function resolveTasksLimit(int $dailyStudyMinutes): int
    {
        return 3;
    }

    private function resolveQuestionCount(int $dailyStudyMinutes, ?int $daysToExam): int
    {
        if ($daysToExam !== null && $daysToExam <= 7) {
            return max(15, min(40, (int) ceil($dailyStudyMinutes / 4)));
        }

        return max(10, min(28, (int) ceil($dailyStudyMinutes / 6)));
    }

    private function resolveMixedDuration(int $dailyStudyMinutes, ?int $daysToExam): int
    {
        if ($daysToExam !== null && $daysToExam <= 7) {
            return max(25, min(50, (int) ceil($dailyStudyMinutes * 0.45)));
        }

        return max(15, min(35, (int) ceil($dailyStudyMinutes * 0.3)));
    }

    private function resolveExamDate(User $user, StudyPlan $todayPlan, ?string $overrideExamDate = null): ?Carbon
    {
        if ($overrideExamDate) {
            return Carbon::parse($overrideExamDate);
        }

        if ($user->exam_date) {
            return Carbon::parse($user->exam_date);
        }

        if ($todayPlan->exam_date) {
            return Carbon::parse($todayPlan->exam_date);
        }

        $upcomingPlan = StudyPlan::query()
            ->where('user_id', $user->id)
            ->whereDate('exam_date', '>=', now()->toDateString())
            ->orderBy('exam_date')
            ->first();

        return $upcomingPlan?->exam_date ? Carbon::parse($upcomingPlan->exam_date) : null;
    }

    private function resolveCarryOverTaskIds(User $user, int $courseId): array
    {
        return StudyPlan::query()
            ->where('user_id', $user->id)
            ->whereDate('plan_date', '>=', now()->subDays(3)->toDateString())
            ->orderByDesc('plan_date')
            ->get(['tasks'])
            ->flatMap(function (StudyPlan $plan) use ($courseId) {
                return collect(data_get($plan->tasks, 'courses.' . $courseId, []))
                    ->filter(fn ($task) => is_array($task) && !empty($task['id']) && empty($task['completed']))
                    ->pluck('id');
            })
            ->unique()
            ->values()
            ->all();
    }

    private function resolveOverallAccuracy(User $user): float
    {
        $attempted = (int) ($user->total_questions_attempted ?? 0);
        if ($attempted <= 0) {
            return 0;
        }

        return round((((int) $user->getTotalCorrectAnswers()) / $attempted) * 100, 2);
    }

    private function buildAttemptSignals(User $user, array $courseTopicIds): array
    {
        $courseTopicIds = array_values(array_unique(array_map('intval', $courseTopicIds)));
        $attemptSignalColumns = QuizAttempt::availableColumns([
            'study_mode',
            'session_type',
            'quiz_type',
            'source',
            'score',
            'total_questions',
            'duration_seconds',
            'percentage_grade',
            'topic_id',
            'created_at',
        ]);
        $randomAttemptColumns = QuizAttempt::availableColumns([
            'study_mode',
            'session_type',
            'quiz_type',
            'source',
            'score',
            'total_questions',
            'duration_seconds',
            'percentage_grade',
            'topic_id',
            'created_at',
        ]);

        $topicAttemptsToday = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', '>=', now()->subDays(90)->toDateString())
            ->whereIn('topic_id', $courseTopicIds)
            ->whereNotNull('topic_id')
            ->get($attemptSignalColumns)
            ->map(fn (QuizAttempt $attempt) => [
                'study_mode' => $attempt->study_mode ?: ($attempt->quiz_type === 'random' ? 'quiz' : 'quiz'),
                'session_type' => $attempt->session_type,
                'quiz_type' => $attempt->quiz_type,
                'source' => $attempt->source,
                'score' => (int) ($attempt->score ?? 0),
                'total_questions' => (int) $attempt->total_questions,
                'duration_seconds' => (int) $attempt->duration_seconds,
                'percentage_grade' => (float) ($attempt->percentage_grade ?? 0.0),
                'topic_id' => $attempt->topic_id ? (int) $attempt->topic_id : null,
                'created_at' => $attempt->created_at?->toIso8601String(),
            ])
            ->all();

        $randomAttemptsToday = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', '>=', now()->subDays(90)->toDateString())
            ->where('quiz_type', 'random')
            ->get($randomAttemptColumns)
            ->map(fn (QuizAttempt $attempt) => [
                'study_mode' => $attempt->study_mode ?: 'quiz',
                'session_type' => $attempt->session_type,
                'quiz_type' => $attempt->quiz_type,
                'source' => $attempt->source,
                'score' => (int) ($attempt->score ?? 0),
                'total_questions' => (int) $attempt->total_questions,
                'duration_seconds' => (int) $attempt->duration_seconds,
                'percentage_grade' => (float) ($attempt->percentage_grade ?? 0.0),
                'topic_id' => $attempt->topic_id ? (int) $attempt->topic_id : null,
                'created_at' => $attempt->created_at?->toIso8601String(),
            ])
            ->all();

        $mistakeReviewAttemptsToday = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', '>=', now()->subDays(90)->toDateString())
            ->where(function ($query) {
                $query->where('quiz_type', 'mistake_review')
                    ->orWhere('study_mode', 'mistake_review');
            })
            ->get(['topic_id', 'total_questions']);

        $mistakeReviewsTodayByTopic = $mistakeReviewAttemptsToday
            ->filter(fn (QuizAttempt $attempt) => $attempt->topic_id !== null && in_array((int) $attempt->topic_id, $courseTopicIds, true))
            ->groupBy(fn (QuizAttempt $attempt) => (int) $attempt->topic_id)
            ->map(fn (Collection $attempts) => (int) $attempts->sum('total_questions'))
            ->all();

        return [
            'topic_attempts_today' => $topicAttemptsToday,
            'random_attempts_today' => $randomAttemptsToday,
            'mistake_reviews_today' => (int) $mistakeReviewAttemptsToday->sum('total_questions'),
            'mistake_reviews_today_in_course' => array_sum($mistakeReviewsTodayByTopic),
            'mistake_reviews_today_by_topic' => $mistakeReviewsTodayByTopic,
            'course_topic_ids' => $courseTopicIds,
        ];
    }

    private function buildDueMistakeSignals(array $backlogSignals): array
    {
        return [
            'total_due' => (int) ($backlogSignals['due_spaced_reviews'] ?? 0),
            'by_topic' => collect($backlogSignals['by_topic'] ?? [])
                ->filter(fn (array $signal) => (int) ($signal['due_spaced_reviews'] ?? 0) > 0)
                ->map(fn (array $signal) => [
                    'topic_id' => (int) ($signal['topic_id'] ?? 0),
                    'due_count' => (int) ($signal['due_spaced_reviews'] ?? 0),
                    'assigned_mistake_ids' => array_map('intval', $signal['due_mistake_ids'] ?? []),
                    'minutes_overdue' => (int) ($signal['minutes_overdue'] ?? 0),
                    'average_risk' => (float) ($signal['average_risk'] ?? 0.0),
                    'active_open_count' => (int) ($signal['active_open_backlog'] ?? 0),
                    'deferred_count' => (int) ($signal['deferred_skipped_backlog'] ?? 0),
                    'recent_unresolved_count' => (int) ($signal['recent_unresolved_count'] ?? 0),
                    'stale_skipped_count' => (int) ($signal['stale_skipped_count'] ?? 0),
                ])
                ->all(),
            'total_due' => (int) ($backlogSignals['due_spaced_reviews'] ?? 0),
        ];
    }

    private function buildConceptSignals(User $user, array $courseTopicIds): array
    {
        return $this->conceptAnalyticsService->buildSignals($user, $courseTopicIds);
    }

    private function buildTopicSignals(
        User $user,
        Collection $topics,
        array $backlogSignals,
        array $assignmentSignals,
        array $conceptSignals,
        ?int $daysToExam,
        ?string $preferredSource
    ): array
    {
        $topicIds = $topics->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (empty($topicIds)) {
            return [
                'attempted_topic_ids' => [],
                'source_percentages' => [],
                'source_totals' => [],
                'source_samples' => [],
                'topic_rolling_accuracy' => [],
                'last_activity_hours' => [],
                'profiles' => [],
            ];
        }

        $attemptedTopicIds = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('topic_id', $topicIds)
            ->whereNotNull('topic_id')
            ->distinct()
            ->pluck('topic_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $sourceTotalsByTopic = Question::query()
            ->whereIn('topic_id', $topicIds)
            ->whereNotNull('source')
            ->selectRaw('topic_id, source, COUNT(*) as total')
            ->groupBy('topic_id', 'source')
            ->get()
            ->groupBy('topic_id');

        $recentQuizAttempts = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('topic_id', $topicIds)
            ->whereNotNull('topic_id')
            ->whereDate('created_at', '>=', now()->subDays(90)->toDateString())
            ->where('total_questions', '>', 0)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(QuizAttempt::availableColumns([
                'id',
                'topic_id',
                'study_mode',
                'source',
                'score',
                'percentage_grade',
                'total_questions',
                'session_type',
                'created_at',
            ]));

        $tutorAttemptsByTopicSource = $recentQuizAttempts
            ->filter(fn (QuizAttempt $attempt) => (string) ($attempt->study_mode ?? '') === 'tutor')
            ->groupBy(fn (QuizAttempt $attempt) => (int) $attempt->topic_id . '|' . (string) ($attempt->source ?? ''));

        $lastTutorDateByTopic = $recentQuizAttempts
            ->filter(fn (QuizAttempt $attempt) => (string) ($attempt->study_mode ?? '') === 'tutor')
            ->groupBy('topic_id')
            ->map(fn (Collection $attempts) => $attempts->first()->created_at);

        $recentTopicAttempts = $recentQuizAttempts
            ->groupBy('topic_id')
            ->map(function (Collection $attempts) use ($lastTutorDateByTopic) {
                $window = $attempts->take(5);
                $lastTutorDate = $lastTutorDateByTopic->get((int) ($window->first()?->topic_id ?? 0));

                $failedQuizCount = $window
                    ->filter(function (QuizAttempt $attempt) use ($lastTutorDate) {
                        if ((string) ($attempt->study_mode ?? '') !== 'quiz') {
                            return false;
                        }
                        if ($lastTutorDate && $attempt->created_at->lt($lastTutorDate)) {
                            return false;
                        }

                        return (float) ($attempt->percentage_grade ?? (($attempt->total_questions > 0) ? ($attempt->score / $attempt->total_questions) * 100 : 0.0)) < 70;
                    })
                    ->count();

                return [
                    'accuracy' => $this->weightedAttemptAccuracy($window),
                    'failed_quiz_count' => $failedQuizCount,
                    'hours_since_last_activity' => $window->first()?->created_at
                        ? Carbon::parse($window->first()->created_at)->diffInHours(now())
                        : null,
                ];
            });

        $recentSourceAttempts = $recentQuizAttempts
            ->filter(fn (QuizAttempt $attempt) => !empty($attempt->source))
            ->groupBy(fn (QuizAttempt $attempt) => (int) $attempt->topic_id . '|' . (string) $attempt->source);

        $sourcePercentages = [];
        $sourceTotals = [];
        $sourceSamples = [];
        $sourceFailedQuizCounts = [];
        $topicRollingAccuracy = [];
        $lastActivityHours = [];

        foreach ($topicIds as $topicId) {
            $topicId = (int) $topicId;
            $topicProgress = $topics->firstWhere('id', $topicId)?->userProgress->first();
            $fallbackLastAttemptDate = $topicProgress?->last_attempt_date;
            $fallbackLastActivityHours = $fallbackLastAttemptDate
                ? Carbon::parse($fallbackLastAttemptDate)->diffInHours(now())
                : null;
            $topicStats = $recentTopicAttempts->get($topicId);
            $topicAccuracy = $topicStats['accuracy']
                ?? (float) ($topicProgress?->percentage_grade ?? 0.0);
            $topicRollingAccuracy[$topicId] = round((float) $topicAccuracy, 2);
            $lastActivityHours[$topicId] = $topicStats['hours_since_last_activity']
                ?? $fallbackLastActivityHours;

            $topicSourceTotals = ($sourceTotalsByTopic->get($topicId) ?? collect())
                ->pluck('total', 'source')
                ->map(fn ($total) => (int) $total)
                ->toArray();

            $sourceTotals[$topicId] = $topicSourceTotals;

            foreach ($topicSourceTotals as $source => $total) {
                $window = collect($recentSourceAttempts->get($topicId . '|' . $source, collect()))
                    ->take(5)
                    ->values();
                $sampleCount = $window->count();
                $sourceSamples[$topicId][$source] = $sampleCount;

                $lastTutorDate = $lastTutorDateByTopic->get($topicId);
                $sourceFailedQuizCounts[$topicId][$source] = $window
                    ->filter(function (QuizAttempt $attempt) use ($lastTutorDate) {
                        if ((string) ($attempt->study_mode ?? '') !== 'quiz') {
                            return false;
                        }
                        if ($lastTutorDate && $attempt->created_at->lt($lastTutorDate)) {
                            return false;
                        }

                        return (float) ($attempt->percentage_grade ?? (($attempt->total_questions > 0) ? ($attempt->score / $attempt->total_questions) * 100 : 0.0)) < 70;
                    })
                    ->count();

                if ($sampleCount >= 2) {
                    $sourcePercentages[$topicId][$source] = $this->weightedAttemptAccuracy($window);
                    continue;
                }

                $sourcePercentages[$topicId][$source] = $sampleCount > 0
                    ? $topicRollingAccuracy[$topicId]
                    : 0.0;
            }
        }

        $profiles = $this->buildTopicProfiles(
            $topics,
            $topicRollingAccuracy,
            $lastActivityHours,
            $sourceTotals,
            $sourceSamples,
            $sourcePercentages,
            $sourceFailedQuizCounts,
            $backlogSignals,
            $assignmentSignals,
            $conceptSignals,
            $daysToExam,
            $preferredSource,
            $tutorAttemptsByTopicSource->all(),
            $recentQuizAttempts
        );

        $preferredSourcePhaseActive = collect($profiles)
            ->contains(fn (array $profile) => (bool) ($profile['preferred_source_pending'] ?? false));

        return [
            'attempted_topic_ids' => $attemptedTopicIds,
            'source_percentages' => $sourcePercentages,
            'source_totals' => $sourceTotals,
            'source_samples' => $sourceSamples,
            'topic_rolling_accuracy' => $topicRollingAccuracy,
            'last_activity_hours' => $lastActivityHours,
            'preferred_source_phase_active' => $preferredSourcePhaseActive ?? false,
            'profiles' => $profiles,
        ];
    }

    private function buildTopicProfiles(
        Collection $topics,
        array $topicRollingAccuracy,
        array $lastActivityHours,
        array $sourceTotals,
        array $sourceSamples,
        array $sourcePercentages,
        array $sourceFailedQuizCounts,
        array $backlogSignals,
        array $assignmentSignals,
        array $conceptSignals,
        ?int $daysToExam,
        ?string $preferredSource,
        array $tutorAttemptsBySource,
        Collection $recentQuizAttempts
    ): array {
        $dueByTopic = collect($backlogSignals['by_topic'] ?? [])->keyBy('topic_id');
        $assignmentByTopic = collect($assignmentSignals['topics'] ?? [])->keyBy('topic_id');
        $maxDue = max(1, (int) collect($backlogSignals['by_topic'] ?? [])->max('due_spaced_reviews'));
        $examUrgency = $this->resolveExamUrgencyScore($daysToExam);
        $orderedTopics = $topics
            ->sortBy(fn (Topic $topic) => sprintf(
                '%010d-%010d',
                (int) ($topic->display_order ?? PHP_INT_MAX),
                (int) $topic->id
            ))
            ->values();

        $profiles = $orderedTopics->mapWithKeys(function (Topic $topic) use (
            $topicRollingAccuracy,
            $lastActivityHours,
            $sourceTotals,
            $sourceSamples,
            $sourcePercentages,
            $sourceFailedQuizCounts,
            $dueByTopic,
            $assignmentByTopic,
            $conceptSignals,
            $maxDue,
            $examUrgency,
            $preferredSource,
            $tutorAttemptsBySource,
            $recentQuizAttempts
        ) {
            $topicId = (int) $topic->id;
            $topicDueSignal = $dueByTopic->get($topicId, []);
            $topicAssignmentSignal = $assignmentByTopic->get($topicId, []);
            $topicProgress = $topic->userProgress->first();

            $accuracy = (float) ($topicRollingAccuracy[$topicId] ?? 0.0);
            $hoursSinceLastActivity = $lastActivityHours[$topicId] ?? null;

            $topicSources = $sourceTotals[$topicId] ?? [];
            $totalSources = count($topicSources);
            $attemptedSources = collect($sourceSamples[$topicId] ?? [])->filter(fn ($samples) => (int) $samples > 0)->count();
            $coverageGap = $totalSources > 0
                ? max(0.0, min(1.0, 1 - ($attemptedSources / max(1, $totalSources))))
                : ((float) $accuracy <= 0.0 ? 1.0 : 0.0);

            $hasPreferredSourceMaterial = $preferredSource !== null && array_key_exists($preferredSource, $topicSources);
            $preferredSourceSamples = $hasPreferredSourceMaterial ? (int) ($sourceSamples[$topicId][$preferredSource] ?? 0) : 0;
            $preferredSourceAccuracy = $hasPreferredSourceMaterial ? (float) ($sourcePercentages[$topicId][$preferredSource] ?? 0.0) : null;
            $topicHasBlockingBacklog = (int) ($topicDueSignal['reviewable_backlog'] ?? 0) > 0;
            $topicHasDueReview = (int) ($topicDueSignal['due_spaced_reviews'] ?? 0) > 0;
            $hasPersistedProgressionState = $topicProgress !== null && (
                $topicProgress->planner_tutor_cleared_at !== null
                || $topicProgress->planner_quiz_cleared_at !== null
                || $topicProgress->planner_cleared_at !== null
            );

            $resolvedTutorClearedAt = $hasPersistedProgressionState
                ? $topicProgress?->planner_tutor_cleared_at
                : $this->inferTutorClearedAt(
                    $recentQuizAttempts,
                    $topicId,
                    $preferredSource,
                    $hasPreferredSourceMaterial,
                    $tutorAttemptsBySource
                );
            $stageTwoStats = $this->resolveStageTwoQuizStats(
                $recentQuizAttempts,
                $topicId,
                $preferredSource,
                $hasPreferredSourceMaterial,
                $resolvedTutorClearedAt
            );
            $resolvedQuizClearedAt = $hasPersistedProgressionState
                ? $topicProgress?->planner_quiz_cleared_at
                : $this->inferQuizClearedAt($stageTwoStats);
            $resolvedClearedAt = $hasPersistedProgressionState
                ? $topicProgress?->planner_cleared_at
                : $this->inferClearedAt($resolvedQuizClearedAt, $topicHasBlockingBacklog);

            $progressionAccuracy = $hasPreferredSourceMaterial ? $preferredSourceAccuracy : $accuracy;
            $progressionCoverageGap = $hasPreferredSourceMaterial
                ? ($preferredSourceSamples > 0 ? 0.0 : 1.0)
                : $coverageGap;

            $dueUrgency = $this->resolveDueUrgencyScore($topicDueSignal, $maxDue);
            $deadlinePressure = $this->resolveAssignmentUrgencyScore($topicAssignmentSignal);

            $conceptProfile = $conceptSignals['topics'][$topicId] ?? [];
            $conceptRisk = (float) ($conceptProfile['risk_score'] ?? 0.0);
            $weakConcepts = (array) ($conceptProfile['weak_concepts'] ?? []);

            $neglect = $hoursSinceLastActivity === null
                ? 0.5
                : max(0.0, min(1.0, $hoursSinceLastActivity / 96));

            $sourceAffinityWeight = $this->resolveSourceAffinityWeight(
                $topicId,
                $preferredSource,
                $sourceTotals,
                $sourceSamples,
                $sourcePercentages,
                $progressionCoverageGap
            );

            $progressionWeakness = max(0.0, min(1.0, 1 - ($progressionAccuracy / 100)));
            $priorityScore = round((
                ($dueUrgency * 30)
                + ($deadlinePressure * 25)
                + ($progressionWeakness * 20)
                + ($progressionCoverageGap * 15)
                + ($neglect * 10)
                + ($sourceAffinityWeight * 8)
            ), 2);

            $hasRecordedActivity = $hoursSinceLastActivity !== null || $attemptedSources > 0;
            $hasTopicBacklog = !empty($topicDueSignal) && (int) ($topicDueSignal['total_backlog'] ?? 0) > 0;

            $tutorCleared = $resolvedTutorClearedAt !== null
                || $resolvedQuizClearedAt !== null
                || $resolvedClearedAt !== null;
            $quizCleared = $resolvedQuizClearedAt !== null || $resolvedClearedAt !== null;
            $backlogCleared = $resolvedClearedAt !== null || !$topicHasBlockingBacklog;

            $failedQuizCount = $hasPreferredSourceMaterial
                ? (int) ($sourceFailedQuizCounts[$topicId][$preferredSource] ?? 0)
                : (int) collect($sourceFailedQuizCounts[$topicId] ?? [])->sum();
            $quizFailedRepeatedly = $failedQuizCount >= 2;

            $progressionCleared = $resolvedClearedAt !== null;
            $latestStructuredFailureAt = $this->resolveLatestStructuredFailureAt(
                $recentQuizAttempts,
                $topicId,
                $preferredSource,
                $hasPreferredSourceMaterial
            );
            $isRegressed = $resolvedClearedAt !== null
                && $latestStructuredFailureAt !== null
                && $latestStructuredFailureAt->gt($resolvedClearedAt->copy()->addHours(48));

            $progressionStage = match (true) {
                $isRegressed => 'quiz',
                $resolvedClearedAt !== null => 'cleared',
                $resolvedQuizClearedAt !== null => 'mistakes',
                $resolvedTutorClearedAt !== null => 'quiz',
                default => 'tutor',
            };

            $isUntouched = $hasPreferredSourceMaterial
                ? (!$tutorCleared && $preferredSourceSamples === 0 && !$hasTopicBacklog)
                : ($accuracy <= 0.0 && empty($topicDueSignal) && !$hasRecordedActivity && !$hasTopicBacklog);

            $isReviewHeavy = $topicHasDueReview;
            $isWeakStale = !$isUntouched && !$isReviewHeavy && $hoursSinceLastActivity !== null && $hoursSinceLastActivity > 48 && ($progressionAccuracy < 72 || $conceptRisk >= 0.9);
            $isWeak = !$isUntouched && (
                $priorityScore >= 55
                || $progressionAccuracy < 72
                || $conceptRisk >= 0.95
                || $isWeakStale
                || $isRegressed
            );
            $isSecure = $progressionStage === 'cleared' && !$topicHasBlockingBacklog && !$isReviewHeavy && !$isWeak && $progressionAccuracy >= 72;

            $state = match (true) {
                $isUntouched => 'untouched',
                $isRegressed => 'regressed',
                $isReviewHeavy => 'review_heavy',
                $isWeakStale => 'weak_stale',
                $isWeak => 'weak_recent',
                $isSecure => 'secure',
                default => 'steady',
            };
            $recommendedStudyMode = match ($state) {
                'untouched' => 'tutor',
                'weak_stale' => 'quiz',
                'review_heavy' => 'mistake_review',
                default => 'quiz',
            };

            return [$topicId => [
                'priority_score' => $priorityScore,
                'accuracy' => round($accuracy, 2),
                'progression_accuracy' => round($progressionAccuracy, 2),
                'coverage_gap' => round($coverageGap, 4),
                'progression_coverage_gap' => round($progressionCoverageGap, 4),
                'neglect' => round($neglect, 4),
                'due_urgency' => round($dueUrgency, 4),
                'deadline_pressure' => round($deadlinePressure, 4),
                'concept_risk' => round($conceptRisk, 4),
                'source_affinity_weight' => round($sourceAffinityWeight ?? 0.0, 4),
                'weak_concepts' => $weakConcepts,
                'hours_since_last_activity' => $hoursSinceLastActivity,
                'has_preferred_source_material' => $hasPreferredSourceMaterial,
                'preferred_source_samples' => $preferredSourceSamples,
                'preferred_source_accuracy' => $preferredSourceAccuracy,
                'stage_two_question_count' => (int) ($stageTwoStats['question_count'] ?? 0),
                'stage_two_accuracy' => round((float) ($stageTwoStats['accuracy'] ?? 0.0), 2),
                'stage_two_fallback_used' => (bool) ($stageTwoStats['fallback_used'] ?? false),
                'stage_two_pass_ready' => (bool) ($stageTwoStats['pass_ready'] ?? false),
                'tutor_cleared' => $tutorCleared,
                'quiz_cleared' => $quizCleared,
                'backlog_cleared' => $backlogCleared,
                'progression_cleared' => $progressionCleared && !$isRegressed,
                'progression_stage' => $progressionStage,
                'is_regressed' => $isRegressed,
                'quiz_failed_repeatedly' => $quizFailedRepeatedly,
                'preferred_source_pending' => $hasPreferredSourceMaterial && !$progressionCleared,
                'has_reviewable_backlog' => $topicHasBlockingBacklog,
                'has_due_review' => $topicHasDueReview,
                'resolved_tutor_cleared_at' => $resolvedTutorClearedAt?->toIso8601String(),
                'resolved_quiz_cleared_at' => $resolvedQuizClearedAt?->toIso8601String(),
                'resolved_cleared_at' => $resolvedClearedAt?->toIso8601String(),
                'last_attempt_at' => $topicProgress?->last_attempt_date?->toIso8601String(),
                'has_persisted_progression_state' => $hasPersistedProgressionState,
                'is_untouched' => $isUntouched,
                'is_review_heavy' => $isReviewHeavy,
                'is_weak' => $isWeak,
                'is_secure' => $isSecure,
                'topic_state' => $state,
                'recommended_study_mode' => $recommendedStudyMode,
                'curriculum_unlocked' => false,
            ]];
        })->all();

        $previousTopicCleared = true;
        $frontierResolved = false;
        foreach ($orderedTopics as $topic) {
            $topicId = (int) $topic->id;
            $profiles[$topicId]['curriculum_unlocked'] = $previousTopicCleared;

            if ((bool) ($profiles[$topicId]['curriculum_unlocked'] ?? false)
                && !($profiles[$topicId]['has_persisted_progression_state'] ?? false)
                && (float) ($profiles[$topicId]['progression_accuracy'] ?? 0.0) >= 70.0
                && $profiles[$topicId]['resolved_tutor_cleared_at'] === null) {
                $profiles[$topicId]['tutor_cleared'] = true;
                $profiles[$topicId]['resolved_tutor_cleared_at'] = $profiles[$topicId]['resolved_quiz_cleared_at']
                    ?? ($profiles[$topicId]['last_attempt_at'] ?? null);
                $profiles[$topicId]['is_untouched'] = false;
                $profiles[$topicId]['recommended_study_mode'] = 'quiz';

                if ((string) ($profiles[$topicId]['progression_stage'] ?? '') === 'tutor') {
                    $profiles[$topicId]['progression_stage'] = 'quiz';
                }
            }

            $isFrontierTopic = !$frontierResolved
                && (bool) ($profiles[$topicId]['curriculum_unlocked'] ?? false)
                && (string) ($profiles[$topicId]['progression_stage'] ?? '') !== 'cleared';

            if ($isFrontierTopic) {
                $frontierResolved = true;
            }

            $previousTopicCleared = (bool) ($profiles[$topicId]['progression_stage'] === 'cleared');
        }

        return $profiles;
    }

    private function inferTutorClearedAt(
        Collection $recentQuizAttempts,
        int $topicId,
        ?string $preferredSource,
        bool $hasPreferredSourceMaterial,
        array $tutorAttemptsBySource
    ): ?Carbon {
        $preferredKey = $topicId . '|' . (string) $preferredSource;
        if ($hasPreferredSourceMaterial && !empty($tutorAttemptsBySource[$preferredKey] ?? [])) {
            return collect($tutorAttemptsBySource[$preferredKey])
                ->map(fn (QuizAttempt $attempt) => $attempt->created_at instanceof Carbon ? $attempt->created_at : ($attempt->created_at ? Carbon::parse($attempt->created_at) : null))
                ->filter()
                ->sortDesc()
                ->first();
        }

        $topicTutorAttempt = $recentQuizAttempts
            ->filter(fn (QuizAttempt $attempt) => (int) ($attempt->topic_id ?? 0) === $topicId)
            ->filter(fn (QuizAttempt $attempt) => (string) ($attempt->study_mode ?? '') === 'tutor')
            ->filter(function (QuizAttempt $attempt) use ($preferredSource, $hasPreferredSourceMaterial) {
                if (!$hasPreferredSourceMaterial || $preferredSource === null) {
                    return true;
                }

                return strcasecmp((string) ($attempt->source ?? ''), $preferredSource) === 0;
            })
            ->sortByDesc(fn (QuizAttempt $attempt) => $attempt->created_at?->timestamp ?? 0)
            ->first();

        return $topicTutorAttempt?->created_at instanceof Carbon
            ? $topicTutorAttempt->created_at
            : ($topicTutorAttempt?->created_at ? Carbon::parse($topicTutorAttempt->created_at) : null);
    }

    private function resolveStageTwoQuizStats(
        Collection $recentQuizAttempts,
        int $topicId,
        ?string $preferredSource,
        bool $hasPreferredSourceMaterial,
        ?Carbon $resolvedTutorClearedAt
    ): array {
        $structuredQuizAttempts = $recentQuizAttempts
            ->filter(fn (QuizAttempt $attempt) => (int) ($attempt->topic_id ?? 0) === $topicId)
            ->filter(fn (QuizAttempt $attempt) => (string) ($attempt->study_mode ?? '') === 'quiz')
            ->reject(fn (QuizAttempt $attempt) => (string) ($attempt->session_type ?? '') === 'random_quiz')
            ->filter(function (QuizAttempt $attempt) use ($resolvedTutorClearedAt) {
                if (!$resolvedTutorClearedAt instanceof Carbon) {
                    return true;
                }

                return $attempt->created_at instanceof Carbon
                    ? $attempt->created_at->gte($resolvedTutorClearedAt)
                    : Carbon::parse($attempt->created_at)->gte($resolvedTutorClearedAt);
            })
            ->values();

        $relevantAttempts = $structuredQuizAttempts;
        $fallbackUsed = false;
        $sourceUsed = null;

        if ($hasPreferredSourceMaterial && $preferredSource !== null) {
            $relevantAttempts = $structuredQuizAttempts
                ->filter(fn (QuizAttempt $attempt) => strcasecmp((string) ($attempt->source ?? ''), $preferredSource) === 0)
                ->values();
            $sourceUsed = $preferredSource;
        } else {
            $fallbackUsed = true;
        }

        $questionCount = (int) $relevantAttempts->sum(fn (QuizAttempt $attempt) => max(0, (int) ($attempt->total_questions ?? 0)));
        $correctCount = (int) $relevantAttempts->sum(fn (QuizAttempt $attempt) => max(0, (int) ($attempt->score ?? 0)));
        $accuracy = $questionCount > 0
            ? round(($correctCount / max(1, $questionCount)) * 100, 2)
            : 0.0;

        $latestPassedAt = $questionCount > 0 && (
            ($hasPreferredSourceMaterial && $questionCount >= 2 && $accuracy >= 70.0)
            || (!$hasPreferredSourceMaterial && $accuracy >= 70.0)
        )
            ? $relevantAttempts
                ->sortByDesc(fn (QuizAttempt $attempt) => $attempt->created_at?->timestamp ?? 0)
                ->first()?->created_at
            : null;

        return [
            'question_count' => $questionCount,
            'accuracy' => $accuracy,
            'fallback_used' => $fallbackUsed,
            'source' => $sourceUsed,
            'pass_ready' => $hasPreferredSourceMaterial
                ? ($questionCount >= 2 && $accuracy >= 70.0)
                : ($questionCount > 0 && $accuracy >= 70.0),
            'latest_passed_at' => $latestPassedAt instanceof Carbon
                ? $latestPassedAt
                : ($latestPassedAt ? Carbon::parse($latestPassedAt) : null),
        ];
    }

    private function inferQuizClearedAt(array $stageTwoStats): ?Carbon
    {
        if (!((bool) ($stageTwoStats['pass_ready'] ?? false))) {
            return null;
        }

        $latestPassedAt = $stageTwoStats['latest_passed_at'] ?? null;
        if ($latestPassedAt instanceof Carbon) {
            return $latestPassedAt;
        }

        return $latestPassedAt ? Carbon::parse($latestPassedAt) : null;
    }

    private function inferClearedAt(?Carbon $resolvedQuizClearedAt, bool $topicHasBlockingBacklog): ?Carbon
    {
        if (!$resolvedQuizClearedAt instanceof Carbon || $topicHasBlockingBacklog) {
            return null;
        }

        return $resolvedQuizClearedAt;
    }

    private function resolveLatestStructuredFailureAt(
        Collection $recentQuizAttempts,
        int $topicId,
        ?string $preferredSource,
        bool $hasPreferredSourceMaterial
    ): ?Carbon {
        $latestFailedAttempt = $recentQuizAttempts
            ->filter(fn (QuizAttempt $attempt) => (int) ($attempt->topic_id ?? 0) === $topicId)
            ->filter(fn (QuizAttempt $attempt) => (string) ($attempt->study_mode ?? '') === 'quiz')
            ->reject(fn (QuizAttempt $attempt) => (string) ($attempt->session_type ?? '') === 'random_quiz')
            ->filter(function (QuizAttempt $attempt) use ($preferredSource, $hasPreferredSourceMaterial) {
                if (!$hasPreferredSourceMaterial || $preferredSource === null) {
                    return true;
                }

                return strcasecmp((string) ($attempt->source ?? ''), $preferredSource) === 0;
            })
            ->filter(function (QuizAttempt $attempt) {
                $percentage = $attempt->percentage_grade;
                if ($percentage === null) {
                    $totalQuestions = max(0, (int) ($attempt->total_questions ?? 0));
                    $percentage = $totalQuestions > 0
                        ? (((int) ($attempt->score ?? 0)) / $totalQuestions) * 100
                        : 0.0;
                }

                return (float) $percentage < 70.0;
            })
            ->sortByDesc(fn (QuizAttempt $attempt) => $attempt->created_at?->timestamp ?? 0)
            ->first();

        return $latestFailedAttempt?->created_at instanceof Carbon
            ? $latestFailedAttempt->created_at
            : ($latestFailedAttempt?->created_at ? Carbon::parse($latestFailedAttempt->created_at) : null);
    }

    private function resolveCoursePlannerSettings(
        User $user,
        Course $course,
        StudyPlan $plan,
        array $globalAllocation,
        int $totalDailyStudyMinutes
    ): array {
        $plannerColumns = $this->availableCoursePlannerColumns();
        $pivot = empty($plannerColumns)
            ? null
            : DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first($plannerColumns);

        $allocatedMinutes = max(
            15,
            (int) data_get($globalAllocation, 'courses.' . $course->id . '.allocated_minutes', $totalDailyStudyMinutes)
        );
        $configuredDailyMinutes = (int) ($pivot->planner_daily_study_minutes ?? $user->daily_study_minutes ?? 90);
        $effectiveDailyMinutes = (int) ($pivot->planner_daily_study_minutes ?? $allocatedMinutes);
        $plannerFocus = (string) ($pivot->planner_focus ?? $user->planner_focus ?? 'balanced');
        $preferredSource = $pivot->planner_preferred_source ?? $this->resolvePreferredSource($user);
        $examDate = $this->resolveExamDate($user, $plan, $pivot->planner_exam_date ?? null);
        $classicRepairDifficulty = $this->resolveClassicRepairDifficulty($user, $pivot);
        $repairMode = $this->resolveRepairMode($user, $course, $pivot, $classicRepairDifficulty);

        return [
            'exam_date' => $examDate,
            'exam_date_iso' => $examDate?->format('Y-m-d'),
            'configured_daily_study_minutes' => max(15, $configuredDailyMinutes),
            'effective_daily_study_minutes' => max(15, min(480, $effectiveDailyMinutes)),
            'allocated_minutes' => $allocatedMinutes,
            'planner_focus' => $plannerFocus,
            'preferred_source' => $preferredSource,
            'repair_mode' => $repairMode,
            'classic_repair_difficulty' => $classicRepairDifficulty,
            'course_overrides' => [
                'planner_exam_date' => $pivot->planner_exam_date ?? null,
                'planner_target_score' => $pivot->planner_target_score ?? null,
                'planner_daily_study_minutes' => $pivot->planner_daily_study_minutes ?? null,
                'planner_focus' => $pivot->planner_focus ?? null,
                'planner_preferred_source' => $pivot->planner_preferred_source ?? null,
                'planner_repair_mode' => $pivot->planner_repair_mode ?? null,
                'planner_classic_repair_difficulty' => $pivot->planner_classic_repair_difficulty ?? null,
            ],
        ];
    }

    private function availableCoursePlannerColumns(): array
    {
        if (self::$availableCoursePlannerColumns !== null) {
            return self::$availableCoursePlannerColumns;
        }

        $plannerColumns = [
            'planner_exam_date',
            'planner_target_score',
            'planner_daily_study_minutes',
            'planner_focus',
            'planner_preferred_source',
            'planner_repair_mode',
            'planner_classic_repair_difficulty',
        ];

        self::$availableCoursePlannerColumns = array_values(array_filter(
            $plannerColumns,
            static fn (string $column): bool => Schema::hasColumn('course_user', $column)
        ));

        return self::$availableCoursePlannerColumns;
    }

    private function resolvePreferredSource(User $user): ?string
    {
        $university = strtolower(trim((string) ($user->university ?? '')));

        return match (true) {
            str_contains($university, 'assuit'), str_contains($university, 'assiut'), str_contains($university, 'asyut') => 'Assuit',
            str_contains($university, 'cairo') => 'Cairo',
            str_contains($university, 'alex') => 'Alexandria',
            str_contains($university, 'mansoura') => 'Mansoura',
            str_contains($university, 'ain shams'), str_contains($university, 'ainshams') => 'Ain Shams',
            default => null,
        };
    }

    private function resolveDueUrgencyScore(array $topicDueSignal, int $maxDue): float
    {
        $dueCount = (int) ($topicDueSignal['due_spaced_reviews'] ?? 0);
        $minutesOverdue = (int) ($topicDueSignal['minutes_overdue'] ?? 0);
        $averageRisk = (float) ($topicDueSignal['average_risk'] ?? 0.0);

        if ($dueCount <= 0) {
            return 0.0;
        }

        $countScore = min(1.0, $dueCount / max(1, $maxDue));
        $overdueScore = min(1.0, $minutesOverdue / 1440);

        return round(min(1.0, ($countScore * 0.55) + ($averageRisk * 0.30) + ($overdueScore * 0.15)), 4);
    }

    private function resolveExamUrgencyScore(?int $daysToExam): float
    {
        if ($daysToExam === null || $daysToExam > 21) {
            return 0.0;
        }

        if ($daysToExam < 3) {
            return 1.0;
        }

        if ($daysToExam <= 7) {
            return 0.85;
        }

        if ($daysToExam <= 10) {
            return 0.65;
        }

        return 0.35;
    }

    private function resolveAssignmentUrgencyScore(array $topicAssignmentSignal): float
    {
        if ((int) ($topicAssignmentSignal['overdue_assignment_count'] ?? 0) > 0) {
            return 1.0;
        }

        if ((int) ($topicAssignmentSignal['due_within_48h_count'] ?? 0) > 0) {
            return 0.9;
        }

        $nearestDeadline = data_get($topicAssignmentSignal, 'nearest_assignment_deadline');
        if (!$nearestDeadline) {
            return 0.0;
        }

        $deadline = Carbon::parse($nearestDeadline);
        if ($deadline->lte(now()->addDays(7))) {
            return 0.45;
        }

        return 0.15;
    }

    private function resolveSourceAffinityWeight(
        int $topicId,
        ?string $preferredSource,
        array $sourceTotals,
        array $sourceSamples,
        array $sourcePercentages,
        float $coverageGap
    ): float {
        if ($preferredSource === null) {
            return 0.0;
        }

        $topicSources = $sourceTotals[$topicId] ?? [];
        if (!array_key_exists($preferredSource, $topicSources)) {
            return 0.0;
        }

        $sampleCount = (int) data_get($sourceSamples, $topicId . '.' . $preferredSource, 0);
        $sourceAccuracy = (float) data_get($sourcePercentages, $topicId . '.' . $preferredSource, 0.0);
        $preferredCoverageGap = $sampleCount > 0 ? 0.0 : 1.0;
        $sourceWeakness = max(0.0, min(1.0, 1 - ($sourceAccuracy / 100)));
        $topicShare = min(1.0, ((int) ($topicSources[$preferredSource] ?? 0)) / max(1, array_sum($topicSources)));

        return round(
            min(1.0, ($preferredCoverageGap * 0.55) + ($sourceWeakness * 0.30) + ($topicShare * 0.15) + ($coverageGap * 0.10)),
            4
        );
    }

    private function weightedAttemptAccuracy(Collection $attempts): float
    {
        $weightedCorrect = 0.0;
        $weightedQuestions = 0.0;

        foreach ($attempts as $attempt) {
            $weight = $this->attemptWeight($attempt);
            $weightedCorrect += ((int) ($attempt->score ?? 0)) * $weight;
            $weightedQuestions += max(0, (int) ($attempt->total_questions ?? 0)) * $weight;
        }

        if ($weightedQuestions <= 0) {
            return 0.0;
        }

        return round(($weightedCorrect / $weightedQuestions) * 100, 2);
    }

    private function attemptWeight(QuizAttempt $attempt): float
    {
        return $attempt->session_type === 'random_quiz' ? 0.75 : 1.0;
    }

    private function resolveClassicMasteryThreshold(string $classicRepairDifficulty): int
    {
        return $classicRepairDifficulty === 'hard' ? 3 : 2;
    }

    private function resolveClassicRepairDifficulty(User $user, ?object $pivot): string
    {
        $difficulty = $pivot->planner_classic_repair_difficulty
            ?? $user->classic_repair_difficulty
            ?? 'easy';

        return in_array($difficulty, ['easy', 'hard'], true) ? $difficulty : 'easy';
    }

    private function resolveRepairMode(User $user, Course $course, ?object $pivot, string $classicRepairDifficulty): string
    {
        $hasSrAccess = (bool) ($user->has_sr_access ?? false);

        // 1. Course Override
        $courseMode = $pivot->planner_repair_mode ?? null;
        if ($courseMode === 'spaced' && !$hasSrAccess) { 
            $courseMode = null; 
        }
        if (in_array($courseMode, ['easy', 'hard', 'spaced'], true)) {
            return $courseMode;
        }

        // 2. Global Setting
        $globalMode = $user->repair_mode ?? null;
        if ($globalMode === 'spaced' && !$hasSrAccess) { 
            $globalMode = null; 
        }
        if (in_array($globalMode, ['easy', 'hard', 'spaced'], true)) {
            return $globalMode;
        }

        // 3. Inference from 14-day activity (if has access)
        if ($hasSrAccess && $this->hasRecentSpacedReviewActivity($user, $course)) {
            return 'spaced';
        }

        // 4. Default Fallback (Classic)
        return $classicRepairDifficulty;
    }

    private function hasRecentSpacedReviewActivity(User $user, Course $course): bool
    {
        return QuizAttempt::query()
            ->join('topics', 'topics.id', '=', 'quiz_attempts.topic_id')
            ->where('quiz_attempts.user_id', $user->id)
            ->where('topics.course_id', $course->id)
            ->whereDate('quiz_attempts.created_at', '>=', now()->subDays(14)->toDateString())
            ->where(function ($query) {
                $query->where('quiz_attempts.study_mode', 'mistake_review')
                    ->orWhere('quiz_attempts.quiz_type', 'mistake_review');
            })
            ->exists();
    }
}
