<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserTopicProgress;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceTrackingService
{
    private const CACHE_TTL_SECONDS = 600;
    private const WEAK_TOPIC_THRESHOLD = 75.0;
    private const CRITICAL_TOPIC_THRESHOLD = 25.0;
    private const STRUGGLING_TOPIC_THRESHOLD = 50.0;
    private const URGENT_INACTIVITY_HOURS = 48;
    private const MISTAKE_REMINDER_INACTIVITY_HOURS = 24;

    public function getWeakTopicCoach(User $user, int $limit = 5): array
    {
        $cacheKey = "performance_tracking_coach_{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($user, $limit) {
            $topicProgress = $this->getTrackedTopicProgress($user);

            if ($topicProgress->isEmpty()) {
                return [
                    'focus_topic' => null,
                    'topics' => collect(),
                    'summary' => [
                        'weak_topic_count' => 0,
                        'critical_topic_count' => 0,
                        'open_mistakes_count' => 0,
                    ],
                ];
            }

            $topicIds = $topicProgress->pluck('topic_id')->all();
            $mistakesByTopic = $this->getOpenMistakesByTopic($user->id, $topicIds);
            $recentAttemptsByTopic = $this->getRecentAttemptsByTopic($user->id, $topicIds);

            $coachTopics = $topicProgress
                ->map(function ($progress) use ($mistakesByTopic, $recentAttemptsByTopic) {
                    $topicId = (int) $progress->topic_id;
                    $mistakeStats = $mistakesByTopic->get($topicId, [
                        'open_mistakes_count' => 0,
                        'last_mistake_at' => null,
                    ]);
                    $recentAttempts = $recentAttemptsByTopic->get($topicId, collect());

                    $topic = $this->buildCoachTopic($progress, $mistakeStats, $recentAttempts);

                    return $topic['grade'] < self::WEAK_TOPIC_THRESHOLD ? $topic : null;
                })
                ->filter()
                ->sortByDesc('priority_score')
                ->values();

            $focusTopic = $coachTopics->first();

            return [
                'focus_topic' => $focusTopic,
                'topics' => $coachTopics->take(max(1, $limit)),
                'summary' => [
                    'weak_topic_count' => $coachTopics->count(),
                    'critical_topic_count' => $coachTopics->where('severity', 'critical')->count(),
                    'open_mistakes_count' => (int) $coachTopics->sum('open_mistakes_count'),
                ],
            ];
        });
    }

    public function getReminderSnapshot(User $user): array
    {
        $coach = $this->getWeakTopicCoach($user, 5);
        $focusTopic = $coach['focus_topic'];
        $openMistakeCount = (int) ($coach['summary']['open_mistakes_count'] ?? 0);

        $lastMistakeReviewAt = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('quiz_type', 'mistake_review')
                    ->orWhere('study_mode', 'mistake_review');
            })
            ->max('created_at');

        $mistakeReviewInactiveHours = $lastMistakeReviewAt
            ? Carbon::parse($lastMistakeReviewAt)->diffInHours(now())
            : null;

        $shouldSendWeakTopicReminder = false;
        $weakTopicPriority = 'normal';

        if ($focusTopic) {
            $inactiveHours = $focusTopic['hours_since_last_activity'];
            $shouldSendWeakTopicReminder = $focusTopic['severity'] === 'critical'
                ? ($inactiveHours === null || $inactiveHours >= self::URGENT_INACTIVITY_HOURS)
                : ($coach['summary']['critical_topic_count'] > 0 || $coach['summary']['weak_topic_count'] >= 3);

            if ($focusTopic['severity'] === 'critical' && ($inactiveHours === null || $inactiveHours >= self::URGENT_INACTIVITY_HOURS)) {
                $weakTopicPriority = 'urgent';
            } elseif ($focusTopic['severity'] === 'critical' || ($coach['summary']['weak_topic_count'] ?? 0) >= 3) {
                $weakTopicPriority = 'high';
            }
        }

        $shouldSendMistakeReminder = $openMistakeCount > 0
            && ($mistakeReviewInactiveHours === null || $mistakeReviewInactiveHours >= self::MISTAKE_REMINDER_INACTIVITY_HOURS);

        return [
            'coach' => $coach,
            'open_mistakes_count' => $openMistakeCount,
            'mistake_review_inactive_hours' => $mistakeReviewInactiveHours,
            'should_send_mistake_reminder' => $shouldSendMistakeReminder,
            'should_send_weak_topic_reminder' => $shouldSendWeakTopicReminder,
            'weak_topic_priority' => $weakTopicPriority,
        ];
    }

    private function getTrackedTopicProgress(User $user): Collection
    {
        $progress = UserTopicProgress::query()
            ->join('topics', 'user_topic_progress.topic_id', '=', 'topics.id')
            ->join('courses', 'topics.course_id', '=', 'courses.id')
            ->when(!$user->isAdmin() && !$user->isTutor(), function ($query) use ($user) {
                $query->join('course_user', function ($join) use ($user) {
                    $join->on('courses.id', '=', 'course_user.course_id')
                        ->where('course_user.user_id', '=', $user->id)
                        ->where('course_user.is_enrolled', '=', true);
                });
            })
            ->where('user_topic_progress.user_id', $user->id)
            ->whereNotNull('user_topic_progress.percentage_grade')
            ->whereNotNull('user_topic_progress.last_attempt_date')
            ->select([
                'user_topic_progress.topic_id',
                'user_topic_progress.percentage_grade',
                'user_topic_progress.last_attempt_date',
                'topics.name as topic_name',
                'topics.course_id',
                'topics.display_order',
                'topics.is_locked as topic_manual_lock',
                'courses.name as course_name',
            ])
            ->orderBy('user_topic_progress.percentage_grade')
            ->get();

        // 🛡️ SECURITY: Filter out topics that are locked (manually or for preview)
        return $progress->filter(function ($topic) use ($user) {
            // If topic is manually locked, exclude it
            if ($topic->topic_manual_lock) {
                return false;
            }
            
            // If topic is locked for preview users, exclude it
            return !$user->isTopicPreviewLocked($topic->topic_id);
        });
    }

    private function getOpenMistakesByTopic(int $userId, array $topicIds): Collection
    {
        if (empty($topicIds)) {
            return collect();
        }

        return DB::table('mistakes')
            ->join('questions', 'mistakes.question_id', '=', 'questions.id')
            ->where('mistakes.user_id', $userId)
            ->where('mistakes.mastered', false)
            ->where('mistakes.skipped', false)
            ->whereIn('questions.topic_id', $topicIds)
            ->groupBy('questions.topic_id')
            ->selectRaw('questions.topic_id, COUNT(*) as open_mistakes_count, MAX(mistakes.last_attempt_date) as last_mistake_at')
            ->get()
            ->mapWithKeys(function ($row) {
                return [(int) $row->topic_id => [
                    'open_mistakes_count' => (int) $row->open_mistakes_count,
                    'last_mistake_at' => $row->last_mistake_at,
                ]];
            });
    }

    private function getRecentAttemptsByTopic(int $userId, array $topicIds): Collection
    {
        if (empty($topicIds)) {
            return collect();
        }

        $attempts = DB::table('quiz_attempts')
            ->where('user_id', $userId)
            ->whereIn('topic_id', $topicIds)
            ->where('quiz_type', '!=', 'mistake_review')
            ->whereNotNull('percentage_grade')
            ->orderByDesc('created_at')
            ->limit(60)
            ->get([
                'id',
                'topic_id',
                'attempt_id',
                'percentage_grade',
                'created_at',
            ]);

        return $attempts
            ->groupBy('topic_id')
            ->map(function ($topicAttempts) {
                return $topicAttempts
                    ->groupBy(function ($attempt) {
                        return $attempt->attempt_id ?: 'attempt_' . $attempt->id;
                    })
                    ->map(function ($attemptGroup) {
                        $latest = $attemptGroup->sortByDesc('created_at')->first();

                        return [
                            'percentage_grade' => round((float) $attemptGroup->avg('percentage_grade'), 1),
                            'created_at' => $latest->created_at,
                        ];
                    })
                    ->sortByDesc('created_at')
                    ->take(3)
                    ->values();
            });
    }

    private function buildCoachTopic(object $progress, array $mistakeStats, Collection $recentAttempts): array
    {
        $grade = round((float) $progress->percentage_grade, 1);
        $severity = $this->severityForGrade($grade);
        $openMistakesCount = (int) ($mistakeStats['open_mistakes_count'] ?? 0);

        $recentAverage = round((float) ($recentAttempts->take(2)->avg('percentage_grade') ?? $grade), 1);
        $previousAverage = round((float) ($recentAttempts->skip(2)->avg('percentage_grade') ?? $recentAverage), 1);
        $trendDelta = round($recentAverage - $previousAverage, 1);

        $lastActivity = collect([
            $progress->last_attempt_date,
            $recentAttempts->first()['created_at'] ?? null,
            $mistakeStats['last_mistake_at'] ?? null,
        ])->filter()->map(fn ($value) => Carbon::parse($value))->sortDesc()->first();

        $hoursSinceLastActivity = $lastActivity ? $lastActivity->diffInHours(now()) : null;
        $daysSinceLastActivity = $lastActivity ? $lastActivity->diffInDays(now()) : null;

        $priorityScore = (100 - $grade) * 1.3;
        $priorityScore += min($openMistakesCount * 6, 30);
        $priorityScore += $hoursSinceLastActivity ? min(intdiv($hoursSinceLastActivity, 24) * 2, 20) : 12;
        $priorityScore += $severity === 'critical' ? 20 : ($severity === 'struggling' ? 10 : 0);
        $priorityScore += $trendDelta < 0 ? abs($trendDelta) : 0;

        return [
            'topic_id' => (int) $progress->topic_id,
            'topic_name' => $progress->topic_name,
            'course_id' => (int) $progress->course_id,
            'course_name' => $progress->course_name,
            'grade' => $grade,
            'severity' => $severity,
            'severity_icon' => $this->iconForSeverity($severity),
            'severity_label' => ucfirst($severity),
            'open_mistakes_count' => $openMistakesCount,
            'recent_average' => $recentAverage,
            'trend_delta' => $trendDelta,
            'trend_direction' => $trendDelta > 2 ? 'up' : ($trendDelta < -2 ? 'down' : 'flat'),
            'last_activity_at' => $lastActivity?->toIso8601String(),
            'days_since_last_activity' => $daysSinceLastActivity,
            'hours_since_last_activity' => $hoursSinceLastActivity,
            'priority_score' => round($priorityScore, 1),
            'reason_text' => $this->buildReasonText($grade, $openMistakesCount, $daysSinceLastActivity),
            'coach_text' => $this->buildCoachText($severity, $trendDelta, $openMistakesCount, $daysSinceLastActivity),
            'practice_url' => route('quiz.start', ['course' => (int) $progress->course_id, 'topic' => (int) $progress->topic_id], false),
            'mistakes_url' => route('review.learn', [
                'topic_ids' => [(int) $progress->topic_id],
            ], false),
        ];
    }

    private function severityForGrade(float $grade): string
    {
        if ($grade <= self::CRITICAL_TOPIC_THRESHOLD) {
            return 'critical';
        }

        if ($grade < self::STRUGGLING_TOPIC_THRESHOLD) {
            return 'struggling';
        }

        if ($grade < self::WEAK_TOPIC_THRESHOLD) {
            return 'improving';
        }

        return 'healthy';
    }

    private function iconForSeverity(string $severity): string
    {
        return match ($severity) {
            'critical' => 'fas fa-exclamation-circle',
            'struggling' => 'fas fa-exclamation-triangle',
            'improving' => 'fas fa-info-circle',
            default => 'fas fa-check-circle',
        };
    }

    private function buildReasonText(float $grade, int $openMistakesCount, ?int $daysSinceLastActivity): string
    {
        $parts = [number_format($grade, 1) . '% accuracy'];

        if ($openMistakesCount > 0) {
            $parts[] = $openMistakesCount . ' open mistake' . ($openMistakesCount === 1 ? '' : 's');
        }

        if ($daysSinceLastActivity !== null) {
            $parts[] = $daysSinceLastActivity === 0
                ? 'active today'
                : 'last active ' . $daysSinceLastActivity . 'd ago';
        }

        return implode(' · ', $parts);
    }

    private function buildCoachText(string $severity, float $trendDelta, int $openMistakesCount, ?int $daysSinceLastActivity): string
    {
        if ($severity === 'critical') {
            return $openMistakesCount > 0
                ? 'Critical topic. Clear the mistakes here first, then retake the topic quiz.'
                : 'Critical topic. Retake this topic soon to prevent it from falling further behind.';
        }

        if ($trendDelta < -2) {
            return 'Recent performance is dropping. A short focused quiz now will stabilize this topic.';
        }

        if ($daysSinceLastActivity !== null && $daysSinceLastActivity >= 3) {
            return 'This topic has been inactive for a few days. A quick review now should recover momentum.';
        }

        return 'This topic still needs attention, but it is recoverable with one focused practice session.';
    }
}
