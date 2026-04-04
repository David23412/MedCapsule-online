<?php

namespace Tests\Unit\Services\Grading;

use App\Services\TextProcessingService;
use App\Services\PatternMatcherService;
use App\Services\MedicalKnowledgeService;
use App\Services\WrittenAnswerEvaluationService;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for WrittenAnswerEvaluationService.
 *
 * Tests cover:
 *   - Exact, case-insensitive, and normalized matching
 *   - Order-independent matching (reordered words, slash vs conjunction)
 *   - Fuzzy typo tolerance
 *   - Enumeration grading (list items in any order)
 *   - Comparison question grading
 *   - Definition question grading
 *   - Numerical value grading
 *   - ANS hard gates (sympathetic / parasympathetic)
 *   - Contradiction / antonym detection
 *   - Location mismatch detection
 *   - Alternative answers
 *   - Edge cases (empty, whitespace, gibberish)
 */
class WrittenAnswerEvaluationServiceTest extends TestCase
{
    private WrittenAnswerEvaluationService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $tp = new TextProcessingService();
        $pm = new PatternMatcherService();
        $mk = new MedicalKnowledgeService();
        $this->svc = new WrittenAnswerEvaluationService($tp, $pm, $mk);
        $this->svc->setCacheConfig(false); // disable cache for deterministic tests
    }

    // =========================================================================
    // Helper
    // =========================================================================

    private function grade(string $submitted, string $correct, array $alternatives = []): array
    {
        return $this->svc->evaluateAnswer($submitted, $correct, $alternatives);
    }

    private function assertCorrect(string $submitted, string $correct, array $alts = [], string $msg = ''): void
    {
        $result = $this->grade($submitted, $correct, $alts);
        $this->assertTrue(
            $result['isCorrect'],
            $msg ?: "Expected CORRECT: \"{$submitted}\" vs \"{$correct}\" — got similarity={$result['similarity']}, reason={$result['reason']}"
        );
    }

    private function assertIncorrect(string $submitted, string $correct, array $alts = [], string $msg = ''): void
    {
        $result = $this->grade($submitted, $correct, $alts);
        $this->assertFalse(
            $result['isCorrect'],
            $msg ?: "Expected INCORRECT: \"{$submitted}\" vs \"{$correct}\" — got similarity={$result['similarity']}, reason={$result['reason']}"
        );
    }

    // =========================================================================
    // 1. EXACT / CASE-INSENSITIVE / NORMALIZED MATCHES
    // =========================================================================

    public function test_exact_match(): void
    {
        $this->assertCorrect('The heart pumps blood', 'The heart pumps blood');
    }

    public function test_case_insensitive_match(): void
    {
        $this->assertCorrect('the heart pumps blood', 'The Heart Pumps Blood');
    }

    public function test_normalized_match_typo_corrected(): void
    {
        // "hart" → "heart" via typo correction
        $this->assertCorrect('The hart pumps blood', 'The heart pumps blood');
    }

    public function test_normalized_match_synonym_replaced(): void
    {
        // "raises" → "increases" via synonym normalization
        $this->assertCorrect(
            'Sympathetic nervous system raises heart rate',
            'Sympathetic nervous system increases heart rate'
        );
    }

    // =========================================================================
    // 2. ORDER-INDEPENDENT MATCHING — THE KEY USER SCENARIO
    // =========================================================================

    public function test_order_independent_slash_vs_conjunction(): void
    {
        $this->assertCorrect(
            'Genetic and biological factors',
            'Biological/genetic factors'
        );
    }

    public function test_order_independent_reversed_conjunction(): void
    {
        $this->assertCorrect(
            'Biological and genetic factors',
            'Genetic and biological factors'
        );
    }

    public function test_order_independent_factors_repeated(): void
    {
        // "Genetic factors and Biological factors" — repeats "factors"
        $this->assertCorrect(
            'Genetic factors and Biological factors',
            'Biological/genetic factors'
        );
    }

    public function test_order_independent_and_vs_slash(): void
    {
        $this->assertCorrect(
            'Environmental and social factors',
            'Social/environmental factors'
        );
    }

    public function test_order_independent_three_items(): void
    {
        // Three items in different order
        $this->assertCorrect(
            'Genetic, environmental, and social factors',
            'Social, genetic, and environmental factors'
        );
    }

    public function test_order_independent_medical_terms(): void
    {
        $this->assertCorrect(
            'Afferent and efferent neurons',
            'Efferent and afferent neurons'
        );
    }

    public function test_order_independent_complex_phrase(): void
    {
        $this->assertCorrect(
            'Increases heart rate and decreases digestion',
            'Decreases digestion and increases heart rate'
        );
    }

    // =========================================================================
    // 3. TYPO TOLERANCE
    // =========================================================================

    public function test_typo_single_letter_swap(): void
    {
        $this->assertCorrect('Parasympatheic nervous system', 'Parasympathetic nervous system');
    }

    public function test_typo_missing_letter(): void
    {
        $this->assertCorrect('Determiants of health', 'Determinants of health');
    }

    public function test_typo_medical_spelling_variants(): void
    {
        $this->assertCorrect('pnemonia treatment', 'pneumonia treatment');
    }

    public function test_typo_multiple_words_with_errors(): void
    {
        $this->assertCorrect(
            'The parasympatheic nervus system decreses hart rate',
            'The parasympathetic nervous system decreases heart rate'
        );
    }

    public function test_typo_british_vs_american_spelling(): void
    {
        // Both directions should work (ischemia/ischaemia) via typo correction
        $this->assertCorrect('ischaemia of the heart', 'ischemia of the heart');
    }

    // =========================================================================
    // 4. ENUMERATION / LIST QUESTIONS
    // =========================================================================

    public function test_enumeration_all_items_present(): void
    {
        $this->assertCorrect(
            'Biological factors, environmental factors, and social factors',
            'Determinants of health includes biological factors, environmental factors, and social factors'
        );
    }

    public function test_enumeration_different_order(): void
    {
        $this->assertCorrect(
            'Social factors, biological factors, environmental factors',
            'Biological factors, environmental factors, and social factors'
        );
    }

    public function test_enumeration_missing_most_items(): void
    {
        // Only 1 out of 3 items → should fail (33% coverage < 75% threshold)
        $this->assertIncorrect(
            'Biological factors only',
            'Biological factors, environmental factors, and social factors'
        );
    }

    public function test_enumeration_with_typos_in_items(): void
    {
        $this->assertCorrect(
            'biologcal factors, environmetal factors, and social factors',
            'biological factors, environmental factors, and social factors'
        );
    }

    // =========================================================================
    // 5. COMPARISON QUESTIONS
    // =========================================================================

    public function test_comparison_both_sides_addressed(): void
    {
        $this->assertCorrect(
            'Arteries carry oxygenated blood away from the heart while veins carry deoxygenated blood towards the heart',
            'Arteries carry oxygenated blood away from the heart whereas veins carry deoxygenated blood toward the heart'
        );
    }

    public function test_comparison_only_one_side(): void
    {
        // Only mentions arteries, not veins — partial answer
        $result = $this->grade(
            'Arteries carry oxygenated blood',
            'Arteries carry oxygenated blood away from the heart whereas veins carry deoxygenated blood toward the heart'
        );
        // May or may not be correct depending on similarity, but similarity should be < 0.9
        $this->assertLessThan(0.95, $result['similarity']);
    }

    // =========================================================================
    // 6. DEFINITION QUESTIONS
    // =========================================================================

    public function test_definition_correct_paraphrased(): void
    {
        $this->assertCorrect(
            'The heart is a muscular organ that pumps blood throughout the body',
            'The heart is a muscular organ that circulates blood through the body'
        );
    }

    public function test_definition_wrong_organ(): void
    {
        $this->assertIncorrect(
            'The liver is a muscular organ that pumps blood',
            'The heart is a muscular organ that pumps blood'
        );
    }

    // =========================================================================
    // 7. NUMERICAL VALUES
    // =========================================================================

    public function test_numerical_exact_match(): void
    {
        $this->assertCorrect(
            'Normal hemoglobin level is 12.0-16.0 g/dL',
            'Normal hemoglobin level is 12.0-16.0 g/dL'
        );
    }

    public function test_numerical_wrong_values(): void
    {
        $this->assertIncorrect(
            'Normal hemoglobin level is 5.0-8.0 g/dL',
            'Normal hemoglobin level is 12.0-16.0 g/dL'
        );
    }

    // =========================================================================
    // 8. ANS HARD GATES (sympathetic / parasympathetic)
    // =========================================================================

    public function test_sympathetic_correct_effects(): void
    {
        $this->assertCorrect(
            'Increases heart rate and decreases digestion',
            'The sympathetic nervous system increases heart rate and decreases digestion'
        );
    }

    public function test_sympathetic_reversed_effects_rejected(): void
    {
        $this->assertIncorrect(
            'Decreases heart rate and increases digestion',
            'The sympathetic nervous system increases heart rate and decreases digestion'
        );
    }

    public function test_parasympathetic_correct_effects(): void
    {
        $this->assertCorrect(
            'Decreases heart rate and increases digestion',
            'The parasympathetic nervous system decreases heart rate and increases digestion'
        );
    }

    public function test_parasympathetic_reversed_effects_rejected(): void
    {
        $this->assertIncorrect(
            'Increases heart rate and decreases digestion',
            'The parasympathetic nervous system decreases heart rate and increases digestion'
        );
    }

    public function test_sympathetic_tachycardia_variant(): void
    {
        $result = $this->grade(
            'Tachycardia and suppresses digestion',
            'The sympathetic nervous system increases heart rate and decreases digestion'
        );
        // "tachycardia" counts as "heart rate increases" in the ANS gates
        $this->assertTrue($result['isCorrect']);
    }

    public function test_parasympathetic_bradycardia_variant(): void
    {
        $result = $this->grade(
            'Bradycardia and promotes digestion',
            'The parasympathetic nervous system decreases heart rate and increases digestion'
        );
        $this->assertTrue($result['isCorrect']);
    }

    // =========================================================================
    // 9. CONTRADICTION / ANTONYM DETECTION
    // =========================================================================

    public function test_contradiction_blocks_high_similarity(): void
    {
        // Student says "increases" when correct says "decreases"
        // Even though the rest is identical, should be blocked
        $result = $this->grade(
            'Blood pressure increases in shock',
            'Blood pressure decreases in shock'
        );
        $this->assertFalse($result['isCorrect']);
    }

    public function test_no_false_contradiction_on_same_terms(): void
    {
        // Both say "increases" — no contradiction
        $this->assertCorrect(
            'Heart rate increases during exercise',
            'Heart rate increases during exercise'
        );
    }

    // =========================================================================
    // 10. LOCATION MISMATCH DETECTION
    // =========================================================================

    public function test_location_mismatch_protein_synthesis_in_mitochondria(): void
    {
        $result = $this->grade(
            'Protein synthesis occurs in the mitochondrion',
            'Protein synthesis occurs on ribosomes'
        );
        $this->assertFalse($result['isCorrect']);
    }

    public function test_location_correct_protein_synthesis(): void
    {
        $this->assertCorrect(
            'Protein synthesis occurs on ribosomes',
            'Protein synthesis occurs on ribosomes'
        );
    }

    public function test_location_mismatch_glycolysis_in_nucleus(): void
    {
        $result = $this->grade(
            'Glycolysis occurs in the nucleus',
            'Glycolysis occurs in the cytoplasm'
        );
        $this->assertFalse($result['isCorrect']);
    }

    // =========================================================================
    // 11. ALTERNATIVE ANSWERS
    // =========================================================================

    public function test_alternative_answer_accepted(): void
    {
        $this->assertCorrect(
            'The myocardium',
            'The heart muscle',
            ['The myocardium', 'The cardiac muscle']
        );
    }

    public function test_alternative_answer_with_typo(): void
    {
        $this->assertCorrect(
            'The myocardim',
            'The heart muscle',
            ['The myocardium', 'The cardiac muscle']
        );
    }

    public function test_alternative_answer_reordered(): void
    {
        $this->assertCorrect(
            'Genetic and biological factors',
            'Environmental and social factors',
            ['Biological/genetic factors', 'Hereditary factors']
        );
    }

    // =========================================================================
    // 12. EDGE CASES
    // =========================================================================

    public function test_empty_submitted_answer(): void
    {
        $result = $this->grade('', 'The heart pumps blood');
        $this->assertFalse($result['isCorrect']);
        $this->assertSame(0.0, $result['similarity']);
    }

    public function test_whitespace_only_answer(): void
    {
        $result = $this->grade('   ', 'The heart pumps blood');
        $this->assertFalse($result['isCorrect']);
    }

    public function test_empty_correct_answer(): void
    {
        $result = $this->grade('The heart pumps blood', '');
        $this->assertFalse($result['isCorrect']);
    }

    public function test_gibberish_answer(): void
    {
        $this->assertIncorrect(
            'asdfghjkl qwerty zxcvbnm',
            'The heart pumps blood through the circulatory system'
        );
    }

    public function test_single_word_correct_match(): void
    {
        $this->assertCorrect('Heart', 'Heart');
    }

    public function test_single_word_typo(): void
    {
        $this->assertCorrect('hart', 'heart');
    }

    // =========================================================================
    // 13. PARAPHRASING / REPHRASING
    // =========================================================================

    public function test_paraphrase_passive_to_active(): void
    {
        $this->assertCorrect(
            'Blood is pumped by the heart',
            'The heart pumps blood'
        );
    }

    public function test_paraphrase_synonym_usage(): void
    {
        $this->assertCorrect(
            'The heart propels blood through vessels',
            'The heart pumps blood through vessels'
        );
    }

    public function test_extra_details_still_correct(): void
    {
        // Student adds extra info but includes the correct answer
        $this->assertCorrect(
            'The heart is a muscular organ that pumps blood throughout the body via arteries and veins',
            'The heart pumps blood throughout the body'
        );
    }

    // =========================================================================
    // 14. ABBREVIATION MATCHING
    // =========================================================================

    public function test_abbreviation_sns_matches_full_name(): void
    {
        $this->assertCorrect(
            'The SNS increases heart rate',
            'The sympathetic nervous system increases heart rate'
        );
    }

    public function test_abbreviation_pns_matches_full_name(): void
    {
        $this->assertCorrect(
            'The PNS decreases heart rate',
            'The parasympathetic nervous system decreases heart rate'
        );
    }

    // =========================================================================
    // 15. COMPLEX REAL-WORLD SCENARIOS
    // =========================================================================

    public function test_realworld_determinants_of_health(): void
    {
        $this->assertCorrect(
            'Genetic and biological factors',
            'Biological/genetic factors'
        );
    }

    public function test_realworld_determinants_expanded(): void
    {
        $this->assertCorrect(
            'Genetic factors and Biological factors',
            'Biological/genetic factors'
        );
    }

    public function test_realworld_epithelial_classification(): void
    {
        $this->assertCorrect(
            'Simple squamous epithelium',
            'Simple squamous epithelial tissue'
        );
    }

    public function test_realworld_krebs_cycle_location(): void
    {
        $this->assertCorrect(
            'The Krebs cycle takes place in the mitochondrial matrix',
            'The Krebs cycle occurs in the mitochondrial matrix'
        );
    }

    public function test_realworld_completely_wrong_answer(): void
    {
        $this->assertIncorrect(
            'The respiratory system is responsible for digestion',
            'The respiratory system is responsible for gas exchange'
        );
    }

    public function test_realworld_close_but_wrong_concept(): void
    {
        $this->assertIncorrect(
            'Parasympathetic nervous system increases heart rate',
            'Sympathetic nervous system increases heart rate'
        );
    }

    // =========================================================================
    // 16. SIMILARITY SCORE SANITY CHECKS
    // =========================================================================

    public function test_perfect_match_gives_score_1(): void
    {
        $result = $this->grade('The heart pumps blood', 'The heart pumps blood');
        $this->assertSame(1.0, $result['similarity']);
    }

    public function test_empty_answer_gives_score_0(): void
    {
        $result = $this->grade('', 'The heart pumps blood');
        $this->assertSame(0.0, $result['similarity']);
    }

    public function test_correct_answer_has_high_similarity(): void
    {
        $result = $this->grade(
            'The heart is a muscular organ that pumps blood',
            'The heart is a muscular organ that pumps blood throughout the body'
        );
        $this->assertGreaterThan(0.7, $result['similarity']);
    }

    public function test_wrong_answer_has_low_similarity(): void
    {
        $result = $this->grade(
            'Photosynthesis converts sunlight to energy',
            'The heart pumps blood through the circulatory system'
        );
        $this->assertLessThan(0.5, $result['similarity']);
    }

    // =========================================================================
    // 17. RESPONSE STRUCTURE
    // =========================================================================

    public function test_response_has_required_keys(): void
    {
        $result = $this->grade('Heart pumps blood', 'Heart pumps blood');
        $this->assertArrayHasKey('isCorrect', $result);
        $this->assertArrayHasKey('similarity', $result);
        $this->assertArrayHasKey('reason', $result);
    }

    public function test_response_similarity_is_float(): void
    {
        $result = $this->grade('Heart pumps blood', 'Heart pumps blood');
        $this->assertIsFloat($result['similarity']);
    }

    public function test_response_isCorrect_is_bool(): void
    {
        $result = $this->grade('Heart pumps blood', 'Heart pumps blood');
        $this->assertIsBool($result['isCorrect']);
    }
}
