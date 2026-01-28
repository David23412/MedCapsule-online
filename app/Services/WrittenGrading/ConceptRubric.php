<?php

namespace App\Services\WrittenGrading;

/**
 * Core data structure describing how a single written question should be graded.
 *
 * This is deliberately generic and independent of any specific similarity engine.
 * It can be populated from config files, a database, or even auto-generated
 * from curated markdown like questions.md.
 */
class ConceptRubric
{
    /**
     * Machine-readable question identifier (e.g. IMM-W001).
     *
     * @var string
     */
    public string $id;

    /**
     * Human-readable question type (definition, mechanism, enumeration, compare, etc.).
     *
     * @var string
     */
    public string $type;

    /**
     * Maximum score for this question (typically 100).
     *
     * @var int
     */
    public int $maxScore;

    /**
     * Ordered list of core / secondary / bonus concepts.
     *
     * Each element:
     * [
     *   'id'          => string,   // stable concept id, e.g. IG_GLYCOPROTEIN
     *   'weight'      => float,    // relative weight, will be normalized
     *   'role'        => string,   // core | secondary | bonus
     *   'description' => string,   // plain English description
     *   'phrases'     => string[], // canonical paraphrases / key phrases
     * ]
     *
     * @var array<int, array<string, mixed>>
     */
    public array $concepts = [];

    /**
     * Known dangerous misconceptions to penalize explicitly.
     *
     * Each element:
     * [
     *   'id'       => string,
     *   'penalty'  => float,    // fraction of maxScore to deduct (0–1)
     *   'patterns' => string[], // substrings / regex patterns that indicate this misconception
     *   'message'  => string,   // feedback snippet
     * ]
     *
     * @var array<int, array<string, mixed>>
     */
    public array $misconceptions = [];

    /**
     * Factory helper from array (e.g. config / JSON).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rubric = new self();

        $rubric->id        = $data['id'] ?? '';
        $rubric->type      = $data['type'] ?? 'generic';
        $rubric->maxScore  = (int)($data['max_score'] ?? 100);
        $rubric->concepts  = $data['concepts'] ?? [];
        $rubric->misconceptions = $data['misconceptions'] ?? [];

        return $rubric;
    }
}


