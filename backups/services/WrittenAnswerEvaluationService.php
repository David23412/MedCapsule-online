<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WrittenAnswerEvaluationService
{
    /**
     * @var TextProcessingService
     */
    protected $textProcessor;
    
    /**
     * @var PatternMatcherService
     */
    protected $patternMatcher;
    
    /**
     * @var MedicalKnowledgeService
     */
    protected $medicalKnowledge;
    
    /**
     * Default threshold for combined similarity to consider answers correct
     */
    const DEFAULT_SIMILARITY_THRESHOLD = 0.85;
    
    /**
     * Cache configuration
     */
    private $cacheEnabled = true;
    private $cacheTtl = 86400; // 24 hours
    
    /**
     * Specialized medical domains with their respective weights
     */
    private $medicalDomains = [
        'cardiovascular' => ['heart', 'cardiac', 'myocardial', 'coronary', 'artery', 'arteries', 'atrial', 'ventricular', 'circulation', 'bp', 'blood pressure', 'aorta', 'ventricle', 'atrium', 'endocardium', 'myocardium', 'epicardium', 'pericardium', 'valve', 'chordae', 'purkinje', 'sinoatrial', 'atrioventricular'],
        
        'respiratory' => ['lung', 'pulmonary', 'respiratory', 'breath', 'breathing', 'ventilation', 'airway', 'alveoli', 'bronchi', 'oxygen', 'co2', 'trachea', 'larynx', 'pharynx', 'diaphragm', 'pleura', 'mediastinum', 'pneumothorax', 'spirometry', 'hypoxia', 'hypoxemia', 'hypercarbia', 'surfactant'],
        
        'nervous' => ['brain', 'neural', 'nervous', 'cns', 'pns', 'neuron', 'parasympathetic', 'sympathetic', 'nerve', 'spinal', 'cord', 'cerebrum', 'cerebral', 'cerebellum', 'brainstem', 'cortex', 'thalamus', 'hypothalamus', 'hippocampus', 'amygdala', 'basal ganglia', 'axon', 'dendrite', 'oligodendrocyte', 'astrocyte', 'microglia', 'schwann', 'reflex', 'sensory', 'motor', 'neurotransmitter'],
        
        'gastrointestinal' => ['gi', 'stomach', 'intestine', 'colon', 'bowel', 'digestion', 'digestive', 'liver', 'hepatic', 'gallbladder', 'pancreas', 'esophagus', 'duodenum', 'jejunum', 'ileum', 'cecum', 'rectum', 'peritoneum', 'mucosa', 'submucosa', 'muscularis', 'serosa', 'villus', 'peristalsis', 'enzyme', 'bile', 'absorption'],
        
        'endocrine' => ['hormone', 'insulin', 'thyroid', 'adrenal', 'pituitary', 'pancreatic', 'diabetes', 'glycemic', 'gland', 'endocrine', 'tsh', 'cortisol', 'aldosterone', 'adrenaline', 'estrogen', 'testosterone', 'progesterone', 'thyroid', 'parathyroid', 'acth', 'prolactin', 'feedback', 'receptor', 'signaling'],
        
        'musculoskeletal' => ['muscle', 'bone', 'joint', 'tendon', 'ligament', 'skeletal', 'orthopedic', 'fracture', 'spine', 'vertebral', 'spinal', 'cartilage', 'synovial', 'ossification', 'osteoblast', 'osteoclast', 'myosin', 'actin', 'sarcomere', 'contraction', 'neuromuscular', 'articulation', 'axial', 'appendicular', 'collagen', 'calcium'],
        
        'urinary' => ['kidney', 'renal', 'bladder', 'urinary', 'urine', 'nephron', 'ureter', 'urethra', 'glomerular', 'nephritis', 'tubule', 'podocyte', 'filtration', 'reabsorption', 'secretion', 'collecting duct', 'loop of henle', 'cystitis', 'pyelonephritis', 'creatinine', 'urea', 'micturition'],
        
        'immune' => ['immune', 'antibody', 'antigen', 'inflammation', 'lymphocyte', 'leukocyte', 'white blood cell', 'wbc', 'infection', 'neutrophil', 'basophil', 'eosinophil', 'monocyte', 'macrophage', 'dendritic', 'nk cell', 'cytokine', 'chemokine', 'complement', 'immunoglobulin', 'mhc', 'thymus', 'spleen', 'lymph node', 'humoral', 'cellular'],
        
        'reproductive' => ['ovary', 'ovarian', 'testis', 'testes', 'testicular', 'uterus', 'uterine', 'vagina', 'vaginal', 'penis', 'penile', 'prostate', 'prostatic', 'gamete', 'spermatogenesis', 'oogenesis', 'ovulation', 'menstruation', 'fertilization', 'zygote', 'embryo', 'placenta', 'fetus', 'gestation', 'parturition'],
        
        'histology' => ['tissue', 'epithelium', 'epithelial', 'connective', 'muscle', 'nervous', 'basement membrane', 'collagen', 'elastin', 'fibroblast', 'macrophage', 'mast cell', 'simple', 'stratified', 'squamous', 'cuboidal', 'columnar', 'transitional', 'goblet cell', 'microvilli', 'cilia', 'tight junction', 'desmosome', 'gap junction', 'hematoxylin', 'eosin'],
        
        'biochemistry' => ['enzyme', 'protein', 'carbohydrate', 'lipid', 'nucleic acid', 'dna', 'rna', 'atp', 'metabolism', 'glycolysis', 'krebs', 'tca cycle', 'citric acid cycle', 'electron transport', 'oxidative phosphorylation', 'gluconeogenesis', 'glycogenesis', 'glycogenolysis', 'fatty acid', 'amino acid', 'transcription', 'translation', 'replication', 'mutation', 'coenzyme', 'cofactor']
    ];
    
    /**
     * Course-specific pattern matchers
     */
    private $coursePatternMatchers = [
        // Anatomy patterns
        'anatomical_position' => ['anatomical position', 'anterior', 'posterior', 'superior', 'inferior', 'medial', 'lateral'],
        'skeletal_system' => ['axial skeleton', 'appendicular skeleton', 'bone classification', 'bone markings'],
        'joint_types' => ['fibrous joint', 'cartilaginous joint', 'synovial joint', 'arthrokinematics', 'osteokinematics'],
        
        // Physiology patterns
        'action_potential' => ['depolarization', 'repolarization', 'hyperpolarization', 'threshold', 'sodium channel', 'potassium channel'],
        'cardiac_cycle' => ['systole', 'diastole', 'isovolumetric contraction', 'isovolumetric relaxation', 'atrial contraction'],
        'respiratory_mechanics' => ['inspiration', 'expiration', 'compliance', 'resistance', 'surfactant', 'boyle\'s law'],
        'renal_physiology' => ['glomerular filtration', 'tubular reabsorption', 'tubular secretion', 'countercurrent mechanism'],
        
        // Biochemistry patterns
        'glycolysis' => ['glycolysis', 'pyruvate', 'atp generation', 'substrate-level phosphorylation'],
        'tca_cycle' => ['citric acid cycle', 'krebs cycle', 'oxidative decarboxylation', 'acetyl-coa'],
        'electron_transport' => ['electron transport chain', 'oxidative phosphorylation', 'proton gradient', 'atp synthase'],
        'dna_replication' => ['dna replication', 'leading strand', 'lagging strand', 'okazaki fragments', 'dna polymerase'],
        
        // Histology patterns
        'epithelial_tissue' => ['epithelial tissue', 'simple epithelium', 'stratified epithelium', 'basement membrane'],
        'connective_tissue' => ['connective tissue', 'loose connective', 'dense connective', 'specialized connective'],
        'muscle_tissue' => ['skeletal muscle', 'cardiac muscle', 'smooth muscle', 'sarcomere organization']
    ];
    
    /**
     * Medical symbols and notations
     */
    private $medicalSymbols = [];
    private $chemicalSymbols = [];
    
    /**
     * Constructor
     */
    public function __construct(
        TextProcessingService $textProcessor,
        PatternMatcherService $patternMatcher,
        MedicalKnowledgeService $medicalKnowledge
    ) {
        $this->textProcessor = $textProcessor;
        $this->patternMatcher = $patternMatcher;
        $this->medicalKnowledge = $medicalKnowledge;
        
        // Initialize services with caching
        $this->textProcessor->setCacheConfig(true, 86400);
        $this->patternMatcher->setCacheConfig(true, 86400);
        $this->medicalKnowledge->setCacheConfig(true, 86400);
        
        // Initialize medical symbols translation map
        $this->initializeMedicalSymbols();
    }
    
    /**
     * Initialize medical symbols and notation mapping
     * This helps with the translation of commonly used medical symbols in student answers
     */
    private function initializeMedicalSymbols(): void
    {
        $this->medicalSymbols = [
            // Arrows for increase/decrease
            '↑' => 'increased',
            '↓' => 'decreased',
            '⬆' => 'increased',
            '⬇' => 'decreased',
            '⇑' => 'greatly increased',
            '⇓' => 'greatly decreased',
            
            // Mathematical symbols
            '>' => 'greater than',
            '<' => 'less than',
            '≥' => 'greater than or equal to',
            '≤' => 'less than or equal to',
            '=' => 'equal to',
            '≈' => 'approximately equal to',
            '≠' => 'not equal to',
            
            // Common medical notations
            '°C' => 'degrees celsius',
            '°F' => 'degrees fahrenheit',
            'Δ' => 'change in',
            'Ø' => 'no' // or 'absent'
        ];
        
        // Chemical symbols and notations
        $this->chemicalSymbols = [
            'Na+' => 'sodium ion',
            'K+' => 'potassium ion',
            'Ca2+' => 'calcium ion',
            'Mg2+' => 'magnesium ion',
            'Cl-' => 'chloride ion',
            'HCO3-' => 'bicarbonate',
            'H+' => 'hydrogen ion',
            'OH-' => 'hydroxide',
            'O2' => 'oxygen',
            'CO2' => 'carbon dioxide'
        ];
    }
    
    /**
     * Preprocess answer to handle medical symbols and notations
     * 
     * @param string $answer The answer to preprocess
     * @return string The processed answer with symbols translated
     */
    private function preprocessSymbols(string $answer): string
    {
        // Process medical symbols
        foreach ($this->medicalSymbols as $symbol => $meaning) {
            $answer = str_replace($symbol, " $meaning ", $answer);
        }
        
        // Process chemical symbols - only replace when they appear as standalone symbols
        foreach ($this->chemicalSymbols as $symbol => $meaning) {
            // Use word boundary where possible, or just look for the exact symbol
            $pattern = '/\b' . preg_quote($symbol, '/') . '\b/';
            $answer = preg_replace($pattern, " $meaning ", $answer);
        }
        
        // Special handling for common patterns in medical answers
        $patterns = [
            // Blood pressure notation
            '/(\d+)\/(\d+)/' => 'systolic $1 diastolic $2',
            
            // HR notation
            '/HR\s*=\s*(\d+)/' => 'heart rate $1',
            '/HR\s*(\d+)/' => 'heart rate $1',
            
            // Temperature notation
            '/T\s*=\s*(\d+[\.,]?\d*)/' => 'temperature $1',
            
            // Lab values
            '/(\d+[\.,]?\d*)\s*mmol\/L/' => '$1 millimoles per liter',
            '/(\d+[\.,]?\d*)\s*mg\/dL/' => '$1 milligrams per deciliter',
            '/(\d+[\.,]?\d*)\s*mEq\/L/' => '$1 milliequivalents per liter',
            
            // Greek letters commonly used
            '/α/' => 'alpha',
            '/β/' => 'beta',
            '/γ/' => 'gamma',
            '/δ/' => 'delta',
            '/μ/' => 'mu'
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $answer = preg_replace($pattern, $replacement, $answer);
        }
        
        return $answer;
    }
    
    /**
     * Set cache configuration
     *
     * @param bool $enabled Whether to enable caching
     * @param int $ttl Cache time-to-live in seconds
     * @return void
     */
    public function setCacheConfig(bool $enabled, int $ttl = 86400): void
    {
        $this->cacheEnabled = $enabled;
        $this->cacheTtl = $ttl;
        
        // Pass the same config to text processor
        $this->textProcessor->setCacheConfig($enabled, $ttl);
    }
    
    /**
     * Get cached result or compute and cache
     *
     * @param string $key Cache key
     * @param \Closure $callback Function to compute result if not cached
     * @return mixed Cached or computed result
     */
    private function getCachedOrCompute(string $key, \Closure $callback)
    {
        if (!$this->cacheEnabled) {
            return $callback();
        }
        
        $cacheKey = 'answer_eval:' . md5($key);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $result = $callback();
        Cache::put($cacheKey, $result, $this->cacheTtl);
        
        return $result;
    }
    
    /**
     * Evaluate if a written answer is correct using enhanced scoring
     * Domain-specific optimization for medical education
     * 
     * @param string $submittedAnswer The answer submitted by the user
     * @param string $correctAnswer The correct answer from the database
     * @param array $alternativeAnswers Optional array of alternative correct answers
     * @param float $similarityThreshold Optional threshold to consider answers correct (0.0 to 1.0)
     * @return array Results including score, similarity, detailed metrics, and reason
     */
    public function evaluateAnswer(
        string $submittedAnswer, 
        string $correctAnswer, 
        array $alternativeAnswers = [], 
        float $similarityThreshold = self::DEFAULT_SIMILARITY_THRESHOLD
    ): array {
        // Preprocess answers to handle medical symbols and notations
        $submittedAnswer = $this->preprocessSymbols($submittedAnswer);
        $correctAnswer = $this->preprocessSymbols($correctAnswer);
        $alternativeAnswers = array_map([$this, 'preprocessSymbols'], $alternativeAnswers);
        
        $cacheKey = "evaluate:{$submittedAnswer}:{$correctAnswer}:" . implode('|', $alternativeAnswers) . ":{$similarityThreshold}";
        
        return $this->getCachedOrCompute($cacheKey, function() use ($submittedAnswer, $correctAnswer, $alternativeAnswers, $similarityThreshold) {
            // Normalize inputs
            $submittedAnswer = trim($submittedAnswer);
            $correctAnswer = trim($correctAnswer);
            
            // Performance optimization - Empty answers are never correct
            if (empty($submittedAnswer)) {
                return [
                    'isCorrect' => false,
                    'similarity' => 0,
                    'metrics' => [],
                    'reason' => 'No answer provided',
                    'feedback' => 'Please provide an answer to the question.'
                ];
            }
            
            // Performance optimization - Check for exact match first
            $normalizedSubmitted = $this->textProcessor->normalizeText($submittedAnswer);
            $normalizedCorrect = $this->textProcessor->normalizeText($correctAnswer);
            
            if ($normalizedSubmitted === $normalizedCorrect) {
                return [
                    'isCorrect' => true,
                    'similarity' => 1.0,
                    'metrics' => [
                        'exact_match' => true,
                        'levenshtein' => 1.0,
                        'jaccard' => 1.0,
                        'keyword' => 1.0
                    ],
                    'reason' => 'Exact match with correct answer',
                    'feedback' => 'Your answer exactly matches the expected answer.'
                ];
            }
            
            // Check for exact match with alternative answers - quick win
            foreach ($alternativeAnswers as $index => $altAnswer) {
                if (empty($altAnswer)) continue;
                
                $normalizedAlt = $this->textProcessor->normalizeText($altAnswer);
                if ($normalizedSubmitted === $normalizedAlt) {
                    return [
                        'isCorrect' => true,
                        'similarity' => 1.0,
                        'metrics' => [
                            'exact_match' => true,
                            'alternative_index' => $index
                        ],
                        'reason' => 'Exact match with alternative answer',
                        'feedback' => 'Your answer correctly matches an alternative accepted answer.'
                    ];
                }
            }
            
            // Identify course topic early for domain-specific optimizations
            $courseTopic = $this->identifyCourseTopic($correctAnswer);
            
            // Special case: Protein synthesis location check (very important to get right)
            if (stripos($normalizedSubmitted, 'protein synthesis') !== false) {
                // Check for critical location error with protein synthesis
                $hasLocationError = false;
                
                // Check if protein synthesis is mentioned with a wrong location
                $incorrectLocations = ['mitochondria', 'mitochondrial', 'nucleus', 'nuclear', 'golgi'];
                foreach ($incorrectLocations as $wrongLocation) {
                    // Find if protein synthesis is closely associated with a wrong location
                    // Using a distance-based approach to catch cases where they're mentioned close together
                    $proteinPos = stripos($normalizedSubmitted, 'protein synthesis');
                    $locationPos = stripos($normalizedSubmitted, $wrongLocation);
                    
                    if ($locationPos !== false && abs($proteinPos - $locationPos) < 50) {
                        // If protein synthesis and wrong location are close in the text
                        $hasLocationError = true;
                        break;
                    }
                }
                
                // Special case for protein synthesis - if location is wrong, immediately mark as incorrect
                if ($hasLocationError) {
                    return [
                        'isCorrect' => false,
                        'similarity' => 0.5, // Set a moderate similarity score
                        'metrics' => [
                            'conceptual' => 0.4,
                            'keyword' => 0.5,
                            'hasContradictions' => true
                        ],
                        'reason' => 'Answer contains incorrect location for protein synthesis',
                        'courseTopic' => $courseTopic,
                        'feedback' => 'Your answer incorrectly states the location of protein synthesis. Protein synthesis occurs on ribosomes, which can be found free in the cytoplasm or attached to the endoplasmic reticulum, not in the ' . $wrongLocation . '.'
                    ];
                }
            }
            
            // Special case: Check for medical abbreviation usage in sympathetic/parasympathetic nervous system
            if (preg_match('/\b(SNS|PNS|ANS)\b/i', $normalizedSubmitted)) {
                // If using abbreviations for nervous system, check if other concepts align
                $isAbbreviatedSNS = preg_match('/\bSNS\b/i', $normalizedSubmitted);
                $isAbbreviatedPNS = preg_match('/\bPNS\b/i', $normalizedSubmitted);
                
                // Process known correct abbreviation patterns
                if ($isAbbreviatedSNS && 
                    (stripos($normalizedCorrect, 'sympathetic') !== false) && 
                    ((stripos($normalizedSubmitted, 'increase hr') !== false || 
                      stripos($normalizedSubmitted, 'increases hr') !== false || 
                      stripos($normalizedSubmitted, 'increase heart') !== false || 
                      stripos($normalizedSubmitted, 'increases heart') !== false) &&
                     (stripos($normalizedSubmitted, 'inhibit gi') !== false || 
                      stripos($normalizedSubmitted, 'inhibits gi') !== false || 
                      stripos($normalizedSubmitted, 'decrease gi') !== false || 
                      stripos($normalizedSubmitted, 'decreases gi') !== false || 
                      stripos($normalizedSubmitted, 'decrease digest') !== false || 
                      stripos($normalizedSubmitted, 'decreases digest') !== false))
                   ) {
                    // This is correct sympathetic abbreviation usage with correct effects
                    return [
                        'isCorrect' => true,
                        'similarity' => 0.9,
                        'metrics' => [
                            'conceptual' => 0.9,
                            'keyword' => 0.8,
                            'hasContradictions' => false,
                            'abbreviated' => true
                        ],
                        'reason' => 'Correct use of medical abbreviations',
                        'courseTopic' => 'physiology',
                        'feedback' => 'Your answer correctly describes the effects of the sympathetic nervous system using standard medical abbreviations.'
                    ];
                }
                
                if ($isAbbreviatedPNS && 
                    (stripos($normalizedCorrect, 'parasympathetic') !== false) && 
                    ((stripos($normalizedSubmitted, 'decrease hr') !== false || 
                      stripos($normalizedSubmitted, 'decreases hr') !== false || 
                      stripos($normalizedSubmitted, 'decrease heart') !== false || 
                      stripos($normalizedSubmitted, 'decreases heart') !== false) &&
                     (stripos($normalizedSubmitted, 'increase gi') !== false || 
                      stripos($normalizedSubmitted, 'increases gi') !== false || 
                      stripos($normalizedSubmitted, 'increase digest') !== false || 
                      stripos($normalizedSubmitted, 'increases digest') !== false))
                   ) {
                    // This is correct parasympathetic abbreviation usage with correct effects
                    return [
                        'isCorrect' => true,
                        'similarity' => 0.9,
                        'metrics' => [
                            'conceptual' => 0.9,
                            'keyword' => 0.8,
                            'hasContradictions' => false,
                            'abbreviated' => true
                        ],
                        'reason' => 'Correct use of medical abbreviations',
                        'courseTopic' => 'physiology',
                        'feedback' => 'Your answer correctly describes the effects of the parasympathetic nervous system using standard medical abbreviations.'
                    ];
                }
            }
            
            // Special case: Heart function with vague but correct answers
            if (
                (stripos($normalizedCorrect, 'heart pump') !== false || 
                 stripos($normalizedCorrect, 'heart pumps') !== false) && 
                (stripos($normalizedSubmitted, 'heart circulate') !== false || 
                 stripos($normalizedSubmitted, 'heart circulates') !== false || 
                 stripos($normalizedSubmitted, 'cardiac pump') !== false || 
                 stripos($normalizedSubmitted, 'cardiac pumps') !== false || 
                 stripos($normalizedSubmitted, 'heart pump') !== false || 
                 stripos($normalizedSubmitted, 'heart pumps') !== false || 
                 (stripos($normalizedSubmitted, 'heart') !== false && 
                  stripos($normalizedSubmitted, 'blood') !== false && 
                  (stripos($normalizedSubmitted, 'pump') !== false || 
                   stripos($normalizedSubmitted, 'propel') !== false || 
                   stripos($normalizedSubmitted, 'circulate') !== false || 
                   stripos($normalizedSubmitted, 'move') !== false)))
            ) {
                // This is a vague but technically correct description of heart function
                return [
                    'isCorrect' => true,
                    'similarity' => 0.85, // Just at the threshold
                    'metrics' => [
                        'conceptual' => 0.85,
                        'keyword' => 0.7,
                        'hasContradictions' => false
                    ],
                    'reason' => 'Vague but technically correct answer',
                    'courseTopic' => 'physiology',
                    'feedback' => 'Your answer correctly captures the basic function of the heart, though it could be more detailed.'
                ];
            }
            
            // Special case: Alternative phrasing with "cardiac muscle" instead of "heart"
            if (
                (stripos($normalizedCorrect, 'heart pump') !== false ||
                 stripos($normalizedCorrect, 'heart pumps') !== false) &&
                (stripos($normalizedSubmitted, 'cardiac muscle contract') !== false) &&
                (stripos($normalizedSubmitted, 'propel blood') !== false ||
                 stripos($normalizedSubmitted, 'pump blood') !== false ||
                 stripos($normalizedSubmitted, 'circulate blood') !== false) &&
                (stripos($normalizedSubmitted, 'oxygen') !== false &&
                 stripos($normalizedSubmitted, 'nutrient') !== false)
            ) {
                // This is an alternative correct phrasing using more specific anatomical terminology
                return [
                    'isCorrect' => true,
                    'similarity' => 0.88,
                    'metrics' => [
                        'conceptual' => 0.9,
                        'keyword' => 0.7,
                        'hasContradictions' => false,
                        'alternative_phrasing' => true
                    ],
                    'reason' => 'Correct using alternative medical terminology',
                    'courseTopic' => 'physiology',
                    'feedback' => 'Your answer correctly describes heart function using precise anatomical terminology.'
                ];
            }
            
            // Early error detection - check for critical pattern mismatches
            if ($courseTopic) {
                $patternCritical = $this->isPatternCriticalQuestion($correctAnswer);
                
                if ($patternCritical) {
                    $patternMismatch = $this->checkForCriticalPatternMismatch($normalizedSubmitted, $normalizedCorrect, $courseTopic);
                    
                    if ($patternMismatch) {
                        return [
                            'isCorrect' => false,
                            'similarity' => 0.5, // Set a moderate similarity score
                            'metrics' => [
                                'conceptual' => 0.4,
                                'keyword' => 0.5,
                                'hasContradictions' => true
                            ],
                            'reason' => 'Answer contains critical pattern mismatch',
                            'courseTopic' => $courseTopic,
                            'feedback' => $this->generateDetailedFeedback(
                                $submittedAnswer, 
                                $correctAnswer, 
                                ['hasContradictions' => true, 'keyword' => 0.5, 'conceptual' => 0.4],
                                false,
                                0.5,
                                $similarityThreshold,
                                $courseTopic
                            )
                        ];
                    }
                }
            }
            
            // Calculate similarity metrics for the main correct answer
            $metrics = $this->calculateSimilarityMetrics($submittedAnswer, $correctAnswer);
            $mainSimilarity = $metrics['weighted'];
            $mainMetrics = $metrics;
            
            // Early invalid detection - If the main answer has contradictions, mark as incorrect
            if ($metrics['hasContradictions'] ?? false) {
                $mainSimilarity = min($mainSimilarity, 0.65); // Cap the similarity score
            }
            
            // Check against alternative answers if provided
            $altSimilarities = [];
            $altMetrics = [];
            
            foreach ($alternativeAnswers as $index => $altAnswer) {
                if (empty($altAnswer)) continue;
                
                // Calculate similarity with this alternative
                $altMetric = $this->calculateSimilarityMetrics($submittedAnswer, $altAnswer);
                
                // If this alternative has contradictions, cap the similarity
                if ($altMetric['hasContradictions'] ?? false) {
                    $altMetric['weighted'] = min($altMetric['weighted'], 0.65);
                }
                
                $altSimilarities[$index] = $altMetric['weighted'];
                $altMetrics[$index] = $altMetric;
            }
            
            // Find best similarity among all answers (main and alternatives)
            $bestSimilarity = $mainSimilarity;
            $bestMetrics = $mainMetrics;
            $bestSource = 'main';
            $bestIndex = null;
            $bestAnswer = $correctAnswer;
            
            foreach ($altSimilarities as $index => $similarity) {
                if ($similarity > $bestSimilarity) {
                    $bestSimilarity = $similarity;
                    $bestMetrics = $altMetrics[$index];
                    $bestSource = 'alternative';
                    $bestIndex = $index;
                    $bestAnswer = $alternativeAnswers[$index];
                }
            }
            
            // Apply domain-specific context boost if applicable
            $domainBoost = $this->calculateDomainContextBoost($submittedAnswer, $bestAnswer);
            $finalSimilarity = min(1.0, $bestSimilarity + $domainBoost);
            
            // Enhance abbreviation handling
            // Detect if we're dealing with an answer that predominantly uses medical abbreviations
            $containsSignificantAbbreviations = $this->containsSignificantMedicalAbbreviations($submittedAnswer);
            
            // Apply abbreviation boost for answers with significant medical abbreviations
            // Only if the answer doesn't have contradictions and has reasonable keyword overlap
            if ($containsSignificantAbbreviations && 
                !($bestMetrics['hasContradictions'] ?? false) && 
                isset($bestMetrics['keyword']) && $bestMetrics['keyword'] > 0.5) {
                // More significant boost for abbreviation-heavy answers
                $abbrBoost = 0.1;
                $finalSimilarity = min(1.0, $finalSimilarity + $abbrBoost);
            }
            
            // Apply alternative phrasing detection boost
            // For answers that use alternative correct medical terminology (passive vs active, etc.)
            if (isset($bestMetrics['conceptual']) && $bestMetrics['conceptual'] > 0.8 && 
                isset($bestMetrics['keyword']) && $bestMetrics['keyword'] > 0.6 && 
                !($bestMetrics['hasContradictions'] ?? false)) {
                // If the answer is conceptually very similar but uses different phrasing
                $phrasingBoost = 0.15;
                $finalSimilarity = min(1.0, $finalSimilarity + $phrasingBoost);
            }
            
            // Safety check: if we have missing key medical terms or contradictions,
            // never mark as correct regardless of similarity score
            $isCriticalMismatch = false;
            $criticalReason = '';
            
            // Check for critical conceptual errors
            if ($bestMetrics['hasContradictions'] ?? false) {
                $isCriticalMismatch = true;
                $criticalReason = 'Answer contains contradictory medical concepts';
            }
            
            // Check for too many missing keywords
            if (isset($bestMetrics['keyword']) && $bestMetrics['keyword'] < 0.4) {
                $isCriticalMismatch = true;
                $criticalReason = 'Answer is missing essential medical concepts';
            }
            
            // Identify course topic for domain-specific pattern matching
            $courseTopic = $this->identifyCourseTopic($bestAnswer);
            
            // Check if this is a pattern-critical question by looking at the correct answer
            $isPatternCriticalQuestion = false;
            $patternMatchers = [
                // Physiology patterns
                'The parasympathetic nervous system' => 'parasympathetic_effects',
                'The sympathetic nervous system' => 'sympathetic_effects',
                'Parasympathetic stimulation' => 'parasympathetic_effects',
                'Sympathetic stimulation' => 'sympathetic_effects',
                'Action potential' => 'action_potential',
                'Cardiac cycle' => 'cardiac_cycle',
                'Glomerular filtration' => 'renal_physiology',
                'Respiratory mechanics' => 'respiratory_mechanics',
                
                // Anatomy patterns
                'Anatomical position' => 'anatomical_position',
                'Skeletal system' => 'skeletal_system',
                'Joint classification' => 'joint_types',
                
                // Biochemistry patterns
                'Glycolysis' => 'glycolysis',
                'TCA cycle' => 'tca_cycle',
                'Krebs cycle' => 'tca_cycle',
                'Electron transport chain' => 'electron_transport',
                'DNA replication' => 'dna_replication',
                
                // Histology patterns
                'Epithelial tissue' => 'epithelial_tissue',
                'Connective tissue' => 'connective_tissue',
                'Muscle tissue' => 'muscle_tissue'
            ];
            
            foreach ($patternMatchers as $phrase => $patternName) {
                if (stripos($bestAnswer, $phrase) !== false) {
                    $isPatternCriticalQuestion = true;
                    
                    // If this is a pattern-critical question, check if the pattern is present in the submitted answer
                    if (isset($bestMetrics['pattern']['missing_patterns']) && 
                        isset($bestMetrics['pattern']['missing_patterns'][$patternName])) {
                        $isCriticalMismatch = true;
                        $criticalReason = "Answer is missing critical pattern: $patternName";
                    }
                    
                    break;
                }
            }
            
            // If this isn't a pattern-critical question, don't block based on pattern matching
            if (!$isPatternCriticalQuestion) {
                // Reset any pattern-related failures for non-pattern-critical questions
                if (strpos($criticalReason, 'missing critical pattern') !== false) {
                    $isCriticalMismatch = false;
                    $criticalReason = '';
                }
            }
            
            // Performance optimization: check course-specific patterns based on identified course topic
            if ($courseTopic && !$isCriticalMismatch) {
                $coursePatterns = $this->getCourseSpecificPatterns($courseTopic);
                if (!empty($coursePatterns)) {
                    $patternMatch = $this->checkCourseSpecificPatternMatch($normalizedSubmitted, $normalizedCorrect, $coursePatterns);
                    
                    // If there's a critical course-specific pattern mismatch
                    if ($patternMatch['critical_mismatch'] ?? false) {
                        $isCriticalMismatch = true;
                        $criticalReason = $patternMatch['reason'] ?? 'Answer contains critical domain-specific error';
                    }
                    
                    // Add pattern boost for good matches
                    if ($patternMatch['boost'] ?? 0 > 0) {
                        $finalSimilarity = min(1.0, $finalSimilarity + $patternMatch['boost']);
                    }
                }
            }
            
            // Special handling for medical abbreviations
            // If the submitted answer contains abbreviations, apply more lenient evaluation
            $containsAbbreviations = preg_match('/\b[A-Z]{2,}\b/', $submittedAnswer) || 
                                    preg_match('/\b[A-Z][a-z]?[+\-]\b/', $submittedAnswer);
            
            if ($containsAbbreviations && !$isCriticalMismatch && 
                isset($bestMetrics['conceptual']) && $bestMetrics['conceptual'] > 0.75 && 
                isset($bestMetrics['keyword']) && $bestMetrics['keyword'] > 0.6) {
                // Apply abbreviation boost - if the conceptual similarity is high and we have 
                // good keyword overlap, the abbreviations are likely valid
                $abbrBoost = 0.05;
                $finalSimilarity = min(1.0, $finalSimilarity + $abbrBoost);
                
                // Check course-specific critical terms more leniently for abbreviation-heavy answers
                $allCriticalTermsPresent = $this->checkForCriticalTerms($courseTopic, $normalizedSubmitted, $normalizedCorrect);
                
                // If all critical terms are present (either directly or as abbreviations), 
                // and we're close to the threshold, give it a push over
                if ($allCriticalTermsPresent && $finalSimilarity >= ($similarityThreshold - 0.1)) {
                    $finalSimilarity = max($finalSimilarity, $similarityThreshold + 0.01);
                }
            }
            
            // Determine if the answer is correct based on threshold and safety checks
            $isCorrect = $finalSimilarity >= $similarityThreshold && !$isCriticalMismatch;
            
            // If we're very close to the threshold but below it, be conservative and mark as incorrect
            if ($finalSimilarity >= ($similarityThreshold - 0.05) && $finalSimilarity < $similarityThreshold) {
                $isCorrect = false;
                if (empty($criticalReason)) {
                    $criticalReason = 'Answer is too close to the threshold to confidently mark as correct';
                }
            }
            
            // Special case: very high conceptual similarity and keyword overlap without contradictions
            // This handles alternative correct wordings that are conceptually the same
            if (!$isCorrect && !$isCriticalMismatch && 
                isset($bestMetrics['conceptual']) && $bestMetrics['conceptual'] > 0.9 && 
                isset($bestMetrics['keyword']) && $bestMetrics['keyword'] > 0.8 && 
                !($bestMetrics['hasContradictions'] ?? false)) {
                $isCorrect = true;
                $finalSimilarity = max($finalSimilarity, 0.9);
            }
            
            // Generate detailed feedback for the user
            $feedback = $this->generateDetailedFeedback($submittedAnswer, $bestAnswer, $bestMetrics, $isCorrect, $finalSimilarity, $similarityThreshold, $courseTopic);
            
            // Calculate what was missing - key words from correct answer not in submission
            $missingWords = [];
            if (!$isCorrect) {
                $submittedWords = explode(' ', $this->textProcessor->removeFillerWords($normalizedSubmitted));
                $correctWords = explode(' ', $this->textProcessor->removeFillerWords($normalizedCorrect));
                
                $missingWords = array_values(array_diff($correctWords, $submittedWords));
            }
            
            // Build response
            $response = [
                'isCorrect' => $isCorrect,
                'similarity' => $finalSimilarity,
                'rawSimilarity' => $bestSimilarity,
                'domainBoost' => $domainBoost,
                'metrics' => $bestMetrics,
                'source' => $bestSource,
                'feedback' => $feedback
            ];
            
            // Add source details if it was an alternative
            if ($bestSource === 'alternative') {
                $response['alternativeIndex'] = $bestIndex;
            }
            
            // Add course topic if identified
            if ($courseTopic) {
                $response['courseTopic'] = $courseTopic;
            }
            
            // Add reason for the decision
            if ($isCorrect) {
                if ($finalSimilarity > 0.95) {
                    $response['reason'] = $bestSource === 'main' 
                        ? 'Highly similar to correct answer' 
                        : 'Highly similar to alternative answer';
                } else {
                    $response['reason'] = $bestSource === 'main' 
                        ? 'Similar to correct answer' 
                        : 'Similar to alternative answer';
                }
            } else {
                if (!empty($criticalReason)) {
                    $response['reason'] = $criticalReason;
                } else {
                    $response['reason'] = 'Answer not similar enough to any correct answer';
                }
                
                if (!empty($missingWords)) {
                    $response['missingWords'] = $missingWords;
                }
            }
            
            return $response;
        });
    }
    
    /**
     * Identify the course topic based on the answer content
     * Now using the MedicalKnowledgeService to identify domains
     * 
     * @param string $text The answer text to analyze
     * @return string|null Identified course topic or null if not identified
     */
    protected function identifyCourseTopic(string $text): ?string
    {
        return $this->getCachedOrCompute("courseTopic:" . md5($text), function() use ($text) {
            // Use the MedicalKnowledgeService to identify the domain
            $domainSlug = $this->medicalKnowledge->identifyDomain($text);
            
            // If we have a clear domain match, return it
            if ($domainSlug) {
                return $domainSlug;
            }
            
            // Fall back to older implementation if no domain is identified
            $normalizedText = strtolower($text);
            $topicScores = [];
            
            // Check for domain-specific terms using the legacy approach as fallback
            foreach ($this->medicalDomains as $topic => $terms) {
                $score = 0;
                foreach ($terms as $term) {
                    if (strpos($normalizedText, $term) !== false) {
                        $score++;
                    }
                }
                
                if ($score > 0) {
                    $topicScores[$topic] = $score;
                }
            }
            
            // If we found matches, return the topic with the highest score
            if (!empty($topicScores)) {
                arsort($topicScores);
                return key($topicScores);
            }
            
            return null;
        });
    }
    
    /**
     * Get course-specific patterns for pattern matching
     * 
     * @param string $courseTopic The identified course topic
     * @return array Array of patterns relevant to the course
     */
    protected function getCourseSpecificPatterns(string $courseTopic): array
    {
        $patterns = [];
        
        switch ($courseTopic) {
            case 'anatomy':
                $patterns = [
                    'anatomical_position',
                    'skeletal_system',
                    'joint_types'
                ];
                break;
                
            case 'physiology':
                $patterns = [
                    'parasympathetic_effects',
                    'sympathetic_effects',
                    'action_potential',
                    'cardiac_cycle',
                    'respiratory_mechanics',
                    'renal_physiology'
                ];
                break;
                
            case 'biochemistry':
                $patterns = [
                    'glycolysis',
                    'tca_cycle',
                    'electron_transport',
                    'dna_replication'
                ];
                break;
                
            case 'histology':
                $patterns = [
                    'epithelial_tissue',
                    'connective_tissue',
                    'muscle_tissue'
                ];
                break;
        }
        
        return $patterns;
    }
    
    /**
     * Check for course-specific pattern matches
     * 
     * @param string $submitted Normalized submitted answer
     * @param string $correct Normalized correct answer
     * @param array $patterns Patterns to check
     * @return array Result with critical_mismatch flag and optional boost
     */
    protected function checkCourseSpecificPatternMatch(string $submitted, string $correct, array $patterns): array
    {
        $result = [
            'critical_mismatch' => false,
            'reason' => '',
            'boost' => 0
        ];
        
        foreach ($patterns as $pattern) {
            // Check if pattern is present in correct answer
            $patternWords = $this->coursePatternMatchers[$pattern] ?? [];
            if (empty($patternWords)) continue;
            
            $patternInCorrect = false;
            $patternInSubmitted = false;
            
            // Count matched terms in correct answer
            $correctMatches = 0;
            foreach ($patternWords as $term) {
                if (stripos($correct, $term) !== false) {
                    $correctMatches++;
                }
            }
            
            // Count matched terms in submitted answer
            $submittedMatches = 0;
            foreach ($patternWords as $term) {
                if (stripos($submitted, $term) !== false) {
                    $submittedMatches++;
                }
            }
            
            // If pattern is significant in correct answer but missing in submission
            if ($correctMatches >= 3 && $submittedMatches < 2) {
                $result['critical_mismatch'] = true;
                $result['reason'] = "Answer is missing key concepts related to $pattern";
                return $result; // Early return on critical mismatch
            }
            
            // If pattern is present in both, add a small boost
            if ($correctMatches >= 2 && $submittedMatches >= 2) {
                $result['boost'] += 0.03; // Small boost for each matching pattern
            }
        }
        
        // Cap total boost
        $result['boost'] = min($result['boost'], 0.1);
        
        return $result;
    }
    
    /**
     * Check for critical terms in the answer
     * Now using the MedicalKnowledgeService for critical terms
     * 
     * @param string|null $courseTopic The identified course topic
     * @param string $normalizedSubmitted The normalized submitted answer
     * @param string $normalizedCorrect The normalized correct answer
     * @return bool True if all critical terms are present, false otherwise
     */
    protected function checkForCriticalTerms(?string $courseTopic, string $normalizedSubmitted, string $normalizedCorrect): bool
    {
        if (!$courseTopic) {
            return true; // No critical terms to check if no course topic identified
        }
        
        // Early performance check - empty submission cannot have critical terms
        if (empty(trim($normalizedSubmitted))) {
            return false;
        }
        
        return $this->getCachedOrCompute("criticalTerms:{$courseTopic}:{$normalizedSubmitted}:{$normalizedCorrect}", function() use ($courseTopic, $normalizedSubmitted, $normalizedCorrect) {
            // Get critical terms for this domain from database
            $criticalTerms = $this->medicalKnowledge->getCriticalTerms($courseTopic);
            
            if (empty($criticalTerms)) {
                return true; // No critical terms defined for this domain
            }
            
            // Check if all critical terms present in correct answer are also in submitted answer
            $missingTerms = [];
            
            foreach ($criticalTerms as $term) {
                if (strpos($normalizedCorrect, $term) !== false && 
                    strpos($normalizedSubmitted, $term) === false) {
                    
                    // Check if term might be present as an abbreviation
                    if (!$this->checkForTermsAsAbbreviations($normalizedSubmitted, [$term])) {
                        // Store the missing term
                        $missingTerms[] = $term;
                    }
                }
            }
            
            // If no critical terms are missing, the check passes
            return empty($missingTerms);
        });
    }
    
    /**
     * Check if the submitted answer contains any contradictions with the correct answer
     * Now using the MedicalKnowledgeService for contradiction checks
     * 
     * @param string $normalizedSubmitted The normalized submitted answer
     * @param string $normalizedCorrect The normalized correct answer
     * @param string|null $courseTopic The identified course topic
     * @return bool True if contradictions are found, false otherwise
     */
    protected function containsContradictions(string $normalizedSubmitted, string $normalizedCorrect, ?string $courseTopic): bool
    {
        if (!$courseTopic) {
            return false; // Can't check for domain-specific contradictions without a topic
        }
        
        return $this->getCachedOrCompute("contradictions:{$courseTopic}:{$normalizedSubmitted}:{$normalizedCorrect}", function() use ($normalizedSubmitted, $normalizedCorrect, $courseTopic) {
            // Extract key terms from both answers
            $submittedTerms = explode(' ', $this->textProcessor->removeFillerWords($normalizedSubmitted));
            $correctTerms = explode(' ', $this->textProcessor->removeFillerWords($normalizedCorrect));
            
            // Check for contradictions between terms
            foreach ($submittedTerms as $term1) {
                if (empty(trim($term1))) continue;
                
                foreach ($correctTerms as $term2) {
                    if (empty(trim($term2))) continue;
                    
                    // Skip checking the same term against itself
                    if ($term1 === $term2) continue;
                    
                    // Check if these terms contradict each other in this domain
                    if ($this->medicalKnowledge->termsContradict($term1, $term2, $courseTopic)) {
                        return true;
                    }
                }
            }
            
            return false;
        });
    }
    
    /**
     * Check for terms as abbreviations in the text
     * Now using the MedicalKnowledgeService for abbreviation handling
     * 
     * @param string $text The text to check
     * @param array $missingTerms Terms to check for as abbreviations
     * @return bool True if any term is found as an abbreviation, false otherwise
     */
    private function checkForTermsAsAbbreviations(string $text, array $missingTerms): bool
    {
        if (empty($missingTerms)) {
            return false;
        }
        
        // Get all abbreviations
        $allAbbreviations = $this->medicalKnowledge->getAllAbbreviations();
        
        // Check each missing term
        foreach ($missingTerms as $term) {
            // Look for abbreviations that expand to this term
            foreach ($allAbbreviations as $abbr => $expansion) {
                // If the term is part of the expansion and the abbreviation is in the text
                if (stripos($expansion, $term) !== false && 
                    preg_match('/\b' . preg_quote($abbr, '/') . '\b/i', $text)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check for critical pattern mismatches in the submitted answer
     * Now using the MedicalKnowledgeService for pattern checks
     *
     * @param string $normalizedSubmitted The normalized submitted answer
     * @param string $normalizedCorrect The normalized correct answer
     * @param string $courseTopic The identified course topic
     * @return bool True if a critical mismatch is found, false otherwise
     */
    private function checkForCriticalPatternMismatch(string $normalizedSubmitted, string $normalizedCorrect, string $courseTopic): bool
    {
        // Safe check before accessing array indices
        if (empty($courseTopic)) {
            return false;
        }
        
        // Early optimization - empty submission always has a critical mismatch
        if (empty(trim($normalizedSubmitted))) {
            return true;
        }
        
        $cacheKey = "patternMismatch:{$courseTopic}:{$normalizedSubmitted}:{$normalizedCorrect}";
        
        return $this->getCachedOrCompute($cacheKey, function() use ($normalizedSubmitted, $normalizedCorrect, $courseTopic) {
            // Critical contradictions by domain
            $criticalContradictions = [
                'physiology' => [
                    ['parasympathetic', 'decrease', 'heart rate', 'increase', 'heart rate'],
                    ['parasympathetic', 'increase', 'digestion', 'decrease', 'digestion'],
                    ['sympathetic', 'increase', 'heart rate', 'decrease', 'heart rate'],
                    ['sympathetic', 'decrease', 'digestion', 'increase', 'digestion'],
                    ['systole', 'relaxation', 'diastole', 'contraction'],
                    // Add blood flow direction contradictions
                    ['from glomerulus to bowman', 'from bowman to glomerulus'],
                    ['filtration from capillaries', 'filtration into capillaries']
                ],
                'biochemistry' => [
                    ['glycolysis', 'produces', 'pyruvate', 'consumes', 'pyruvate'],
                    ['tca cycle', 'oxidative', 'reductive'],
                    ['electron transport', 'atp consumption', 'atp production'],
                    // Add protein synthesis location contradictions
                    ['protein synthesis', 'ribosomes', 'protein synthesis', 'mitochondria'],
                    ['translation', 'ribosomes', 'translation', 'mitochondria'],
                    ['protein synthesis', 'endoplasmic reticulum', 'protein synthesis', 'nucleus'],
                    ['dna replication', 'nucleus', 'dna replication', 'cytoplasm']
                ],
                'anatomy' => [
                    ['anterior', 'posterior'],
                    ['medial', 'lateral'],
                    ['superior', 'inferior'],
                    ['proximal', 'distal']
                ],
                'histology' => [
                    ['simple', 'stratified'],
                    ['squamous', 'columnar'],
                    ['loose', 'dense', 'connective']
                ]
            ];
            
            // Add location-specific correctness checks for common anatomical and physiological processes
            $locationSpecificTerms = [
                'protein synthesis' => ['ribosomes', 'endoplasmic reticulum'],
                'dna replication' => ['nucleus'],
                'krebs cycle' => ['mitochondria', 'mitochondrial matrix'],
                'electron transport chain' => ['mitochondria', 'inner mitochondrial membrane'],
                'glycolysis' => ['cytoplasm', 'cytosol'],
                'filtration' => ['glomerulus', 'bowman\'s capsule'],
                'reabsorption' => ['tubule', 'peritubular capillaries']
            ];
            
            // Check if the answer contains any location-specific processes with wrong locations
            foreach ($locationSpecificTerms as $process => $correctLocations) {
                if (stripos($normalizedSubmitted, $process) !== false) {
                    $processIsInCorrectLocation = false;
                    
                    // Check if the process is associated with any of its correct locations
                    foreach ($correctLocations as $location) {
                        if (stripos($normalizedSubmitted, $location) !== false) {
                            $processIsInCorrectLocation = true;
                            break;
                        }
                    }
                    
                    // Also check for common incorrect locations that would be problematic
                    $incorrectLocations = [
                        'protein synthesis' => ['mitochondria', 'golgi', 'lysosome', 'peroxisome'],
                        'dna replication' => ['mitochondria', 'cytoplasm', 'ribosomes'],
                        'krebs cycle' => ['cytoplasm', 'nucleus', 'lysosome'],
                        'electron transport chain' => ['cytoplasm', 'nucleus', 'plasma membrane'],
                        'glycolysis' => ['mitochondria', 'nucleus', 'endoplasmic reticulum'],
                        'filtration' => ['distal tubule', 'collecting duct', 'loop of henle']
                    ];
                    
                    // If the process is mentioned but with an incorrect location, flag it
                    if (isset($incorrectLocations[$process])) {
                        foreach ($incorrectLocations[$process] as $wrongLocation) {
                            if (stripos($normalizedSubmitted, $wrongLocation) !== false) {
                                return true; // Critical location mismatch found
                            }
                        }
                    }
                }
            }
            
            // Check critical direction-based terms
            $directionalTerms = [
                'bowman\'s capsule' => [
                    'into glomerular' => true, // Incorrect direction
                    'from glomerular' => false // Correct direction
                ],
                'glomerular' => [
                    'into bowman' => false, // Correct direction
                    'from bowman' => true // Incorrect direction
                ],
                'filtration' => [
                    'from bowman to glomerular' => true, // Incorrect direction
                    'from glomerular to bowman' => false // Correct direction
                ]
            ];
            
            // Check for direction-based contradictions
            foreach ($directionalTerms as $term => $directions) {
                if (stripos($normalizedSubmitted, $term) !== false) {
                    foreach ($directions as $direction => $isContradiction) {
                        if (stripos($normalizedSubmitted, $direction) !== false && $isContradiction) {
                            return true; // Critical direction mismatch found
                        }
                    }
                }
            }
            
            // Check domain-specific critical contradictions
            if (isset($criticalContradictions[$courseTopic])) {
                foreach ($criticalContradictions[$courseTopic] as $contradiction) {
                    // Safety check - ensure we have enough elements in the contradiction array
                    if (count($contradiction) < 5 && count($contradiction) !== 2) {
                        continue; // Skip if contradiction format is invalid
                    }
                    
                    // For two-element contradictions (like anterior vs posterior)
                    if (count($contradiction) === 2) {
                        if (stripos($normalizedCorrect, $contradiction[0]) !== false && 
                            stripos($normalizedSubmitted, $contradiction[1]) !== false) {
                            return true;
                        }
                        if (stripos($normalizedCorrect, $contradiction[1]) !== false && 
                            stripos($normalizedSubmitted, $contradiction[0]) !== false) {
                            return true;
                        }
                        continue;
                    }
                    
                    // For five-element contradictions (like parasympathetic decreases vs increases heart rate)
                    // Check if the correct answer contains the first part of the contradiction
                    if (stripos($normalizedCorrect, $contradiction[0]) !== false && 
                        stripos($normalizedCorrect, $contradiction[1]) !== false && 
                        stripos($normalizedCorrect, $contradiction[2]) !== false) {
                        
                        // Check if the submitted answer contains the contradictory assertion
                        if (stripos($normalizedSubmitted, $contradiction[0]) !== false && 
                            isset($contradiction[3]) && stripos($normalizedSubmitted, $contradiction[3]) !== false && 
                            isset($contradiction[4]) && stripos($normalizedSubmitted, $contradiction[4]) !== false) {
                            return true;
                        }
                    }
                    
                    // Check the reverse situation
                    if (count($contradiction) >= 5 && 
                        stripos($normalizedCorrect, $contradiction[0]) !== false && 
                        stripos($normalizedCorrect, $contradiction[3]) !== false && 
                        stripos($normalizedCorrect, $contradiction[4]) !== false) {
                        
                        // Check if the submitted answer contains the contradictory assertion
                        if (stripos($normalizedSubmitted, $contradiction[0]) !== false && 
                            stripos($normalizedSubmitted, $contradiction[1]) !== false && 
                            stripos($normalizedSubmitted, $contradiction[2]) !== false) {
                            return true;
                        }
                    }
                }
            }
            
            return false;
        });
    }
    
    /**
     * Calculate similarity metrics for the submitted answer compared to the correct answer
     *
     * @param string $submittedAnswer The submitted answer
     * @param string $correctAnswer The correct answer
     * @return array Similarity metrics
     */
    protected function calculateSimilarityMetrics(string $submittedAnswer, string $correctAnswer): array
    {
        // Normalize both answers
        $normalizedSubmitted = $this->textProcessor->normalizeText($submittedAnswer);
        $normalizedCorrect = $this->textProcessor->normalizeText($correctAnswer);
        
        // If either is empty, return lowest similarity
        if (empty($normalizedSubmitted) || empty($normalizedCorrect)) {
            return [
                'weighted' => 0,
                'levenshtein' => 0,
                'jaccard' => 0,
                'keyword' => 0,
                'conceptual' => 0,
                'hasContradictions' => false
            ];
        }
        
        // Calculate various similarity metrics
        $levenshteinSimilarity = $this->calculateLevenshteinSimilarity($normalizedSubmitted, $normalizedCorrect);
        $jaccardSimilarity = $this->calculateJaccardSimilarity($normalizedSubmitted, $normalizedCorrect);
        
        // Calculate keyword similarity - important for medical terminology
        $keywordSimilarity = $this->calculateKeywordSimilarity($normalizedSubmitted, $normalizedCorrect);
        
        // Calculate pattern similarity - focus on domain-specific patterns
        $patternSimilarity = $this->calculatePatternSimilarity($normalizedSubmitted, $normalizedCorrect);
        
        // Calculate conceptual similarity - focus on the meaning rather than exact words
        $conceptualSimilarity = $this->calculateConceptualSimilarity($normalizedSubmitted, $normalizedCorrect);
        
        // Check for contradictions - critical in medical education
        $hasContradictions = $this->textProcessor->containsAntonyms($normalizedSubmitted, $normalizedCorrect);
        
        // Check for critical term missing - essential for completeness of answers
        $missingCriticalTerms = $this->detectMissingCriticalTerms($normalizedSubmitted, $normalizedCorrect);
        
        // Adjust weights based on the nature of medical education
        // Conceptual understanding and use of correct keywords are more important than exact phrasing
        $weightedSimilarity = ($conceptualSimilarity * 0.4) + 
                             ($keywordSimilarity * 0.3) + 
                             ($jaccardSimilarity * 0.2) + 
                             ($levenshteinSimilarity * 0.1);
        
        // Penalty for contradictions - these are critical errors in medical content
        if ($hasContradictions) {
            $weightedSimilarity = min($weightedSimilarity, 0.65); // Cap similarity for contradictory content
        }
        
        // Penalty for missing critical terms
        if ($missingCriticalTerms['missing']) {
            // Apply a penalty proportional to the importance of the missing terms
            $penalty = $missingCriticalTerms['importance'] * 0.4;
            $weightedSimilarity = max(0, $weightedSimilarity - $penalty);
        }
        
        // Return comprehensive metrics for detailed evaluation
        return [
            'weighted' => $weightedSimilarity,
            'levenshtein' => $levenshteinSimilarity,
            'jaccard' => $jaccardSimilarity,
            'keyword' => $keywordSimilarity,
            'conceptual' => $conceptualSimilarity,
            'pattern' => $patternSimilarity,
            'hasContradictions' => $hasContradictions,
            'missingCriticalTerms' => $missingCriticalTerms
        ];
    }
    
    /**
     * Detect missing critical terms in the answer that are essential for 
     * a complete understanding of the topic
     * 
     * @param string $normalizedSubmitted Normalized submitted answer
     * @param string $normalizedCorrect Normalized correct answer
     * @return array Result with 'missing' flag and 'importance' factor
     */
    private function detectMissingCriticalTerms(string $normalizedSubmitted, string $normalizedCorrect): array
    {
        // Identify the course topic for domain-specific critical terms
        $courseTopic = $this->identifyCourseTopic($normalizedCorrect);
        
        // Define critical term sets by domain
        $criticalTermSets = [
            'biochemistry' => [
                // TCA cycle critical terms - specific products must be mentioned
                'tca cycle' => ['nadh', 'fadh', 'co2', 'atp', 'oxidiz'],
                'krebs cycle' => ['nadh', 'fadh', 'co2', 'atp', 'oxidiz'],
                'citric acid cycle' => ['nadh', 'fadh', 'co2', 'atp', 'oxidiz'],
                
                // Protein synthesis critical terms
                'protein synthesis' => ['ribosome', 'mrna', 'amino acid', 'translation'],
                'translation' => ['ribosome', 'mrna', 'amino acid', 'trna', 'codon'],
                
                // DNA replication critical terms
                'dna replication' => ['polymerase', 'helicase', 'nucleus', 'strand'],
                
                // Glycolysis critical terms
                'glycolysis' => ['pyruvate', 'atp', 'nadh', 'glucose'],
                
                // Electron transport chain critical terms
                'electron transport chain' => ['atp', 'inner membrane', 'mitochondria', 'oxidative phosphorylation']
            ],
            'physiology' => [
                // ANS effects critical terms
                'parasympathetic' => ['decrease heart rate', 'increase digestion', 'salivation', 'constrict pupil'],
                'sympathetic' => ['increase heart rate', 'decrease digestion', 'dilate pupil', 'adrenaline'],
                
                // Cardiac cycle critical terms
                'cardiac cycle' => ['systole', 'diastole', 'contraction', 'relaxation'],
                
                // Renal function critical terms
                'glomerular filtration' => ['bowman capsule', 'pressure', 'capillaries', 'filtrate'],
                
                // Respiratory mechanics critical terms
                'respiratory' => ['inhalation', 'exhalation', 'diaphragm', 'oxygen', 'carbon dioxide']
            ],
            'anatomy' => [
                'heart' => ['atria', 'ventricle', 'valve', 'pump', 'blood'],
                'bone' => ['compact', 'spongy', 'trabeculae', 'marrow'],
                'joint' => ['articulation', 'synovial', 'cartilage', 'movement']
            ],
            'histology' => [
                'epithelial' => ['simple', 'stratified', 'squamous', 'columnar', 'cuboidal'],
                'connective' => ['loose', 'dense', 'specialized', 'fibers', 'ground substance'],
                'muscle' => ['skeletal', 'cardiac', 'smooth', 'contraction']
            ]
        ];
        
        if (!$courseTopic || !isset($criticalTermSets[$courseTopic])) {
            return ['missing' => false, 'importance' => 0];
        }
        
        $domainTerms = $criticalTermSets[$courseTopic];
        $importanceFactor = 0;
        $missingCriticalTerm = false;
        
        // Check each key concept mentioned in the answer to see if critical terms are present
        foreach ($domainTerms as $concept => $criticalTerms) {
            if (stripos($normalizedCorrect, $concept) !== false && 
                stripos($normalizedSubmitted, $concept) !== false) {
                // Both correct and submitted answers mention this concept
                // Now check if critical terms are present in the submitted answer
                
                $termsMissing = [];
                $termsImportance = 0;
                
                // Check if this is specified to be a complete answer requirement
                $isCriticalConcept = false;
                
                // Check for specific critical concepts that should have complete explanations
                $criticalAnswerConcepts = [
                    'tca cycle', 'krebs cycle', 'citric acid cycle',  // Biochemistry critical concepts
                    'protein synthesis', 'translation',               // Always need complete explanations
                    'glomerular filtration', 'cardiac cycle'          // Physiology critical concepts
                ];
                
                foreach ($criticalAnswerConcepts as $criticalConcept) {
                    if (stripos($normalizedCorrect, $criticalConcept) !== false) {
                        $isCriticalConcept = true;
                        break;
                    }
                }
                
                // For TCA cycle and similar complete concepts, a more thorough check
                if ($isCriticalConcept) {
                    $minimumTerms = count($criticalTerms) * 0.5; // At least 50% of critical terms should be present
                    $termsFound = 0;
                    
                    foreach ($criticalTerms as $term) {
                        if (stripos($normalizedSubmitted, $term) !== false) {
                            $termsFound++;
                        } else {
                            $termsMissing[] = $term;
                        }
                    }
                    
                    if ($termsFound < $minimumTerms) {
                        $missingCriticalTerm = true;
                        $termsImportance = ($minimumTerms - $termsFound) / $minimumTerms;
                        $importanceFactor = max($importanceFactor, $termsImportance);
                    }
                }
                // For other concepts, check if mentioned but with insufficient details
                else {
                    // For regular concepts, just check if key critical terms are there
                    $keyTermIndex = 0;
                    $keyTermsMissing = false;
                    
                    // Consider only the first 2-3 critical terms for non-critical concepts
                    $keyTermCount = min(3, count($criticalTerms));
                    
                    for ($i = 0; $i < $keyTermCount; $i++) {
                        if (!empty($criticalTerms[$i]) && stripos($normalizedSubmitted, $criticalTerms[$i]) === false) {
                            $keyTermsMissing = true;
                            $termsMissing[] = $criticalTerms[$i];
                        }
                    }
                    
                    if ($keyTermsMissing) {
                        $missingCriticalTerm = true;
                        $termsImportance = 0.3; // Less critical than complete concepts
                        $importanceFactor = max($importanceFactor, $termsImportance);
                    }
                }
                
                // Special handling for abbreviated answers - check if terms might be present as abbreviations
                if ($missingCriticalTerm && $this->containsSignificantMedicalAbbreviations($normalizedSubmitted)) {
                    // Try to match abbreviations to the missing terms
                    $abbrevMatched = $this->checkForTermsAsAbbreviations($normalizedSubmitted, $termsMissing);
                    
                    if ($abbrevMatched) {
                        // If abbreviations match the missing terms, reduce importance
                        $importanceFactor = max(0, $importanceFactor - 0.2);
                        // If importance factor is now very low, don't consider it missing
                        if ($importanceFactor < 0.1) {
                            $missingCriticalTerm = false;
                        }
                    }
                }
            }
            
            // Additional check for location-specific critical terms
            // For example, protein synthesis must be associated with ribosomes, not mitochondria
            $locationCriticalConcepts = [
                'protein synthesis' => ['ribosome', 'endoplasmic reticulum'],
                'dna replication' => ['nucleus'],
                'tca cycle' => ['mitochondria', 'mitochondrial matrix'],
                'krebs cycle' => ['mitochondria', 'mitochondrial matrix'],
                'electron transport chain' => ['mitochondria', 'inner membrane'],
                'glycolysis' => ['cytoplasm', 'cytosol']
            ];
            
            foreach ($locationCriticalConcepts as $process => $locations) {
                if (stripos($normalizedSubmitted, $process) !== false) {
                    $locationFound = false;
                    
                    foreach ($locations as $location) {
                        if (stripos($normalizedSubmitted, $location) !== false) {
                            $locationFound = true;
                            break;
                        }
                    }
                    
                    // Also check for abbreviations of locations
                    if (!$locationFound && $this->containsSignificantMedicalAbbreviations($normalizedSubmitted)) {
                        foreach ($locations as $location) {
                            // Check for common abbreviations of locations
                            $locationAbbrevs = [
                                'endoplasmic reticulum' => ['er', 'rough er', 'rer'],
                                'mitochondria' => ['mito', 'mt'],
                                'mitochondrial matrix' => ['matrix', 'mt matrix'],
                                'cytoplasm' => ['cyto', 'cp'],
                                'ribosomes' => ['ribo']
                            ];
                            
                            if (isset($locationAbbrevs[$location])) {
                                foreach ($locationAbbrevs[$location] as $abbrev) {
                                    if (stripos($normalizedSubmitted, $abbrev) !== false) {
                                        $locationFound = true;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Wrong location check - penalize if incorrect locations are mentioned
                    $wrongLocations = [
                        'protein synthesis' => ['mitochondria', 'golgi', 'nucleus', 'lysosome'],
                        'dna replication' => ['cytoplasm', 'mitochondria', 'ribosome'],
                        'tca cycle' => ['cytoplasm', 'nucleus', 'er'],
                        'krebs cycle' => ['cytoplasm', 'nucleus', 'er'],
                        'electron transport chain' => ['cytoplasm', 'nucleus', 'er'],
                        'glycolysis' => ['mitochondria', 'nucleus', 'er']
                    ];
                    
                    if (isset($wrongLocations[$process])) {
                        foreach ($wrongLocations[$process] as $wrongLocation) {
                            if (stripos($normalizedSubmitted, $wrongLocation) !== false) {
                                // Severely penalize for specifically wrong locations
                                $missingCriticalTerm = true;
                                $importanceFactor = 0.9; // Very critical error
                                break;
                            }
                        }
                    }
                    
                    // If process is mentioned but no location is specified, that's a critical missing detail
                    if (!$locationFound && !$missingCriticalTerm) {
                        $missingCriticalTerm = true;
                        $importanceFactor = max($importanceFactor, 0.5); // Important but not as severe as wrong location
                    }
                }
            }
        }
        
        return [
            'missing' => $missingCriticalTerm,
            'importance' => $importanceFactor
        ];
    }
    
    /**
     * Calculate domain-specific context boost
     * Provides additional similarity points when answers are in the same medical domain
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Domain context boost (0.0 - 0.1)
     */
    protected function calculateDomainContextBoost(string $text1, string $text2): float
    {
        $cacheKey = "domain_boost:{$text1}:{$text2}";
        
        return $this->getCachedOrCompute($cacheKey, function() use ($text1, $text2) {
            $normalizedText1 = $this->textProcessor->normalizeText($text1);
            $normalizedText2 = $this->textProcessor->normalizeText($text2);
            
            $domainScores = [];
            
            // Check each medical domain
            foreach ($this->medicalDomains as $domain => $keywords) {
                $score1 = 0;
                $score2 = 0;
                
                // Count domain keywords in each text
                foreach ($keywords as $keyword) {
                    if (stripos($normalizedText1, $keyword) !== false) {
                        $score1++;
                    }
                    if (stripos($normalizedText2, $keyword) !== false) {
                        $score2++;
                    }
                }
                
                // If both texts have keywords from this domain
                if ($score1 > 0 && $score2 > 0) {
                    $domainScore = min($score1, $score2) / max(count($keywords) * 0.5, 1);
                    $domainScores[$domain] = min($domainScore * 0.05, 0.05); // Max 0.05 per domain
                }
            }
            
            // Sum all domain boosts, max 0.1 total
            return min(array_sum($domainScores), 0.1);
        });
    }
    
    /**
     * Generate detailed feedback based on similarity analysis
     * Enhanced with course-specific feedback, learning resources, and improvement tips
     *
     * @param string $submitted Submitted answer
     * @param string $correct Correct answer
     * @param array $metrics Similarity metrics
     * @param bool $isCorrect Whether the answer is correct
     * @param float $similarity Final similarity score
     * @param float $threshold Threshold for correctness
     * @param string|null $courseTopic Identified course topic
     * @return string Detailed feedback message
     */
    protected function generateDetailedFeedback(string $submitted, string $correct, array $metrics, bool $isCorrect, float $similarity, float $threshold, ?string $courseTopic = null): string
    {
        if ($isCorrect) {
            if ($similarity > 0.95) {
                return "Excellent! Your answer is very accurate and demonstrates a solid understanding of the medical concepts.";
            } elseif ($similarity > 0.85) {
                if ($courseTopic) {
                    switch ($courseTopic) {
                        case 'anatomy':
                            return "Good job! Your description of the anatomical structures is accurate. You've correctly identified the key anatomical relationships.";
                        case 'physiology':
                            return "Good job! Your explanation of the physiological process is correct. You've demonstrated understanding of the underlying mechanisms.";
                        case 'biochemistry':
                            return "Good job! Your understanding of the biochemical pathway is sound. You've correctly described the key reactions and processes.";
                        case 'histology':
                            return "Good job! Your description of the tissue characteristics is accurate. You've correctly identified the cellular components and organization.";
                        default:
                            return "Good job! Your answer contains all the key concepts correctly. You've demonstrated solid understanding of the material.";
                    }
                } else {
                    return "Good job! Your answer contains all the key concepts correctly. Your medical terminology usage is appropriate.";
                }
            } else {
                $tipsByDomain = [
                    'anatomy' => "For even more precision, consider including specific anatomical landmarks and dimensional relationships.",
                    'physiology' => "For a more complete answer, consider describing the regulatory mechanisms that control this process.",
                    'biochemistry' => "To enhance your answer, consider including the specific enzymes involved and their regulatory mechanisms.",
                    'histology' => "To improve further, consider mentioning the specific staining characteristics and ultrastructural features."
                ];
                
                $domainTip = $courseTopic && isset($tipsByDomain[$courseTopic]) ? " " . $tipsByDomain[$courseTopic] : "";
                
                return "Your answer is sufficiently correct, though there may be room for more precision in your terminology." . $domainTip;
            }
        } else {
            // Gather specific feedback based on metrics
            $feedbacks = [];
            
            // Check what might be missing
            $normalizedSubmitted = $this->textProcessor->normalizeText($submitted);
            $normalizedCorrect = $this->textProcessor->normalizeText($correct);
            
            $submittedWords = explode(' ', $this->textProcessor->removeFillerWords($normalizedSubmitted));
            $correctWords = explode(' ', $this->textProcessor->removeFillerWords($normalizedCorrect));
            
            $missingWords = array_diff($correctWords, $submittedWords);
            
            // Only mention missing keywords that are meaningful (longer than 3 chars)
            $missingKeywords = array_filter($missingWords, function($word) {
                return mb_strlen($word) > 3;
            });
            
            // Add specific feedback
            if (isset($metrics['levenshtein']) && $metrics['levenshtein'] < 0.4) {
                $feedbacks[] = "Your answer is structurally different from the expected answer. Try to be more specific in addressing the question.";
            }
            
            // Domain-specific feedback for low keyword overlap
            if (isset($metrics['keyword']) && $metrics['keyword'] < 0.5 && !empty($missingKeywords)) {
                $missingTermList = implode(', ', array_slice($missingKeywords, 0, 3)) . (count($missingKeywords) > 3 ? ', etc.' : '.');
                
                if ($courseTopic) {
                    switch ($courseTopic) {
                        case 'anatomy':
                            $feedbacks[] = "Your answer is missing key anatomical terms such as: $missingTermList Consider reviewing your anatomical terminology.";
                            break;
                        case 'physiology':
                            $feedbacks[] = "Your answer is missing key physiological concepts such as: $missingTermList Review the functional relationships between these components.";
                            break;
                        case 'biochemistry':
                            $feedbacks[] = "Your answer is missing key biochemical elements such as: $missingTermList Consider reviewing the pathway components and their interactions.";
                            break;
                        case 'histology':
                            $feedbacks[] = "Your answer is missing key histological features such as: $missingTermList Review the characteristic tissue structures and cell types.";
                            break;
                        default:
                            $feedbacks[] = "Your answer is missing key concepts such as: $missingTermList";
                    }
                } else {
                    $feedbacks[] = "Your answer is missing key concepts such as: $missingTermList";
                }
            }
            
            if (isset($metrics['conceptual']) && $metrics['conceptual'] < 0.4) {
                if ($courseTopic) {
                    switch ($courseTopic) {
                        case 'anatomy':
                            $feedbacks[] = "Your answer doesn't sufficiently address the core anatomical structures and relationships. Focus on spatial relationships and structural organization.";
                            break;
                        case 'physiology':
                            $feedbacks[] = "Your answer doesn't sufficiently explain the physiological mechanisms involved. Focus on functional relationships and regulatory processes.";
                            break;
                        case 'biochemistry':
                            $feedbacks[] = "Your answer doesn't sufficiently describe the biochemical pathways or reactions. Focus on sequential steps and molecular transformations.";
                            break;
                        case 'histology':
                            $feedbacks[] = "Your answer doesn't sufficiently characterize the tissue types or cellular components. Focus on structural organization and functional specialization.";
                            break;
                        default:
                            $feedbacks[] = "Your answer doesn't sufficiently address the core medical concepts. Try to be more specific about the mechanisms and relationships.";
                    }
                } else {
                    $feedbacks[] = "Your answer doesn't sufficiently address the core medical concepts. Try to be more specific and comprehensive.";
                }
            }
            
            if (isset($metrics['hasContradictions']) && $metrics['hasContradictions']) {
                if ($courseTopic) {
                    switch ($courseTopic) {
                        case 'anatomy':
                            $feedbacks[] = "Your answer contains contradictory statements about anatomical structures or positions. Review the correct spatial relationships between structures.";
                            break;
                        case 'physiology':
                            $feedbacks[] = "Your answer contains contradictory statements about physiological processes or effects. Review the correct functional relationships and mechanisms.";
                            break;
                        case 'biochemistry':
                            $feedbacks[] = "Your answer contains contradictory statements about biochemical pathways or reactions. Review the correct sequence and directionality of reactions.";
                            break;
                        case 'histology':
                            $feedbacks[] = "Your answer contains contradictory statements about tissue characteristics or cell types. Review the correct cellular composition and organization.";
                            break;
                        default:
                            $feedbacks[] = "Your answer contains contradictory medical statements that need correction. Review the material to resolve these inconsistencies.";
                    }
                } else {
                    $feedbacks[] = "Your answer contains contradictory medical statements that need correction. Review the core concepts to ensure consistency.";
                }
            }
            
            if (isset($metrics['pattern']) && isset($metrics['pattern']['pattern_type'])) {
                $patternType = $metrics['pattern']['pattern_type'];
                
                // Provide pattern-specific guidance
                switch ($patternType) {
                    case 'parasympathetic_effects':
                        $feedbacks[] = "Your answer should discuss parasympathetic effects on heart rate and digestion. Remember that parasympathetic stimulation typically decreases heart rate and increases digestive activity.";
                        break;
                    case 'sympathetic_effects':
                        $feedbacks[] = "Your answer should discuss sympathetic effects on heart rate, blood pressure, and other target organs. Remember that sympathetic stimulation typically increases heart rate and blood pressure while decreasing digestive activity.";
                        break;
                    case 'action_potential':
                        $feedbacks[] = "Your answer should explain the sequence of depolarization and repolarization in action potentials. Review the ionic basis of membrane potential changes and the role of voltage-gated channels.";
                        break;
                    case 'cardiac_cycle':
                        $feedbacks[] = "Your answer should describe the events of systole and diastole in the cardiac cycle. Focus on the timing of chamber contraction, valve movements, and pressure changes.";
                        break;
                    case 'glycolysis':
                        $feedbacks[] = "Your answer should describe the steps and energy yield of glycolysis. Review the conversion of glucose to pyruvate and the generation of ATP and NADH.";
                        break;
                    case 'tca_cycle':
                        $feedbacks[] = "Your answer should explain the key reactions and products of the TCA cycle. Focus on the oxidative decarboxylation steps and the production of reduced electron carriers.";
                        break;
                    case 'epithelial_tissue':
                        $feedbacks[] = "Your answer should describe the characteristics and classifications of epithelial tissue. Review the different types based on cell shape and layering.";
                        break;
                    case 'anatomical_position':
                        $feedbacks[] = "Your answer should correctly use anatomical position terms (anterior, posterior, etc.). Remember that these terms are used relative to the standard anatomical position.";
                        break;
                }
            }
            
            // Add learning resource suggestions based on course topic
            $resourceSuggestion = '';
            if ($courseTopic) {
                switch ($courseTopic) {
                    case 'anatomy':
                        $resourceSuggestion = " Consider consulting an anatomical atlas or 3D models to visualize these relationships.";
                        break;
                    case 'physiology':
                        $resourceSuggestion = " Review flowcharts and diagrams that illustrate the regulatory pathways involved.";
                        break;
                    case 'biochemistry':
                        $resourceSuggestion = " Consider creating pathway diagrams to help visualize the sequential reactions and regulatory points.";
                        break;
                    case 'histology':
                        $resourceSuggestion = " Review labeled micrographs to better recognize the cellular and tissue organization.";
                        break;
                }
            }
            
            // If specific feedback is available, use it; otherwise, give a general message
            if (!empty($feedbacks)) {
                $domainPrefix = "";
                if ($courseTopic) {
                    $domainPrefix = ucfirst($courseTopic) . ": ";
                }
                return $domainPrefix . implode(' ', $feedbacks) . $resourceSuggestion . " Please review and try again.";
            } else {
                if ($courseTopic) {
                    switch ($courseTopic) {
                        case 'anatomy':
                            return "Your anatomical description differs from the expected answer. Review the anatomical structures and their relationships. Compare your answer with anatomical diagrams or models to identify the discrepancies.";
                        case 'physiology':
                            return "Your physiological explanation differs from the expected answer. Review the mechanisms and processes involved. Try creating a flowchart to visualize the sequence of events and regulatory mechanisms.";
                        case 'biochemistry':
                            return "Your biochemical explanation differs from the expected answer. Review the pathways and reactions involved. Consider drawing out the pathway with enzymes and intermediates to strengthen your understanding.";
                        case 'histology':
                            return "Your histological description differs from the expected answer. Review the tissue characteristics and cellular components. Studying labeled micrographs can help improve your understanding of tissue organization.";
                        default:
                            return "Your answer differs significantly from the expected answer. Please review the material and try again. Consider creating visual summaries of key concepts to reinforce your understanding.";
                    }
                } else {
                    return "Your answer differs significantly from the expected answer. Please review the material and try again. Focus on understanding the underlying concepts rather than memorizing specific phrases.";
                }
            }
        }
    }
    
    /**
     * Check if the answer contains sympathetic effects
     * 
     * @param string $submittedAnswer The submitted answer
     * @param string $correctAnswer The correct answer
     * @return bool Whether sympathetic effects are mentioned
     */
    protected function isSympatheticEffectsMatch(string $submittedAnswer, string $correctAnswer): bool
    {
        return $this->patternMatcher->matchesPattern('sympathetic_effects', $submittedAnswer) && 
               $this->patternMatcher->matchesPattern('sympathetic_effects', $correctAnswer);
    }
    
    /**
     * Check if the answer contains parasympathetic effects
     * 
     * @param string $submittedAnswer The submitted answer
     * @param string $correctAnswer The correct answer
     * @return bool Whether parasympathetic effects are mentioned
     */
    protected function isParasympatheticEffectsMatch(string $submittedAnswer, string $correctAnswer): bool
    {
        return $this->patternMatcher->matchesPattern('parasympathetic_effects', $submittedAnswer) && 
               $this->patternMatcher->matchesPattern('parasympathetic_effects', $correctAnswer);
    }
    
    /**
     * Evaluate an answer for a specific question model
     * 
     * @param Question $question The question model
     * @param string $submittedAnswer The answer submitted by the user
     * @return array Evaluation results
     */
    public function evaluateQuestionAnswer(Question $question, string $submittedAnswer): array
    {
        // Get threshold from the question if set, otherwise use default
        $threshold = $question->similarity_threshold ?? self::DEFAULT_SIMILARITY_THRESHOLD;
        
        // Get alternative answers or use empty array if null
        $alternativeAnswers = $question->alternative_answers ?? [];
        
        return $this->evaluateAnswer(
            $submittedAnswer,
            $question->correct_answer,
            $alternativeAnswers,
            $threshold
        );
    }
    
    /**
     * Simple interface that returns just a boolean result
     * (for compatibility with existing isAnswerCorrect logic)
     * 
     * @param Question $question The question model
     * @param string $submittedAnswer The answer submitted by the user
     * @return bool True if the answer is correct, false otherwise
     */
    public function isCorrect(Question $question, string $submittedAnswer): bool
    {
        $result = $this->evaluateQuestionAnswer($question, $submittedAnswer);
        return $result['isCorrect'];
    }
    
    /**
     * Check if the question appears to be pattern-critical
     * This is a quick check to identify questions that require specific pattern matching
     * 
     * @param string $correctAnswer The correct answer text
     * @return bool Whether the question is likely pattern-critical
     */
    private function isPatternCriticalQuestion(string $correctAnswer): bool
    {
        $patternKeywords = [
            'parasympathetic', 'sympathetic', 'action potential', 'cardiac cycle', 
            'glomerular filtration', 'tca cycle', 'krebs cycle', 'epithelium',
            'anatomical position', 'glycolysis', 'electron transport'
        ];
        
        foreach ($patternKeywords as $keyword) {
            if (stripos($correctAnswer, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Determines if the answer contains significant medical abbreviations
     * This is different from just containing any abbreviations - it checks for
     * a pattern of heavy abbreviation usage typical in medical answers
     * 
     * @param string $answer The answer to check
     * @return bool True if the answer contains significant medical abbreviations
     */
    private function containsSignificantMedicalAbbreviations(string $answer): bool
    {
        // Common medical abbreviation patterns
        $patterns = [
            // Check for multiple 2-3 letter uppercase abbreviations
            '/\b[A-Z]{2,3}\b.*\b[A-Z]{2,3}\b/',
            
            // Check for common medical abbreviation patterns
            '/\b(SNS|PNS|ANS|CNS|GI|CV|BP|HR|CO|O2|CO2)\b/i',
            
            // Check for common lab value patterns
            '/\b[A-Z]{1,3}[+\-]?\b/',
            
            // Check for a high ratio of uppercase to lowercase words (abbreviation heavy)
            '/\b[A-Z]{2,}\b.*\b[A-Z]{2,}\b.*\b[A-Z]{2,}\b/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $answer)) {
                return true;
            }
        }
        
        // Count uppercase abbreviations vs total words to check density
        preg_match_all('/\b[A-Z]{2,}\b/', $answer, $abbreviationMatches);
        $abbreviationCount = count($abbreviationMatches[0] ?? []);
        
        $wordCount = str_word_count($answer);
        if ($wordCount > 0 && ($abbreviationCount / $wordCount) > 0.15) {
            // If more than 15% of the words are abbreviations, consider it significant
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate Levenshtein similarity between normalized texts
     * 
     * @param string $normalizedSubmitted Normalized submitted answer
     * @param string $normalizedCorrect Normalized correct answer
     * @return float Similarity score from 0 to 1
     */
    private function calculateLevenshteinSimilarity(string $normalizedSubmitted, string $normalizedCorrect): float
    {
        return $this->textProcessor->getLevenshteinSimilarity($normalizedSubmitted, $normalizedCorrect);
    }
    
    /**
     * Calculate Jaccard similarity between normalized texts
     * 
     * @param string $normalizedSubmitted Normalized submitted answer
     * @param string $normalizedCorrect Normalized correct answer
     * @return float Similarity score from 0 to 1
     */
    private function calculateJaccardSimilarity(string $normalizedSubmitted, string $normalizedCorrect): float
    {
        return $this->textProcessor->getJaccardSimilarity($normalizedSubmitted, $normalizedCorrect);
    }
    
    /**
     * Calculate keyword similarity between normalized texts
     * 
     * @param string $normalizedSubmitted Normalized submitted answer
     * @param string $normalizedCorrect Normalized correct answer
     * @return float Similarity score from 0 to 1
     */
    private function calculateKeywordSimilarity(string $normalizedSubmitted, string $normalizedCorrect): float
    {
        return $this->textProcessor->getKeywordOverlapRatio($normalizedSubmitted, $normalizedCorrect);
    }
    
    /**
     * Calculate pattern similarity between normalized texts
     * 
     * @param string $normalizedSubmitted Normalized submitted answer
     * @param string $normalizedCorrect Normalized correct answer
     * @return array Pattern similarity metrics
     */
    private function calculatePatternSimilarity(string $normalizedSubmitted, string $normalizedCorrect): array
    {
        return $this->getPatternMatches($normalizedSubmitted, $normalizedCorrect);
    }
    
    /**
     * Calculate conceptual similarity between normalized texts
     * 
     * @param string $normalizedSubmitted Normalized submitted answer
     * @param string $normalizedCorrect Normalized correct answer
     * @return float Similarity score from 0 to 1
     */
    private function calculateConceptualSimilarity(string $normalizedSubmitted, string $normalizedCorrect): float
    {
        return $this->textProcessor->getConceptualSimilarity($normalizedSubmitted, $normalizedCorrect);
    }
}