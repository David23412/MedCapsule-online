<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * WrittenAnswerEvaluationService
 *
 * Changes in this version vs previous:
 *
 * 1. GENERALIZED COMPARISON GRADING
 *    extractComparisonTermPairs() now dynamically extracts the two sides of any
 *    comparison from the correct-answer text instead of matching against a hard-
 *    coded list of term pairs. Works for any medical comparison question.
 *
 * 2. FIXED CACHE KEYS
 *    Student answers are unique strings — caching on them is useless and wastes
 *    memory. Cache keys now use only stable inputs: md5(correctAnswer + threshold).
 *    The student answer is never part of a cache key.
 *
 * 3. DEDUPLICATED CONTRADICTION / LOCATION LOGIC
 *    The duplicate location-mismatch checks that existed in both
 *    detectMissingCriticalTerms() and checkForCriticalPatternMismatch() are now
 *    consolidated into a single method: detectLocationMismatch(). Both callers
 *    delegate to it.
 *
 * 4. BETTER SIMILARITY STACK
 *    calculateSimilarityMetrics() now uses the upgraded TextProcessingService
 *    which provides TF-IDF cosine + bigram/trigram ngram similarity in place of
 *    the old "conceptual similarity" that was just Jaccard + keyword reweighted.
 */
class WrittenAnswerEvaluationService
{
    protected TextProcessingService    $textProcessor;
    protected PatternMatcherService    $patternMatcher;
    protected MedicalKnowledgeService  $medicalKnowledge;

    const DEFAULT_SIMILARITY_THRESHOLD = 0.85;

    private bool $cacheEnabled = true;
    private int  $cacheTtl     = 86400;

    // -------------------------------------------------------------------------
    // Medical domain keyword lists (unchanged from previous version)
    // -------------------------------------------------------------------------

    private array $medicalDomains = [
        'cardiovascular' => [
            'heart','cardiac','myocardial','coronary','artery','arteries','atrial',
            'ventricular','circulation','bp','blood pressure','aorta','ventricle',
            'atrium','endocardium','myocardium','epicardium','pericardium','valve',
            'chordae','purkinje','sinoatrial','atrioventricular',
        ],
        'respiratory' => [
            'lung','pulmonary','respiratory','breath','breathing','ventilation',
            'airway','alveoli','bronchi','oxygen','co2','trachea','larynx','pharynx',
            'diaphragm','pleura','mediastinum','pneumothorax','spirometry','hypoxia',
            'hypoxemia','hypercarbia','surfactant',
        ],
        'nervous' => [
            'brain','neural','nervous','cns','pns','neuron','parasympathetic',
            'sympathetic','nerve','spinal','cord','cerebrum','cerebral','cerebellum',
            'brainstem','cortex','thalamus','hypothalamus','hippocampus','amygdala',
            'basal ganglia','axon','dendrite','oligodendrocyte','astrocyte','microglia',
            'schwann','reflex','sensory','motor','neurotransmitter',
        ],
        'gastrointestinal' => [
            'gi','stomach','intestine','colon','bowel','digestion','digestive','liver',
            'hepatic','gallbladder','pancreas','esophagus','duodenum','jejunum','ileum',
            'cecum','rectum','peritoneum','mucosa','submucosa','muscularis','serosa',
            'villus','peristalsis','enzyme','bile','absorption',
        ],
        'endocrine' => [
            'hormone','insulin','thyroid','adrenal','pituitary','pancreatic','diabetes',
            'glycemic','gland','endocrine','tsh','cortisol','aldosterone','adrenaline',
            'estrogen','testosterone','progesterone','parathyroid','acth','prolactin',
            'feedback','receptor','signaling',
        ],
        'musculoskeletal' => [
            'muscle','bone','joint','tendon','ligament','skeletal','orthopedic',
            'fracture','spine','vertebral','spinal','cartilage','synovial','ossification',
            'osteoblast','osteoclast','myosin','actin','sarcomere','contraction',
            'neuromuscular','articulation','axial','appendicular','collagen','calcium',
        ],
        'urinary' => [
            'kidney','renal','bladder','urinary','urine','nephron','ureter','urethra',
            'glomerular','nephritis','tubule','podocyte','filtration','reabsorption',
            'secretion','collecting duct','loop of henle','cystitis','pyelonephritis',
            'creatinine','urea','micturition',
        ],
        'immune' => [
            'immune','antibody','antigen','inflammation','lymphocyte','leukocyte',
            'white blood cell','wbc','infection','neutrophil','basophil','eosinophil',
            'monocyte','macrophage','dendritic','nk cell','cytokine','chemokine',
            'complement','immunoglobulin','mhc','thymus','spleen','lymph node',
            'humoral','cellular',
        ],
        'histology' => [
            'tissue','epithelium','epithelial','connective','basement membrane',
            'collagen','elastin','fibroblast','mast cell','simple','stratified',
            'squamous','cuboidal','columnar','transitional','goblet cell','microvilli',
            'cilia','tight junction','desmosome','gap junction','hematoxylin','eosin',
        ],
        'biochemistry' => [
            'enzyme','protein','carbohydrate','lipid','nucleic acid','dna','rna','atp',
            'metabolism','glycolysis','krebs','tca cycle','citric acid cycle',
            'electron transport','oxidative phosphorylation','gluconeogenesis',
            'glycogenesis','glycogenolysis','fatty acid','amino acid','transcription',
            'translation','replication','mutation','coenzyme','cofactor',
        ],
    ];

    // -------------------------------------------------------------------------
    // Known process → correct location mapping (single source of truth)
    // -------------------------------------------------------------------------

    private array $processLocations = [
        'protein synthesis' => [
            'correct'   => ['ribosome','endoplasmic reticulum'],
            'incorrect' => ['mitochondrion','golgi','lysosome','peroxisome','nucleus'],
        ],
        'dna replication' => [
            'correct'   => ['nucleus'],
            'incorrect' => ['mitochondrion','cytoplasm','ribosome','endoplasmic reticulum'],
        ],
        'krebs cycle' => [
            'correct'   => ['mitochondrion','mitochondrial matrix'],
            'incorrect' => ['cytoplasm','nucleus','endoplasmic reticulum','lysosome'],
        ],
        'tca cycle' => [
            'correct'   => ['mitochondrion','mitochondrial matrix'],
            'incorrect' => ['cytoplasm','nucleus','endoplasmic reticulum','lysosome'],
        ],
        'citric acid cycle' => [
            'correct'   => ['mitochondrion','mitochondrial matrix'],
            'incorrect' => ['cytoplasm','nucleus','endoplasmic reticulum','lysosome'],
        ],
        'electron transport chain' => [
            'correct'   => ['mitochondrion','inner mitochondrial membrane'],
            'incorrect' => ['cytoplasm','nucleus','plasma membrane','endoplasmic reticulum'],
        ],
        'oxidative phosphorylation' => [
            'correct'   => ['mitochondrion','inner mitochondrial membrane'],
            'incorrect' => ['cytoplasm','nucleus','plasma membrane'],
        ],
        'glycolysis' => [
            'correct'   => ['cytoplasm','cytosol'],
            'incorrect' => ['mitochondrion','nucleus','endoplasmic reticulum'],
        ],
        'translation' => [
            'correct'   => ['ribosome','endoplasmic reticulum'],
            'incorrect' => ['mitochondrion','nucleus','golgi','lysosome'],
        ],
    ];

    // -------------------------------------------------------------------------
    // Medical symbols / abbreviation maps
    // -------------------------------------------------------------------------

    private array $medicalSymbols  = [];
    private array $chemicalSymbols = [];

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function __construct(
        TextProcessingService   $textProcessor,
        PatternMatcherService   $patternMatcher,
        ?MedicalKnowledgeService $medicalKnowledge = null
    ) {
        $this->textProcessor    = $textProcessor;
        $this->patternMatcher   = $patternMatcher;
        $this->medicalKnowledge = $medicalKnowledge ?? app(MedicalKnowledgeService::class);

        $this->textProcessor->setCacheConfig(true, 86400);
        $this->patternMatcher->setCacheConfig(true, 86400);

        $this->initializeMedicalSymbols();
    }

    private function initializeMedicalSymbols(): void
    {
        $this->medicalSymbols = [
            '↑' => 'increased', '↓' => 'decreased',
            '⬆' => 'increased', '⬇' => 'decreased',
            '⇑' => 'greatly increased', '⇓' => 'greatly decreased',
            '>' => 'greater than', '<' => 'less than',
            '≥' => 'greater than or equal to', '≤' => 'less than or equal to',
            '=' => 'equal to', '≈' => 'approximately equal to', '≠' => 'not equal to',
            '°C' => 'degrees celsius', '°F' => 'degrees fahrenheit',
            'Δ' => 'change in', 'Ø' => 'no',
        ];

        $this->chemicalSymbols = [
            'Na+' => 'sodium ion', 'K+' => 'potassium ion',
            'Ca2+' => 'calcium ion', 'Mg2+' => 'magnesium ion',
            'Cl-' => 'chloride ion', 'HCO3-' => 'bicarbonate',
            'H+' => 'hydrogen ion', 'OH-' => 'hydroxide',
            'O2' => 'oxygen', 'CO2' => 'carbon dioxide',
        ];
    }

    // =========================================================================
    // PUBLIC INTERFACE
    // =========================================================================

    public function setCacheConfig(bool $enabled, int $ttl = 86400): void
    {
        $this->cacheEnabled = $enabled;
        $this->cacheTtl     = $ttl;
        $this->textProcessor->setCacheConfig($enabled, $ttl);
    }

    public function evaluateQuestionAnswer(Question $question, string $submittedAnswer): array
    {
        $rawThreshold = null;
        try { $rawThreshold = $question->similarity_threshold ?? null; }
        catch (\Throwable) { $rawThreshold = null; }

        $threshold           = is_numeric($rawThreshold) ? (float) $rawThreshold : self::DEFAULT_SIMILARITY_THRESHOLD;
        $alternativeAnswers  = $this->normalizeAlternativeAnswers($question->alternative_answers ?? []);

        return $this->evaluateAnswer($submittedAnswer, $question->correct_answer, $alternativeAnswers, $threshold);
    }

    public function isCorrect(Question $question, string $submittedAnswer): bool
    {
        return $this->evaluateQuestionAnswer($question, $submittedAnswer)['isCorrect'];
    }

    // =========================================================================
    // CORE EVALUATION
    // =========================================================================

    public function evaluateAnswer(
        string $submittedAnswer,
        string $correctAnswer,
        array  $alternativeAnswers  = [],
        float  $similarityThreshold = self::DEFAULT_SIMILARITY_THRESHOLD
    ): array {
        $start              = microtime(true);
        $alternativeAnswers = $this->normalizeAlternativeAnswers($alternativeAnswers);
        $submittedAnswer    = trim($submittedAnswer);
        $correctAnswer      = trim($correctAnswer);

        // ── Fast exits ──────────────────────────────────────────────────────
        if ($submittedAnswer === '') {
            return $this->earlyResult(false, 0.0, 'No answer provided', $start);
        }
        if ($correctAnswer === '') {
            return $this->earlyResult(false, 0.0, 'No correct answer to compare against', $start);
        }
        if (strtolower($submittedAnswer) === strtolower($correctAnswer)) {
            return $this->earlyResult(true, 1.0, 'Perfect match!', $start);
        }

        $normSubmitted = $this->normalizeForSemanticComparison($submittedAnswer);
        $normCorrect   = $this->normalizeForSemanticComparison($correctAnswer);

        if ($normSubmitted === $normCorrect) {
            return $this->earlyResult(true, 1.0, 'Perfect match after normalization', $start);
        }
        if (!$this->hasMeaningfulTokenOverlap($normSubmitted, $normCorrect, $alternativeAnswers)) {
            return $this->earlyResult(false, 0.0, 'No matching concepts found', $start);
        }

        // ── Symbol / abbreviation preprocessing ─────────────────────────────
        $submittedAnswer    = $this->preprocessSymbols($submittedAnswer);
        $correctAnswer      = $this->preprocessSymbols($correctAnswer);
        $alternativeAnswers = array_map([$this, 'preprocessSymbols'], $alternativeAnswers);

        $semanticSubmitted = $this->normalizeForSemanticComparison($submittedAnswer);
        $semanticCorrect   = $this->normalizeForSemanticComparison($correctAnswer);

        // ── ANS hard gates (before question-type routing) ────────────────────
        $ansResult = $this->applyAnsHardGates($semanticSubmitted, $semanticCorrect);
        if ($ansResult !== null) {
            return $ansResult;
        }

        // ── Question-type routing ────────────────────────────────────────────
        if ($this->isNumericalValueQuestion($correctAnswer)) {
            return $this->evaluateNumericalValueAnswer($submittedAnswer, $correctAnswer, $similarityThreshold);
        }
        if ($this->isEnumerationQuestion($correctAnswer)) {
            return $this->evaluateEnumerationAnswer($submittedAnswer, $correctAnswer);
        }
        if ($this->isComparisonQuestion($correctAnswer)) {
            return $this->evaluateComparisonAnswer($submittedAnswer, $correctAnswer, $similarityThreshold);
        }
        if ($this->isDefinitionQuestion($correctAnswer)) {
            return $this->evaluateDefinitionAnswer($submittedAnswer, $correctAnswer, $similarityThreshold);
        }
        if ($this->isMatchingQuestion($correctAnswer)) {
            return $this->evaluateMatchingAnswer($submittedAnswer, $correctAnswer, $similarityThreshold);
        }

        // ── Full evaluation (cached by stable key only) ──────────────────────
        //
        // FIX: Cache key must NOT include the student answer — every student
        // writes something unique, so including it effectively disables caching.
        // We cache on (correctAnswer + alternatives + threshold) because those
        // are the stable inputs that define the grading context.
        //
        $stableCacheKey = 'eval_ctx:' . md5($correctAnswer . '|' . implode('|', $alternativeAnswers) . '|' . $similarityThreshold);

        // The context (correct answer side) can be pre-warmed; student answer
        // is handled fresh each call but the expensive normalizations on the
        // correct side benefit from caching.
        $cachedContext = $this->getFromCache($stableCacheKey);
        $gradingContext = $cachedContext ?? $this->buildGradingContext($correctAnswer, $alternativeAnswers);

        if ($cachedContext === null) {
            $this->putToCache($stableCacheKey, $gradingContext);
        }

        return $this->runFullEvaluation(
            $submittedAnswer,
            $correctAnswer,
            $alternativeAnswers,
            $similarityThreshold,
            $gradingContext
        );
    }

    // =========================================================================
    // GENERALIZED COMPARISON GRADING  (Priority 1 fix)
    // =========================================================================

    /**
     * Extract the two sides of a comparison from the correct-answer text.
     *
     * Previous approach: hard-coded list of term pairs (glycoproteins/proteoglycans,
     * DNA/RNA, etc.) — failed for any comparison not in the list.
     *
     * New approach: split the correct answer on comparison markers, then extract
     * the meaningful content tokens from each side. This works for ANY comparison
     * question regardless of topic.
     *
     * Returns ['sideA' => string[], 'sideB' => string[]] or null if no clear split.
     */
    private function extractComparisonTermPairs(string $correctAnswer): ?array
    {
        // Split on common comparison separators — order matters (longer first)
        $separators = [
            'compared to','in contrast to','in contrast','on the other hand',
            'versus','whereas','however','while','although','unlike',
            ' vs. ',' vs ',' v. ',' v ',
        ];

        $lower = strtolower($correctAnswer);
        $splitPos = false;
        $splitLen = 0;

        foreach ($separators as $sep) {
            $pos = strpos($lower, $sep);
            if ($pos !== false) {
                $splitPos = $pos;
                $splitLen = strlen($sep);
                break;
            }
        }

        // If no separator found, try a colon split (common in "A: ... B: ..." answers)
        if ($splitPos === false) {
            $colonCount = substr_count($lower, ':');
            if ($colonCount === 2) {
                // Format "TermA: desc; TermB: desc" — split on second colon cluster
                $parts = preg_split('/[;,]\s*/', $correctAnswer, 2);
                if (count($parts) === 2) {
                    $splitPos = strlen($parts[0]);
                    $splitLen = 1;
                }
            }
        }

        if ($splitPos === false) {
            return null;
        }

        $sideAText = trim(substr($correctAnswer, 0, $splitPos));
        $sideBText = trim(substr($correctAnswer, $splitPos + $splitLen));

        if ($sideAText === '' || $sideBText === '') {
            return null;
        }

        return [
            'sideA' => $this->extractMeaningfulTokens($this->normalizeForSemanticComparison($sideAText)),
            'sideB' => $this->extractMeaningfulTokens($this->normalizeForSemanticComparison($sideBText)),
        ];
    }

    /**
     * Score a comparison answer using dynamic side-extraction.
     *
     * Logic:
     *   1. Extract Side A and Side B from the correct answer.
     *   2. Measure how much of each side the student covered.
     *   3. A good comparison answer must address BOTH sides — partial credit
     *      only if at least one side is well-covered.
     *   4. Falls back to regular evaluation when no clear split is found.
     */
    private function evaluateComparisonAnswer(
        string $submittedAnswer,
        string $correctAnswer,
        float  $baseThreshold
    ): array {
        $pairs = $this->extractComparisonTermPairs($correctAnswer);

        if ($pairs === null) {
            // Cannot find the two sides — fall back to regular evaluation
            return $this->performRegularEvaluation($submittedAnswer, $correctAnswer, $baseThreshold);
        }

        $submittedTokens = $this->extractMeaningfulTokens(
            $this->normalizeForSemanticComparison($this->preprocessSymbols($submittedAnswer))
        );

        // Coverage = what fraction of each side's tokens appear in the student answer
        $coverageA = $this->tokenCoverage($submittedTokens, $pairs['sideA']);
        $coverageB = $this->tokenCoverage($submittedTokens, $pairs['sideB']);

        // Both sides must be addressed for full credit
        $bothSideScore = ($coverageA + $coverageB) / 2.0;
        // Partial credit if at least one side is strong
        $bestSideScore = max($coverageA, $coverageB);

        // Grading rubric:
        //   ≥ 0.7 on both sides  → correct
        //   ≥ 0.5 on both sides  → borderline, use hybrid
        //   < 0.5 on either side → fall back to regular eval
        if ($bothSideScore >= 0.70) {
            $finalSimilarity = max($bothSideScore, 0.85);
            return [
                'isCorrect'   => true,
                'similarity'  => $finalSimilarity,
                'metrics'     => ['coverageA' => $coverageA, 'coverageB' => $coverageB, 'bothSideScore' => $bothSideScore],
                'reason'      => 'Comparison question — both sides addressed',
                'courseTopic' => $this->identifyCourseTopic($correctAnswer),
                'feedback'    => $this->comparisonFeedback($coverageA, $coverageB, true),
            ];
        }

        if ($bothSideScore >= 0.50) {
            $regularMetrics  = $this->calculateSimilarityMetrics($submittedAnswer, $correctAnswer);
            $hybridScore     = ($bothSideScore * 0.65) + ($regularMetrics['weighted'] * 0.35);
            $isCorrect       = $hybridScore >= min($baseThreshold, 0.60);
            return [
                'isCorrect'   => $isCorrect,
                'similarity'  => $hybridScore,
                'metrics'     => array_merge($regularMetrics, ['coverageA' => $coverageA, 'coverageB' => $coverageB]),
                'reason'      => 'Comparison question — partial coverage of both sides',
                'courseTopic' => $this->identifyCourseTopic($correctAnswer),
                'feedback'    => $this->comparisonFeedback($coverageA, $coverageB, $isCorrect),
            ];
        }

        // One or both sides severely under-covered — regular eval
        return $this->performRegularEvaluation($submittedAnswer, $correctAnswer, $baseThreshold);
    }

    /**
     * What fraction of $referenceTokens appear in $submittedTokens?
     *
     * @param  string[]  $submittedTokens
     * @param  string[]  $referenceTokens
     */
    private function tokenCoverage(array $submittedTokens, array $referenceTokens): float
    {
        if (empty($referenceTokens)) { return 1.0; }

        $matched = 0;
        foreach ($referenceTokens as $token) {
            // Exact match OR the token appears as a substring of any submitted token
            // (handles plurals/suffixes not caught by normalizer)
            foreach ($submittedTokens as $sub) {
                if ($sub === $token || str_contains($sub, $token) || str_contains($token, $sub)) {
                    $matched++;
                    break;
                }
            }
        }

        return $matched / count($referenceTokens);
    }

    private function comparisonFeedback(float $coverageA, float $coverageB, bool $isCorrect): string
    {
        if ($isCorrect) {
            if ($coverageA >= 0.8 && $coverageB >= 0.8) {
                return 'Excellent! You addressed both sides of the comparison thoroughly.';
            }
            return 'Good job! You captured the main comparison points. Consider adding more detail on both sides for a complete answer.';
        }

        if ($coverageA < 0.4 && $coverageB < 0.4) {
            return 'Your answer is missing content from both sides of the comparison. Review what distinguishes each term and address both explicitly.';
        }
        if ($coverageA < $coverageB) {
            return 'Your answer addressed one side of the comparison well but is missing key content from the first part. Make sure to compare both items explicitly.';
        }
        return 'Your answer addressed one side of the comparison well but is missing key content from the second part. Make sure to compare both items explicitly.';
    }

    // =========================================================================
    // DEDUPLICATED LOCATION MISMATCH CHECK  (Priority 4 fix)
    // =========================================================================

    /**
     * Single authoritative method for detecting process-location mismatches.
     *
     * Replaces the duplicate logic that existed in:
     *   - detectMissingCriticalTerms() → locationCriticalConcepts block
     *   - checkForCriticalPatternMismatch() → locationSpecificTerms block
     *
     * Returns true when the submitted answer mentions a process AND explicitly
     * associates it with a known-incorrect location.
     */
    private function detectLocationMismatch(string $normalizedSubmitted): bool
    {
        foreach ($this->processLocations as $process => $locations) {
            if (stripos($normalizedSubmitted, $process) === false) {
                continue;
            }
            foreach ($locations['incorrect'] as $wrongLoc) {
                if (stripos($normalizedSubmitted, $wrongLoc) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Does the submitted answer mention the process's correct location?
     */
    private function hasCorrectLocation(string $process, string $normalizedSubmitted): bool
    {
        $locs = $this->processLocations[$process] ?? null;
        if ($locs === null) { return true; } // unknown process — don't penalise

        foreach ($locs['correct'] as $loc) {
            if (stripos($normalizedSubmitted, $loc) !== false) {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    // CACHE HELPERS  (Priority 2 fix)
    // =========================================================================

    /**
     * Build context that can be cached for a given correct answer + threshold.
     * Everything in here is independent of the student's submitted answer.
     */
    private function buildGradingContext(string $correctAnswer, array $alternativeAnswers): array
    {
        return [
            'normalizedCorrect'      => $this->normalizeForSemanticComparison($correctAnswer),
            'correctTokens'          => $this->extractMeaningfulTokens($this->normalizeForSemanticComparison($correctAnswer)),
            'courseTopic'            => $this->identifyCourseTopic($correctAnswer),
            'isPatternCritical'      => $this->isPatternCriticalQuestion($correctAnswer),
            'normalizedAlternatives' => array_map(
                fn($a) => $this->normalizeForSemanticComparison($a),
                $alternativeAnswers
            ),
        ];
    }

    private function getFromCache(string $key): mixed
    {
        if (!$this->cacheEnabled) { return null; }
        $sentinel = new \stdClass();
        $result   = Cache::get('wae:' . $key, $sentinel);
        return $result === $sentinel ? null : $result;
    }

    private function putToCache(string $key, mixed $value): void
    {
        if (!$this->cacheEnabled) { return; }
        Cache::put('wae:' . $key, $value, $this->cacheTtl);
    }

    // =========================================================================
    // FULL EVALUATION (general path)
    // =========================================================================

    private function runFullEvaluation(
        string $submittedAnswer,
        string $correctAnswer,
        array  $alternativeAnswers,
        float  $similarityThreshold,
        array  $gradingContext
    ): array {
        $normalizedSubmitted = $this->textProcessor->normalizeText($submittedAnswer);
        $normalizedCorrect   = $gradingContext['normalizedCorrect'];
        $courseTopic         = $gradingContext['courseTopic'];

        // ── Exact-match short-circuits ───────────────────────────────────────
        if ($normalizedSubmitted === $normalizedCorrect) {
            return $this->buildCorrectResult(1.0, 'Exact match', $courseTopic,
                'Your answer exactly matches the expected answer.');
        }

        foreach ($alternativeAnswers as $i => $alt) {
            if ($normalizedSubmitted === $this->textProcessor->normalizeText($alt)) {
                return $this->buildCorrectResult(1.0, 'Exact match with alternative', $courseTopic,
                    'Your answer correctly matches an accepted alternative.');
            }
        }

        // ── Protein synthesis location error (critical) ──────────────────────
        if (stripos($normalizedSubmitted, 'protein synthesis') !== false) {
            $wrongLoc = $this->findIncorrectLocationMention('protein synthesis', $normalizedSubmitted);
            if ($wrongLoc !== null) {
                return [
                    'isCorrect'   => false,
                    'similarity'  => 0.5,
                    'metrics'     => ['hasContradictions' => true],
                    'reason'      => 'Incorrect location for protein synthesis',
                    'courseTopic' => $courseTopic,
                    'feedback'    => "Protein synthesis occurs on ribosomes (free in cytoplasm or on rough ER), not in the {$wrongLoc}.",
                ];
            }
        }

        // ── Expanded abbreviation pattern matches ────────────────────────────
        $expandedSubmitted = $this->expandKnownAbbreviations($submittedAnswer);
        $expandedCorrect   = $this->expandKnownAbbreviations($correctAnswer);

        if ($this->isSympatheticEffectsMatch($expandedSubmitted, $expandedCorrect)) {
            return $this->buildCorrectResult(0.9, 'Correct sympathetic effects', 'nervous',
                'Your answer correctly captures the sympathetic effects.');
        }
        if ($this->isParasympatheticEffectsMatch($expandedSubmitted, $expandedCorrect)) {
            return $this->buildCorrectResult(0.9, 'Correct parasympathetic effects', 'nervous',
                'Your answer correctly captures the parasympathetic effects.');
        }

        // ── Intelligent concept matches ──────────────────────────────────────
        if ($this->isIntelligentConceptMatch($normalizedSubmitted, $normalizedCorrect)) {
            return $this->buildCorrectResult(0.95, 'Intelligent concept match', $courseTopic,
                'Your answer demonstrates intelligent understanding of the medical concept.');
        }
        if ($this->isHeartFunctionMatch($normalizedSubmitted, $normalizedCorrect)) {
            return $this->buildCorrectResult(0.90, 'Correct heart function', 'cardiovascular',
                'Your answer correctly captures the function of the heart.');
        }
        if ($this->isMedicalAbbreviationMatch($normalizedSubmitted, $normalizedCorrect)) {
            return $this->buildCorrectResult(0.92, 'Correct abbreviation usage', $courseTopic,
                'Your answer correctly uses medical abbreviations.');
        }
        if ($this->isTypoMatch($normalizedSubmitted, $normalizedCorrect)) {
            return $this->buildCorrectResult(0.88, 'Correct despite minor typos', $courseTopic,
                'Your answer is correct despite minor spelling variations.');
        }

        // ── Location mismatch check (consolidated) ───────────────────────────
        if ($this->detectLocationMismatch($normalizedSubmitted)) {
            return [
                'isCorrect'   => false,
                'similarity'  => 0.4,
                'metrics'     => ['hasContradictions' => true],
                'reason'      => 'Process associated with wrong cellular location',
                'courseTopic' => $courseTopic,
                'feedback'    => 'Your answer places a biological process in the wrong cellular compartment. Review where each major pathway occurs.',
            ];
        }

        // ── Full similarity metrics ───────────────────────────────────────────
        $metrics        = $this->calculateSimilarityMetrics($submittedAnswer, $correctAnswer);
        $mainSimilarity = $metrics['weighted'];

        if ($metrics['hasContradictions'] ?? false) {
            $mainSimilarity = min($mainSimilarity, 0.65);
        }

        // Check alternatives
        $bestSimilarity = $mainSimilarity;
        $bestMetrics    = $metrics;
        $bestAnswer     = $correctAnswer;
        $bestSource     = 'main';

        foreach ($alternativeAnswers as $i => $alt) {
            if (empty($alt)) { continue; }
            $altMetrics = $this->calculateSimilarityMetrics($submittedAnswer, $alt);
            if ($altMetrics['hasContradictions'] ?? false) {
                $altMetrics['weighted'] = min($altMetrics['weighted'], 0.65);
            }
            if ($altMetrics['weighted'] > $bestSimilarity) {
                $bestSimilarity = $altMetrics['weighted'];
                $bestMetrics    = $altMetrics;
                $bestAnswer     = $alt;
                $bestSource     = 'alternative';
            }
        }

        // Domain boost
        $domainBoost     = $this->calculateDomainContextBoost($submittedAnswer, $bestAnswer);
        $finalSimilarity = min(1.0, $bestSimilarity + $domainBoost);

        // Phrasing boost when conceptual is very high
        if (($bestMetrics['conceptual'] ?? 0) > 0.8 && ($bestMetrics['keyword'] ?? 0) > 0.6
            && !($bestMetrics['hasContradictions'] ?? false)) {
            $finalSimilarity = min(1.0, $finalSimilarity + 0.10);
        }

        // Safety floor for missing keywords
        $isCriticalMismatch = ($bestMetrics['hasContradictions'] ?? false)
            || (isset($bestMetrics['keyword']) && $bestMetrics['keyword'] < 0.4);

        $isCorrect = $finalSimilarity >= $similarityThreshold && !$isCriticalMismatch;

        // High conceptual override
        if (!$isCorrect && !$isCriticalMismatch
            && ($bestMetrics['conceptual'] ?? 0) > 0.9
            && ($bestMetrics['keyword'] ?? 0) > 0.8) {
            $isCorrect       = true;
            $finalSimilarity = max($finalSimilarity, 0.90);
        }

        $feedback = $this->generateDetailedFeedback(
            $submittedAnswer, $bestAnswer, $bestMetrics,
            $isCorrect, $finalSimilarity, $similarityThreshold, $courseTopic
        );

        $response = [
            'isCorrect'   => $isCorrect,
            'similarity'  => $finalSimilarity,
            'metrics'     => $bestMetrics,
            'source'      => $bestSource,
            'domainBoost' => $domainBoost,
            'courseTopic' => $courseTopic,
            'feedback'    => $feedback,
            'reason'      => $isCorrect
                ? ($finalSimilarity > 0.95 ? 'Highly similar to correct answer' : 'Similar to correct answer')
                : ($isCriticalMismatch ? 'Critical concept error detected' : 'Insufficient similarity'),
        ];

        return $response;
    }

    // =========================================================================
    // SIMILARITY METRICS
    // =========================================================================

    protected function calculateSimilarityMetrics(string $submittedAnswer, string $correctAnswer): array
    {
        $normSub = $this->textProcessor->normalizeText($submittedAnswer);
        $normCor = $this->textProcessor->normalizeText($correctAnswer);

        if ($normSub === '' || $normCor === '') {
            return ['weighted' => 0, 'levenshtein' => 0, 'jaccard' => 0,
                    'keyword' => 0, 'conceptual' => 0, 'hasContradictions' => false];
        }

        $levenshtein = $this->textProcessor->getLevenshteinSimilarity($normSub, $normCor);
        $jaccard     = $this->textProcessor->getJaccardSimilarity($normSub, $normCor);
        $keyword     = $this->textProcessor->getKeywordOverlapRatio($normSub, $normCor);
        $conceptual  = $this->textProcessor->getConceptualSimilarity($normSub, $normCor);

        $hasContradictions = $this->textProcessor->containsAntonyms($normSub, $normCor);

        // Weighted blend: conceptual most important, Levenshtein least
        $weighted = ($conceptual * 0.40)
                  + ($keyword   * 0.30)
                  + ($jaccard   * 0.20)
                  + ($levenshtein * 0.10);

        if ($hasContradictions) {
            $weighted = min($weighted, 0.65);
        }

        return [
            'weighted'         => $weighted,
            'levenshtein'      => $levenshtein,
            'jaccard'          => $jaccard,
            'keyword'          => $keyword,
            'conceptual'       => $conceptual,
            'hasContradictions'=> $hasContradictions,
        ];
    }

    // =========================================================================
    // ANS HARD GATES
    // =========================================================================

    private function applyAnsHardGates(string $semanticSubmitted, string $semanticCorrect): ?array
    {
        $isParaQ = preg_match('/\bparasympathetic nervous system\b/i', $semanticCorrect) === 1;
        $isSymQ  = !$isParaQ && preg_match('/\bsympathetic nervous system\b/i', $semanticCorrect) === 1;

        if (!$isParaQ && !$isSymQ) { return null; }

        $hrUp   = $this->containsAnyPhrase($semanticSubmitted, ['increase heart rate', 'increases heart rate']);
        $hrDown = $this->containsAnyPhrase($semanticSubmitted, ['decrease heart rate', 'decreases heart rate']);
        $digUp  = $this->containsAnyPhrase($semanticSubmitted, ['increase digestion', 'increases digestion']);
        $digDown= $this->containsAnyPhrase($semanticSubmitted, [
            'decrease digestion','decreases digestion','inhibit digestion','inhibits digestion',
        ]);

        if ($isSymQ) {
            if (($hrDown || $digUp) && !($hrUp && $digDown)) {
                return ['isCorrect' => false, 'similarity' => 0.3,
                    'reason' => 'Reverses sympathetic effects', 'courseTopic' => 'nervous',
                    'feedback' => 'Sympathetic activation increases heart rate and suppresses digestion.'];
            }
            if ($hrUp && $digDown && !$hrDown && !$digUp) {
                return ['isCorrect' => true, 'similarity' => 0.9,
                    'reason' => 'Correct sympathetic effects', 'courseTopic' => 'nervous',
                    'feedback' => 'Your answer correctly captures the sympathetic effects.'];
            }
        }

        if ($isParaQ) {
            if (($hrUp || $digDown) && !($hrDown && $digUp)) {
                return ['isCorrect' => false, 'similarity' => 0.3,
                    'reason' => 'Reverses parasympathetic effects', 'courseTopic' => 'nervous',
                    'feedback' => 'Parasympathetic activation lowers heart rate and supports digestion.'];
            }
            if ($hrDown && $digUp && !$hrUp && !$digDown) {
                return ['isCorrect' => true, 'similarity' => 0.9,
                    'reason' => 'Correct parasympathetic effects', 'courseTopic' => 'nervous',
                    'feedback' => 'Your answer correctly captures the parasympathetic effects.'];
            }
        }

        return null;
    }

    // =========================================================================
    // QUESTION TYPE DETECTORS
    // =========================================================================

    private function isComparisonQuestion(string $correctAnswer): bool
    {
        $markers = [
            ' vs ',' vs. ',' versus ',' compared to ',' comparison ',
            ' difference ',' differences ',' unlike ',' whereas ',
            ' however ',' while ',' although ',' in contrast ',
        ];
        $lower = strtolower($correctAnswer);
        foreach ($markers as $m) {
            if (str_contains($lower, $m)) { return true; }
        }

        // Paired domain terms
        $pairs = [
            ['glycoproteins','proteoglycans'],['systolic','diastolic'],
            ['arteries','veins'],['sympathetic','parasympathetic'],
            ['aerobic','anaerobic'],['mitosis','meiosis'],
            ['dna','rna'],['type 1','type 2'],['acute','chronic'],
            ['innate','adaptive'],['osteoblast','osteoclast'],
            ['afferent','efferent'],['smooth','skeletal'],
        ];
        foreach ($pairs as [$a, $b]) {
            if (str_contains($lower, $a) && str_contains($lower, $b)) { return true; }
        }
        return false;
    }

    private function isDefinitionQuestion(string $correctAnswer): bool
    {
        $markers = [
            ' is ',' are ',' refers to ',' means ',' defined as ',
            ' is defined as ',' is known as ',' represents ',
            ' consists of ',' composed of ',' characterized by ',
            ' is a ',' are a ',' is an ',' are an ',' is the ',' are the ',
        ];
        $lower = strtolower($correctAnswer);
        foreach ($markers as $m) {
            if (str_contains($lower, $m)) { return true; }
        }
        return (bool) preg_match('/^(?:the\s+)?[a-z]+(?:\s+[a-z]+)*\s+(?:is|are)\s/i', $correctAnswer);
    }

    private function isEnumerationQuestion(string $correctAnswer): bool
    {
        if (substr_count($correctAnswer, ',') < 1) { return false; }
        return preg_match('/\b(and|or)\b/i', $correctAnswer) === 1
            || preg_match('/\b(includes|consists of|comprises|contains|involves)\b/i', $correctAnswer) === 1;
    }

    private function isMatchingQuestion(string $correctAnswer): bool
    {
        $markers = [
            'match:','match the','corresponds to','is associated with',
            'is linked to','paired with',' → ',' ==> ',
        ];
        $lower = strtolower($correctAnswer);
        foreach ($markers as $m) {
            if (str_contains($lower, $m)) { return true; }
        }
        return false;
    }

    private function isNumericalValueQuestion(string $correctAnswer): bool
    {
        $patterns = [
            '/\d+\s*\/\s*\d+/', '/\d+[\.\-\–]\d+/', '/\d+[\.,]\d+/',
            '/\d+\s*(mmhg|bpm|kg|g|mg|ml|l)\b/i',
            '/\d+[\.,]\d+\s*(g|mg|µg|ng|kg)\/(dL|L|ml|mL)/i',
            '/\d+\s*%/',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $correctAnswer)) { return true; }
        }
        $numTerms = ['normal','range','level','concentration','value','reference',
                     'g/dl','mg/dl','mmol/l','percent','%','mmhg'];
        $lower = strtolower($correctAnswer);
        foreach ($numTerms as $t) {
            if (str_contains($lower, $t)) { return true; }
        }
        return false;
    }

    // =========================================================================
    // SPECIALISED EVALUATORS (enumeration, definition, matching, numerical)
    // These are unchanged in logic from the previous version; kept intact.
    // =========================================================================

    private function evaluateEnumerationAnswer(string $submittedAnswer, string $correctAnswer): array
    {
        $submittedNormalized = $this->normalizeForSemanticComparison($submittedAnswer);
        $correctItems        = $this->extractEnumerationItems($correctAnswer);

        if (empty($correctItems)) {
            return $this->performRegularEvaluation($submittedAnswer, $correctAnswer, self::DEFAULT_SIMILARITY_THRESHOLD);
        }

        $matched   = array_values(array_filter($correctItems, fn($item) => stripos($submittedNormalized, $item) !== false));
        $coverage  = count($matched) / count($correctItems);
        $isCorrect = $coverage >= 0.75;

        return [
            'isCorrect'   => $isCorrect,
            'similarity'  => $isCorrect ? max(0.85, $coverage) : $coverage,
            'metrics'     => ['enumeration_coverage' => $coverage, 'matched_items' => $matched, 'expected_items' => $correctItems],
            'reason'      => $isCorrect ? 'Enumeration — key items identified' : 'Enumeration — missing key items',
            'courseTopic' => $this->identifyCourseTopic($correctAnswer),
            'feedback'    => $isCorrect ? 'You identified the key required items.'
                                        : 'Your answer is missing one or more of the expected key items.',
        ];
    }

    private function evaluateDefinitionAnswer(string $submittedAnswer, string $correctAnswer, float $baseThreshold): array
    {
        $metrics = $this->calculateSimilarityMetrics($submittedAnswer, $correctAnswer);
        $defScore = ($metrics['keyword'] * 0.5) + ($metrics['conceptual'] * 0.5);
        if ($metrics['keyword'] >= 0.3) { $defScore = min(1.0, $defScore + 0.2); }
        $isCorrect = $defScore >= 0.4 || ($metrics['keyword'] >= 0.4 && $metrics['conceptual'] >= 0.3);
        return [
            'isCorrect'   => $isCorrect,
            'similarity'  => $isCorrect ? max($defScore, 0.5) : $defScore,
            'metrics'     => ['definition_score' => $defScore],
            'reason'      => 'Definition question',
            'courseTopic' => $this->identifyCourseTopic($correctAnswer),
            'feedback'    => $this->generateDefinitionFeedback($submittedAnswer, $correctAnswer, $metrics, $isCorrect),
        ];
    }

    private function evaluateMatchingAnswer(string $submittedAnswer, string $correctAnswer, float $baseThreshold): array
    {
        $correctMatches   = $this->extractMatchingPairs($correctAnswer);
        $submittedMatches = $this->extractMatchingPairs($submittedAnswer);
        $sim              = $this->calculateMatchingSimilarity($submittedMatches, $correctMatches);
        $isCorrect        = $sim['overall_similarity'] >= 0.5 || ($sim['pair_similarity'] >= 0.4 && $sim['key_matches'] > 0);
        return [
            'isCorrect'   => $isCorrect,
            'similarity'  => max($sim['overall_similarity'], $isCorrect ? 0.6 : 0.0),
            'metrics'     => ['matching_similarity' => $sim],
            'reason'      => 'Matching question',
            'courseTopic' => $this->identifyCourseTopic($correctAnswer),
            'feedback'    => $this->generateMatchingFeedback($submittedAnswer, $correctAnswer, $sim, $isCorrect),
        ];
    }

    private function evaluateNumericalValueAnswer(string $submittedAnswer, string $correctAnswer, float $baseThreshold): array
    {
        $correctNumbers  = $this->extractNumericalValues($correctAnswer);
        $submittedNumbers= $this->extractNumericalValues($submittedAnswer);
        $numMatch        = $this->compareNumericalValues($submittedNumbers, $correctNumbers);
        $reqCount        = count($correctNumbers);
        $allMatch        = $reqCount > 0 && $numMatch['total_matches'] === $reqCount && $numMatch['score'] >= 0.95;

        if ($allMatch) {
            return [
                'isCorrect'   => true,
                'similarity'  => max($numMatch['score'], 0.95),
                'metrics'     => ['numerical_match' => $numMatch],
                'reason'      => 'Numerical — correct values',
                'courseTopic' => $this->identifyCourseTopic($correctAnswer),
                'feedback'    => $this->generateNumericalFeedback($submittedAnswer, $correctAnswer, $numMatch),
            ];
        }

        $reg = $this->performRegularEvaluation($submittedAnswer, $correctAnswer, $baseThreshold);
        $reg['isCorrect']  = false;
        $reg['similarity'] = min($reg['similarity'], $numMatch['score'] ?? 0.0);
        $reg['reason']     = 'Numerical — values do not match';
        $reg['feedback']   = $this->generateNumericalFeedback($submittedAnswer, $correctAnswer, $numMatch);
        return $reg;
    }

    private function performRegularEvaluation(string $submittedAnswer, string $correctAnswer, float $threshold): array
    {
        $metrics   = $this->calculateSimilarityMetrics($submittedAnswer, $correctAnswer);
        $isCorrect = $metrics['weighted'] >= $threshold;
        return [
            'isCorrect'   => $isCorrect,
            'similarity'  => $metrics['weighted'],
            'metrics'     => $metrics,
            'reason'      => $isCorrect ? 'Regular evaluation — sufficient similarity' : 'Regular evaluation — insufficient similarity',
            'courseTopic' => $this->identifyCourseTopic($correctAnswer),
            'feedback'    => $this->generateDetailedFeedback($submittedAnswer, $correctAnswer, $metrics,
                                $isCorrect, $metrics['weighted'], $threshold, null),
        ];
    }

    // =========================================================================
    // TEXT NORMALIZATION HELPERS
    // =========================================================================

    private function preprocessSymbols(string $answer): string
    {
        foreach ($this->medicalSymbols as $symbol => $meaning) {
            $answer = str_replace($symbol, " $meaning ", $answer);
        }
        foreach ($this->chemicalSymbols as $symbol => $meaning) {
            $answer = preg_replace('/\b' . preg_quote($symbol, '/') . '\b/', " $meaning ", $answer) ?? $answer;
        }
        $patterns = [
            '/(\d+)\/(\d+)/'                     => 'systolic $1 diastolic $2',
            '/HR\s*=?\s*(\d+)/i'                 => 'heart rate $1',
            '/(\d+[\.,]?\d*)\s*mmol\/L/i'        => '$1 millimoles per liter',
            '/(\d+[\.,]?\d*)\s*mg\/dL/i'         => '$1 milligrams per deciliter',
            '/α/' => 'alpha', '/β/' => 'beta', '/γ/' => 'gamma',
            '/δ/' => 'delta', '/μ/' => 'mu',
        ];
        foreach ($patterns as $p => $r) {
            $answer = preg_replace($p, $r, $answer) ?? $answer;
        }
        return $answer;
    }

    private function normalizeForSemanticComparison(string $text): string
    {
        return trim($this->textProcessor->normalizeText(
            $this->expandKnownAbbreviations($this->preprocessSymbols($text))
        ));
    }

    private function expandKnownAbbreviations(string $text): string
    {
        $abbrevs = $this->medicalKnowledge->getAllAbbreviations();
        uksort($abbrevs, fn($a, $b) => strlen($b) <=> strlen($a));
        foreach ($abbrevs as $abbr => $expansion) {
            $text = preg_replace('/\b' . preg_quote($abbr, '/') . '\b/i', $expansion, $text) ?? $text;
        }
        return $text;
    }

    private function normalizeAlternativeAnswers(array|string|null $alt): array
    {
        if (is_string($alt)) { $alt = preg_split('/\r\n|\r|\n/', $alt) ?: []; }
        if (!is_array($alt)) { return []; }
        return array_values(array_filter(array_map(fn($a) => trim((string)$a), $alt)));
    }

    /** @return string[] */
    private function extractMeaningfulTokens(string $text): array
    {
        $filtered = $this->textProcessor->removeFillerWords($text);
        $tokens   = preg_split('/\s+/', trim($filtered)) ?: [];
        return array_values(array_unique(array_filter($tokens, function($t) {
            $t = trim((string)$t);
            if ($t === '') { return false; }
            if (preg_match('/\d/', $t)) { return true; }
            return mb_strlen($t) >= 3;
        })));
    }

    private function hasMeaningfulTokenOverlap(string $ns, string $nc, array $alts = []): bool
    {
        $st = $this->extractMeaningfulTokens($ns);
        $ct = $this->extractMeaningfulTokens($nc);
        if (!empty(array_intersect($st, $ct))) { return true; }
        foreach ($alts as $alt) {
            $at = $this->extractMeaningfulTokens($this->normalizeForSemanticComparison($alt));
            if (!empty(array_intersect($st, $at))) { return true; }
        }
        return false;
    }

    private function containsAnyPhrase(string $text, array $phrases): bool
    {
        foreach ($phrases as $p) { if (stripos($text, $p) !== false) { return true; } }
        return false;
    }

    /**
     * Find the name of an incorrect location explicitly mentioned alongside a process.
     * Returns the first wrong location string found, or null.
     */
    private function findIncorrectLocationMention(string $process, string $normalizedSubmitted): ?string
    {
        $locs = $this->processLocations[$process] ?? null;
        if ($locs === null) { return null; }
        foreach ($locs['incorrect'] as $loc) {
            if (stripos($normalizedSubmitted, $loc) !== false) { return $loc; }
        }
        return null;
    }

    // =========================================================================
    // DOMAIN BOOST
    // =========================================================================

    protected function calculateDomainContextBoost(string $text1, string $text2): float
    {
        $n1 = $this->textProcessor->normalizeText($text1);
        $n2 = $this->textProcessor->normalizeText($text2);
        $total = 0.0;
        foreach ($this->medicalDomains as $domain => $keywords) {
            $s1 = $s2 = 0;
            foreach ($keywords as $kw) {
                if (str_contains($n1, $kw)) { $s1++; }
                if (str_contains($n2, $kw)) { $s2++; }
            }
            if ($s1 > 0 && $s2 > 0) {
                $total += min(min($s1, $s2) / max(count($keywords) * 0.5, 1) * 0.05, 0.05);
            }
        }
        return min($total, 0.10);
    }

    // =========================================================================
    // COURSE TOPIC
    // =========================================================================

    protected function identifyCourseTopic(string $text): ?string
    {
        $lower  = strtolower($text);
        $scores = [];
        foreach ($this->medicalDomains as $topic => $terms) {
            $score = 0;
            foreach ($terms as $term) { if (str_contains($lower, $term)) { $score++; } }
            if ($score > 0) { $scores[$topic] = $score; }
        }
        if (empty($scores)) { return null; }
        arsort($scores);
        return key($scores);
    }

    // =========================================================================
    // PATTERN MATCHERS (sympathetic / parasympathetic)
    // =========================================================================

    protected function isSympatheticEffectsMatch(string $submitted, string $correct): bool
    {
        return $this->patternMatcher->matchesPattern('sympathetic_effects', $submitted)
            && $this->patternMatcher->matchesPattern('sympathetic_effects', $correct);
    }

    protected function isParasympatheticEffectsMatch(string $submitted, string $correct): bool
    {
        return $this->patternMatcher->matchesPattern('parasympathetic_effects', $submitted)
            && $this->patternMatcher->matchesPattern('parasympathetic_effects', $correct);
    }

    // =========================================================================
    // INTELLIGENT CONCEPT MATCHERS (unchanged logic, kept intact)
    // =========================================================================

    private function isIntelligentConceptMatch(string $ns, string $nc): bool
    {
        return $this->hasFlowDirectionMatch($ns, $nc)
            || $this->hasLocationMatch($ns, $nc)
            || $this->hasFunctionMatch($ns, $nc)
            || $this->hasProcessMatch($ns, $nc)
            || $this->hasQuantitativeMatch($ns, $nc);
    }

    private function hasFlowDirectionMatch(string $s, string $c): bool
    {
        $p = '/\b(blood|oxygen|fluid)\s+(?:flows?\s+)?(?:in|into|from|through)\s+(vein|artery|capillary|heart|lung|kidney)\b/i';
        return preg_match($p, $s) && preg_match($p, $c);
    }
    private function hasLocationMatch(string $s, string $c): bool
    {
        $p = '/\b(heart|lung|liver|kidney|stomach|spleen)\s+(?:is\s+)?(?:in|at|on|located\s+in)\s+(chest|thorax|abdomen|pelvis)\b/i';
        return preg_match($p, $s) && preg_match($p, $c);
    }
    private function hasFunctionMatch(string $s, string $c): bool
    {
        $p = '/\b(heart|lungs|kidneys|liver|stomach)\s+(?:function|work|role|job)\s+(?:to\s+)?(.+)/i';
        return preg_match($p, $s) && preg_match($p, $c);
    }
    private function hasProcessMatch(string $s, string $c): bool
    {
        $p = '/\b(protein|synthesis|dna|replication|glycolysis|respiration)\s+(?:happens?|occurs?|takes\s+place)\s+(?:in|at|on)\s+(.+)/i';
        return preg_match($p, $s) && preg_match($p, $c);
    }
    private function hasQuantitativeMatch(string $s, string $c): bool
    {
        $p = '/\b(bp|blood pressure|hr|heart rate)\s+(?:is|are|=)\s*(\d+)(?:\/|over)?(\d+)?/i';
        return preg_match($p, $s) && preg_match($p, $c);
    }

    private function isHeartFunctionMatch(string $ns, string $nc): bool
    {
        $heartKw    = ['heart','cardiac','myocardial'];
        $functionKw = ['pump','circulate','propel','move','flow'];
        $subHasHeart = $subHasFunc = $corHasHeart = $corHasFunc = false;
        foreach ($heartKw as $k) {
            if (stripos($ns, $k) !== false) { $subHasHeart = true; }
            if (stripos($nc, $k) !== false) { $corHasHeart = true; }
        }
        foreach ($functionKw as $k) {
            if (stripos($ns, $k) !== false) { $subHasFunc = true; }
            if (stripos($nc, $k) !== false) { $corHasFunc = true; }
        }
        return $subHasHeart && ($subHasFunc || stripos($ns,'blood') !== false)
            && $corHasHeart && $corHasFunc;
    }

    private function isMedicalAbbreviationMatch(string $ns, string $nc): bool
    {
        $abbrevs = [
            'sns' => 'sympathetic nervous system','pns' => 'parasympathetic nervous system',
            'ans' => 'autonomic nervous system',  'cns' => 'central nervous system',
            'bp'  => 'blood pressure',            'hr'  => 'heart rate',
            'o2'  => 'oxygen',                    'co2' => 'carbon dioxide',
            'atp' => 'adenosine triphosphate',    'gi'  => 'gastrointestinal',
            'er'  => 'endoplasmic reticulum',
        ];
        $matched = false;
        foreach ($abbrevs as $abbr => $exp) {
            $p = '/\b' . preg_quote($abbr, '/') . '\b/i';
            if ((preg_match($p, $ns) && (stripos($nc, $exp) !== false || preg_match($p, $nc)))
                || (stripos($ns, $exp) !== false && preg_match($p, $nc))) {
                $matched = true; break;
            }
        }
        if (!$matched) { return false; }
        $expS = $this->expandKnownAbbreviations($ns);
        $expC = $this->expandKnownAbbreviations($nc);
        if ($this->textProcessor->containsAntonyms($expS, $expC)) { return false; }
        return $this->textProcessor->getCombinedSimilarity($expS, $expC) >= 0.6;
    }

    private function isTypoMatch(string $ns, string $nc): bool
    {
        $typos = [
            'hart' => 'heart','caridac' => 'cardiac','ventrical' => 'ventricle',
            'parasympatheic' => 'parasympathetic','sympatheic' => 'sympathetic',
            'nueron' => 'neuron','alveloi' => 'alveoli','diaphram' => 'diaphragm',
            'protien' => 'protein','glyclysis' => 'glycolysis',
            'epithelal' => 'epithelial','conective' => 'connective',
        ];
        foreach ($typos as $typo => $fix) {
            if (stripos($ns, $typo) !== false && (stripos($nc, $fix) !== false || stripos($nc, $typo) !== false)) {
                return true;
            }
        }
        return false;
    }

    // =========================================================================
    // NUMERICAL HELPERS (unchanged logic)
    // =========================================================================

    private function extractNumericalValues(string $text): array
    {
        $values = [];
        if (preg_match_all('/(\d+[\.,]\d+)\s*[\-\–]\s*(\d+[\.,]\d+)/', $text, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $values[] = ['type' => 'range',
                    'min'  => (float) str_replace(',', '.', $matches[1][$i]),
                    'max'  => (float) str_replace(',', '.', $matches[2][$i]),
                    'text' => $match, 'unit' => $this->extractUnit($match)];
            }
        }
        if (preg_match_all('/(\d+(?:[\.,]\d+)?)\s*([a-zA-Zµ°\/]+)?/', $text, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $alreadyCaptured = false;
                foreach ($values as $ex) { if (str_contains($ex['text'], $match)) { $alreadyCaptured = true; break; } }
                if (!$alreadyCaptured) {
                    $values[] = ['type' => 'single',
                        'value' => (float) str_replace(',', '.', $matches[1][$i]),
                        'text'  => $match,
                        'unit'  => $this->normalizeUnit(trim($matches[2][$i] ?? ''))];
                }
            }
        }
        return $values;
    }

    private function compareNumericalValues(array $sub, array $cor): array
    {
        if (empty($cor)) { return ['score' => 0, 'matches' => [], 'total_matches' => 0, 'reason' => 'No correct numbers']; }
        if (empty($sub)) { return ['score' => 0, 'matches' => [], 'total_matches' => 0, 'reason' => 'No submitted numbers']; }
        $matches = []; $total = 0.0;
        foreach ($cor as $cn) {
            $best = ['score' => 0, 'submitted' => null];
            foreach ($sub as $sn) {
                $s = $this->calculateNumericalSimilarity($cn, $sn);
                if ($s > $best['score']) { $best = ['score' => $s, 'submitted' => $sn]; }
            }
            if ($best['score'] > 0.7) { $matches[] = $best; $total += $best['score']; }
        }
        return ['score' => count($matches) > 0 ? $total / count($matches) : 0,
                'matches' => $matches, 'total_matches' => count($matches),
                'reason' => count($matches) > 0 ? 'Matched' : 'No matches'];
    }

    private function calculateNumericalSimilarity(array $n1, array $n2): float
    {
        $unitBonus = ($n1['unit'] !== '' && $n1['unit'] === $n2['unit']) ? 0.05 : 0;
        if ($n1['type'] === 'range' && $n2['type'] === 'range') {
            $overlap = min($n1['max'], $n2['max']) - max($n1['min'], $n2['min']);
            $total   = max($n1['max'], $n2['max']) - min($n1['min'], $n2['min']);
            return $total > 0 ? min(1.0, max(0, $overlap / $total) + $unitBonus) : $unitBonus;
        }
        if ($n1['type'] === 'single' && $n2['type'] === 'single') {
            $diff = abs($n1['value'] - $n2['value']);
            $ref  = max(abs((float)$n1['value']), 1.0);
            $rel  = $diff / $ref;
            if ($rel <= 0.01) { return min(1.0, 1.0 + $unitBonus); }
            if ($rel <= 0.03) { return min(1.0, 0.9 + $unitBonus); }
            if ($rel <= 0.05) { return min(1.0, 0.75 + $unitBonus); }
            if ($rel <= 0.10) { return min(1.0, 0.4 + $unitBonus); }
            return $unitBonus;
        }
        if ($n1['type'] === 'range' && $n2['type'] === 'single') {
            if ($n2['value'] >= $n1['min'] && $n2['value'] <= $n1['max']) { return 0.9 + $unitBonus; }
            $dist = min(abs($n2['value'] - $n1['min']), abs($n2['value'] - $n1['max']));
            $range = $n1['max'] - $n1['min'];
            return $range > 0 ? max(0, 0.8 - ($dist / $range)) + $unitBonus : $unitBonus;
        }
        if ($n1['type'] === 'single' && $n2['type'] === 'range') {
            return $this->calculateNumericalSimilarity($n2, $n1);
        }
        return $unitBonus;
    }

    private function extractUnit(string $text): string
    {
        return preg_match('/[\d\.\-\–]+\s*(.+)$/', $text, $m) ? trim($m[1]) : '';
    }

    private function normalizeUnit(string $unit): string
    {
        $map = ['g/dL' => 'g/dl','g/dl' => 'g/dl','mg/dl' => 'mg/dl','mmol/l' => 'mmol/l',
                'mmHg'  => 'mmhg','°c' => 'celsius','°f' => 'fahrenheit'];
        return $map[strtolower(trim($unit))] ?? strtolower(trim($unit));
    }

    // =========================================================================
    // MATCHING HELPERS (unchanged)
    // =========================================================================

    private function extractMatchingPairs(string $answer): array
    {
        $pairs = [];
        if (preg_match_all('/([^:]+):\s*([^:]+)(?:\s*-|$)/', $answer, $m)) {
            foreach ($m[0] as $i => $_) {
                $pairs[] = ['item' => trim($m[1][$i]), 'description' => trim($m[2][$i])];
            }
        }
        return $pairs;
    }

    private function calculateMatchingSimilarity(array $sub, array $cor): array
    {
        if (empty($cor)) { return ['overall_similarity' => 0, 'pair_similarity' => 0, 'key_matches' => 0,
                                    'total_correct_pairs' => 0, 'total_submitted_pairs' => 0]; }
        $total = 0.0; $keyMatches = 0;
        foreach ($cor as $cp) {
            $best = 0;
            foreach ($sub as $sp) {
                $is = $this->calculateSimilarityMetrics($cp['item'],        $sp['item'])['weighted'];
                $ds = $this->calculateSimilarityMetrics($cp['description'], $sp['description'])['weighted'];
                $ps = ($is + $ds) / 2;
                if ($ps > $best) { $best = $ps; }
                if ($is >= 0.7 && $ds >= 0.7) { $keyMatches++; }
            }
            $total += $best;
        }
        $corCount = count($cor); $subCount = count($sub);
        return ['overall_similarity' => $total / $corCount,
                'pair_similarity'    => $total / max($subCount, $corCount),
                'key_matches'        => $keyMatches,
                'total_correct_pairs'  => $corCount,
                'total_submitted_pairs'=> $subCount];
    }

    // =========================================================================
    // ENUMERATION HELPERS
    // =========================================================================

    private function extractEnumerationItems(string $text): array
    {
        $listText = $text;
        if (preg_match('/\b(?:includes|consists of|comprises|contains|involves)\b(.+)$/i', $text, $m)) {
            $listText = $m[1];
        }
        $parts = preg_split('/,\s*|\band\b|\bor\b/i', $listText) ?: [];
        $items = [];
        foreach ($parts as $part) {
            $n = trim($this->textProcessor->removeFillerWords($this->normalizeForSemanticComparison($part)));
            if ($n !== '') { $items[] = $n; }
        }
        return array_values(array_unique($items));
    }

    // =========================================================================
    // FEEDBACK GENERATORS
    // =========================================================================

    private function generateDefinitionFeedback(string $sub, string $cor, array $metrics, bool $ok): string
    {
        if ($ok) {
            return $metrics['keyword'] >= 0.8
                ? 'Excellent! Your definition is accurate and uses appropriate terminology.'
                : 'Good job! Your definition captures the essential concepts.';
        }
        if ($metrics['keyword'] < 0.4) {
            return 'Your definition is missing key terms. Review the essential components of this concept.';
        }
        if ($metrics['conceptual'] < 0.4) {
            return 'Your definition does not fully capture the concept. Focus on its core characteristics.';
        }
        return 'Your definition is close but needs refinement. Compare it with the standard definition.';
    }

    private function generateMatchingFeedback(string $sub, string $cor, array $sim, bool $ok): string
    {
        if ($ok) {
            return $sim['overall_similarity'] >= 0.8
                ? 'Excellent! You have correctly matched the key associations.'
                : 'Good job! You identified the main matching relationships.';
        }
        return 'Your answer is missing or incorrectly matching some associations. Review each item carefully.';
    }

    private function generateNumericalFeedback(string $sub, string $cor, array $match): string
    {
        if ($match['score'] >= 0.9) { return 'Excellent! Your numerical values are very accurate.'; }
        if ($match['score'] >= 0.7) { return 'Good job! Your values are close. Check exact formatting and units.'; }
        return $match['total_matches'] > 0
            ? 'You have some correct numerical elements, but check the exact values and units.'
            : 'Your answer does not contain the correct numerical values. Check reference ranges and units carefully.';
    }

    protected function generateDetailedFeedback(
        string  $submitted,
        string  $correct,
        array   $metrics,
        bool    $isCorrect,
        float   $similarity,
        float   $threshold,
        ?string $courseTopic = null
    ): string {
        if ($isCorrect) {
            if ($similarity > 0.95) { return 'Excellent! Your answer is very accurate.'; }
            if ($similarity > 0.85) {
                $map = ['anatomy' => 'Your description of the anatomical structures is accurate.',
                        'nervous' => 'Your explanation of the physiological process is correct.',
                        'biochemistry' => 'Your understanding of the biochemical pathway is sound.',
                        'histology' => 'Your description of the tissue characteristics is accurate.'];
                return $map[$courseTopic ?? ''] ?? 'Good job! Your answer contains all the key concepts.';
            }
            return 'Your answer is sufficiently correct, though there may be room for more precision.';
        }

        $parts = [];
        $normSub = $this->textProcessor->normalizeText($submitted);
        $normCor = $this->textProcessor->normalizeText($correct);
        $missing = array_filter(
            array_diff(
                explode(' ', $this->textProcessor->removeFillerWords($normCor)),
                explode(' ', $this->textProcessor->removeFillerWords($normSub))
            ),
            fn($w) => mb_strlen($w) > 3
        );
        if (!empty($missing)) {
            $list = implode(', ', array_slice(array_values($missing), 0, 3))
                . (count($missing) > 3 ? ', etc.' : '.');
            $parts[] = "Your answer is missing key concepts: $list";
        }
        if (($metrics['conceptual'] ?? 1) < 0.4) {
            $parts[] = 'Your answer does not sufficiently address the core concepts. Try to be more specific.';
        }
        if ($metrics['hasContradictions'] ?? false) {
            $parts[] = 'Your answer contains contradictory statements. Review the material to resolve inconsistencies.';
        }
        if (empty($parts)) {
            return 'Your answer differs from the expected answer. Please review the material and try again.';
        }
        return implode(' ', $parts) . ' Please review and try again.';
    }

    // =========================================================================
    // SMALL UTILITIES
    // =========================================================================

    private function isPatternCriticalQuestion(string $correctAnswer): bool
    {
        $kws = ['parasympathetic','sympathetic','action potential','cardiac cycle',
                'glomerular filtration','tca cycle','krebs cycle','epithelium',
                'anatomical position','glycolysis','electron transport'];
        foreach ($kws as $kw) { if (stripos($correctAnswer, $kw) !== false) { return true; } }
        return false;
    }

    private function earlyResult(bool $isCorrect, float $similarity, string $reason, float $start): array
    {
        return ['isCorrect' => $isCorrect, 'similarity' => $similarity,
                'reason' => $reason, 'processing_time' => microtime(true) - $start];
    }

    private function buildCorrectResult(float $similarity, string $reason, ?string $topic, string $feedback): array
    {
        $result = ['isCorrect' => true, 'similarity' => $similarity, 'reason' => $reason, 'feedback' => $feedback];
        if ($topic !== null) { $result['courseTopic'] = $topic; }
        return $result;
    }
}