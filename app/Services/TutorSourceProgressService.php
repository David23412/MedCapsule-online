<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\UserTopicSourceProgress;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TutorSourceProgressService
{
    /**
     * Cache total-question counts per topic/source for the current request.
     *
     * @var array<string,int>
     */
    private array $totalQuestionsCache = [];

    /**
     * Get durable tutor coverage for every available source in a topic.
     *
     * @return array<string,array<string,int|float|bool|string|array>>
     */
    public function getTopicProgressMap(int $userId, Topic $topic): array
    {
        $sourceTotals = $this->getTopicSourceTotals($topic);
        $progress = [];

        foreach ($sourceTotals as $source => $totalQuestions) {
            $progress[$source] = $this->getSourceProgress($userId, $topic, (string) $source, (int) $totalQuestions);
        }

        return $progress;
    }

    /**
     * Get durable tutor coverage for one source, lazily hydrating legacy attempt data when needed.
     *
     * @return array<string,int|float|bool|string|array>
     */
    public function getSourceProgress(int $userId, Topic $topic, string $source, ?int $totalQuestions = null): array
    {
        $normalizedSource = $this->normalizeSource($source);
        $record = $this->findOrHydrateRecord($userId, $topic->id, $normalizedSource);
        $availableQuestions = $totalQuestions ?? $this->getTotalQuestionsForSource($topic, $normalizedSource);

        if ($record && $record->total_questions !== $availableQuestions) {
            $record->forceFill(['total_questions' => $availableQuestions])->save();
        }

        $completedCount = (int) ($record->completed_count ?? 0);
        $correctCount = (int) ($record->correct_count ?? 0);
        $remainingCount = max(0, $availableQuestions - $completedCount);

        return [
            'source' => $normalizedSource,
            'completed' => $completedCount,
            'correct' => $correctCount,
            'total' => $availableQuestions,
            'remaining_count' => $remainingCount,
            'percentage' => $availableQuestions > 0 ? round(($completedCount / $availableQuestions) * 100, 2) : 0,
            'accuracy_percentage' => $completedCount > 0 ? round(($correctCount / $completedCount) * 100, 2) : 0,
            'has_attempts' => $completedCount > 0,
            'completed_question_ids' => $this->normalizeQuestionIds($record->completed_question_ids ?? []),
            'correct_question_ids' => $this->normalizeQuestionIds($record->correct_question_ids ?? []),
            'can_resume_remaining' => $remainingCount > 0,
        ];
    }

    /**
     * Record tutor coverage idempotently for one answered question.
     */
    public function recordAnsweredQuestion(int $userId, Topic $topic, Question $question, bool $isCorrect, ?Carbon $answeredAt = null): UserTopicSourceProgress
    {
        $answeredAt = $answeredAt ?? now();
        $source = $this->normalizeSource((string) ($question->source ?: 'General'));
        $record = $this->findOrHydrateRecord($userId, $topic->id, $source)
            ?? new UserTopicSourceProgress([
                'user_id' => $userId,
                'topic_id' => $topic->id,
                'source' => $source,
            ]);

        $completedIds = $this->normalizeQuestionIds($record->completed_question_ids ?? []);
        $correctIds = $this->normalizeQuestionIds($record->correct_question_ids ?? []);

        if (!in_array($question->id, $completedIds, true)) {
            $completedIds[] = (int) $question->id;
        }

        if ($isCorrect && !in_array($question->id, $correctIds, true)) {
            $correctIds[] = (int) $question->id;
        }

        sort($completedIds);
        sort($correctIds);

        $record->fill([
            'completed_question_ids' => $completedIds,
            'correct_question_ids' => $correctIds,
            'completed_count' => count($completedIds),
            'correct_count' => count($correctIds),
            'total_questions' => $this->getTotalQuestionsForSource($topic, $source),
            'last_attempt_date' => $answeredAt,
            'last_activity_at' => $answeredAt,
        ]);

        $record->save();

        return $record;
    }

    /**
     * Record a full tutor session idempotently.
     *
     * @param array<string,array{questions:Collection<int,Question>,question_data:array<int,array<string,mixed>>}> $sourceGroups
     */
    public function recordSessionAnswers(int $userId, Topic $topic, array $sourceGroups, ?Carbon $answeredAt = null): void
    {
        $answeredAt = $answeredAt ?? now();

        foreach ($sourceGroups as $source => $group) {
            /** @var Collection<int,Question> $questions */
            $questions = $group['questions'] ?? collect();
            $questionData = collect($group['question_data'] ?? [])->keyBy('question_id');

            foreach ($questions as $question) {
                $attempt = $questionData->get($question->id);
                if (!$attempt) {
                    continue;
                }

                $this->recordAnsweredQuestion(
                    $userId,
                    $topic,
                    $question,
                    (bool) ($attempt['is_correct'] ?? false),
                    $answeredAt
                );
            }
        }
    }

    /**
     * Get already covered question ids for selected sources.
     *
     * @param string[] $sources
     * @return int[]
     */
    public function getCompletedQuestionIdsForSources(int $userId, Topic $topic, array $sources): array
    {
        if (in_array('all', $sources, true)) {
            $sources = array_keys($this->getTopicSourceTotals($topic));
        }

        $normalizedSources = array_values(array_unique(array_map([$this, 'normalizeSource'], $sources)));
        $completedIds = [];

        foreach ($normalizedSources as $source) {
            $progress = $this->getSourceProgress($userId, $topic, $source);
            $completedIds = [...$completedIds, ...($progress['completed_question_ids'] ?? [])];
        }

        $completedIds = array_values(array_unique(array_map('intval', $completedIds)));
        sort($completedIds);

        return $completedIds;
    }

    /**
     * @return array<string,int>
     */
    public function getTopicSourceTotals(Topic $topic): array
    {
        return Question::query()
            ->where(function ($query) use ($topic) {
                $query->where('topic_id', $topic->id)
                    ->orWhereRaw('FIND_IN_SET(?, REPLACE(extra_topic, " ", "")) > 0', [$topic->id]);
            })
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->selectRaw('source, COUNT(*) as total')
            ->groupBy('source')
            ->pluck('total', 'source')
            ->mapWithKeys(fn ($total, $source) => [$this->normalizeSource((string) $source) => (int) $total])
            ->toArray();
    }

    private function findOrHydrateRecord(int $userId, int $topicId, string $source): ?UserTopicSourceProgress
    {
        $record = UserTopicSourceProgress::query()
            ->where('user_id', $userId)
            ->where('topic_id', $topicId)
            ->where('source', $source)
            ->first();

        if ($record) {
            return $record;
        }

        $snapshot = $this->buildSnapshotFromLatestAttempt($userId, $topicId, $source);
        if ($snapshot === null) {
            return null;
        }

        return UserTopicSourceProgress::query()->create($snapshot);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function buildSnapshotFromLatestAttempt(int $userId, int $topicId, string $source): ?array
    {
        $latestAttempt = QuizAttempt::query()
            ->where('user_id', $userId)
            ->where('topic_id', $topicId)
            ->where('study_mode', 'tutor')
            ->where('source', $source)
            ->latest('id')
            ->first();

        if (!$latestAttempt) {
            return null;
        }

        $questionData = collect($latestAttempt->question_data ?? []);
        $completedIds = $this->normalizeQuestionIds(
            $questionData
                ->pluck('question_id')
                ->filter()
                ->all()
        );
        $correctIds = $this->normalizeQuestionIds(
            $questionData
                ->filter(fn ($item) => (bool) ($item['is_correct'] ?? false))
                ->pluck('question_id')
                ->filter()
                ->all()
        );

        return [
            'user_id' => $userId,
            'topic_id' => $topicId,
            'source' => $source,
            'completed_question_ids' => $completedIds,
            'correct_question_ids' => $correctIds,
            'completed_count' => count($completedIds),
            'correct_count' => count($correctIds),
            'total_questions' => $this->getTotalQuestionsForSource(Topic::findOrFail($topicId), $source),
            'last_attempt_date' => $latestAttempt->created_at,
            'last_activity_at' => $latestAttempt->updated_at ?? $latestAttempt->created_at,
        ];
    }

    private function getTotalQuestionsForSource(Topic $topic, string $source): int
    {
        $cacheKey = $topic->id . ':' . $source;
        if (array_key_exists($cacheKey, $this->totalQuestionsCache)) {
            return $this->totalQuestionsCache[$cacheKey];
        }

        $this->totalQuestionsCache[$cacheKey] = Question::query()
            ->where(function ($query) use ($topic) {
                $query->where('topic_id', $topic->id)
                    ->orWhereRaw('FIND_IN_SET(?, REPLACE(extra_topic, " ", "")) > 0', [$topic->id]);
            })
            ->where('source', $source)
            ->count();

        return $this->totalQuestionsCache[$cacheKey];
    }

    /**
     * @param array<int,mixed> $ids
     * @return int[]
     */
    private function normalizeQuestionIds(array $ids): array
    {
        $normalized = array_values(array_unique(array_map('intval', array_filter($ids, fn ($id) => $id !== null && $id !== ''))));
        sort($normalized);

        return $normalized;
    }

    private function normalizeSource(string $source): string
    {
        $source = trim($source);

        return $source !== '' ? $source : 'General';
    }
}
