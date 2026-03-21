<?php

namespace App\Services\Planner;

use App\Models\Topic;

class TaskReasonService
{
    public function repairTopic(Topic $topic, float $grade, ?string $source, ?string $conceptName = null): array
    {
        $conceptSuffix = $conceptName ? ' Weakest concept: ' . $conceptName . '.' : '';

        return [
            'reason_code' => 'weak_topic_repair',
            'reason_text' => trim(sprintf(
                '%s is weak at %.1f%%.%s%s',
                $topic->name,
                $grade,
                $source ? ' Focus source: ' . $source . '.' : '',
                $conceptSuffix
            )),
        ];
    }

    public function secureTopic(Topic $topic, float $grade, ?string $source): array
    {
        return [
            'reason_code' => 'near_mastery_secure',
            'reason_text' => trim(sprintf(
                '%s is close to mastery at %.1f%%.%s',
                $topic->name,
                $grade,
                $source ? ' Best next source: ' . $source . '.' : ''
            )),
        ];
    }

    public function frontierTopicSolve(Topic $topic, float $grade, ?string $source): array
    {
        return [
            'reason_code' => 'frontier_topic_solve',
            'reason_text' => trim(sprintf(
                '%s is the current focus topic at %.1f%%.%s',
                $topic->name,
                $grade,
                $source ? ' Solve it on ' . $source . ' next.' : ' Solve it next.'
            )),
        ];
    }

    public function startTopic(Topic $topic, ?string $source): array
    {
        return [
            'reason_code' => 'untouched_topic_start',
            'reason_text' => trim(sprintf(
                '%s has not been started yet.%s',
                $topic->name,
                $source ? ' Recommended source: ' . $source . '.' : ''
            )),
        ];
    }

    public function rebuildTopic(Topic $topic, float $grade, ?string $source, ?string $conceptName = null): array
    {
        $conceptSuffix = $conceptName ? ' Weakest concept: ' . $conceptName . '.' : '';

        return [
            'reason_code' => 'weak_stale_tutor_rebuild',
            'reason_text' => trim(sprintf(
                '%s is weak at %.1f%% and has gone stale.%s%s',
                $topic->name,
                $grade,
                $source ? ' Rebuild it in tutor mode using ' . $source . ' first.' : ' Rebuild it in tutor mode first.',
                $conceptSuffix
            )),
        ];
    }

    public function rapidOverview(Topic $topic, ?string $source): array
    {
        return [
            'reason_code' => 'rapid_overview_exam_gate',
            'reason_text' => trim(sprintf(
                '%s is untouched and the exam is too close for a full tutor block.%s',
                $topic->name,
                $source ? ' Fast source: ' . $source . '.' : ''
            )),
        ];
    }

    public function topicMistakeReview(Topic $topic, int $dueCount, int $minutesOverdue, ?string $conceptName = null): array
    {
        $conceptSuffix = $conceptName ? ' Focus concept: ' . $conceptName . '.' : '';

        return [
            'reason_code' => 'due_mistake_review',
            'reason_text' => sprintf(
                '%s has %d due mistake%s and the oldest is %d minute%s overdue.%s',
                $topic->name,
                $dueCount,
                $dueCount === 1 ? '' : 's',
                $minutesOverdue,
                $minutesOverdue === 1 ? '' : 's',
                $conceptSuffix
            ),
        ];
    }

    public function mistakesCleanup(int $openMistakesCount, ?string $conceptName = null): array
    {
        $conceptSuffix = $conceptName ? ' Most fragile concept: ' . $conceptName . '.' : '';

        return [
            'reason_code' => 'course_mistake_cleanup',
            'reason_text' => sprintf(
                'This course still has %d open mistake%s to clear.%s',
                $openMistakesCount,
                $openMistakesCount === 1 ? '' : 's',
                $conceptSuffix
            ),
        ];
    }

    public function conceptRepair(string $topicName, string $conceptName, float $riskScore, float $retrievability): array
    {
        return [
            'reason_code' => 'concept_repair',
            'reason_text' => sprintf(
                '%s is the weakest concept in %s right now. Risk %.2f, recall %.0f%%.',
                $conceptName,
                $topicName,
                $riskScore,
                $retrievability * 100
            ),
        ];
    }

    public function conceptClusterRepair(string $topicName, string $clusterLabel, array $conceptNames, float $averageRisk): array
    {
        $conceptPreview = collect($conceptNames)
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->take(3)
            ->implode(', ');

        return [
            'reason_code' => 'concept_cluster_repair',
            'reason_text' => trim(sprintf(
                '%s shows a %s across %d concepts. Average risk %.2f.%s',
                $topicName,
                strtolower($clusterLabel),
                count($conceptNames),
                $averageRisk,
                $conceptPreview !== '' ? ' Concepts: ' . $conceptPreview . '.' : ''
            )),
        ];
    }

    public function finalExamSprint(?int $daysToExam, ?string $preferredSource): array
    {
        return [
            'reason_code' => 'exam_pressure_simulation',
            'reason_text' => trim(sprintf(
                'Exam simulation is active because the exam is %s day%s away.%s',
                (string) ($daysToExam ?? 0),
                $daysToExam === 1 ? '' : 's',
                $preferredSource ? ' Preferred source: ' . $preferredSource . '.' : ''
            )),
        ];
    }

    public function accuracyPolish(int $targetScore): array
    {
        return [
            'reason_code' => 'high_target_accuracy_push',
            'reason_text' => sprintf(
                'Target score is %d%%, so the planner is adding an accuracy push.',
                $targetScore
            ),
        ];
    }
}
