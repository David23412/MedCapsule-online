<?php

namespace App\Services;

/**
 * Enhanced TextProcessingService for Written Answer Evaluation
 * Provides comprehensive text analysis and similarity calculation methods.
 *
 * Upgrade over previous version:
 * - Bigram + trigram overlap for phrase-level matching
 * - TF-IDF-style term weighting so rare medical terms count more than common words
 * - Genuinely distinct getConceptualSimilarity (was previously just Jaccard + keyword reweighted)
 * - Synonym normalization expanded
 */
class TextProcessingService
{
    private bool $cacheEnabled = false;
    private int  $cacheTtl     = 3600;

    // ---------------------------------------------------------------------------
    // Public API
    // ---------------------------------------------------------------------------

    public function normalizeText(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s\-\'\.]/', '', $text);
        $text = preg_replace('/\s+/', ' ', trim($text));

        foreach ($this->symbolReplacements() as $symbol => $replacement) {
            $text = str_replace($symbol, $replacement, $text);
        }

        $text = $this->correctMedicalTypos($text);
        $text = $this->normalizeCommonVariants($text);
        $text = $this->applySemanticNormalization($text);

        return trim($text);
    }

    public function processText(string $text): string  { return $this->normalizeText($text); }
    public function cleanText(string $text): string    { return $this->normalizeText($text); }

    public function extractWords(string $text): array
    {
        $text = $this->normalizeText($text);
        return array_values(array_filter(explode(' ', $text), fn($w) => $w !== ''));
    }

    public function removeFillerWords(string $text): string
    {
        $stop = $this->stopWords();
        $words = $this->extractWords($text);
        $filtered = array_filter($words, fn($w) => !in_array($w, $stop, true) && strlen($w) > 1);
        return implode(' ', $filtered);
    }

    public function setCacheConfig(bool $enabled, int $ttl = 3600): void
    {
        $this->cacheEnabled = $enabled;
        $this->cacheTtl     = $ttl;
    }

    // ---------------------------------------------------------------------------
    // Similarity metrics
    // ---------------------------------------------------------------------------

    /**
     * Character-level Levenshtein similarity — best for catching typos.
     */
    public function getLevenshteinSimilarity(string $text1, string $text2): float
    {
        $t1 = $this->normalizeText($text1);
        $t2 = $this->normalizeText($text2);

        if ($t1 === '' || $t2 === '') {
            return 0.0;
        }

        $maxLen = max(strlen($t1), strlen($t2));
        return $maxLen > 0 ? 1.0 - (levenshtein($t1, $t2) / $maxLen) : 0.0;
    }

    /**
     * Word-level Jaccard similarity — good for vocabulary overlap.
     */
    public function getJaccardSimilarity(string $text1, string $text2): float
    {
        $w1 = $this->extractWords($text1);
        $w2 = $this->extractWords($text2);

        if (empty($w1) && empty($w2)) { return 1.0; }
        if (empty($w1) || empty($w2)) { return 0.0; }

        $inter = array_intersect($w1, $w2);
        $union = array_unique(array_merge($w1, $w2));

        return count($union) > 0 ? count($inter) / count($union) : 0.0;
    }

    /**
     * Keyword overlap ratio — precision-focused (correct ÷ total unique).
     */
    public function getKeywordOverlapRatio(string $text1, string $text2): float
    {
        $w1 = $this->extractWords($text1);
        $w2 = $this->extractWords($text2);

        if (empty($w1) || empty($w2)) { return 0.0; }

        $common = array_intersect($w1, $w2);
        $total  = array_unique(array_merge($w1, $w2));

        return count($total) > 0 ? count($common) / count($total) : 0.0;
    }

    /**
     * N-gram similarity (bigrams + trigrams).
     *
     * Phrase-level matching — catches "heart rate increases" vs "increases heart rate"
     * as a partial match rather than a miss, and gives credit for multi-word medical
     * phrases that appear verbatim.
     *
     * Weight: 60% bigrams, 40% trigrams.
     */
    public function getNgramSimilarity(string $text1, string $text2, int $n = 2): float
    {
        $ngrams1 = $this->extractNgrams($this->removeFillerWords($text1), $n);
        $ngrams2 = $this->extractNgrams($this->removeFillerWords($text2), $n);

        if (empty($ngrams1) && empty($ngrams2)) { return 1.0; }
        if (empty($ngrams1) || empty($ngrams2)) { return 0.0; }

        $inter = array_intersect($ngrams1, $ngrams2);
        $union = array_unique(array_merge($ngrams1, $ngrams2));

        return count($union) > 0 ? count($inter) / count($union) : 0.0;
    }

    /**
     * TF-IDF-style weighted keyword similarity.
     *
     * Terms that are rare in general medical language (longer, domain-specific words)
     * are up-weighted. Common words like "the", "is", "cell" get lower weight.
     * This means matching "glycosaminoglycan" matters far more than matching "the".
     *
     * IDF is approximated by word length and a domain-term bonus — no corpus needed.
     */
    public function getTfidfSimilarity(string $text1, string $text2): float
    {
        $words1 = array_filter(
            explode(' ', $this->removeFillerWords($this->normalizeText($text1))),
            fn($w) => strlen($w) >= 3
        );
        $words2 = array_filter(
            explode(' ', $this->removeFillerWords($this->normalizeText($text2))),
            fn($w) => strlen($w) >= 3
        );

        if (empty($words1) || empty($words2)) { return 0.0; }

        $vocab = array_unique(array_merge(array_values($words1), array_values($words2)));

        $vec1 = $this->buildTfidfVector($words1, $vocab);
        $vec2 = $this->buildTfidfVector($words2, $vocab);

        return $this->cosineSimilarity($vec1, $vec2);
    }

    /**
     * Genuinely conceptual similarity — combines:
     *   - TF-IDF cosine (term importance-weighted)
     *   - Bigram overlap (phrase-level)
     *   - Trigram overlap (longer phrase continuity)
     *
     * Weights chosen so that phrase-level matching (bigram/trigram) provides
     * evidence of correct ordering/context, while TF-IDF ensures rare domain
     * terms dominate the score.
     */
    public function getConceptualSimilarity(string $text1, string $text2): float
    {
        $tfidf   = $this->getTfidfSimilarity($text1, $text2);
        $bigram  = $this->getNgramSimilarity($text1, $text2, 2);
        $trigram = $this->getNgramSimilarity($text1, $text2, 3);

        // TF-IDF: 50%, bigram: 30%, trigram: 20%
        return ($tfidf * 0.50) + ($bigram * 0.30) + ($trigram * 0.20);
    }

    /**
     * Combined blended score for higher-level grading decisions.
     * Weights reflect the relative diagnostic value of each metric:
     *   - Conceptual (TF-IDF + ngram): most important
     *   - Keyword overlap: raw coverage
     *   - Jaccard: vocabulary breadth
     *   - Levenshtein: surface-level typo tolerance
     */
    public function getCombinedSimilarity(string $text1, string $text2): float
    {
        return ($this->getConceptualSimilarity($text1, $text2) * 0.40)
             + ($this->getKeywordOverlapRatio($text1, $text2)  * 0.25)
             + ($this->getJaccardSimilarity($text1, $text2)    * 0.20)
             + ($this->getLevenshteinSimilarity($text1, $text2) * 0.15);
    }

    /**
     * Calculate text similarity (general alias).
     */
    public function calculateSimilarity(string $text1, string $text2): float
    {
        return $this->getConceptualSimilarity($text1, $text2);
    }

    /**
     * Antonym / contradiction detection.
     *
     * Returns true when text1 and text2 contain opposing terms from a known pair,
     * indicating the student's answer contradicts the correct answer rather than
     * just paraphrasing it.
     */
    public function containsAntonyms(string $text1, string $text2): bool
    {
        $antonymPairs = [
            ['increase', 'decrease'],
            ['increases', 'decreases'],
            ['elevated', 'reduced'],
            ['high', 'low'],
            ['fast', 'slow'],
            ['more', 'less'],
            ['greater', 'lesser'],
            ['larger', 'smaller'],
            ['positive', 'negative'],
            ['acidic', 'basic'],
            ['alkaline', 'acidic'],
            ['sympathetic', 'parasympathetic'],
            ['anterior', 'posterior'],
            ['superior', 'inferior'],
            ['medial', 'lateral'],
            ['proximal', 'distal'],
            ['afferent', 'efferent'],
            ['flexion', 'extension'],
            ['contraction', 'relaxation'],
            ['systole', 'diastole'],
            ['depolarization', 'repolarization'],
            ['inspiration', 'expiration'],
            ['inhalation', 'exhalation'],
            ['absorption', 'secretion'],
            ['excitatory', 'inhibitory'],
            ['endocrine', 'exocrine'],
            ['aerobic', 'anaerobic'],
            ['oxidation', 'reduction'],
            ['anabolism', 'catabolism'],
        ];

        $words1 = $this->extractWords($text1);
        $words2 = $this->extractWords($text2);

        foreach ($antonymPairs as [$termA, $termB]) {
            $has1A = in_array($termA, $words1, true);
            $has1B = in_array($termB, $words1, true);
            $has2A = in_array($termA, $words2, true);
            $has2B = in_array($termB, $words2, true);

            if (($has1A && $has2B) || ($has1B && $has2A)) {
                return true;
            }
        }

        return false;
    }

    // ---------------------------------------------------------------------------
    // Internal helpers
    // ---------------------------------------------------------------------------

    /**
     * Extract all n-grams from a space-separated word list.
     *
     * @return string[]
     */
    private function extractNgrams(string $text, int $n): array
    {
        $words = array_values(array_filter(
            explode(' ', $this->normalizeText($text)),
            fn($w) => $w !== ''
        ));

        if (count($words) < $n) {
            return $words; // fall back to unigrams rather than returning empty
        }

        $ngrams = [];
        for ($i = 0, $max = count($words) - $n + 1; $i < $max; $i++) {
            $ngrams[] = implode(' ', array_slice($words, $i, $n));
        }

        return $ngrams;
    }

    /**
     * Build a TF-IDF-approximated vector over a shared vocabulary.
     *
     * IDF is approximated as:
     *   - base weight 1.0
     *   - +0.05 per character over 5 (longer = rarer/more specific)
     *   - +0.5 bonus for recognised domain terms
     *   - /2.0 penalty for very common 3-4 letter words
     *
     * @param  string[]  $words  Tokenised words from one answer
     * @param  string[]  $vocab  Shared vocabulary (union of both answers)
     * @return float[]
     */
    private function buildTfidfVector(array $words, array $vocab): array
    {
        $wordCount  = count($words);
        $freqMap    = array_count_values($words);
        $domainBonus = $this->domainTermSet();
        $commonPenalty = $this->commonShortWords();

        $vector = [];
        foreach ($vocab as $term) {
            $tf = isset($freqMap[$term]) ? ($freqMap[$term] / max($wordCount, 1)) : 0.0;

            // Approximate IDF by word characteristics
            $len        = strlen($term);
            $idf        = 1.0 + (max(0, $len - 5) * 0.05);                   // length bonus
            $idf       += isset($domainBonus[$term]) ? 0.5 : 0.0;            // domain term bonus
            $idf       *= isset($commonPenalty[$term]) ? 0.5 : 1.0;          // common word penalty

            $vector[] = $tf * $idf;
        }

        return $vector;
    }

    /**
     * Cosine similarity between two float vectors.
     *
     * @param  float[]  $v1
     * @param  float[]  $v2
     */
    private function cosineSimilarity(array $v1, array $v2): float
    {
        $dot  = 0.0;
        $mag1 = 0.0;
        $mag2 = 0.0;

        foreach ($v1 as $i => $val) {
            $dot  += $val * ($v2[$i] ?? 0.0);
            $mag1 += $val * $val;
            $mag2 += ($v2[$i] ?? 0.0) * ($v2[$i] ?? 0.0);
        }

        $denom = sqrt($mag1) * sqrt($mag2);
        return $denom > 0.0 ? $dot / $denom : 0.0;
    }

    // ---------------------------------------------------------------------------
    // Normalization helpers
    // ---------------------------------------------------------------------------

    private function symbolReplacements(): array
    {
        return [
            '°c' => 'degrees celsius',
            '°f' => 'degrees fahrenheit',
            '↑'  => 'increased',
            '↓'  => 'decreased',
            '→'  => 'leads to',
            '←'  => 'comes from',
            '±'  => 'plus or minus',
            '>'  => 'greater than',
            '<'  => 'less than',
            '≥'  => 'greater than or equal to',
            '≤'  => 'less than or equal to',
        ];
    }

    private function correctMedicalTypos(string $text): string
    {
        $corrections = [
            'hart'            => 'heart',
            'caridac'         => 'cardiac',
            'cardic'          => 'cardiac',
            'ventrical'       => 'ventricle',
            'ventricl'        => 'ventricle',
            'atria'           => 'atrium',
            'artey'           => 'artery',
            'parasympatheic'  => 'parasympathetic',
            'sympatheic'      => 'sympathetic',
            'parasympathetc'  => 'parasympathetic',
            'sympathetc'      => 'sympathetic',
            'nueron'          => 'neuron',
            'neurone'         => 'neuron',
            'alveloi'         => 'alveoli',
            'alvelo'          => 'alveoli',
            'diaphram'        => 'diaphragm',
            'diaphrgm'        => 'diaphragm',
            'protien'         => 'protein',
            'protine'         => 'protein',
            'glyclysis'       => 'glycolysis',
            'pyruvat'         => 'pyruvate',
            'epithelal'       => 'epithelial',
            'epithelum'       => 'epithelial',
            'conective'       => 'connective',
            'muscel'          => 'muscle',
            'tempreture'      => 'temperature',
            'presure'         => 'pressure',
            'pressur'         => 'pressure',
            'metablism'       => 'metabolism',
            'metabolsm'       => 'metabolism',
            'inflamation'     => 'inflammation',
            'infetcion'       => 'infection',
        ];

        foreach ($corrections as $typo => $fix) {
            $text = preg_replace('/\b' . preg_quote($typo, '/') . '\b/i', $fix, $text) ?? $text;
        }

        return $text;
    }

    private function normalizeCommonVariants(string $text): string
    {
        $replacements = [
            '/\btype\s+ii\b/i'                   => 'type 2',
            '/\btype\s+iii\b/i'                  => 'type 3',
            '/\btype\s+iv\b/i'                   => 'type 4',
            '/\bpulse\b/i'                        => 'heart rate',
            '/\bhr\b/i'                           => 'heart rate',
            '/\bgi\b/i'                           => 'digestion',
            '/\bgastrointestinal activity\b/i'   => 'digestion',
            '/\bgastrointestinal\b/i'             => 'digestion',
            '/\bgut motility\b/i'                 => 'digestion',
            '/\bperistalsis\b/i'                  => 'digestion',
            '/\braises?\b/i'                      => 'increases',
            '/\belevates?\b/i'                    => 'increases',
            '/\bboosts?\b/i'                      => 'increases',
            '/\blowers?\b/i'                      => 'decreases',
            '/\breduces?\b/i'                     => 'decreases',
            '/\bslows?\b/i'                       => 'decreases',
            '/\bthorax\b/i'                       => 'chest',
            '/\bthoracic cavity\b/i'              => 'chest',
            '/\bphysician\b/i'                    => 'doctor',
            '/\billness\b/i'                      => 'disease',
            '/\bdiagnoses\b/i'                    => 'diagnose',
            '/\btreats\b/i'                       => 'treat',
            '/\bmultiple layers\b/i'              => 'multiple layer',
            '/\bsingle layers?\b/i'               => 'single layer',
            // Plural → singular normalisation for key terms
            '/\barteries\b/i'                     => 'artery',
            '/\bveins\b/i'                        => 'vein',
            '/\blungs\b/i'                        => 'lung',
            '/\bkidneys\b/i'                      => 'kidney',
            '/\bneurons\b/i'                      => 'neuron',
            '/\bribosomes\b/i'                    => 'ribosome',
            '/\bmitochondria\b/i'                 => 'mitochondrion',
            '/\bventricles\b/i'                   => 'ventricle',
            '/\batria\b/i'                        => 'atrium',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return $text;
    }

    private function applySemanticNormalization(string $text): string
    {
        $rules = [
            '/\b(blood|oxygen|fluid)\s+(?:flows?\s+)?(?:in|from|via|by)\s+(vein|artery|capillary)\b/i'
                => '$1 flows through $2',
            '/\b(heart|lung|liver|kidney)\s+(?:is\s+)?(?:in|at|on)\s+(chest|thorax|abdomen|pelvis)\b/i'
                => '$1 is located in $2',
            '/\b(protein synthesis|dna replication|glycolysis|krebs cycle)\s+(?:happens?|occurs?)\s+(?:in|at|on)\s+(.+)/i'
                => '$1 occurs in $2',
        ];

        foreach ($rules as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return $text;
    }

    // ---------------------------------------------------------------------------
    // Static word lists
    // ---------------------------------------------------------------------------

    /** @return string[] */
    private function stopWords(): array
    {
        return [
            'a','an','the','and','or','but','in','on','at','to','for','of','with','by',
            'is','are','was','were','be','been','being','have','has','had',
            'do','does','did','will','would','could','should','may','might','must','can',
            'this','that','these','those',
            'i','you','he','she','it','we','they','me','him','her','us','them',
            'my','your','his','its','our','their',
            'what','which','who','when','where','why','how',
            'all','any','both','each','few','more','most','other','some','such',
            'no','nor','not','only','own','same','so','than','too','very',
        ];
    }

    /**
     * Domain terms that deserve a TF-IDF bonus.
     * These are specific enough that matching one is meaningful.
     *
     * @return array<string,true>
     */
    private function domainTermSet(): array
    {
        static $set = null;
        if ($set !== null) { return $set; }

        $terms = [
            // Biochemistry
            'glycosaminoglycan','proteoglycan','oligosaccharide','glycoprotein',
            'phospholipid','glycolysis','pyruvate','acetylcoa','nadh','fadh',
            'oxidative','phosphorylation','gluconeogenesis','glycogenolysis',
            'glycogenesis','krebs','citric','electron','ribosome','mitochondrion',
            'endoplasmic','reticulum','nucleotide','coenzyme','cofactor',
            'transcription','translation','replication','polymerase','helicase',
            // Physiology
            'parasympathetic','sympathetic','depolarization','repolarization',
            'hyperpolarization','sinoatrial','atrioventricular','glomerular',
            'filtration','reabsorption','aldosterone','antidiuretic','surfactant',
            'compliance','peristalsis','isovolumetric','ejection','systole','diastole',
            // Anatomy / Histology
            'epithelial','stratified','squamous','cuboidal','columnar','transitional',
            'desmosome','hemidesmosome','glycocalyx','extracellular','sarcomere',
            'myosin','actin','tropomyosin','osteoblast','osteoclast','chondrocyte',
            // Immunology
            'immunoglobulin','lymphocyte','neutrophil','macrophage','cytokine',
            'chemokine','complement','antibody','antigen','histocompatibility',
        ];

        $set = array_fill_keys($terms, true);
        return $set;
    }

    /**
     * Short words that are common enough to warrant a TF-IDF penalty.
     *
     * @return array<string,true>
     */
    private function commonShortWords(): array
    {
        static $set = null;
        if ($set !== null) { return $set; }

        $words = [
            'cell','body','blood','bone','made','make','form','found',
            'part','type','role','used','high','low','rate','help',
            'move','send','take','give','come','goes','also','into',
        ];

        $set = array_fill_keys($words, true);
        return $set;
    }
}