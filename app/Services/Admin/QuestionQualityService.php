<?php

namespace App\Services\Admin;

use App\Models\Question;
use Illuminate\Support\Facades\DB;

class QuestionQualityService
{
    public function scan(?int $courseId, string $issue, int $page): array
    {
        $query = DB::table('questions as q')
            ->leftJoin('topics as t', 't.id', '=', 'q.topic_id')
            ->leftJoin('courses as c', 'c.id', '=', 't.course_id')
            ->select('q.*', 't.name as topic_name', 'c.name as course_name')
            ->when($courseId, fn ($query) => $query->where('t.course_id', $courseId))
            ->orderBy('q.id');
        $counts = ['broken_answer' => 0, 'repeated_explanation' => 0];
        $matches = 0;
        $items = [];
        $perPage = 20;
        // Stream the scan: retain only one page, never the complete question bank.
        foreach ($query->cursor() as $row) {
            $question = (array) $row;
            $issues = $this->issues($question);
            foreach ($issues as $key) {
                $counts[$key]++;
            }
            if (!$issues || ($issue !== 'all' && !in_array($issue, $issues, true))) {
                continue;
            }
            $matches++;
            if ($matches > ($page - 1) * $perPage && count($items) < $perPage) {
                $question['issues'] = $issues;
                $question['update_url'] = route('quiz.tutor.update-question', $row->id);
                $items[] = $question;
            }
        }

        return ['items' => $items, 'counts' => $counts, 'total' => $matches,
            'page' => $page, 'last_page' => max(1, (int) ceil($matches / $perPage))];
    }

    public function issues(array $question): array
    {
        $answer = trim((string) ($question['correct_answer'] ?? ''));
        $written = ($question['question_type'] ?? null) === Question::TYPE_WRITTEN;
        $issues = [];
        $answerText = $answer;
        if (!$written) {
            $letter = strtoupper($answer);
            $answerText = preg_match('/^[A-E]$/', $letter)
                ? (string) ($question['option_' . strtolower($letter)] ?? '') : '';
            if (!preg_match('/^[A-E]$/', $letter) || $this->normalize($answerText) === '') {
                $issues[] = 'broken_answer';
            }
        }
        $explanation = $this->normalize((string) ($question['explanation'] ?? ''));
        $normalizedAnswer = $this->normalize($answerText);
        if ($explanation !== '' && $normalizedAnswer !== '') {
            $variants = [$normalizedAnswer];
            if (!$written) {
                $variants[] = $this->normalize($answer . ': ' . $answerText);
                $variants[] = $this->normalize($answer);
            }
            if (in_array($explanation, $variants, true)) {
                $issues[] = 'repeated_explanation';
            }
        }

        return $issues;
    }

    private function normalize(string $value): string
    {
        $value = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], ' ', $value)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = mb_strtolower($value, 'UTF-8');
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '');
    }
}
