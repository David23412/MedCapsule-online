<?php

namespace App\Services\WrittenGrading;

/**
 * Minimal in-memory rubric repository.
 *
 * This is intentionally tiny and hard-coded, purely to demonstrate how the
 * concept-based system can be plugged into real questions. In production,
 * this would be backed by a database or configuration files generated from
 * curated content like questions.md.
 */
class InMemoryRubricRepository
{
    /**
     * Get a rubric by its canonical id (e.g. IMM-W001).
     *
     * @param string $id
     * @return ConceptRubric|null
     */
    public function findById(string $id): ?ConceptRubric
    {
        $id = strtoupper(trim($id));

        $all = $this->all();

        return $all[$id] ?? null;
    }

    /**
     * Return all known rubrics keyed by id.
     *
     * @return array<string, ConceptRubric>
     */
    public function all(): array
    {
        $rubrics = [];

        // Example: IMM-W001 – "What are immunoglobulins?"
        $rubrics['IMM-W001'] = ConceptRubric::fromArray([
            'id'        => 'IMM-W001',
            'type'      => 'definition',
            'max_score' => 100,
            'concepts'  => [
                [
                    'id'          => 'IG_GLYCOPROTEIN',
                    'role'        => 'core',
                    'weight'      => 0.25,
                    'description' => 'Immunoglobulins are glycoprotein molecules.',
                    'phrases'     => [
                        'glycoprotein molecules',
                        'glycoprotein antibodies',
                        'glycoprotein immunoglobulins',
                    ],
                ],
                [
                    'id'          => 'IG_ANTIBODY',
                    'role'        => 'core',
                    'weight'      => 0.25,
                    'description' => 'They are antibodies.',
                    'phrases'     => [
                        'antibodies',
                        'antibody proteins',
                    ],
                ],
                [
                    'id'          => 'PRODUCED_BY_BCELLS',
                    'role'        => 'core',
                    'weight'      => 0.25,
                    'description' => 'Produced by plasma cells / B lymphocytes.',
                    'phrases'     => [
                        'produced by plasma cells',
                        'produced by b lymphocytes',
                        'produced by b cells',
                        'made by b cells',
                    ],
                ],
                [
                    'id'          => 'RESPOND_TO_ANTIGENS',
                    'role'        => 'core',
                    'weight'      => 0.25,
                    'description' => 'Produced in response to antigens and specifically bind them.',
                    'phrases'     => [
                        'in response to antigens',
                        'bind specific antigens',
                        'recognize specific antigens',
                        'recognize and bind antigens',
                    ],
                ],
            ],
            'misconceptions' => [
                [
                    'id'       => 'LIVER_SYNTHESIS',
                    'penalty'  => 0.3,
                    'patterns' => [
                        'synthesized mainly in the liver',
                        'produced mainly in the liver',
                        'made in the liver',
                    ],
                    'message'  => 'Immunoglobulins are produced by B-lymphocytes / plasma cells, not mainly in the liver.',
                ],
            ],
        ]);

        return $rubrics;
    }
}


