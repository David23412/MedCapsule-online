<?php

namespace App\Services;

/**
 * Enhanced TextProcessingService for Written Answer Evaluation
 * Provides comprehensive text analysis and similarity calculation methods
 */
class TextProcessingService
{
    private $cacheEnabled = false;
    private $cacheTtl = 3600;

    /**
     * Advanced text normalization with typo correction and semantic understanding
     */
    public function normalizeText(string $text): string
    {
        // Convert to lowercase
        $text = strtolower($text);

        // Remove punctuation except hyphens and apostrophes
        $text = preg_replace('/[^\w\s\-\'\.]/', '', $text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));

        // Handle common medical abbreviations and symbols
        $replacements = [
            '°c' => 'degrees celsius',
            '°f' => 'degrees fahrenheit',
            '↑' => 'increased',
            '↓' => 'decreased',
            '→' => 'leads to',
            '←' => 'comes from',
            '±' => 'plus or minus',
            '>' => 'greater than',
            '<' => 'less than',
            '≥' => 'greater than or equal to',
            '≤' => 'less than or equal to',
        ];

        foreach ($replacements as $symbol => $replacement) {
            $text = str_replace($symbol, $replacement, $text);
        }

        // Apply typo correction for common medical terms
        $text = $this->correctMedicalTypos($text);

        // Apply semantic normalization (understanding context)
        $text = $this->applySemanticNormalization($text);

        return trim($text);
    }

    /**
     * Correct common medical typos and misspellings
     */
    private function correctMedicalTypos(string $text): string
    {
        $typoCorrections = [
            // Heart and cardiovascular
            'hart' => 'heart',
            'hear' => 'heart',
            'caridac' => 'cardiac',
            'cardic' => 'cardiac',
            'ventrical' => 'ventricle',
            'ventricl' => 'ventricle',
            'atria' => 'atrium', // normalize plural
            'ventricles' => 'ventricle', // normalize plural
            'artey' => 'artery',
            'arteries' => 'artery', // normalize plural
            'vein' => 'vein',
            'veins' => 'vein', // normalize plural

            // Nervous system
            'parasympatheic' => 'parasympathetic',
            'sympatheic' => 'sympathetic',
            'parasympathetc' => 'parasympathetic',
            'sympathetc' => 'sympathetic',
            'nueron' => 'neuron',
            'neurone' => 'neuron',
            'synapse' => 'synapse',
            'synaps' => 'synapse',
            'axone' => 'axon',
            'axom' => 'axon',
            'dendrite' => 'dendrite',
            'dendrit' => 'dendrite',

            // Respiratory
            'lung' => 'lung',
            'lungs' => 'lung', // normalize plural
            'alveloi' => 'alveoli',
            'alvelo' => 'alveoli',
            'diaphram' => 'diaphragm',
            'diaphrgm' => 'diaphragm',
            'trachea' => 'trachea',
            'trache' => 'trachea',
            'bronchi' => 'bronchi',
            'bronch' => 'bronchi',

            // Biochemistry
            'protien' => 'protein',
            'protine' => 'protein',
            'carbohydrate' => 'carbohydrate',
            'carb' => 'carbohydrate',
            'lipid' => 'lipid',
            'lipd' => 'lipid',
            'atp' => 'atp',
            'adp' => 'adp',
            'dna' => 'dna',
            'rna' => 'rna',
            'glycolysis' => 'glycolysis',
            'glyclysis' => 'glycolysis',
            'pyruvate' => 'pyruvate',
            'pyruvat' => 'pyruvate',

            // Histology
            'epithelal' => 'epithelial',
            'epithelum' => 'epithelial',
            'connective' => 'connective',
            'conective' => 'connective',
            'muscel' => 'muscle',
            'muscels' => 'muscle',

            // General medical
            'tempreture' => 'temperature',
            'temp' => 'temperature',
            'presure' => 'pressure',
            'pressur' => 'pressure',
            'metablism' => 'metabolism',
            'metabolsm' => 'metabolism',
            'inflamation' => 'inflammation',
            'inflamation' => 'inflammation',
            'infection' => 'infection',
            'infetcion' => 'infection',

            // Word order/context corrections
            'flows in' => 'flows through',
            'flows from' => 'flows through',
            'blood in vein' => 'blood in veins',
            'vein in blood' => 'blood in veins',
            'oxygen in lung' => 'oxygen in lungs',
            'lung in oxygen' => 'oxygen in lungs',
        ];

        foreach ($typoCorrections as $typo => $correction) {
            // Use word boundaries to avoid partial replacements
            $text = preg_replace('/\b' . preg_quote($typo, '/') . '\b/i', $correction, $text);
        }

        return $text;
    }

    /**
     * Apply semantic normalization to understand context better
     */
    private function applySemanticNormalization(string $text): string
    {
        $semanticRules = [
            // Direction/flow understanding
            '/\b(blood|oxygen|fluid)\s+(?:flows?\s+)?(?:in|through|via|by)\s+(vein|artery|capillary)\b/i' => '$1 flows through $2',
            '/\b(vein|artery|capillary)\s+(?:flows?\s+)?(?:in|through|via|by)\s+(blood|oxygen|fluid)\b/i' => '$2 flows through $1',

            // Location/position understanding
            '/\b(heart|lung|liver|kidney)\s+(?:is\s+)?(?:in|at|on)\s+(chest|thorax|abdomen|pelvis)\b/i' => '$1 is located in $2',
            '/\b(chest|thorax|abdomen|pelvis)\s+(?:contains?|has)\s+(heart|lung|liver|kidney)\b/i' => '$2 is located in $1',

            // Function understanding
            '/\b(heart|lungs|kidneys|liver)\s+(?:function|work|role)\s+to\s+(.+)/i' => '$1 functions to $2',
            '/\b(.+)\s+(?:is|are)\s+(?:function|role)\s+of\s+(heart|lungs|kidneys|liver)/i' => '$1 is function of $2',

            // Process understanding
            '/\b(protein|synthesis|dna|replication|glycolysis)\s+(?:happens?|occurs?)\s+(?:in|at|on)\s+(.+)/i' => '$1 occurs in $2',
            '/\b(.+)\s+(?:is|are)\s+(?:location|site|place)\s+of\s+(protein|synthesis|dna|replication)/i' => '$2 occurs in $1',

            // Quantitative understanding
            '/\b(bp|blood pressure|hr|heart rate)\s+(?:is|are)\s+(\d+)(?:\/|over)?(\d+)?/i' => '$1 is $2/$3',
            '/\b(\d+)(?:\/|over)?(\d+)?\s+(?:is|are)?\s+(bp|blood pressure|hr|heart rate)/i' => '$3 is $1/$2',
        ];

        foreach ($semanticRules as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    /**
     * Process text input
     */
    public function processText(string $text): string
    {
        return $this->normalizeText($text);
    }

    /**
     * Clean text content
     */
    public function cleanText(string $text): string
    {
        return $this->normalizeText($text);
    }

    /**
     * Extract words from text
     */
    public function extractWords(string $text): array
    {
        $text = $this->normalizeText($text);
        return array_filter(explode(' ', $text));
    }

    /**
     * Remove filler words and get meaningful content
     */
    public function removeFillerWords(string $text): string
    {
        $fillerWords = [
            'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by',
            'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does',
            'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can',
            'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they',
            'me', 'him', 'her', 'us', 'them', 'my', 'your', 'his', 'its', 'our', 'their',
            'what', 'which', 'who', 'when', 'where', 'why', 'how', 'all', 'any', 'both', 'each',
            'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own',
            'same', 'so', 'than', 'too', 'very'
        ];

        $words = $this->extractWords($text);
        $filteredWords = array_filter($words, function($word) use ($fillerWords) {
            return !in_array(strtolower($word), $fillerWords) && strlen($word) > 1;
        });

        return implode(' ', $filteredWords);
    }

    /**
     * Calculate Levenshtein similarity
     */
    public function getLevenshteinSimilarity(string $text1, string $text2): float
    {
        $text1 = $this->normalizeText($text1);
        $text2 = $this->normalizeText($text2);

        if (empty($text1) || empty($text2)) {
            return 0.0;
        }

        $levDistance = levenshtein($text1, $text2);
        $maxLength = max(strlen($text1), strlen($text2));

        return $maxLength > 0 ? (1 - ($levDistance / $maxLength)) : 0.0;
    }

    /**
     * Calculate Jaccard similarity
     */
    public function getJaccardSimilarity(string $text1, string $text2): float
    {
        $words1 = $this->extractWords($text1);
        $words2 = $this->extractWords($text2);

        if (empty($words1) && empty($words2)) {
            return 1.0;
        }

        if (empty($words1) || empty($words2)) {
            return 0.0;
        }

        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));

        return count($union) > 0 ? (count($intersection) / count($union)) : 0.0;
    }

    /**
     * Calculate keyword overlap ratio
     */
    public function getKeywordOverlapRatio(string $text1, string $text2): float
    {
        $words1 = $this->extractWords($text1);
        $words2 = $this->extractWords($text2);

        if (empty($words1) || empty($words2)) {
            return 0.0;
        }

        $commonWords = array_intersect($words1, $words2);
        $totalWords = array_unique(array_merge($words1, $words2));

        return count($totalWords) > 0 ? (count($commonWords) / count($totalWords)) : 0.0;
    }

    /**
     * Check if texts contain antonyms (contradictory terms)
     */
    public function containsAntonyms(string $text1, string $text2): bool
    {
        $antonymPairs = [
            ['increase', 'decrease'],
            ['high', 'low'],
            ['fast', 'slow'],
            ['more', 'less'],
            ['greater', 'lesser'],
            ['larger', 'smaller'],
            ['positive', 'negative'],
            ['acidic', 'basic'],
            ['sympathetic', 'parasympathetic'],
            ['anterior', 'posterior'],
            ['superior', 'inferior'],
            ['medial', 'lateral'],
            ['proximal', 'distal'],
            ['flexion', 'extension'],
            ['contraction', 'relaxation'],
            ['systole', 'diastole'],
            ['inspiration', 'expiration'],
            ['absorption', 'secretion'],
            ['excitatory', 'inhibitory']
        ];

        $words1 = $this->extractWords($text1);
        $words2 = $this->extractWords($text2);

        foreach ($antonymPairs as $pair) {
            $hasFirst1 = in_array($pair[0], $words1);
            $hasSecond1 = in_array($pair[1], $words1);
            $hasFirst2 = in_array($pair[0], $words2);
            $hasSecond2 = in_array($pair[1], $words2);

            // Check if one text has one antonym and the other has the opposite
            if (($hasFirst1 && $hasSecond2) || ($hasSecond1 && $hasFirst2)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate conceptual similarity
     */
    public function getConceptualSimilarity(string $text1, string $text2): float
    {
        $text1 = $this->normalizeText($text1);
        $text2 = $this->normalizeText($text2);

        // Use a combination of Jaccard and keyword overlap for conceptual similarity
        $jaccard = $this->getJaccardSimilarity($text1, $text2);
        $keyword = $this->getKeywordOverlapRatio($text1, $text2);

        // Weight the conceptual similarity more heavily on keyword overlap
        return ($jaccard * 0.4) + ($keyword * 0.6);
    }

    /**
     * Calculate text similarity (general method)
     */
    public function calculateSimilarity(string $text1, string $text2): float
    {
        return $this->getConceptualSimilarity($text1, $text2);
    }

    /**
     * Set cache configuration
     */
    public function setCacheConfig(bool $enabled, int $ttl = 3600): void
    {
        $this->cacheEnabled = $enabled;
        $this->cacheTtl = $ttl;
    }
}
