<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class MedicalDomainService
{
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
    ];

    private $medicalSymbols = [];
    private $medicalAbbreviations = [];

    public function __construct()
    {
        $this->initializeMedicalSymbols();
        $this->initializeMedicalAbbreviations();
    }

    /**
     * Initialize medical symbols mapping
     */
    private function initializeMedicalSymbols(): void
    {
        $this->medicalSymbols = [
            '→' => 'leads to',
            '↑' => 'increases',
            '↓' => 'decreases',
            '±' => 'plus or minus',
            '∆' => 'change',
            'α' => 'alpha',
            'β' => 'beta',
            'γ' => 'gamma',
            'μ' => 'micro',
            '°' => 'degrees',
            '≥' => 'greater than or equal to',
            '≤' => 'less than or equal to',
            '≠' => 'not equal to',
            '∞' => 'infinity',
            '∝' => 'proportional to',
        ];
    }

    /**
     * Identify course topic from text
     */
    public function identifyCourseTopic(string $text): ?string
    {
        return $this->getCachedOrCompute("courseTopic:" . md5($text), function() use ($text) {
            $normalizedText = strtolower($text);
            $domainScores = [];
            
            foreach ($this->medicalDomains as $domain => $keywords) {
                $score = 0;
                foreach ($keywords as $keyword) {
                    if (strpos($normalizedText, $keyword) !== false) {
                        $score++;
                    }
                }
                if ($score > 0) {
                    $domainScores[$domain] = $score;
                }
            }
            
            if (empty($domainScores)) {
                return null;
            }
            
            return array_key_first(array_slice(arsort($domainScores) ? $domainScores : [], 0, 1, true));
        });
    }

    /**
     * Calculate domain context boost
     */
    public function calculateDomainContextBoost(string $text1, string $text2): float
    {
        $cacheKey = "domain_boost:{$text1}:{$text2}";
        
        return $this->getCachedOrCompute($cacheKey, function() use ($text1, $text2) {
            $domain1 = $this->identifyCourseTopic($text1);
            $domain2 = $this->identifyCourseTopic($text2);
            
            if ($domain1 && $domain2 && $domain1 === $domain2) {
                return 0.05; // 5% boost for same domain
            }
            
            return 0.0;
        });
    }

    /**
     * Preprocess medical symbols in text
     */
    public function preprocessSymbols(string $answer): string
    {
        foreach ($this->medicalSymbols as $symbol => $replacement) {
            $answer = str_replace($symbol, $replacement, $answer);
        }
        return $answer;
    }

    /**
     * Get medical domains
     */
    public function getMedicalDomains(): array
    {
        return $this->medicalDomains;
    }

    /**
     * Cache helper method
     */
    private function getCachedOrCompute(string $key, \Closure $callback)
    {
        if (!$this->cacheEnabled) {
            return $callback();
        }

        return Cache::remember($key, $this->cacheTtl, $callback);
    }

    /**
     * Initialize medical abbreviations
     */
    private function initializeMedicalAbbreviations(): void
    {
        $this->medicalAbbreviations = [
            'afib' => 'atrial fibrillation',
            'bp' => 'blood pressure',
            'bpm' => 'beats per minute',
            'chf' => 'congestive heart failure',
            'copd' => 'chronic obstructive pulmonary disease',
            'cva' => 'cerebrovascular accident',
            'dm' => 'diabetes mellitus',
            'dvt' => 'deep vein thrombosis',
            'ekg' => 'electrocardiogram',
            'ecg' => 'electrocardiogram',
            'gi' => 'gastrointestinal',
            'htn' => 'hypertension',
            'mi' => 'myocardial infarction',
            'pe' => 'pulmonary embolism',
            'sob' => 'shortness of breath',
            'uti' => 'urinary tract infection',
            'cns' => 'central nervous system',
            'pns' => 'peripheral nervous system',
            'o2' => 'oxygen',
            'co2' => 'carbon dioxide',
            'h2o' => 'water',
            'na' => 'sodium',
            'k' => 'potassium',
            'ca' => 'calcium',
            'mg' => 'magnesium',
            'cl' => 'chloride'
        ];
    }

    /**
     * Expand medical abbreviations in text
     */
    public function expandAbbreviations(string $text): string
    {
        $normalizedText = strtolower($text);
        
        foreach ($this->medicalAbbreviations as $abbr => $expansion) {
            $normalizedText = str_replace($abbr, $expansion, $normalizedText);
        }
        
        return $normalizedText;
    }

    /**
     * Get medical abbreviations
     */
    public function getMedicalAbbreviations(): array
    {
        return $this->medicalAbbreviations;
    }

    /**
     * Set cache configuration
     */
    public function setCacheConfig(bool $enabled, int $ttl = 86400): void
    {
        $this->cacheEnabled = $enabled;
        $this->cacheTtl = $ttl;
    }
}