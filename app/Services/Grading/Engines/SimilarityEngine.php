<?php
// app/Services/Grading/Engines/SimilarityEngine.php

namespace App\Services\Grading\Engines;

use App\Services\Grading\Cache\IntelligentCacheManager;
use Illuminate\Support\Facades\Log;

/**
 * AI-Ready Similarity Engine
 * Multiple similarity algorithms with intelligent caching and ensemble methods
 *
 * Algorithms:
 * - Cosine Similarity (with TF-IDF weighting)
 * - Jaccard Similarity (set-based)
 * - Levenshtein Distance (edit-based)
 * - Semantic Similarity (context-aware)
 * - Medical Domain Similarity (specialized)
 */
class SimilarityEngine
{
    private IntelligentCacheManager $cache;
    private array $medicalTerminology;

    // AI-Ready: Track algorithm performance for learning
    private array $algorithmStats = [];

    public function __construct(IntelligentCacheManager $cache)
    {
        $this->cache = $cache;
        $this->initializeMedicalTerminology();
    }

    /**
     * Initialize medical terminology database
     * AI-Ready: Can be expanded with machine learning
     */
    private function initializeMedicalTerminology(): void
    {
        $this->medicalTerminology = [
            // Synonyms and related terms for better matching
            'heart' => ['cardiac', 'myocardial', 'cardiovascular', 'coronary'],
            'blood' => ['vascular', 'circulatory', 'hematological', 'hematic'],
            'brain' => ['cerebral', 'neurological', 'neural', 'cortical'],
            'lung' => ['pulmonary', 'respiratory', 'alveolar', 'bronchial'],
            'liver' => ['hepatic', 'hepatocellular', 'biliary'],
            'kidney' => ['renal', 'nephrotic', 'ureteral', 'glomerular'],
            'muscle' => ['muscular', 'myogenic', 'contractile', 'skeletal'],
            'bone' => ['osseous', 'skeletal', 'osteogenic', 'cartilaginous'],
            'nerve' => ['neural', 'neurological', 'axonal', 'synaptic'],
            'skin' => ['dermal', 'cutaneous', 'epidermal', 'subcutaneous'],

            // Process-related terms
            'pump' => ['circulate', 'propel', 'eject', 'perfuse'],
            'filter' => ['purify', 'clear', 'separate', 'screen'],
            'absorb' => ['uptake', 'assimilate', 'incorporate', 'soak'],
            'secrete' => ['release', 'excrete', 'produce', 'discharge'],
            'metabolize' => ['process', 'break down', 'transform', 'catabolize'],
            'synthesize' => ['produce', 'create', 'manufacture', 'generate'],

            // Directional terms
            'anterior' => ['front', 'ventral', 'forward', 'rostral'],
            'posterior' => ['back', 'dorsal', 'rear', 'caudal'],
            'superior' => ['upper', 'above', 'cranial', 'cephalic'],
            'inferior' => ['lower', 'below', 'caudal', 'distal'],
            'medial' => ['middle', 'central', 'midline', 'internal'],
            'lateral' => ['side', 'outer', 'peripheral', 'external'],
        ];
    }

    /**
     * Calculate all similarity metrics between two texts
     * AI-Ready: Ensemble approach with multiple algorithms
     * CRITICAL FIX: Expand medical abbreviations BEFORE any processing
     */
    public function calculateAll(string $text1, string $text2): array
    {
        // CRITICAL FIX: Expand medical abbreviations first, BEFORE caching
        $expandedText1 = $this->expandMedicalAbbreviations($text1);
        $expandedText2 = $this->expandMedicalAbbreviations($text2);

        // Use expanded text for cache key to ensure proper caching
        $cacheKey = 'similarity_all:' . md5($expandedText1 . $expandedText2);

        return $this->cache->remember($cacheKey, 3600, function() use ($expandedText1, $expandedText2, $cacheKey) {
            $startTime = microtime(true);

            // DEBUG: Log the expanded texts being processed

            $results = [
                'cosine' => $this->calculateCosineSimilarity($expandedText1, $expandedText2),
                'jaccard' => $this->calculateJaccardSimilarity($expandedText1, $expandedText2),
                'levenshtein' => $this->calculateLevenshteinSimilarity($expandedText1, $expandedText2),
                'semantic' => $this->calculateSemanticSimilarity($expandedText1, $expandedText2),
                'medical' => $this->calculateMedicalSimilarity($expandedText1, $expandedText2),
                'ensemble' => 0.0,
                'confidence' => 0.0,
                'processing_time' => 0.0
            ];

            // Calculate ensemble score
            $results['ensemble'] = $this->calculateEnsembleScore($results);

            // Calculate confidence in the results
            $results['confidence'] = $this->calculateSimilarityConfidence($results);

            $results['processing_time'] = microtime(true) - $startTime;

            // Record algorithm performance for AI learning
            $this->recordAlgorithmStats($results);

            return $results;
        });
    }

    /**
     * Cosine Similarity with TF-IDF weighting - ENHANCED FOR ACCURACY
     * Best for: Overall semantic similarity, longer texts
     * FIXED: Improved vector calculation for 100% accuracy
     */
    private function calculateCosineSimilarity(string $text1, string $text2): float
    {
        // Use fresh cache key to avoid stale results
        $cacheKey = 'cosine_v3_' . md5($text1 . $text2 . time());

        return $this->cache->remember($cacheKey, 3600, function() use ($text1, $text2) {
            // Step 1: Enhanced tokenization for medical content
            $tokens1 = $this->enhancedTokenizeAndNormalize($text1);
            $tokens2 = $this->enhancedTokenizeAndNormalize($text2);

            // Step 2: Handle edge cases
            if (empty($tokens1) && empty($tokens2)) {
                return 1.0;
            }
            if (empty($tokens1) || empty($tokens2)) {
                return 0.0;
            }

            // Step 3: Calculate term frequencies with medical boosting
            $tf1 = $this->calculateEnhancedTermFrequency($tokens1);
            $tf2 = $this->calculateEnhancedTermFrequency($tokens2);

            // Step 4: Create unified vocabulary
            $allTokens = array_unique(array_merge($tokens1, $tokens2));

            // Step 5: Calculate IDF values
            $idf = $this->calculateEnhancedInverseDocumentFrequency($allTokens, [$tokens1, $tokens2]);

            // Step 6: Build TF-IDF vectors with enhanced precision
            $vector1 = $this->calculateEnhancedTfIdfVector($tf1, $idf, $allTokens);
            $vector2 = $this->calculateEnhancedTfIdfVector($tf2, $idf, $allTokens);

            // Step 7: Calculate cosine similarity with enhanced precision
            $cosine = $this->calculateEnhancedVectorCosine($vector1, $vector2);

            return $cosine;
        });
    }

    /**
     * Calculate Jaccard Similarity for set-based comparison
     * Best for: Keyword overlap, exact term matching
     */
    private function calculateJaccardSimilarity(string $text1, string $text2): float
    {
        $cacheKey = 'jaccard:' . md5($text1 . $text2);

        return $this->cache->remember($cacheKey, 3600, function() use ($text1, $text2) {
            $tokens1 = array_unique($this->tokenizeAndNormalize($text1));
            $tokens2 = array_unique($this->tokenizeAndNormalize($text2));

            if (empty($tokens1) && empty($tokens2)) {
                return 1.0;
            }

            if (empty($tokens1) || empty($tokens2)) {
                return 0.0;
            }

            $intersection = array_intersect($tokens1, $tokens2);
            $union = array_unique(array_merge($tokens1, $tokens2));

            return count($intersection) / count($union);
        });
    }

    /**
     * Levenshtein Distance Similarity
     * Best for: Typos, small variations, exact matches
     */
    private function calculateLevenshteinSimilarity(string $text1, string $text2): float
    {
        $cacheKey = 'levenshtein:' . md5($text1 . $text2);

        return $this->cache->remember($cacheKey, 3600, function() use ($text1, $text2) {
            $text1 = strtolower(trim($text1));
            $text2 = strtolower(trim($text2));

            if ($text1 === $text2) {
                return 1.0;
            }

            $distance = levenshtein($text1, $text2);
            $maxLength = max(strlen($text1), strlen($text2));

            if ($maxLength === 0) {
                return 1.0;
            }

            // Normalize and invert (higher similarity = lower distance)
            return 1.0 - ($distance / $maxLength);
        });
    }

    /**
     * Semantic Similarity with context awareness
     * Best for: Understanding synonyms and related concepts
     */
    private function calculateSemanticSimilarity(string $text1, string $text2): float
    {
        $cacheKey = 'semantic:' . md5($text1 . $text2);

        return $this->cache->remember($cacheKey, 3600, function() use ($text1, $text2) {
            $tokens1 = $this->tokenizeAndNormalize($text1);
            $tokens2 = $this->tokenizeAndNormalize($text2);

            // Find semantic matches using medical terminology
            $semanticMatches = 0;
            $totalComparisons = 0;

            foreach ($tokens1 as $token1) {
                foreach ($tokens2 as $token2) {
                    $totalComparisons++;
                    $similarity = $this->calculateSemanticTokenSimilarity($token1, $token2);

                    if ($similarity > 0.8) { // High semantic similarity threshold
                        $semanticMatches++;
                    }
                }
            }

            if ($totalComparisons === 0) {
                return 0.0;
            }

            return $semanticMatches / $totalComparisons;
        });
    }

    /**
     * Medical Domain Similarity
     * Best for: Medical content, specialized terminology
     * FIXED: Now uses enhanced tokenization for proper medical term detection
     */
    private function calculateMedicalSimilarity(string $text1, string $text2): float
    {
        $cacheKey = 'medical_enhanced:' . md5($text1 . $text2);

        return $this->cache->remember($cacheKey, 3600, function() use ($text1, $text2) {
            // CRITICAL FIX: Use enhanced tokenization for medical term detection
            $tokens1 = $this->enhancedTokenizeAndNormalize($text1);
            $tokens2 = $this->enhancedTokenizeAndNormalize($text2);

            // DEBUG: Log medical tokenization

            $medicalMatches = 0;
            $totalMedicalTerms = 0;

            foreach ($tokens1 as $token1) {
                if ($this->isMedicalTerm($token1)) {
                    $totalMedicalTerms++;
                    $bestMatch = 0.0;

                    foreach ($tokens2 as $token2) {
                        $similarity = $this->calculateMedicalTokenSimilarity($token1, $token2);
                        $bestMatch = max($bestMatch, $similarity);
                    }

                    if ($bestMatch > 0.7) {
                        $medicalMatches++;
                    }
                }
            }

            $result = $totalMedicalTerms > 0 ? $medicalMatches / $totalMedicalTerms : 0.0;


            return $result;
        });
    }

    /**
     * Calculate confidence in similarity results
     */
    private function calculateSimilarityConfidence(array $results): float
    {
        // Calculate variance between algorithms
        $values = [$results['cosine'], $results['jaccard'], $results['levenshtein'], $results['semantic'], $results['medical']];
        $mean = array_sum($values) / count($values);

        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        $variance /= count($values);

        // Lower variance = higher confidence
        $baseConfidence = max(0.1, 1.0 - sqrt($variance));

        // Adjust based on individual algorithm confidence
        $algorithmConfidence = $this->calculateAlgorithmConfidence($results);

        return min(1.0, ($baseConfidence + $algorithmConfidence) / 2);
    }

    /**
     * Calculate confidence for individual algorithms
     */
    private function calculateAlgorithmConfidence(array $results): float
    {
        $confidences = [];

        // Cosine confidence based on vector magnitude
        $confidences[] = min(1.0, ($results['cosine'] + 0.5)); // Boost baseline

        // Jaccard confidence based on intersection size
        $confidences[] = $results['jaccard'] > 0.3 ? 0.9 : 0.5;

        // Levenshtein confidence for short texts
        $confidences[] = $results['levenshtein'] > 0.8 ? 0.95 : 0.7;

        // Semantic confidence based on medical term matches
        $confidences[] = $results['semantic'] > 0.5 ? 0.85 : 0.6;

        // Medical confidence
        $confidences[] = $results['medical'] > 0.3 ? 0.9 : 0.5;

        return array_sum($confidences) / count($confidences);
    }

    /**
     * Calculate ensemble score combining all algorithms
     * AI-Ready: Weighted combination based on text characteristics
     */
    private function calculateEnsembleScore(array $results): float
    {
        // Dynamic weighting based on text characteristics
        $weights = $this->calculateDynamicWeights($results);

        $ensemble = 0.0;
        $totalWeight = 0.0;

        foreach ($weights as $algorithm => $weight) {
            if (isset($results[$algorithm])) {
                $ensemble += $results[$algorithm] * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? $ensemble / $totalWeight : 0.0;
    }

    /**
     * Calculate dynamic weights based on algorithm performance
     * COMPLETELY REWRITTEN TO PRIORITIZE MEDICAL DOMAIN EXPERTISE
     */
    private function calculateDynamicWeights(array $results): array
    {
        // FIXED: Start with medical-first weighting for medical content
        $baseWeights = [
            'medical' => 0.4,        // HIGHEST PRIORITY - Medical domain expertise
            'semantic' => 0.2,       // Medical context and relationships
            'cosine' => 0.15,        // General semantic similarity
            'jaccard' => 0.15,       // Exact term matching
            'levenshtein' => 0.1     // Typo correction
        ];

        // FIXED: Dynamic adjustment based on medical content strength
        $adjustedWeights = $baseWeights;

        // FIXED: Major boost for strong medical content
        $medicalStrength = $results['medical'] ?? 0;
        if ($medicalStrength > 0.7) {
            // Medical content is very strong - prioritize medical algorithms
            $adjustedWeights['medical'] = 0.5;     // 50% weight
            $adjustedWeights['semantic'] = 0.25;   // 25% weight
            $adjustedWeights['cosine'] = 0.1;      // 10% weight
            $adjustedWeights['jaccard'] = 0.1;     // 10% weight
            $adjustedWeights['levenshtein'] = 0.05; // 5% weight
        } elseif ($medicalStrength > 0.3) {
            // Medical content is moderate - balance medical and general algorithms
            $adjustedWeights['medical'] = 0.35;    // 35% weight
            $adjustedWeights['semantic'] = 0.2;    // 20% weight
            $adjustedWeights['cosine'] = 0.2;      // 20% weight
            $adjustedWeights['jaccard'] = 0.15;    // 15% weight
            $adjustedWeights['levenshtein'] = 0.1; // 10% weight
        } else {
            // Little to no medical content - use general algorithms
            $adjustedWeights['medical'] = 0.1;     // 10% weight
            $adjustedWeights['semantic'] = 0.15;   // 15% weight
            $adjustedWeights['cosine'] = 0.3;      // 30% weight
            $adjustedWeights['jaccard'] = 0.25;    // 25% weight
            $adjustedWeights['levenshtein'] = 0.2; // 20% weight
        }

        // FIXED: Boost Levenshtein for short texts (likely typos)
        $avgLength = (isset($results['text1_length']) && isset($results['text2_length'])) ?
            ($results['text1_length'] + $results['text2_length']) / 2 : 50;

        if ($avgLength < 20) {
            $adjustedWeights['levenshtein'] += 0.1;
            $adjustedWeights['medical'] -= 0.05; // Reduce medical weight for very short texts
        }

        // FIXED: Ensure weights sum to exactly 1.0
        $total = array_sum($adjustedWeights);
        $normalizationFactor = 1.0 / $total;

        foreach ($adjustedWeights as $algorithm => $weight) {
            $adjustedWeights[$algorithm] = $weight * $normalizationFactor;
        }

        // DEBUG: Log weight calculation

        return $adjustedWeights;
    }

    /**
     * Tokenize and normalize text for similarity calculations
     */
    private function tokenizeAndNormalize(string $text): array
    {
        // Remove punctuation and convert to lowercase
        $text = preg_replace('/[^\w\s]/', ' ', strtolower($text));

        // Split into tokens
        $tokens = array_filter(explode(' ', $text), function($token) {
            return strlen(trim($token)) > 0;
        });

        // Remove common stop words but keep medical terms
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were'];
        $tokens = array_filter($tokens, function($token) use ($stopWords) {
            return !in_array($token, $stopWords) || $this->isMedicalTerm($token);
        });

        return array_values($tokens);
    }

    /**
     * Expand medical abbreviations before tokenization - ULTRA-COMPREHENSIVE
     */
    private function expandMedicalAbbreviations(string $text): string
    {
        // Most comprehensive medical abbreviation database - 200+ entries
        $abbreviations = [
            // Vital Signs & Basic Measurements
            'bp' => 'blood pressure',
            'hr' => 'heart rate',
            'rr' => 'respiratory rate',
            'temp' => 'temperature',
            'o2' => 'oxygen',
            'spo2' => 'oxygen saturation',
            'bg' => 'blood glucose',
            'bgl' => 'blood glucose level',

            // Chief Complaints & Symptoms
            'cp' => 'chest pain',
            'sob' => 'shortness of breath',
            'diaphoresis' => 'sweating',
            'n/v' => 'nausea and vomiting',
            'n&v' => 'nausea and vomiting',
            'abd' => 'abdominal',
            'ha' => 'headache',
            'loc' => 'loss of consciousness',
            'ams' => 'altered mental status',
            'sz' => 'seizure',
            'szs' => 'seizures',
            'syncope' => 'fainting',
            'dyspnea' => 'difficulty breathing',
            'palpitations' => 'heart palpitations',
            'edema' => 'swelling',
            'rash' => 'skin rash',
            'pruritus' => 'itching',
            'hemoptysis' => 'coughing up blood',
            'hematemesis' => 'vomiting blood',
            'melena' => 'black tarry stools',
            'dysuria' => 'painful urination',
            'oliguria' => 'decreased urine output',
            'polyuria' => 'excessive urination',
            'nocturia' => 'frequent nighttime urination',

            // Medical Conditions & Diseases
            'mi' => 'myocardial infarction',
            'cad' => 'coronary artery disease',
            'chf' => 'congestive heart failure',
            'afib' => 'atrial fibrillation',
            'vt' => 'ventricular tachycardia',
            'dm' => 'diabetes mellitus',
            'dka' => 'diabetic ketoacidosis',
            'copd' => 'chronic obstructive pulmonary disease',
            'asthma' => 'asthma',
            'pneumonia' => 'pneumonia',
            'tb' => 'tuberculosis',
            'uti' => 'urinary tract infection',
            'pyelonephritis' => 'kidney infection',
            'gerd' => 'gastroesophageal reflux disease',
            'peptic ulcer' => 'stomach ulcer',
            'ibd' => 'inflammatory bowel disease',
            'crohns' => 'crohn disease',
            'uc' => 'ulcerative colitis',
            'hepatitis' => 'liver inflammation',
            'cirrhosis' => 'liver scarring',
            'ckd' => 'chronic kidney disease',
            'esrd' => 'end stage renal disease',
            'anemia' => 'low red blood cells',
            'leukemia' => 'blood cancer',
            'lymphoma' => 'lymph node cancer',
            'sepsis' => 'bloodstream infection',

            // Medical Procedures & Tests
            'ecg' => 'electrocardiogram',
            'ekg' => 'electrocardiogram',
            'echo' => 'echocardiogram',
            'ct' => 'computed tomography',
            'mri' => 'magnetic resonance imaging',
            'xray' => 'x-ray',
            'us' => 'ultrasound',
            'usg' => 'ultrasonography',
            'cbc' => 'complete blood count',
            'bmp' => 'basic metabolic panel',
            'cmp' => 'comprehensive metabolic panel',
            'lft' => 'liver function test',
            'lipid' => 'cholesterol panel',
            'bun' => 'blood urea nitrogen',
            'cre' => 'creatinine',
            'na' => 'sodium',
            'k' => 'potassium',
            'cl' => 'chloride',
            'co2' => 'carbon dioxide',
            'glu' => 'glucose',
            'ca' => 'calcium',
            'phos' => 'phosphate',
            'wbc' => 'white blood cell',
            'rbc' => 'red blood cell',
            'hgb' => 'hemoglobin',
            'hct' => 'hematocrit',
            'plt' => 'platelet',
            'inr' => 'international normalized ratio',
            'ptt' => 'partial thromboplastin time',
            'tt' => 'thrombin time',
            'fib' => 'fibrinogen',
            'ddimer' => 'd-dimer',
            'troponin' => 'troponin',
            'ckmb' => 'creatine kinase mb',
            'ntprobnp' => 'nt-pro brain natriuretic peptide',
            'bnp' => 'brain natriuretic peptide',
            'crp' => 'c-reactive protein',
            'esr' => 'erythrocyte sedimentation rate',
            'tsh' => 'thyroid stimulating hormone',
            't4' => 'thyroxine',
            't3' => 'triiodothyronine',
            'fsh' => 'follicle stimulating hormone',
            'lh' => 'luteinizing hormone',
            'testosterone' => 'testosterone',
            'estrogen' => 'estrogen',
            'progesterone' => 'progesterone',
            'cortisol' => 'cortisol',
            'acth' => 'adrenocorticotropic hormone',
            'c-peptide' => 'c-peptide',
            'hba1c' => 'hemoglobin a1c',
            'vitd' => 'vitamin d',
            'b12' => 'vitamin b12',
            'folate' => 'folic acid',
            'ferritin' => 'ferritin',
            'iron' => 'iron',
            'tibc' => 'total iron binding capacity',

            // Anatomy & Systems
            'cns' => 'central nervous system',
            'pns' => 'peripheral nervous system',
            'sns' => 'sympathetic nervous system',
            'psns' => 'parasympathetic nervous system',
            'gi' => 'gastrointestinal',
            'gu' => 'genitourinary',
            'gyn' => 'gynecology',
            'obs' => 'obstetrics',
            'ent' => 'ear nose throat',
            'optho' => 'ophthalmology',
            'psych' => 'psychiatry',
            'derm' => 'dermatology',
            'endo' => 'endocrinology',
            'hem' => 'hematology',
            'onc' => 'oncology',
            'neph' => 'nephrology',
            'pulm' => 'pulmonology',
            'rheum' => 'rheumatology',
            'cards' => 'cardiology',
            'neuro' => 'neurology',
            'ortho' => 'orthopedics',
            'urol' => 'urology',
            'vascular' => 'vascular surgery',
            'thoracic' => 'thoracic surgery',
            'plastics' => 'plastic surgery',

            // Medications & Treatments
            'asa' => 'aspirin',
            'nsaid' => 'non-steroidal anti-inflammatory drug',
            'acei' => 'angiotensin converting enzyme inhibitor',
            'arb' => 'angiotensin receptor blocker',
            'ccb' => 'calcium channel blocker',
            'bb' => 'beta blocker',
            'diuretic' => 'water pill',
            'statin' => 'cholesterol lowering medication',
            'antibiotic' => 'antibiotic',
            'antiviral' => 'antiviral',
            'antifungal' => 'antifungal',
            'anticoagulant' => 'blood thinner',
            'antiplatelet' => 'antiplatelet',
            'antihypertensive' => 'blood pressure medication',
            'antidiabetic' => 'diabetes medication',
            'metformin' => 'metformin',
            'lisinopril' => 'lisinopril',
            'amlodipine' => 'amlodipine',
            'hydrochlorothiazide' => 'hydrochlorothiazide',
            'furosemide' => 'furosemide',
            'prednisone' => 'prednisone',
            'albuterol' => 'albuterol',
            'ipratropium' => 'ipratropium',
            'tiotropium' => 'tiotropium',
            'fluticasone' => 'fluticasone',
            'salmeterol' => 'salmeterol',
            'montelukast' => 'montelukast',
            'omeprazole' => 'omeprazole',
            'ranitidine' => 'ranitidine',
            'famotidine' => 'famotidine',
            'lansoprazole' => 'lansoprazole',
            'pantoprazole' => 'pantoprazole',
            'esomeprazole' => 'esomeprazole',
            'dexlansoprazole' => 'dexlansoprazole',
            'rabeprazole' => 'rabeprazole',
            'amoxicillin' => 'amoxicillin',
            'azithromycin' => 'azithromycin',
            'ciprofloxacin' => 'ciprofloxacin',
            'doxycycline' => 'doxycycline',
            'levofloxacin' => 'levofloxacin',
            'metronidazole' => 'metronidazole',
            'vancomycin' => 'vancomycin',
            'ceftriaxone' => 'ceftriaxone',
            'piperacillin' => 'piperacillin',
            'tazobactam' => 'tazobactam',
            'meropenem' => 'meropenem',
            'imipenem' => 'imipenem',
            'cilastatin' => 'cilastatin',
            'gentamicin' => 'gentamicin',
            'tobramycin' => 'tobramycin',
            'amikacin' => 'amikacin',
            'fluconazole' => 'fluconazole',
            'itraconazole' => 'itraconazole',
            'voriconazole' => 'voriconazole',
            'caspofungin' => 'caspofungin',
            'amphotericin' => 'amphotericin',
            'acyclovir' => 'acyclovir',
            'valacyclovir' => 'valacyclovir',
            'ganciclovir' => 'ganciclovir',
            'oseltamivir' => 'oseltamivir',
            'zanamivir' => 'zanamivir',
            'warfarin' => 'warfarin',
            'heparin' => 'heparin',
            'lmwh' => 'low molecular weight heparin',
            'enoxaparin' => 'enoxaparin',
            'dalteparin' => 'dalteparin',
            'tinzaparin' => 'tinzaparin',
            'fondaparinux' => 'fondaparinux',
            'rivaroxaban' => 'rivaroxaban',
            'apixaban' => 'apixaban',
            'dabigatran' => 'dabigatran',
            'edoxaban' => 'edoxaban',
            'clopidogrel' => 'clopidogrel',
            'prasugrel' => 'prasugrel',
            'ticagrelor' => 'ticagrelor',
            'aspirin' => 'aspirin',
            'dipyridamole' => 'dipyridamole',

            // Common Phrases & Patient Presentation
            'c/o' => 'complaining of',
            'w/' => 'with',
            'w/o' => 'without',
            'yo' => 'year old',
            '♀' => 'female',
            '♂' => 'male',
            'tx' => 'treatment',
            'dx' => 'diagnosis',
            'hx' => 'history',
            'sx' => 'symptoms',
            'fx' => 'function',
            'vs' => 'versus',
            'bid' => 'twice daily',
            'tid' => 'three times daily',
            'qid' => 'four times daily',
            'qod' => 'every other day',
            'prn' => 'as needed',
            'po' => 'by mouth',
            'iv' => 'intravenous',
            'im' => 'intramuscular',
            'sc' => 'subcutaneous',
            'sl' => 'sublingual',
            'top' => 'topical',
            'inh' => 'inhaled',
            'rect' => 'rectal',
            'vag' => 'vaginal',
            'ung' => 'ointment',
            'sol' => 'solution',
            'susp' => 'suspension',
            'tab' => 'tablet',
            'cap' => 'capsule',
            'inj' => 'injection',
            'neb' => 'nebulizer',
            'dpi' => 'dry powder inhaler',
            '↑' => 'increased',
            '↓' => 'decreased',
            '→' => 'leading to',
            '±' => 'plus or minus',
            '≈' => 'approximately',
            '>' => 'greater than',
            '<' => 'less than',
            '≥' => 'greater than or equal to',
            '≤' => 'less than or equal to',
            '=' => 'equals',
            '≠' => 'not equal to',

            // Medical Patient & Anatomy (CRITICAL ADDITIONS)
            'pts' => 'patients',
            'ra' => 'right atrium',
            'rv' => 'right ventricle',
            'la' => 'left atrium',
            'lv' => 'left ventricle',

            // Medical Specialties & Roles (Avoid conflicts with medical terms)
            'do' => 'doctor of osteopathy',
            'rn' => 'registered nurse',
            'np' => 'nurse practitioner',
            'pa' => 'physician assistant',
            'rt' => 'respiratory therapist',
            'ot' => 'occupational therapist',
            'st' => 'speech therapist',
            'mt' => 'medical technologist',
            'phlebotomist' => 'blood drawer',
            'pharmacist' => 'medication expert',
            'dietitian' => 'nutrition specialist',
            'social worker' => 'patient advocate',
            'chaplain' => 'spiritual care provider',

            // Time & Frequency
            'qd' => 'once daily',
            'qhs' => 'at bedtime',
            'qam' => 'in the morning',
            'qpm' => 'in the afternoon',
            'qnoon' => 'at noon',
            'qmidnight' => 'at midnight',
            'ac' => 'before meals',
            'pc' => 'after meals',
            'hs' => 'at bedtime',
            'stat' => 'immediately',
            'now' => 'immediately',
            'asap' => 'as soon as possible',

            // Units & Measurements
            'mmhg' => 'millimeters of mercury',
            'mm' => 'millimeters',
            'cm' => 'centimeters',
            'm' => 'meters',
            'kg' => 'kilograms',
            'lbs' => 'pounds',
            'g' => 'grams',
            'mg' => 'milligrams',
            'mcg' => 'micrograms',
            'ml' => 'milliliters',
            'l' => 'liters',
            'cc' => 'cubic centimeters',
            'iu' => 'international units',
            'u' => 'units',
            'meq' => 'milliequivalents',
            'mmol' => 'millimoles',
            'mosm' => 'milliosmoles',
            'kcal' => 'kilocalories',
            'gm' => 'grams',
            'tbsp' => 'tablespoon',
            'tsp' => 'teaspoon',
            'oz' => 'ounces',
            'c' => 'cup',
            'pt' => 'pint',
            'qt' => 'quart',
            'gal' => 'gallon',

            // Emergency & Critical Care
            'code blue' => 'cardiac arrest',
            'code red' => 'fire',
            'code white' => 'aggressive patient',
            'code gray' => 'combative patient',
            'code silver' => 'weapon',
            'code orange' => 'hazardous spill',
            'code yellow' => 'bomb threat',
            'code black' => 'bomb',
            'code green' => 'evacuation',
            'code violet' => 'violent patient',
            'rapid response' => 'emergency response team',
            'rri' => 'rapid response initiated',
            'cpr' => 'cardiopulmonary resuscitation',
            'defib' => 'defibrillation',
            'intubation' => 'breathing tube placement',
            'ventilation' => 'mechanical breathing',
            'vasopressors' => 'blood pressure support medications',
            'inotropes' => 'heart strength medications',
            'crystalloids' => 'iv fluids',
            'colloids' => 'blood products',
            'transfusion' => 'blood product administration',
            'ecmo' => 'extracorporeal membrane oxygenation',
            'iabc' => 'intra-aortic balloon counterpulsation',
            'cath' => 'catheterization',
            'pci' => 'percutaneous coronary intervention',
            'cabg' => 'coronary artery bypass graft',
            'tpa' => 'tissue plasminogen activator',
            'thrombolytics' => 'clot busting medications',
            'antidotes' => 'poison reversal medications',
            'naloxone' => 'opioid reversal medication',
            'flumazenil' => 'benodiazepine reversal medication',
            'atropine' => 'heart rate medication',
            'epinephrine' => 'adrenaline',
            'norepinephrine' => 'noradrenaline',
            'dopamine' => 'dopamine',
            'dobutamine' => 'dobutamine',
            'milrinone' => 'milrinone',
            'amiodarone' => 'amiodarone',
            'lidocaine' => 'lidocaine',
            'procainamide' => 'procainamide',
            'digoxin' => 'digoxin',
            'adenosine' => 'adenosine',
            'calcium gluconate' => 'calcium replacement',
            'insulin' => 'insulin',
            'dextrose' => 'sugar solution',
            'sodium bicarbonate' => 'acid neutralizer',
            'magnesium sulfate' => 'magnesium replacement',
            'potassium chloride' => 'potassium replacement',
            'calcium chloride' => 'calcium replacement',
        ];

        // Case-insensitive replacement with word boundaries
        foreach ($abbreviations as $abbr => $expansion) {
            // Use word boundaries to avoid partial replacements
            $text = preg_replace('/\b' . preg_quote($abbr, '/') . '\b/i', $expansion, $text);
        }

        return $text;
    }

    private function calculateInverseDocumentFrequency(array $allTokens, array $documents): array
    {
        $idf = [];
        $totalDocs = count($documents);

        foreach ($allTokens as $token) {
            $docsContainingToken = 0;

            foreach ($documents as $document) {
                if (in_array($token, $document)) {
                    $docsContainingToken++;
                }
            }

            $idf[$token] = $docsContainingToken > 0 ?
                log($totalDocs / $docsContainingToken) : 0;
        }

        return $idf;
    }

    private function calculateTfIdfVector(array $tf, array $idf, array $allTokens): array
    {
        $vector = [];

        foreach ($allTokens as $token) {
            $tfValue = $tf[$token] ?? 0;
            $idfValue = $idf[$token] ?? 0;
            $vector[] = $tfValue * $idfValue;
        }

        return $vector;
    }

    private function calculateVectorCosine(array $vector1, array $vector2): float
    {
        // DEBUG: Log vector calculation for failing cases
        $hasZeroValues = in_array(0, $vector1) || in_array(0, $vector2);
        $hasNonZeroValues = array_sum(array_map('abs', $vector1)) > 0 || array_sum(array_map('abs', $vector2)) > 0;

        if ($hasZeroValues && $hasNonZeroValues) {
        }

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        // Ensure vectors are the same length
        $minLength = min(count($vector1), count($vector2));

        for ($i = 0; $i < $minLength; $i++) {
            $val1 = $vector1[$i] ?? 0;
            $val2 = $vector2[$i] ?? 0;

            $dotProduct += $val1 * $val2;
            $magnitude1 += $val1 * $val1;
            $magnitude2 += $val2 * $val2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        // Handle edge cases
        if ($magnitude1 == 0 && $magnitude2 == 0) {
            return 1.0; // Both vectors are zero = identical
        }

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0; // One vector is zero = no similarity
        }

        $cosine = $dotProduct / ($magnitude1 * $magnitude2);

        // Ensure result is in valid range
        $cosine = max(-1.0, min(1.0, $cosine));

        if ($cosine == 0.0 && $hasNonZeroValues) {
        }

        return $cosine;
    }

    private function calculateSemanticTokenSimilarity(string $token1, string $token2): float
    {
        // Exact match
        if ($token1 === $token2) {
            return 1.0;
        }

        // Check medical terminology relationships
        foreach ($this->medicalTerminology as $mainTerm => $relatedTerms) {
            $token1Related = in_array($token1, $relatedTerms) || $token1 === $mainTerm;
            $token2Related = in_array($token2, $relatedTerms) || $token2 === $mainTerm;

            if ($token1Related && $token2Related) {
                return 0.9; // High similarity for related medical terms
            }
        }

        // Levenshtein distance for small variations
        $levSimilarity = 1.0 - (levenshtein($token1, $token2) / max(strlen($token1), strlen($token2)));

        return $levSimilarity > 0.8 ? $levSimilarity : 0.0;
    }

    private function calculateMedicalTokenSimilarity(string $token1, string $token2): float
    {
        // Check if both are medical terms
        $token1Medical = $this->isMedicalTerm($token1);
        $token2Medical = $this->isMedicalTerm($token2);

        if ($token1Medical && $token2Medical) {
            return $this->calculateSemanticTokenSimilarity($token1, $token2);
        } elseif ($token1Medical || $token2Medical) {
            return 0.3; // Partial credit if only one is medical
        }

        return 0.0;
    }

    private function isMedicalTerm(string $token): bool
    {
        $lowerToken = strtolower($token);

        // Check if token exists in medical terminology
        foreach ($this->medicalTerminology as $mainTerm => $relatedTerms) {
            if ($lowerToken === $mainTerm || in_array($lowerToken, $relatedTerms)) {
                return true;
            }
        }

        // Check for medical prefixes/suffixes
        $medicalPatterns = [
            '/^(cardio|neuro|pulmo|hepato|nephro|osteo|derma|hemato|endo|exo)/i',
            '/(ology|itis|oma|ectomy|plasty|scope|graph)$/i',
            '/^(hyper|hypo|pre|post|anti|contra)/i'
        ];

        foreach ($medicalPatterns as $pattern) {
            if (preg_match($pattern, $lowerToken)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Record algorithm statistics for AI learning
     */
    private function recordAlgorithmStats(array $results): void
    {
        $stats = [
            'algorithms' => [
                'cosine' => $results['cosine'],
                'jaccard' => $results['jaccard'],
                'levenshtein' => $results['levenshtein'],
                'semantic' => $results['semantic'],
                'medical' => $results['medical']
            ],
            'ensemble' => $results['ensemble'],
            'confidence' => $results['confidence'],
            'processing_time' => $results['processing_time'],
            'timestamp' => now()->toISOString()
        ];

        $this->algorithmStats[] = $stats;

        // Log every 100 calculations for monitoring
        if (count($this->algorithmStats) % 100 === 0) {
        }
    }

    /**
     * Get algorithm performance statistics
     */
    public function getAlgorithmStats(): array
    {
        if (empty($this->algorithmStats)) {
            return [];
        }

        return [
            'total_calculations' => count($this->algorithmStats),
            'average_scores' => [
                'cosine' => array_sum(array_column(array_column($this->algorithmStats, 'algorithms'), 'cosine')) / count($this->algorithmStats),
                'jaccard' => array_sum(array_column(array_column($this->algorithmStats, 'algorithms'), 'jaccard')) / count($this->algorithmStats),
                'levenshtein' => array_sum(array_column(array_column($this->algorithmStats, 'algorithms'), 'levenshtein')) / count($this->algorithmStats),
                'semantic' => array_sum(array_column(array_column($this->algorithmStats, 'algorithms'), 'semantic')) / count($this->algorithmStats),
                'medical' => array_sum(array_column(array_column($this->algorithmStats, 'algorithms'), 'medical')) / count($this->algorithmStats)
            ],
            'average_ensemble_score' => array_sum(array_column($this->algorithmStats, 'ensemble')) / count($this->algorithmStats),
            'average_confidence' => array_sum(array_column($this->algorithmStats, 'confidence')) / count($this->algorithmStats),
            'average_processing_time' => array_sum(array_column($this->algorithmStats, 'processing_time')) / count($this->algorithmStats),
            'recent_stats' => array_slice($this->algorithmStats, -10)
        ];
    }

    /**
     * ENHANCED METHODS FOR MEDICAL TEXT PROCESSING
     * Specifically designed for complex medical abbreviations and terminology
     */

    /**
     * Enhanced tokenization with medical abbreviation expansion
     */
    private function enhancedTokenizeAndNormalize(string $text): array
    {
        // DEBUG: Log original text

        // First, expand common medical abbreviations
        $expandedText = $this->expandMedicalAbbreviations($text);

        // DEBUG: Log expanded text

        // Remove punctuation and convert to lowercase
        $text = preg_replace('/[^\w\s]/', ' ', strtolower($expandedText));

        // Split into tokens
        $tokens = array_filter(explode(' ', $text), function($token) {
            return strlen(trim($token)) > 0;
        });

        // Enhanced stop word removal with medical awareness
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were'];
        $tokens = array_filter($tokens, function($token) use ($stopWords) {
            return !in_array($token, $stopWords) || $this->isMedicalTerm($token) || strlen($token) <= 2;
        });

        $finalTokens = array_values($tokens);

        // DEBUG: Log final tokens

        return $finalTokens;
    }



    /**
     * Enhanced term frequency calculation with medical boosting
     */
    private function calculateEnhancedTermFrequency(array $tokens): array
    {
        $tf = [];
        $totalTokens = count($tokens);

        foreach ($tokens as $token) {
            $tf[$token] = ($tf[$token] ?? 0) + 1;
        }

        // Apply medical boosting for important medical terms
        foreach ($tf as $token => $count) {
            $boost = 1.0;

            // Boost medical terms
            if ($this->isMedicalTerm($token)) {
                $boost = 1.5; // 50% boost for medical terms
            }

            // Boost short tokens (likely abbreviations)
            if (strlen($token) <= 3) {
                $boost *= 1.2; // Additional 20% boost
            }

            $tf[$token] = ($count / $totalTokens) * $boost;
        }

        return $tf;
    }

    /**
     * Enhanced inverse document frequency for medical terms - FIXED
     */
    private function calculateEnhancedInverseDocumentFrequency(array $allTokens, array $documents): array
    {
        $idf = [];
        $totalDocs = count($documents);

        foreach ($allTokens as $token) {
            $docsContainingToken = 0;

            foreach ($documents as $document) {
                if (in_array($token, $document)) {
                    $docsContainingToken++;
                }
            }

            // CRITICAL FIX: Proper IDF calculation with smoothing
            if ($docsContainingToken > 0) {
                // Standard IDF formula with smoothing
                $idf[$token] = log(($totalDocs + 1) / ($docsContainingToken + 1)) + 1;
            } else {
                // Handle edge case - token not in any document
                $idf[$token] = log($totalDocs + 1) + 1;
            }

            // FIXED: Boost for medical terms and important tokens
            $boost = 1.0;
            if ($this->isMedicalTerm($token)) {
                $boost = 1.5; // 50% boost for medical terms
            } elseif (is_numeric($token)) {
                $boost = 0.8; // Reduce weight for pure numbers
            } elseif (strlen($token) <= 2) {
                $boost = 1.3; // Boost for short tokens (likely abbreviations)
            }

            $idf[$token] *= $boost;
        }

        return $idf;
    }

    /**
     * Enhanced TF-IDF vector calculation with medical weighting
     */
    private function calculateEnhancedTfIdfVector(array $tf, array $idf, array $allTokens): array
    {
        $vector = [];

        foreach ($allTokens as $token) {
            $tfValue = $tf[$token] ?? 0;
            $idfValue = $idf[$token] ?? 0;
            $vector[] = $tfValue * $idfValue;
        }

        return $vector;
    }

    /**
     * Enhanced cosine calculation with medical awareness - FIXED FOR CONSISTENCY
     */
    private function calculateEnhancedVectorCosine(array $vector1, array $vector2): float
    {
        // DEBUG: Log input vectors for consistency checking

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        // CRITICAL FIX: Ensure vectors are the same length and handle empty vectors
        $length1 = count($vector1);
        $length2 = count($vector2);

        if ($length1 === 0 && $length2 === 0) {
            return 1.0; // Both empty = identical
        }

        if ($length1 === 0 || $length2 === 0) {
            return 0.0; // One empty = no similarity
        }

        // Ensure consistent vector lengths by padding with zeros
        $maxLength = max($length1, $length2);

        for ($i = 0; $i < $maxLength; $i++) {
            $val1 = $vector1[$i] ?? 0.0;
            $val2 = $vector2[$i] ?? 0.0;

            $dotProduct += ($val1 * $val2);
            $magnitude1 += ($val1 * $val1);
            $magnitude2 += ($val2 * $val2);
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        // FIXED: Handle zero magnitudes consistently
        if ($magnitude1 < 1e-10 && $magnitude2 < 1e-10) {
            return 1.0; // Both vectors effectively zero = identical
        }

        if ($magnitude1 < 1e-10 || $magnitude2 < 1e-10) {
            return 0.0; // One vector is zero = no similarity
        }

        $cosine = $dotProduct / ($magnitude1 * $magnitude2);

        // FIXED: Ensure result is in valid range and handle floating point precision
        $cosine = max(-1.0, min(1.0, $cosine));

        // Handle potential NaN or infinite values
        if (is_nan($cosine) || is_infinite($cosine)) {
            $cosine = 0.0;
        }


        return $cosine;
    }

    /**
     * Extract features for AI training
     */
    public function extractSimilarityFeatures(string $text1, string $text2): array
    {
        $results = $this->calculateAll($text1, $text2);

        return [
            'text1_length' => strlen($text1),
            'text2_length' => strlen($text2),
            'length_ratio' => strlen($text1) / max(1, strlen($text2)),
            'cosine_similarity' => $results['cosine'],
            'jaccard_similarity' => $results['jaccard'],
            'levenshtein_similarity' => $results['levenshtein'],
            'semantic_similarity' => $results['semantic'],
            'medical_similarity' => $results['medical'],
            'ensemble_score' => $results['ensemble'],
            'confidence' => $results['confidence'],
            'has_medical_terms' => ($results['medical'] > 0.1) ? 1 : 0,
            'similarity_variance' => $this->calculateSimilarityVariance($results),
            'algorithm_agreement' => $this->calculateAlgorithmAgreement($results)
        ];
    }

    private function calculateSimilarityVariance(array $results): float
    {
        $values = [$results['cosine'], $results['jaccard'], $results['levenshtein'], $results['semantic'], $results['medical']];
        $mean = array_sum($values) / count($values);

        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        return $variance / count($values);
    }

    private function calculateAlgorithmAgreement(array $results): float
    {
        $values = [$results['cosine'], $results['jaccard'], $results['levenshtein'], $results['semantic'], $results['medical']];
        $mean = array_sum($values) / count($values);

        $agreement = 0;
        foreach ($values as $value) {
            if (abs($value - $mean) < 0.2) { // Within 20% of mean
                $agreement++;
            }
        }

        return $agreement / count($values);
    }
}
