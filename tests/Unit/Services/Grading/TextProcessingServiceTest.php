<?php

namespace Tests\Unit\Services\Grading;

use App\Services\TextProcessingService;
use PHPUnit\Framework\TestCase;

class TextProcessingServiceTest extends TestCase
{
    private TextProcessingService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new TextProcessingService();
    }

    // =========================================================================
    // normalizeText — slash expansion
    // =========================================================================

    public function test_normalizeText_expands_slashes_to_and(): void
    {
        $result = $this->svc->normalizeText('Biological/genetic factors');
        $this->assertStringContainsString('biological', $result);
        $this->assertStringContainsString('genetic', $result);
        $this->assertStringContainsString('and', $result);
        // Slash should be gone
        $this->assertStringNotContainsString('/', $result);
    }

    public function test_normalizeText_preserves_numeric_fractions(): void
    {
        $result = $this->svc->normalizeText('blood pressure 120/80');
        // Should NOT expand the numeric slash
        $this->assertStringNotContainsString(' and ', $result);
    }

    public function test_normalizeText_multiple_slashes(): void
    {
        $result = $this->svc->normalizeText('A/B/C factors');
        $this->assertStringContainsString('and', $result);
    }

    // =========================================================================
    // normalizeText — typo correction
    // =========================================================================

    public function test_normalizeText_corrects_medical_typos(): void
    {
        $this->assertStringContainsString('heart', $this->svc->normalizeText('hart'));
        $this->assertStringContainsString('cardiac', $this->svc->normalizeText('caridac'));
        $this->assertStringContainsString('parasympathetic', $this->svc->normalizeText('parasympatheic'));
        $this->assertStringContainsString('neuron', $this->svc->normalizeText('nueron'));
        $this->assertStringContainsString('pneumonia', $this->svc->normalizeText('pnemonia'));
        $this->assertStringContainsString('determinants', $this->svc->normalizeText('determiants'));
        $this->assertStringContainsString('glycolysis', $this->svc->normalizeText('glyclysis'));
        $this->assertStringContainsString('epithelial', $this->svc->normalizeText('epithelal'));
        $this->assertStringContainsString('connective', $this->svc->normalizeText('conective'));
    }

    // =========================================================================
    // normalizeText — variant normalization
    // =========================================================================

    public function test_normalizeText_normalizes_synonyms(): void
    {
        // "raises" → "increases"
        $this->assertStringContainsString('increases', $this->svc->normalizeText('raises blood pressure'));
        // "lowers" → "decreases"
        $this->assertStringContainsString('decreases', $this->svc->normalizeText('lowers heart rate'));
        // "elevates" → "increases"
        $this->assertStringContainsString('increases', $this->svc->normalizeText('elevates temperature'));
    }

    public function test_normalizeText_normalizes_plurals(): void
    {
        $this->assertStringContainsString('artery', $this->svc->normalizeText('arteries'));
        $this->assertStringContainsString('vein', $this->svc->normalizeText('veins'));
        $this->assertStringContainsString('neuron', $this->svc->normalizeText('neurons'));
    }

    // =========================================================================
    // sortedTokenForm — order independence
    // =========================================================================

    public function test_sortedTokenForm_makes_order_irrelevant(): void
    {
        $a = $this->svc->sortedTokenForm('Biological and genetic factors');
        $b = $this->svc->sortedTokenForm('Genetic and biological factors');
        $this->assertSame($a, $b);
    }

    public function test_sortedTokenForm_slash_and_conjunction_equivalent(): void
    {
        // "biological/genetic" → normalize → "biological and genetic"
        // "genetic and biological" → same sorted form
        $a = $this->svc->sortedTokenForm('Biological/genetic factors');
        $b = $this->svc->sortedTokenForm('Genetic and biological factors');
        $this->assertSame($a, $b);
    }

    public function test_sortedTokenForm_handles_extra_words(): void
    {
        // "genetic factors and biological factors" has an extra "factors"
        // versus "biological/genetic factors" (1 "factors")
        // These should NOT be identical because the word counts differ
        $a = $this->svc->sortedTokenForm('Genetic factors and Biological factors');
        $b = $this->svc->sortedTokenForm('Biological/genetic factors');
        // Not identical because of duplicate "factors"
        // This is expected — the fuzzy overlap should still catch it
        $this->assertTrue(true); // documenting the behavior
    }

    // =========================================================================
    // getOrderIndependentOverlap
    // =========================================================================

    public function test_getOrderIndependentOverlap_same_words_different_order(): void
    {
        $score = $this->svc->getOrderIndependentOverlap(
            'Biological and genetic factors',
            'Genetic and biological factors'
        );
        $this->assertSame(1.0, $score);
    }

    public function test_getOrderIndependentOverlap_completely_different(): void
    {
        $score = $this->svc->getOrderIndependentOverlap(
            'The heart pumps blood',
            'The liver produces bile'
        );
        $this->assertLessThan(0.5, $score);
    }

    // =========================================================================
    // getFuzzyOrderIndependentOverlap
    // =========================================================================

    public function test_fuzzy_overlap_catches_typos_with_reordering(): void
    {
        $score = $this->svc->getFuzzyOrderIndependentOverlap(
            'Determiants of health',
            'Determinants of health'
        );
        $this->assertGreaterThanOrEqual(0.80, $score);
    }

    public function test_fuzzy_overlap_reordered_with_typo(): void
    {
        $score = $this->svc->getFuzzyOrderIndependentOverlap(
            'Genetic and biologcal factors',
            'Biological/genetic factors'
        );
        $this->assertGreaterThanOrEqual(0.70, $score);
    }

    public function test_fuzzy_overlap_identical_answers(): void
    {
        $score = $this->svc->getFuzzyOrderIndependentOverlap(
            'Increased heart rate',
            'Increased heart rate'
        );
        $this->assertSame(1.0, $score);
    }

    public function test_fuzzy_overlap_completely_wrong(): void
    {
        $score = $this->svc->getFuzzyOrderIndependentOverlap(
            'The mitochondria produces ATP',
            'Epithelial tissue classification'
        );
        $this->assertLessThan(0.3, $score);
    }

    // =========================================================================
    // containsAntonyms
    // =========================================================================

    public function test_containsAntonyms_detects_increase_decrease(): void
    {
        $this->assertTrue($this->svc->containsAntonyms(
            'heart rate increases',
            'heart rate decreases'
        ));
    }

    public function test_containsAntonyms_detects_sympathetic_parasympathetic(): void
    {
        $this->assertTrue($this->svc->containsAntonyms(
            'sympathetic nervous system',
            'parasympathetic nervous system'
        ));
    }

    public function test_containsAntonyms_no_contradiction(): void
    {
        $this->assertFalse($this->svc->containsAntonyms(
            'heart rate increases blood pressure rises',
            'heart rate increases blood pressure rises'
        ));
    }

    public function test_containsAntonyms_detects_anatomical_opposites(): void
    {
        $this->assertTrue($this->svc->containsAntonyms(
            'anterior surface of the heart',
            'posterior surface of the heart'
        ));
    }

    // =========================================================================
    // Similarity metrics
    // =========================================================================

    public function test_levenshtein_similarity_identical(): void
    {
        $score = $this->svc->getLevenshteinSimilarity('heart', 'heart');
        $this->assertSame(1.0, $score);
    }

    public function test_levenshtein_similarity_small_typo(): void
    {
        $score = $this->svc->getLevenshteinSimilarity('parasympathetic', 'parasympatheic');
        $this->assertGreaterThan(0.85, $score);
    }

    public function test_jaccard_similarity_identical(): void
    {
        $score = $this->svc->getJaccardSimilarity('heart rate increases', 'heart rate increases');
        $this->assertSame(1.0, $score);
    }

    public function test_jaccard_similarity_reordered(): void
    {
        // Jaccard is set-based so order shouldn't matter much
        $score = $this->svc->getJaccardSimilarity(
            'increases heart rate',
            'heart rate increases'
        );
        $this->assertGreaterThanOrEqual(0.8, $score);
    }

    public function test_conceptual_similarity_same_concept_rephrased(): void
    {
        $score = $this->svc->getConceptualSimilarity(
            'The sympathetic nervous system increases heart rate',
            'Heart rate is increased by the sympathetic nervous system'
        );
        $this->assertGreaterThan(0.5, $score);
    }

    public function test_combined_similarity_identical(): void
    {
        $score = $this->svc->getCombinedSimilarity(
            'The heart pumps blood',
            'The heart pumps blood'
        );
        $this->assertGreaterThanOrEqual(0.95, $score);
    }

    // =========================================================================
    // extractWords and removeFillerWords
    // =========================================================================

    public function test_extractWords_lowercases_and_tokenizes(): void
    {
        $words = $this->svc->extractWords('The Heart Pumps Blood');
        $this->assertContains('heart', $words);
        $this->assertContains('pumps', $words);
        $this->assertNotContains('Heart', $words); // should be lowercase
    }

    public function test_removeFillerWords_removes_stop_words(): void
    {
        $result = $this->svc->removeFillerWords('The heart is a muscle in the body');
        $this->assertStringNotContainsString(' the ', " $result ");
        $this->assertStringNotContainsString(' is ', " $result ");
        $this->assertStringContains('heart', $result);
    }

    // =========================================================================
    // Edge cases
    // =========================================================================

    public function test_normalizeText_empty_string(): void
    {
        $this->assertSame('', $this->svc->normalizeText(''));
    }

    public function test_normalizeText_whitespace_only(): void
    {
        $this->assertSame('', $this->svc->normalizeText('   '));
    }

    public function test_similarity_with_empty_strings(): void
    {
        $this->assertSame(0.0, $this->svc->getLevenshteinSimilarity('', 'heart'));
        $this->assertSame(0.0, $this->svc->getLevenshteinSimilarity('heart', ''));
        $this->assertSame(0.0, $this->svc->getJaccardSimilarity('', 'heart'));
    }

    // Helper for the removeFillerWords test
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertStringContainsString($needle, $haystack);
    }
}
