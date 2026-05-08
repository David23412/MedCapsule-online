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

        // Expand slashes to " and " BEFORE stripping special characters
        // so "biological/genetic" → "biological and genetic"
        $text = $this->expandSlashSeparators($text);
        $text = $this->expandWordSeparators($text);

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
        $stop = $this->stopWordSet();
        $words = $this->extractWords($text);
        $filtered = array_filter($words, fn($w) => !isset($stop[$w]) && strlen($w) > 1);
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
        static $antonymPairs = [
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

        // Flip word arrays to O(1) hash-key sets instead of O(n) in_array() scans.
        $set1 = array_fill_keys($this->extractWords($text1), true);
        $set2 = array_fill_keys($this->extractWords($text2), true);

        foreach ($antonymPairs as [$termA, $termB]) {
            if ((isset($set1[$termA]) && isset($set2[$termB])) ||
                (isset($set1[$termB]) && isset($set2[$termA]))) {
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
            // Cardiovascular
            'hart'            => 'heart',
            'caridac'         => 'cardiac',
            'cardic'          => 'cardiac',
            'ventrical'       => 'ventricle',
            'ventricl'        => 'ventricle',
            'atria'           => 'atrium',
            'artey'           => 'artery',
            'arterry'         => 'artery',
            'arrythmia'       => 'arrhythmia',
            'arythmia'        => 'arrhythmia',
            'tachycarida'     => 'tachycardia',
            'bradycarida'     => 'bradycardia',
            'atherosclersois' => 'atherosclerosis',
            'atheroschlerosis'=> 'atherosclerosis',
            'hypertention'    => 'hypertension',
            'hypotention'     => 'hypotension',
            'hemorhage'       => 'hemorrhage',
            'hemmorhage'      => 'hemorrhage',
            'hemorrhagge'     => 'hemorrhage',
            'ischaemia'       => 'ischemia',

            // Nervous
            'parasympatheic'  => 'parasympathetic',
            'sympatheic'      => 'sympathetic',
            'behavioural'     => 'behavioral',
            'behaviour'       => 'behavior',
            'parasympathetc'  => 'parasympathetic',
            'sympathetc'      => 'sympathetic',
            'nueron'          => 'neuron',
            'neurone'         => 'neuron',
            'nuerotransmitter'=> 'neurotransmitter',
            'cerebrm'         => 'cerebrum',
            'cerebelum'       => 'cerebellum',
            'nervus'          => 'nervous',
            'nervious'        => 'nervous',
            'nerous'          => 'nervous',
            'nevrous'         => 'nervous',
            'synaps'          => 'synapse',
            'dendrit'         => 'dendrite',
            'decreses'        => 'decreases',
            'increaes'        => 'increases',
            'increses'        => 'increases',
            'decreaes'        => 'decreases',

            // Respiratory
            'alveloi'         => 'alveoli',
            'alvelo'          => 'alveoli',
            'diaphram'        => 'diaphragm',
            'diaphrgm'        => 'diaphragm',
            'pnemonia'        => 'pneumonia',
            'pnuemonia'       => 'pneumonia',
            'pneumona'        => 'pneumonia',
            'pharanyx'        => 'pharynx',
            'laranyx'         => 'larynx',
            'bronchitus'      => 'bronchitis',

            // Biochemistry
            'protien'         => 'protein',
            'protine'         => 'protein',
            'glyclysis'       => 'glycolysis',
            'glycolsis'       => 'glycolysis',
            'pyruvat'         => 'pyruvate',
            'gluconeogenisis' => 'gluconeogenesis',
            'metabolsm'       => 'metabolism',
            'metablism'       => 'metabolism',
            'catabolsim'      => 'catabolism',
            'anabolsim'       => 'anabolism',

            // Histology / Anatomy
            'epithelal'       => 'epithelial',
            'epithelum'       => 'epithelial',
            'epethelial'      => 'epithelial',
            'conective'       => 'connective',
            'conectiv'        => 'connective',
            'muscel'          => 'muscle',
            'muscl'           => 'muscle',
            'ligiment'        => 'ligament',
            'tendion'         => 'tendon',
            'cartilidge'      => 'cartilage',
            'periostium'      => 'periosteum',

            // General medical
            'tempreture'      => 'temperature',
            'temperture'      => 'temperature',
            'presure'         => 'pressure',
            'pressur'         => 'pressure',
            'inflamation'     => 'inflammation',
            'inflammtion'     => 'inflammation',
            'infetcion'       => 'infection',
            'infecton'        => 'infection',
            'determiants'     => 'determinants',
            'determants'      => 'determinants',
            'determnants'     => 'determinants',
            'deteminants'     => 'determinants',
            'homeostais'      => 'homeostasis',
            'homoeostasis'    => 'homeostasis',
            'diagnositc'      => 'diagnostic',
            'diagnositic'     => 'diagnostic',
            'prognsis'        => 'prognosis',
            'symptomes'       => 'symptoms',
            'syndrom'         => 'syndrome',
            'congential'      => 'congenital',
            'hereditory'      => 'hereditary',
            'perscription'    => 'prescription',
            'adminsitration'  => 'administration',

            // Immunology
            'nautral'         => 'natural',
            'natuaral'        => 'natural',
            'imuunity'        => 'immunity',
            'imunity'         => 'immunity',
            'antobody'        => 'antibody',
            'antibodie'       => 'antibody',
            'lympocyte'       => 'lymphocyte',
            'lymhocyte'       => 'lymphocyte',
            'thrombocye'      => 'thrombocyte',
            'erythrocye'      => 'erythrocyte',
            'imunoglobulin'   => 'immunoglobulin',
            'immmunoglobulin' => 'immunoglobulin',
            'vacination'      => 'vaccination',
            'innoculation'    => 'inoculation',

            // Pharmacology
            'pharmacolgy'     => 'pharmacology',
            'toxicolgy'       => 'toxicology',
            'physiolgoy'      => 'physiology',
            'physiolgy'       => 'physiology',
            'pathophisiology' => 'pathophysiology',
            'anatomey'        => 'anatomy',
            'histolgy'        => 'histology',
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
            // Phrase-level normalization for location/process descriptions
            '/\btakes\s+place\s+in\b/i'           => 'occurs in',
            '/\bhappens?\s+in\b/i'                => 'occurs in',
            '/\bis\s+found\s+in\b/i'              => 'occurs in',
            '/\bis\s+located\s+in\b/i'            => 'occurs in',
            '/\bis\s+carried\s+out\s+in\b/i'      => 'occurs in',
            '/\bis\s+performed\s+in\b/i'          => 'occurs in',
            '/\bpumping\b/i'                      => 'pumps',
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
            // Adjective/Adverb → common base normalization
            '/\bnaturally\b/i'                    => 'natural',
            '/\bpassively\b/i'                    => 'passive',
            '/\bartificially\b/i'                 => 'artificial',
            '/\bnatural\s+acquired\b/i'           => 'natural',
            '/\bpassive\s+acquired\b/i'           => 'passive',
            '/\bartificial\s+acquired\b/i'        => 'artificial',
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
            '/\b(protein synthesis|dna replication|glycolysis|krebs cycle|tca cycle|citric acid cycle|electron transport chain|oxidative phosphorylation|translation)\s+(?:takes\s+place|happens?|occurs?|is\s+(?:carried\s+out|performed|found|done))\s+(?:in|at|on)\s+(.+)/i'
                => '$1 occurs in $2',
        ];

        foreach ($rules as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return $text;
    }

    // ---------------------------------------------------------------------------
    // Slash and conjunction normalization
    // ---------------------------------------------------------------------------

    /**
     * Expand slash-separated terms to "X and Y" format.
     *
     * "biological/genetic" → "biological and genetic"
     * "DNA/RNA"            → "DNA and RNA"
     * "A/B/C"              → "A and B and C" (grammatically simple but semantically correct)
     *
     * Only expands slashes that separate words (not numeric fractions like "120/80").
     */
    private function expandSlashSeparators(string $text): string
    {
        // Don't expand numeric fractions (e.g., "120/80", "3/4")
        return preg_replace('/(?<![0-9])\/(?![0-9])/', ' and ', $text) ?? $text;
    }

    /**
     * Expand punctuation separators inside alphabetic compounds to spaces.
     *
     * This makes "open-ended" equivalent to "open ended" while preserving
     * medically meaningful alphanumeric forms such as "cox-1" or "5-ht".
     */
    private function expandWordSeparators(string $text): string
    {
        return preg_replace('/(?<=[a-z])[-_](?=[a-z])/i', ' ', $text) ?? $text;
    }

    /**
     * Build a canonical (sorted) token representation of a text.
     *
     * This enables order-independent comparison so that:
     *   "Biological and genetic factors" → "and biological factors genetic"
     *   "Genetic and biological factors" → "and biological factors genetic"
     *
     * Used to detect reordered answers that are semantically identical.
     */
    public function sortedTokenForm(string $text): string
    {
        $words = $this->extractWords($text);
        sort($words);
        return implode(' ', $words);
    }

    /**
     * Order-independent keyword overlap.
     *
     * Extracts content words (no stop words), sorts them, and compares as sets.
     * This means "Genetic and biological factors" matches "Biological/genetic factors"
     * regardless of word order.
     */
    public function getOrderIndependentOverlap(string $text1, string $text2): float
    {
        $stop = $this->stopWordSet();

        $w1 = array_values(array_unique(array_filter(
            $this->extractWords($text1),
            fn($w) => !isset($stop[$w]) && strlen($w) >= 2
        )));
        $w2 = array_values(array_unique(array_filter(
            $this->extractWords($text2),
            fn($w) => !isset($stop[$w]) && strlen($w) >= 2
        )));

        if (empty($w1) && empty($w2)) { return 1.0; }
        if (empty($w1) || empty($w2)) { return 0.0; }

        sort($w1);
        sort($w2);

        $common = count(array_intersect($w1, $w2));
        $total  = count(array_unique(array_merge($w1, $w2)));

        return $total > 0 ? $common / $total : 0.0;
    }

    /**
     * Fuzzy order-independent overlap using per-word Levenshtein matching.
     *
     * Like getOrderIndependentOverlap but also catches typos:
     *   "Determiants of health" matches "Determinants of health"
     *   "Genetic and biologcal factors" matches "Biological/genetic factors"
     */
    public function getFuzzyOrderIndependentOverlap(string $text1, string $text2): float
    {
        $stop = $this->stopWordSet();

        $w1 = array_values(array_filter(
            $this->extractWords($text1),
            fn($w) => !isset($stop[$w]) && strlen($w) >= 2
        ));
        $w2 = array_values(array_filter(
            $this->extractWords($text2),
            fn($w) => !isset($stop[$w]) && strlen($w) >= 2
        ));

        if (empty($w1) && empty($w2)) { return 1.0; }
        if (empty($w1) || empty($w2)) { return 0.0; }

        // Count how many words in w1 have a fuzzy match in w2
        $matchedInW1 = 0;
        $usedW2 = [];
        foreach ($w1 as $word1) {
            foreach ($w2 as $j => $word2) {
                if (isset($usedW2[$j])) { continue; }
                if ($this->wordsFuzzyMatch($word1, $word2)) {
                    $matchedInW1++;
                    $usedW2[$j] = true;
                    break;
                }
            }
        }

        // Symmetric: also count how many words in w2 have a fuzzy match in w1
        $matchedInW2 = 0;
        $usedW1 = [];
        foreach ($w2 as $word2) {
            foreach ($w1 as $j => $word1) {
                if (isset($usedW1[$j])) { continue; }
                if ($this->wordsFuzzyMatch($word1, $word2)) {
                    $matchedInW2++;
                    $usedW1[$j] = true;
                    break;
                }
            }
        }

        // Average of both directions for balanced coverage
        $fwd = $matchedInW1 / count($w1);
        $rev = $matchedInW2 / count($w2);

        return ($fwd + $rev) / 2.0;
    }

    /**
     * Check if two words are similar enough to be considered a fuzzy match.
     * Uses the same adaptive thresholds as WrittenAnswerEvaluationService::wordsAreSimilar.
     */
    private function wordsFuzzyMatch(string $word1, string $word2): bool
    {
        if ($word1 === $word2) { return true; }

        $len  = max(strlen($word1), strlen($word2));
        $dist = levenshtein($word1, $word2);

        if ($len <= 4) { return $dist <= 1; }
        if ($len <= 8) { return $dist <= 2; }
        return $dist <= 3;
    }

    // ---------------------------------------------------------------------------
    // Static word lists
    // ---------------------------------------------------------------------------

    /** @return string[] */
    /**
     * Stop-word lookup set (keys = words, values = true) built once per process.
     * Using a hash map turns per-word O(n) in_array() lookups into O(1) isset().
     *
     * @return array<string,true>
     */
    private function stopWordSet(): array
    {
        static $set = null;

        if ($set !== null) {
            return $set;
        }

        $set = array_fill_keys([
            'a','an','the','and','or','but','in','on','at','to','for','of','with','by',
            'is','are','was','were','be','been','being','have','has','had',
            'do','does','did','will','would','could','should','may','might','must','can',
            'this','that','these','those',
            'i','you','he','she','it','we','they','me','him','her','us','them',
            'my','your','his','its','our','their',
            'what','which','who','when','where','why','how',
            'all','any','both','each','few','more','most','other','some','such',
            'no','nor','not','only','own','same','so','than','too','very',
        ], true);

        return $set;
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
    /**
     * Medical-aware heuristic stemmer.
     * Reduces medical terms to their semantic roots by stripping common suffixes
     * while preserving the core meaning for concept matching.
     */
    public function medicalStem(string $word): string
    {
        $word = strtolower(trim($word));
        if (strlen($word) <= 3) return $word;

        // Order matters: longer suffixes first
        $rules = [
            '/ation$/' => '',    // activation -> activ
            '/ities$/' => '',    // immunities -> immun
            '/ness$/'  => '',    // weakness -> weak
            '/itis$/'  => '',    // gastritis -> gastr
            '/logy$/'  => '',    // biology -> bio
            '/ical$/'  => '',    // biological -> biolog
            '/ally$/'  => '',    // naturally -> natural
            '/ing$/'   => '',    // secreting -> secret
            '/ism$/'   => '',    // metabolism -> metabol
            '/ist$/'   => '',    // agonist -> agon
            '/ity$/'   => '',    // immunity -> immun
            '/ive$/'   => '',    // active -> activ
            '/ion$/'   => '',    // secretion -> secret
            '/ory$/'   => '',    // secretory -> secret
            '/ary$/'   => '',    // biliary -> bili
            '/ed$/'    => '',    // secreted -> secret
            '/es$/'    => '',    // types -> typ
            '/ly$/'    => '',    // natural -> natur (wait, handle carefully)
            '/ic$/'    => '',    // cardiac -> cardi
            '/al$/'    => '',    // renal -> ren
            '/us$/'    => '',    // nucleus -> nucle
            '/ia$/'    => '',    // hypoxemia -> hypoxem
            '/te$/'    => 't',    // secrete -> secret (matches secretion too)
            '/s$/'     => '',    // cells -> cell
            '/e$/'     => '',    // generic e removal for long words
        ];

        foreach ($rules as $pattern => $replacement) {
            $stemmed = preg_replace($pattern, $replacement, $word);
            if ($stemmed !== $word && strlen($stemmed) >= 3) {
                return $stemmed;
            }
        }

        return $word;
    }
}
