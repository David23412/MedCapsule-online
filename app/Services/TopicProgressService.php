<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\UserTopicProgress;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TopicProgressService
{
    /**
     * Hydrate topics with fresh quiz-mode progress and optionally sync stale persisted rows.
     *
     * @param Collection<int, Topic> $topics
     */
    public function hydrateTopicsWithFreshQuizProgress(Collection $topics, int $userId, bool $syncPersistentProgress = true): void
    {
        if ($topics->isEmpty()) {
            return;
        }

        $progressByTopic = $this->calculateBulkQuizProgress($topics->pluck('id'), $userId);
        $timestamp = now();
        $upserts = [];
        $staleProgressTopicIds = [];

        foreach ($topics as $topic) {
            $freshProgress = $progressByTopic[$topic->id] ?? [
                'percentage_grade' => 0.0,
                'last_attempt_date' => null,
                'all_sources_mastered' => false,
            ];

            $userProgress = $topic->userProgress->first() ?? new UserTopicProgress([
                'user_id' => $userId,
                'topic_id' => $topic->id,
            ]);

            $currentPercentage = (float) ($userProgress->percentage_grade ?? 0);
            $freshPercentage = (float) $freshProgress['percentage_grade'];
            $freshLastAttempt = $freshProgress['last_attempt_date'];
            $currentLastAttempt = $userProgress->last_attempt_date?->toDateTimeString();
            $freshLastAttemptString = $freshLastAttempt?->toDateTimeString();
            $hasAttemptedTopic = $freshLastAttempt !== null;

            if (!$hasAttemptedTopic) {
                $topic->setRelation('userProgress', collect());
                $topic->setAttribute('completion_percentage', 0.0);
                $topic->setAttribute('last_attempt_date', null);
                $topic->setAttribute('all_sources_mastered', false);

                if (
                    $syncPersistentProgress &&
                    $userProgress->exists &&
                    $currentPercentage <= 0 &&
                    $currentLastAttempt === null
                ) {
                    $staleProgressTopicIds[] = $topic->id;
                }

                continue;
            }

            $userProgress->percentage_grade = $freshPercentage;
            $userProgress->last_attempt_date = $freshLastAttempt;
            $topic->setRelation('userProgress', collect([$userProgress]));
            $topic->setAttribute('completion_percentage', $freshPercentage);
            $topic->setAttribute('last_attempt_date', $freshLastAttempt);
            $topic->setAttribute('all_sources_mastered', (bool) $freshProgress['all_sources_mastered']);

            if (
                $syncPersistentProgress &&
                (
                    !$userProgress->exists ||
                    $currentPercentage !== $freshPercentage ||
                    $currentLastAttempt !== $freshLastAttemptString
                )
            ) {
                $upserts[] = [
                    'user_id' => $userId,
                    'topic_id' => $topic->id,
                    'percentage_grade' => $freshPercentage,
                    'last_attempt_date' => $freshLastAttempt,
                    'created_at' => $userProgress->exists ? ($userProgress->created_at ?? $timestamp) : $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if (!empty($upserts)) {
            UserTopicProgress::upsert(
                $upserts,
                ['user_id', 'topic_id'],
                ['percentage_grade', 'last_attempt_date', 'updated_at']
            );
        }

        if (!empty($staleProgressTopicIds)) {
            UserTopicProgress::query()
                ->where('user_id', $userId)
                ->whereIn('topic_id', array_unique($staleProgressTopicIds))
                ->where(function ($query) {
                    $query->whereNull('last_attempt_date')
                        ->where(function ($progressQuery) {
                            $progressQuery->whereNull('percentage_grade')
                                ->orWhere('percentage_grade', '<=', 0);
                        });
                })
                ->delete();
        }
    }

    public function getFreshQuizProgressForTopic(int $userId, int $topicId): array
    {
        return $this->calculateBulkQuizProgress(collect([$topicId]), $userId)[$topicId] ?? [
            'percentage_grade' => 0.0,
            'last_attempt_date' => null,
            'all_sources_mastered' => false,
        ];
    }

    /**
     * @param Collection<int, int>|array<int, int> $topicIds
     * @return array<int, array{percentage_grade: float, last_attempt_date: ?Carbon, all_sources_mastered: bool}>
     */
    public function calculateBulkQuizProgress(Collection|array $topicIds, int $userId): array
    {
        $topicIds = collect($topicIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($topicIds->isEmpty()) {
            return [];
        }

        $directCounts = Question::query()
            ->whereIn('topic_id', $topicIds)
            ->selectRaw('topic_id, source, COUNT(*) as total')
            ->groupBy('topic_id', 'source')
            ->get();

        $sourceTotalsByTopic = $directCounts->groupBy('topic_id')->map(function ($rows) {
            return $rows->map(function ($row) {
                return (object) [
                    'source' => $row->source,
                    'total' => (int) $row->total,
                ];
            })->values();
        });

        $linkedQuestions = Question::query()
            ->whereNotNull('extra_topic')
            ->where('extra_topic', '!=', '')
            ->where(function ($query) use ($topicIds) {
                foreach ($topicIds as $topicId) {
                    $query->orWhereRaw(DB::getDriverName() === 'sqlite'
                        ? "INSTR(',' || REPLACE(extra_topic, ' ', '') || ',', ',' || ? || ',') > 0"
                        : 'FIND_IN_SET(?, REPLACE(extra_topic, " ", "")) > 0', [$topicId]);
                }
            })
            ->get(['topic_id', 'source', 'extra_topic']);

        foreach ($linkedQuestions as $question) {
            $linkedTopicIds = collect(explode(',', (string) $question->extra_topic))
                ->map(fn ($id) => (int) trim($id))
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            foreach ($linkedTopicIds as $linkedTopicId) {
                if (!$topicIds->contains($linkedTopicId) || $linkedTopicId === (int) $question->topic_id) {
                    continue;
                }

                $topicRows = $sourceTotalsByTopic->get($linkedTopicId, collect());
                $existingRow = $topicRows->firstWhere('source', $question->source);

                if ($existingRow) {
                    $existingRow->total++;
                } else {
                    $topicRows->push((object) [
                        'source' => $question->source,
                        'total' => 1,
                    ]);
                }

                $sourceTotalsByTopic->put($linkedTopicId, $topicRows->values());
            }
        }

        $latestAttemptDates = QuizAttempt::query()
            ->where('user_id', $userId)
            ->whereIn('topic_id', $topicIds)
            ->where('study_mode', 'quiz')
            ->selectRaw('topic_id, MAX(created_at) as last_attempt_date')
            ->groupBy('topic_id')
            ->pluck('last_attempt_date', 'topic_id');

        $latestPerSource = QuizAttempt::query()
            ->selectRaw('topic_id, source, MAX(id) as latest_id')
            ->where('user_id', $userId)
            ->whereIn('topic_id', $topicIds)
            ->where('study_mode', 'quiz')
            ->whereNotNull('source')
            ->groupBy('topic_id', 'source');

        $latestAttemptsByTopic = QuizAttempt::query()
            ->from('quiz_attempts as qa')
            ->joinSub($latestPerSource, 'latest_per_source', function ($join) {
                $join->on('qa.id', '=', 'latest_per_source.latest_id');
            })
            ->get(['qa.topic_id', 'qa.source', 'qa.score'])
            ->groupBy('topic_id');

        $progressByTopic = [];

        foreach ($topicIds as $topicId) {
            // Merge counts from direct and extra topics, summing up duplicates
            $sourceTotals = [];
            $topicRows = $sourceTotalsByTopic->get($topicId) ?? collect();
            
            foreach ($topicRows as $row) {
                $source = $row->source;
                $count = (int) $row->total;
                if (!isset($sourceTotals[$source])) {
                    $sourceTotals[$source] = 0;
                }
                $sourceTotals[$source] += $count;
            }

            if (empty($sourceTotals)) {
                $progressByTopic[$topicId] = [
                    'percentage_grade' => 0.0,
                    'last_attempt_date' => $latestAttemptDates->has($topicId)
                        ? Carbon::parse($latestAttemptDates->get($topicId))
                        : null,
                    'all_sources_mastered' => false,
                ];
                continue;
            }

            $latestAttempts = ($latestAttemptsByTopic->get($topicId) ?? collect())->keyBy('source');
            $topicQuestionCount = array_sum($sourceTotals);
            $totalCorrectAnswers = 0;
            $allSourcesMastered = true;

            foreach ($sourceTotals as $source => $sourceQuestionCount) {
                $correctAnswers = (int) optional($latestAttempts->get($source))->score;
                $totalCorrectAnswers += $correctAnswers;

                if ($sourceQuestionCount <= 0 || $correctAnswers < $sourceQuestionCount) {
                    $allSourcesMastered = false;
                }
            }

            $progressByTopic[$topicId] = [
                'percentage_grade' => $topicQuestionCount > 0
                    ? round(($totalCorrectAnswers / $topicQuestionCount) * 100, 2)
                    : 0.0,
                'last_attempt_date' => $latestAttemptDates->has($topicId)
                    ? Carbon::parse($latestAttemptDates->get($topicId))
                    : null,
                'all_sources_mastered' => $allSourcesMastered,
            ];
        }

        return $progressByTopic;
    }
}
