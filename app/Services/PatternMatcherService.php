<?php

namespace App\Services;

/**
 * Enhanced PatternMatcherService for Written Answer Evaluation
 * Provides medical pattern matching and domain-specific analysis
 */
class PatternMatcherService
{
    private $cacheEnabled = false;
    private $cacheTtl = 3600;
    private array $customPatterns = [];

    /**
     * Medical pattern definitions
     */
    private $medicalPatterns = [
        'sympathetic_effects' => [
            'patterns' => [
                '/sympathetic.*increases?.*heart.*rate/i',
                '/sympathetic.*decreases?.*digestion/i',
                '/sympathetic.*increases?.*blood.*pressure/i',
                '/sympathetic.*fight.*or.*flight/i',
                '/sympathetic.*stimulates?.*adrenal/i',
                '/SNS.*increases?.*HR/i',
                '/SNS.*decreases?.*GI/i'
            ],
            'weight' => 0.8
        ],
        'parasympathetic_effects' => [
            'patterns' => [
                '/parasympathetic.*decreases?.*heart.*rate/i',
                '/parasympathetic.*increases?.*digestion/i',
                '/parasympathetic.*rest.*and.*digest/i',
                '/parasympathetic.*decreases?.*blood.*pressure/i',
                '/PNS.*decreases?.*HR/i',
                '/PNS.*increases?.*GI/i'
            ],
            'weight' => 0.8
        ],
        'action_potential' => [
            'patterns' => [
                '/depolarization.*repolarization/i',
                '/sodium.*influx.*potassium.*efflux/i',
                '/threshold.*potential/i',
                '/voltage.*gated.*channels/i',
                '/Na\+.*K\+.*pump/i'
            ],
            'weight' => 0.7
        ],
        'cardiac_cycle' => [
            'patterns' => [
                '/systole.*diastole/i',
                '/contraction.*relaxation/i',
                '/atrial.*ventricular/i',
                '/AV.*valve.*open/i',
                '/semilunar.*valve/i'
            ],
            'weight' => 0.7
        ],
        'respiratory_mechanics' => [
            'patterns' => [
                '/inspiration.*expiration/i',
                '/diaphragm.*contracts/i',
                '/alveolar.*pressure/i',
                '/boyle.*law/i',
                '/surfactant/i'
            ],
            'weight' => 0.6
        ],
        'renal_physiology' => [
            'patterns' => [
                '/glomerular.*filtration/i',
                '/tubular.*reabsorption/i',
                '/tubular.*secretion/i',
                '/countercurrent.*mechanism/i',
                '/loop.*henle/i'
            ],
            'weight' => 0.6
        ],
        'glycolysis' => [
            'patterns' => [
                '/glucose.*pyruvate/i',
                '/ATP.*production/i',
                '/NADH.*formation/i',
                '/substrate.*level.*phosphorylation/i'
            ],
            'weight' => 0.7
        ],
        'tca_cycle' => [
            'patterns' => [
                '/citric.*acid.*cycle/i',
                '/krebs.*cycle/i',
                '/acetyl.*CoA/i',
                '/oxidative.*decarboxylation/i',
                '/NADH.*FADH2/i'
            ],
            'weight' => 0.8
        ],
        'electron_transport' => [
            'patterns' => [
                '/electron.*transport.*chain/i',
                '/oxidative.*phosphorylation/i',
                '/ATP.*synthase/i',
                '/proton.*gradient/i',
                '/inner.*mitochondrial.*membrane/i'
            ],
            'weight' => 0.7
        ],
        'dna_replication' => [
            'patterns' => [
                '/leading.*lagging.*strand/i',
                '/DNA.*polymerase/i',
                '/helicase.*unwinds/i',
                '/semiconservative/i',
                '/okazaki.*fragments/i'
            ],
            'weight' => 0.6
        ],
        'epithelial_tissue' => [
            'patterns' => [
                '/simple.*stratified/i',
                '/squamous.*cuboidal.*columnar/i',
                '/basement.*membrane/i',
                '/tight.*junction/i',
                '/desmosome/i'
            ],
            'weight' => 0.6
        ],
        'connective_tissue' => [
            'patterns' => [
                '/loose.*dense/i',
                '/extracellular.*matrix/i',
                '/collagen.*fibers/i',
                '/specialized.*connective/i'
            ],
            'weight' => 0.6
        ],
        'muscle_tissue' => [
            'patterns' => [
                '/skeletal.*cardiac.*smooth/i',
                '/striated.*non.*striated/i',
                '/sarcomere/i',
                '/myosin.*actin/i'
            ],
            'weight' => 0.6
        ],
        'anatomical_position' => [
            'patterns' => [
                '/anterior.*posterior/i',
                '/superior.*inferior/i',
                '/medial.*lateral/i',
                '/proximal.*distal/i',
                '/palmar.*plantar/i'
            ],
            'weight' => 0.5
        ],
        'skeletal_system' => [
            'patterns' => [
                '/axial.*appendicular/i',
                '/long.*short.*flat.*irregular/i',
                '/compact.*spongy.*bone/i',
                '/diaphysis.*epiphysis/i'
            ],
            'weight' => 0.5
        ],
        'joint_types' => [
            'patterns' => [
                '/fibrous.*cartilaginous.*synovial/i',
                '/hinge.*pivot.*ball.*socket/i',
                '/suture.*syndesmosis/i'
            ],
            'weight' => 0.5
        ]
    ];

    /**
     * Match a specific pattern in text
     */
    public function matchesPattern(string $patternName, string $text): bool
    {
        $registeredPatterns = $this->getRegisteredPatterns();

        if (!isset($registeredPatterns[$patternName])) {
            return false;
        }

        $text = strtolower($text);
        $patterns = $registeredPatterns[$patternName]['patterns'];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get pattern match score
     */
    public function getPatternScore(string $patternName, string $text): float
    {
        $registeredPatterns = $this->getRegisteredPatterns();

        if (!isset($registeredPatterns[$patternName])) {
            return 0.0;
        }

        $text = strtolower($text);
        $patterns = $registeredPatterns[$patternName]['patterns'];
        $weight = $registeredPatterns[$patternName]['weight'];

        $matches = 0;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $matches++;
            }
        }

        if ($matches === 0) {
            return 0.0;
        }

        // Calculate score based on number of matching patterns
        $matchRatio = $matches / count($patterns);
        return $matchRatio * $weight;
    }

    /**
     * Get all matching patterns for text
     */
    public function getMatchingPatterns(string $text): array
    {
        $matches = [];
        $text = strtolower($text);

        foreach ($this->getRegisteredPatterns() as $patternName => $config) {
            $score = $this->getPatternScore($patternName, $text);
            if ($score > 0) {
                $matches[$patternName] = [
                    'score' => $score,
                    'weight' => $config['weight'],
                    'matched_patterns' => $this->getMatchedPatterns($patternName, $text)
                ];
            }
        }

        return $matches;
    }

    /**
     * Identify patterns in a single text payload.
     */
    public function identifyPatterns(string $text): array
    {
        return $this->getMatchingPatterns($text);
    }

    /**
     * Return patterns shared by submitted and correct text.
     */
    public function getPatternMatches(string $submittedText, string $correctText): array
    {
        $matches = [];
        $submittedText = strtolower($submittedText);
        $correctText = strtolower($correctText);

        foreach ($this->getRegisteredPatterns() as $patternName => $_config) {
            $submittedScore = $this->getPatternScore($patternName, $submittedText);
            $correctScore = $this->getPatternScore($patternName, $correctText);

            if ($submittedScore <= 0 || $correctScore <= 0) {
                continue;
            }

            $confidence = min($submittedScore, $correctScore) / max($submittedScore, $correctScore);

            $matches[] = [
                'pattern' => $patternName,
                'description' => str_replace('_', ' ', $patternName),
                'confidence' => (float) max(0.0, min(1.0, $confidence)),
            ];
        }

        return $matches;
    }

    /**
     * Register an additional matcher at runtime.
     */
    public function registerPatternMatcher(string $name, array $patterns, float $weight = 0.5): void
    {
        $this->customPatterns[$name] = [
            'patterns' => $patterns,
            'weight' => max(0.0, min(1.0, $weight)),
        ];
    }

    /**
     * Return all registered patterns, including runtime additions.
     */
    public function getRegisteredPatterns(): array
    {
        return array_merge($this->medicalPatterns, $this->customPatterns);
    }

    /**
     * Get specific matched patterns
     */
    private function getMatchedPatterns(string $patternName, string $text): array
    {
        $matched = [];
        $patterns = $this->getRegisteredPatterns()[$patternName]['patterns'] ?? [];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $matched[] = [
                    'pattern' => $pattern,
                    'matches' => $matches
                ];
            }
        }

        return $matched;
    }

    /**
     * Match patterns in text (legacy method)
     */
    public function matchPatterns(string $text, array $patterns): array
    {
        $matches = [];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $text, $match)) {
                $matches[$pattern] = $match;
            }
        }

        return $matches;
    }

    /**
     * Find specific pattern in text (legacy method)
     */
    public function findPattern(string $text, string $pattern): bool
    {
        return preg_match($pattern, $text) === 1;
    }

    /**
     * Extract pattern groups (legacy method)
     */
    public function extractGroups(string $text, string $pattern): array
    {
        if (preg_match($pattern, $text, $matches)) {
            return $matches;
        }

        return [];
    }

    /**
     * Replace patterns in text (legacy method)
     */
    public function replacePatterns(string $text, array $patterns): string
    {
        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
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
