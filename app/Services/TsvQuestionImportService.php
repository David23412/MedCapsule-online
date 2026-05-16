<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TsvQuestionImportService
{
    public function import(string $tsv, ?int $fallbackTopicId = null, bool $forceFallbackTopic = false): array
    {
        $parsed = $this->parse($tsv, $fallbackTopicId, $forceFallbackTopic);

        if (!empty($parsed['errors'])) {
            return [
                'imported' => 0,
                'errors' => $parsed['errors'],
            ];
        }

        DB::transaction(function () use ($parsed) {
            foreach ($parsed['questions'] as $questionData) {
                Question::create($questionData);
            }
        });

        return [
            'imported' => count($parsed['questions']),
            'errors' => [],
        ];
    }

    public function parse(string $tsv, ?int $fallbackTopicId = null, bool $forceFallbackTopic = false): array
    {
        $questions = [];
        $errors = [];
        $lines = preg_split('/\R/u', trim($tsv));

        if ($lines === false || $lines === ['']) {
            throw new InvalidArgumentException('Paste at least one TSV row.');
        }

        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $rowNumber = $index + 1;
            $columns = str_getcsv($line, "\t");
            $columns = array_map(fn ($value) => $this->cleanCell((string) $value), $columns);

            if ($this->looksLikeHeader($columns)) {
                continue;
            }

            $offset = $this->resolveOffset($columns);
            $rowTopicId = $this->resolveTopicId($columns, $offset, $fallbackTopicId, $forceFallbackTopic);
            $expectedColumns = $offset + 10;

            if (count($columns) < $expectedColumns) {
                $errors[] = "Row {$rowNumber}: expected " . (string) $expectedColumns . ' tab-separated columns, got ' . count($columns) . '.';
                continue;
            }

            if ($rowTopicId === null) {
                $errors[] = "Row {$rowNumber}: topic id is required. Add it as the first TSV column or choose a selected topic mode.";
                continue;
            }

            if (!Topic::query()->whereKey($rowTopicId)->exists()) {
                $errors[] = "Row {$rowNumber}: topic id {$rowTopicId} does not exist.";
                continue;
            }

            $questionType = strtolower($columns[$offset + 8] ?? '');
            if (!in_array($questionType, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_WRITTEN], true)) {
                $errors[] = "Row {$rowNumber}: question type must be multiple_choice or written.";
                continue;
            }

            $questionText = $columns[$offset] ?? '';
            $source = $columns[$offset + 1] ?? '';
            $correctAnswer = $columns[$offset + 7] ?? '';

            if ($questionText === '') {
                $errors[] = "Row {$rowNumber}: question text is required.";
                continue;
            }

            if ($source === '') {
                $errors[] = "Row {$rowNumber}: source is required.";
                continue;
            }

            if ($correctAnswer === '') {
                $errors[] = "Row {$rowNumber}: correct answer is required.";
                continue;
            }

            if ($questionType === Question::TYPE_MULTIPLE_CHOICE) {
                $correctAnswer = strtoupper($correctAnswer);
                if (!in_array($correctAnswer, ['A', 'B', 'C', 'D', 'E'], true)) {
                    $errors[] = "Row {$rowNumber}: multiple-choice correct answer must be A, B, C, D, or E.";
                    continue;
                }
            }

            $questionData = [
                'topic_id' => $rowTopicId,
                'question' => $questionText,
                'source' => $source,
                'option_a' => $questionType === Question::TYPE_MULTIPLE_CHOICE ? $this->nullableCell($columns[$offset + 2] ?? '') : null,
                'option_b' => $questionType === Question::TYPE_MULTIPLE_CHOICE ? $this->nullableCell($columns[$offset + 3] ?? '') : null,
                'option_c' => $questionType === Question::TYPE_MULTIPLE_CHOICE ? $this->nullableCell($columns[$offset + 4] ?? '') : null,
                'option_d' => $questionType === Question::TYPE_MULTIPLE_CHOICE ? $this->nullableCell($columns[$offset + 5] ?? '') : null,
                'option_e' => $questionType === Question::TYPE_MULTIPLE_CHOICE ? $this->nullableCell($columns[$offset + 6] ?? '') : null,
                'correct_answer' => $correctAnswer,
                'question_type' => $questionType,
                'explanation' => $this->nullableCell($columns[$offset + 9] ?? ''),
            ];

            if ($questionType === Question::TYPE_MULTIPLE_CHOICE) {
                foreach (['option_a', 'option_b'] as $option) {
                    if (($questionData[$option] ?? null) === null) {
                        $errors[] = "Row {$rowNumber}: " . strtoupper(str_replace('option_', 'option ', $option)) . ' is required for multiple-choice questions.';
                        continue 2;
                    }
                }

                $correctOption = 'option_' . strtolower($correctAnswer);
                if (($questionData[$correctOption] ?? null) === null) {
                    $errors[] = "Row {$rowNumber}: correct answer {$correctAnswer} points to an empty option.";
                    continue;
                }
            }

            $questions[] = $questionData;
        }

        if (empty($questions) && empty($errors)) {
            $errors[] = 'No importable TSV rows were found.';
        }

        return [
            'questions' => $questions,
            'errors' => $errors,
        ];
    }

    private function resolveOffset(array $columns): int
    {
        return count($columns) >= 11 && is_numeric($columns[0] ?? null) ? 1 : 0;
    }

    private function resolveTopicId(array $columns, int $offset, ?int $fallbackTopicId, bool $forceFallbackTopic): ?int
    {
        if ($forceFallbackTopic) {
            return $fallbackTopicId;
        }

        if ($offset === 1) {
            return (int) $columns[0];
        }

        return $fallbackTopicId;
    }

    private function looksLikeHeader(array $columns): bool
    {
        $normalized = array_map(fn ($value) => strtolower(trim((string) $value)), $columns);

        return in_array('question', $normalized, true)
            || in_array('question_text', $normalized, true);
    }

    private function cleanCell(string $value): string
    {
        return trim($value);
    }

    private function nullableCell(string $value): ?string
    {
        $value = trim($value);

        return $value === '' || $value === '-' ? null : $value;
    }
}
