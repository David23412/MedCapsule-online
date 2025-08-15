<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class MedicalPatternService
{
    private $cacheEnabled = true;
    private $cacheTtl = 3600; // 1 hour

    /**
     * Get course-specific patterns
     */
    public function getCourseSpecificPatterns(string $courseTopic): array
    {
        $patterns = [];
        
        switch ($courseTopic) {
            case 'cardiovascular':
                $patterns = [
                    'critical_terms' => ['heart', 'cardiac', 'blood', 'circulation', 'pressure'],
                    'contradictions' => [
                        'increased_decreased' => ['increased', 'decreased'],
                        'systolic_diastolic' => ['systolic', 'diastolic'],
                    ],
                    'abbreviations' => ['BP', 'HR', 'CO', 'SV', 'TPR'],
                ];
                break;
                
            case 'respiratory':
                $patterns = [
                    'critical_terms' => ['lung', 'breathing', 'oxygen', 'ventilation', 'airway'],
                    'contradictions' => [
                        'inspiration_expiration' => ['inspiration', 'expiration'],
                        'hypoxia_hyperoxia' => ['hypoxia', 'hyperoxia'],
                    ],
                    'abbreviations' => ['O2', 'CO2', 'FEV1', 'FVC', 'COPD'],
                ];
                break;
                
            case 'nervous':
                $patterns = [
                    'critical_terms' => ['brain', 'nerve', 'neuron', 'reflex', 'signal'],
                    'contradictions' => [
                        'sympathetic_parasympathetic' => ['sympathetic', 'parasympathetic'],
                        'sensory_motor' => ['sensory', 'motor'],
                    ],
                    'abbreviations' => ['CNS', 'PNS', 'CSF', 'EEG', 'MRI'],
                ];
                break;
                
            default:
                $patterns = [
                    'critical_terms' => [],
                    'contradictions' => [],
                    'abbreviations' => [],
                ];
        }
        
        return $patterns;
    }

    /**
     * Check course-specific pattern match
     */
    public function checkCourseSpecificPatternMatch(string $submitted, string $correct, array $patterns): array
    {
        $result = [
            'critical_mismatch' => false,
            'boost' => 0.0,
            'details' => []
        ];

        // Check for critical terms
        if (!empty($patterns['critical_terms'])) {
            $missingCritical = [];
            foreach ($patterns['critical_terms'] as $term) {
                if (stripos($correct, $term) !== false && stripos($submitted, $term) === false) {
                    $missingCritical[] = $term;
                }
            }
            
            if (!empty($missingCritical)) {
                $result['critical_mismatch'] = true;
                $result['details']['missing_critical'] = $missingCritical;
            }
        }

        // Check for contradictions
        if (!empty($patterns['contradictions'])) {
            foreach ($patterns['contradictions'] as $contradictionType => $terms) {
                $foundTerms = [];
                foreach ($terms as $term) {
                    if (stripos($submitted, $term) !== false) {
                        $foundTerms[] = $term;
                    }
                }
                
                if (count($foundTerms) > 1) {
                    $result['critical_mismatch'] = true;
                    $result['details']['contradictions'][$contradictionType] = $foundTerms;
                }
            }
        }

        // Check for proper abbreviation usage
        if (!empty($patterns['abbreviations'])) {
            $properAbbreviations = 0;
            foreach ($patterns['abbreviations'] as $abbr) {
                if (stripos($submitted, $abbr) !== false && stripos($correct, $abbr) !== false) {
                    $properAbbreviations++;
                }
            }
            
            if ($properAbbreviations > 0) {
                $result['boost'] = min(0.05, $properAbbreviations * 0.01); // Max 5% boost
                $result['details']['abbreviation_boost'] = $properAbbreviations;
            }
        }

        return $result;
    }

    /**
     * Check for critical terms
     */
    public function checkForCriticalTerms(?string $courseTopic, string $normalizedSubmitted, string $normalizedCorrect): bool
    {
        if (!$courseTopic) {
            return true; // No specific course topic, assume terms are present
        }

        $patterns = $this->getCourseSpecificPatterns($courseTopic);
        $criticalTerms = $patterns['critical_terms'] ?? [];

        foreach ($criticalTerms as $term) {
            if (stripos($normalizedCorrect, $term) !== false && stripos($normalizedSubmitted, $term) === false) {
                // Check if term exists as abbreviation
                if (!$this->checkForTermsAsAbbreviations($normalizedSubmitted, [$term])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check for contradictions
     */
    public function containsContradictions(string $normalizedSubmitted, string $normalizedCorrect, ?string $courseTopic): bool
    {
        if (!$courseTopic) {
            return false;
        }

        $patterns = $this->getCourseSpecificPatterns($courseTopic);
        $contradictions = $patterns['contradictions'] ?? [];

        foreach ($contradictions as $contradictionSet) {
            $foundCount = 0;
            foreach ($contradictionSet as $term) {
                if (stripos($normalizedSubmitted, $term) !== false) {
                    $foundCount++;
                }
            }
            
            if ($foundCount > 1) {
                return true; // Found contradictory terms
            }
        }

        return false;
    }

    /**
     * Check for terms as abbreviations
     */
    private function checkForTermsAsAbbreviations(string $text, array $missingTerms): bool
    {
        if (empty($missingTerms)) {
            return true;
        }

        $commonAbbreviations = [
            'blood pressure' => ['bp', 'b.p.'],
            'heart rate' => ['hr', 'h.r.'],
            'central nervous system' => ['cns', 'c.n.s.'],
            'peripheral nervous system' => ['pns', 'p.n.s.'],
            'oxygen' => ['o2', 'o₂'],
            'carbon dioxide' => ['co2', 'co₂'],
        ];

        foreach ($missingTerms as $term) {
            $termLower = strtolower($term);
            if (isset($commonAbbreviations[$termLower])) {
                foreach ($commonAbbreviations[$termLower] as $abbr) {
                    if (stripos($text, $abbr) !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check for critical pattern mismatch
     */
    public function checkForCriticalPatternMismatch(string $normalizedSubmitted, string $normalizedCorrect, string $courseTopic): bool
    {
        $patterns = $this->getCourseSpecificPatterns($courseTopic);
        $result = $this->checkCourseSpecificPatternMatch($normalizedSubmitted, $normalizedCorrect, $patterns);
        
        return $result['critical_mismatch'];
    }

    /**
     * Check if question is pattern-critical
     */
    public function isPatternCriticalQuestion(string $correctAnswer): bool
    {
        $patternKeywords = [
            'mechanism', 'pathway', 'process', 'sequence', 'steps',
            'causes', 'leads to', 'results in', 'triggers',
            'inhibits', 'activates', 'stimulates', 'suppresses',
            'increases', 'decreases', 'enhances', 'reduces'
        ];

        $normalizedAnswer = strtolower($correctAnswer);
        foreach ($patternKeywords as $keyword) {
            if (strpos($normalizedAnswer, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for significant medical abbreviations
     */
    public function containsSignificantMedicalAbbreviations(string $answer): bool
    {
        $medicalAbbreviations = [
            'ATP', 'DNA', 'RNA', 'CNS', 'PNS', 'ECG', 'EKG', 'MRI', 'CT',
            'BP', 'HR', 'RR', 'O2', 'CO2', 'H2O', 'Na+', 'K+', 'Ca2+',
            'COPD', 'CHF', 'MI', 'CVA', 'TIA', 'DVT', 'PE', 'UTI',
            'IV', 'IM', 'PO', 'PRN', 'BID', 'TID', 'QID'
        ];

        $normalizedAnswer = strtoupper($answer);
        foreach ($medicalAbbreviations as $abbr) {
            if (strpos($normalizedAnswer, $abbr) !== false) {
                return true;
            }
        }

        return false;
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