<?php

namespace App\Services\Learning;

use App\Models\Mistake;
use App\Models\MistakeReviewEvent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ConceptAnalyticsService
{
    private static ?bool $hasReviewEvents = null;
    private static ?bool $conceptAnalyticsSupported = null;

    public function buildSignals(User $user, array $courseTopicIds): array
    {
        if (empty($courseTopicIds) || !$this->supportsConceptAnalytics()) {
            return [
                'by_topic' => [],
                'weakest' => [],
                'clusters' => [],
                'clusters_by_topic' => [],
            ];
        }

        $eventMetrics = $this->eventMetricsByConcept($user->id, $courseTopicIds);

        $mistakes = Mistake::query()
            ->where('user_id', $user->id)
            ->whereHas('question', function ($query) use ($courseTopicIds) {
                $query->whereIn('topic_id', $courseTopicIds);
            })
            ->with('question:id,topic_id,concept')
            ->with('question.topic:id,name')
            ->get([
                'id',
                'user_id',
                'question_id',
                'review_count',
                'lapse_count',
                'difficulty',
                'stability',
                'retrievability',
                'next_review_at',
                'last_response_seconds',
                'last_review_rating',
                'last_review_confidence',
                'last_attempt_date',
                'last_reviewed_at',
            ]);

        $now = now();

        $signals = $mistakes
            ->map(function (Mistake $mistake) use ($eventMetrics, $now) {
                $topicId = (int) ($mistake->question?->topic_id ?? 0);
                $conceptName = trim((string) ($mistake->focus_concept_name ?? ''));
                $conceptSlug = trim((string) ($mistake->focus_concept_slug ?? ''));
                $currentRetrievability = $mistake->currentRetrievability($now);

                if ($topicId <= 0 || $conceptName === '' || $conceptSlug === '') {
                    return null;
                }

                return [
                    'topic_id' => $topicId,
                    'concept_name' => $conceptName,
                    'slug' => $conceptSlug,
                    'concept_id' => abs(crc32($conceptSlug)),
                    'review_count' => (int) ($mistake->review_count ?? 0),
                    'lapse_count' => (int) ($mistake->lapse_count ?? 0),
                    'difficulty' => (float) ($mistake->difficulty ?? 0.45),
                    'stability' => (float) ($mistake->stability ?? 0.50),
                    'retrievability' => $currentRetrievability,
                    'last_response_seconds' => (int) ($mistake->last_response_seconds ?? 0),
                    'last_review_rating' => $mistake->last_review_rating,
                    'last_review_confidence' => $mistake->last_review_confidence,
                    'next_review_at' => $mistake->next_review_at,
                    'event_metrics' => $eventMetrics->get($topicId . '|' . $conceptSlug, []),
                ];
            })
            ->filter()
            ->groupBy(fn (array $signal) => $signal['topic_id'] . '|' . $signal['slug'])
            ->map(function (Collection $group) {
                $first = $group->first();
                $metrics = $first['event_metrics'] ?? [];
                $averageDifficulty = (float) $group->avg('difficulty');
                $averageStability = (float) $group->avg('stability');
                $averageRetrievability = (float) $group->avg('retrievability');
                $averageResponseSeconds = (float) $group->avg('last_response_seconds');
                $reviewCount = (int) $group->sum('review_count');
                $lapseCount = (int) $group->sum('lapse_count');
                $dueBonus = $group->contains(fn (array $signal) => $signal['next_review_at'] && $signal['next_review_at']->lte(now())) ? 0.18 : 0.0;
                $lowConfidenceCorrectCount = (int) ($metrics['low_confidence_correct_count'] ?? 0);
                $overconfidenceErrorCount = (int) ($metrics['overconfidence_error_count'] ?? 0);

                $riskScore = max(0.0, min(2.5,
                    (1 - $averageRetrievability)
                    + ($averageDifficulty * 0.55)
                    + (min(8, $lapseCount) * 0.05)
                    + (min(4, $overconfidenceErrorCount) * 0.06)
                    + (min(4, $lowConfidenceCorrectCount) * 0.03)
                    + (((float) ($metrics['incorrect_rate'] ?? 0.0)) * 0.16)
                    + (((float) ($metrics['hesitation_rate'] ?? 0.0)) * 0.10)
                    + $dueBonus
                ));

                $failureProfile = $this->resolveFailureProfile([
                    'retrievability' => $averageRetrievability,
                    'average_response_seconds' => $averageResponseSeconds,
                    'lapse_count' => $lapseCount,
                    'low_confidence_correct_count' => $lowConfidenceCorrectCount,
                    'overconfidence_error_count' => $overconfidenceErrorCount,
                ], $metrics);

                return [
                    'concept_id' => (int) $first['concept_id'],
                    'topic_id' => (int) $first['topic_id'],
                    'concept_name' => (string) $first['concept_name'],
                    'slug' => (string) $first['slug'],
                    'is_generated' => Str::startsWith((string) $first['slug'], 'topic-'),
                    'review_count' => $reviewCount,
                    'lapse_count' => $lapseCount,
                    'difficulty' => round($averageDifficulty, 2),
                    'stability' => round($averageStability, 2),
                    'retrievability' => round($averageRetrievability, 4),
                    'next_review_at' => $group
                        ->pluck('next_review_at')
                        ->filter()
                        ->sort()
                        ->first(),
                    'risk_score' => round($riskScore, 4),
                    'recent_incorrect_rate' => round((float) ($metrics['incorrect_rate'] ?? 0.0), 4),
                    'hesitation_rate' => round((float) ($metrics['hesitation_rate'] ?? 0.0), 4),
                    'slow_recall_rate' => round((float) ($metrics['slow_recall_rate'] ?? 0.0), 4),
                    'overconfidence_rate' => round((float) ($metrics['overconfidence_rate'] ?? 0.0), 4),
                    'fragile_correct_rate' => round((float) ($metrics['fragile_correct_rate'] ?? 0.0), 4),
                    'failure_cluster' => $failureProfile['cluster'],
                    'failure_cluster_label' => $failureProfile['label'],
                    'failure_cluster_score' => round((float) $failureProfile['score'], 4),
                ];
            })
            ->sortByDesc('risk_score')
            ->values();

        $clusters = $this->buildClusters($signals);

        return [
            'by_topic' => $signals
                ->groupBy('topic_id')
                ->map(fn (Collection $group) => $group->values()->all())
                ->all(),
            'weakest' => $signals->take(12)->all(),
            'clusters' => $clusters->take(10)->values()->all(),
            'clusters_by_topic' => $clusters
                ->groupBy('topic_id')
                ->map(fn (Collection $group) => $group->values()->all())
                ->all(),
        ];
    }

    private function buildClusters(Collection $signals): Collection
    {
        return $signals
            ->filter(fn (array $signal) => empty($signal['is_generated']))
            ->groupBy(fn (array $signal) => sprintf('%d:%s', (int) ($signal['topic_id'] ?? 0), (string) ($signal['failure_cluster'] ?? 'balanced')))
            ->map(function (Collection $group) {
                $first = $group->first();
                $conceptNames = $group->pluck('concept_name')
                    ->filter(fn ($name) => is_string($name) && trim($name) !== '')
                    ->values()
                    ->all();

                return [
                    'topic_id' => (int) ($first['topic_id'] ?? 0),
                    'failure_cluster' => (string) ($first['failure_cluster'] ?? 'balanced'),
                    'failure_cluster_label' => (string) ($first['failure_cluster_label'] ?? 'Balanced'),
                    'concept_count' => $group->count(),
                    'concept_ids' => $group->pluck('concept_id')->map(fn ($id) => (int) $id)->values()->all(),
                    'concept_names' => $conceptNames,
                    'average_risk' => round((float) $group->avg(fn (array $signal) => (float) ($signal['risk_score'] ?? 0.0)), 4),
                    'average_retrievability' => round((float) $group->avg(fn (array $signal) => (float) ($signal['retrievability'] ?? 0.9)), 4),
                    'cluster_score' => round((float) $group->avg(fn (array $signal) => (float) ($signal['failure_cluster_score'] ?? 0.0)), 4),
                ];
            })
            ->filter(function (array $cluster) {
                return $cluster['concept_count'] >= 2
                    && $cluster['average_risk'] >= 0.9
                    && $cluster['failure_cluster'] !== 'balanced';
            })
            ->sortByDesc(fn (array $cluster) => ($cluster['average_risk'] * 100)
                + ($cluster['cluster_score'] * 20)
                + ($cluster['concept_count'] * 8))
            ->values();
    }

    private function resolveFailureProfile(array $memory, array $metrics): array
    {
        $retrievability = (float) ($memory['retrievability'] ?? 0.9);
        $averageResponseSeconds = (float) ($memory['average_response_seconds'] ?? 0.0);
        $incorrectRate = (float) ($metrics['incorrect_rate'] ?? 0.0);
        $hesitationRate = (float) ($metrics['hesitation_rate'] ?? 0.0);
        $overconfidenceRate = (float) ($metrics['overconfidence_rate'] ?? 0.0);
        $fragileCorrectRate = (float) ($metrics['fragile_correct_rate'] ?? 0.0);
        $slowRecallRate = (float) ($metrics['slow_recall_rate'] ?? 0.0);

        $scores = [
            'repeated_lapse' => ((int) ($memory['lapse_count'] ?? 0) * 0.22)
                + (max(0.0, 0.75 - $retrievability) * 1.5)
                + ($incorrectRate * 0.9),
            'overconfidence_trap' => ((int) ($memory['overconfidence_error_count'] ?? 0) * 0.35)
                + ($overconfidenceRate * 1.5),
            'hesitation_drift' => ($hesitationRate * 1.35)
                + (min(4, (int) ($memory['low_confidence_correct_count'] ?? 0)) * 0.08),
            'slow_recall' => ($slowRecallRate * 1.25)
                + (max(0.0, $averageResponseSeconds - 14.0) / 12.0),
            'fragile_recall' => ((int) ($memory['low_confidence_correct_count'] ?? 0) * 0.24)
                + ($fragileCorrectRate * 1.15)
                + (max(0.0, 0.70 - $retrievability) * 0.8),
        ];

        arsort($scores);
        $cluster = (string) array_key_first($scores);
        $score = (float) ($scores[$cluster] ?? 0.0);

        if ($score < 0.78) {
            return [
                'cluster' => 'balanced',
                'label' => 'Balanced weakness',
                'score' => $score,
            ];
        }

        return [
            'cluster' => $cluster,
            'label' => match ($cluster) {
                'repeated_lapse' => 'Repeated lapse pattern',
                'overconfidence_trap' => 'Overconfidence trap',
                'hesitation_drift' => 'Hesitation drift',
                'slow_recall' => 'Slow recall pattern',
                'fragile_recall' => 'Fragile recall pattern',
                default => 'Balanced weakness',
            },
            'score' => $score,
        ];
    }

    private function eventMetricsByConcept(int $userId, array $courseTopicIds): Collection
    {
        if (self::$hasReviewEvents === null) {
            self::$hasReviewEvents = Schema::hasTable('mistake_review_events');
        }

        if (!self::$hasReviewEvents) {
            return collect();
        }

        return DB::table('mistake_review_events as events')
            ->join('questions as questions', 'questions.id', '=', 'events.question_id')
            ->join('topics as topics', 'topics.id', '=', 'questions.topic_id')
            ->where('events.user_id', $userId)
            ->where('events.event_type', MistakeReviewEvent::EVENT_TYPE_REVIEW)
            ->whereIn('questions.topic_id', $courseTopicIds)
            ->where(function ($query) {
                $query->whereNotNull('questions.concept')
                    ->orWhere('topics.name', '!=', '');
            })
            ->where('events.reviewed_at', '>=', now()->subDays(90))
            ->groupBy(
                'questions.topic_id',
                DB::raw('COALESCE(NULLIF(questions.concept, ""), topics.name)')
            )
            ->get([
                'questions.topic_id',
                DB::raw('COALESCE(NULLIF(questions.concept, ""), topics.name) as concept_name'),
                DB::raw('COUNT(*) as review_events'),
                DB::raw('SUM(CASE WHEN events.is_correct = 1 AND events.confidence_level = "guessed" THEN 1 ELSE 0 END) as low_confidence_correct_count'),
                DB::raw('SUM(CASE WHEN events.is_correct = 0 AND events.confidence_level = "knew" THEN 1 ELSE 0 END) as overconfidence_error_count'),
                DB::raw('AVG(CASE WHEN events.is_correct = 0 THEN 1 ELSE 0 END) as incorrect_rate'),
                DB::raw('AVG(
                    CASE
                        WHEN (
                            (CASE
                                WHEN events.confidence_level = "guessed" THEN 0.26
                                WHEN events.confidence_level = "unsure" THEN 0.12
                                ELSE 0.0
                            END) +
                            (CASE
                                WHEN COALESCE(events.response_seconds, 0) >= 120 THEN 0.62
                                WHEN COALESCE(events.response_seconds, 0) >= 75 THEN 0.46
                                WHEN COALESCE(events.response_seconds, 0) >= 45 THEN 0.30
                                WHEN COALESCE(events.response_seconds, 0) >= 25 THEN 0.16
                                WHEN COALESCE(events.response_seconds, 0) >= 15 THEN 0.08
                                ELSE 0.0
                            END)
                        ) >= 1.0 THEN 1.0
                        ELSE (
                            (CASE
                                WHEN events.confidence_level = "guessed" THEN 0.26
                                WHEN events.confidence_level = "unsure" THEN 0.12
                                ELSE 0.0
                            END) +
                            (CASE
                                WHEN COALESCE(events.response_seconds, 0) >= 120 THEN 0.62
                                WHEN COALESCE(events.response_seconds, 0) >= 75 THEN 0.46
                                WHEN COALESCE(events.response_seconds, 0) >= 45 THEN 0.30
                                WHEN COALESCE(events.response_seconds, 0) >= 25 THEN 0.16
                                WHEN COALESCE(events.response_seconds, 0) >= 15 THEN 0.08
                                ELSE 0.0
                            END)
                        )
                    END
                ) as hesitation_rate'),
                DB::raw('AVG(CASE WHEN COALESCE(events.response_seconds, 0) >= 18 THEN 1 ELSE 0 END) as slow_recall_rate'),
                DB::raw('AVG(CASE WHEN events.is_correct = 0 AND events.confidence_level = "knew" THEN 1 ELSE 0 END) as overconfidence_rate'),
                DB::raw('AVG(CASE WHEN events.is_correct = 1 AND events.confidence_level = "guessed" THEN 1 ELSE 0 END) as fragile_correct_rate'),
            ])
            ->mapWithKeys(fn ($row) => [
                ((int) $row->topic_id) . '|' . Str::slug((string) $row->concept_name) => [
                    'review_events' => (int) ($row->review_events ?? 0),
                    'low_confidence_correct_count' => (int) ($row->low_confidence_correct_count ?? 0),
                    'overconfidence_error_count' => (int) ($row->overconfidence_error_count ?? 0),
                    'incorrect_rate' => (float) ($row->incorrect_rate ?? 0.0),
                    'hesitation_rate' => (float) ($row->hesitation_rate ?? 0.0),
                    'slow_recall_rate' => (float) ($row->slow_recall_rate ?? 0.0),
                    'overconfidence_rate' => (float) ($row->overconfidence_rate ?? 0.0),
                    'fragile_correct_rate' => (float) ($row->fragile_correct_rate ?? 0.0),
                ],
            ]);
    }

    private function supportsConceptAnalytics(): bool
    {
        if (self::$conceptAnalyticsSupported === null) {
            self::$conceptAnalyticsSupported = Schema::hasTable('questions')
                && Schema::hasTable('topics');
        }

        return self::$conceptAnalyticsSupported;
    }
}
