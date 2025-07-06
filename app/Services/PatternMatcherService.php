<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PatternMatcherService
{
    /**
     * Cache settings
     */
    private bool $cacheEnabled = true;
    private int $cacheTtl = 86400; // 24 hours
    
    /**
     * Registered pattern matchers
     */
    private array $patternMatchers = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->registerDefaultPatternMatchers();
    }
    
    /**
     * Set cache configuration
     *
     * @param bool $enabled Whether caching is enabled
     * @param int $ttl Time to live in seconds
     * @return self
     */
    public function setCacheConfig(bool $enabled, int $ttl = 86400): self
    {
        $this->cacheEnabled = $enabled;
        $this->cacheTtl = $ttl;
        return $this;
    }
    
    /**
     * Register default pattern matchers
     */
    private function registerDefaultPatternMatchers(): void
    {
        // ANS-related patterns
        $this->registerPatternMatcher('ans_division', function ($text) {
            $text = strtolower($text);
            
            $hasANS = preg_match('/\b(ans|autonomic nervous system)\b/', $text);
            
            $hasSympatheticMention = preg_match('/\b(sympathetic|adrenergic)\b/', $text);
            $hasParasympatheticMention = preg_match('/\b(parasympathetic|cholinergic|vagal)\b/', $text);
            
            return $hasANS && ($hasSympatheticMention || $hasParasympatheticMention);
        });
        
        // Sympathetic effects pattern
        $this->registerPatternMatcher('sympathetic_effects', function ($text) {
            $text = strtolower($text);
            
            $hasSympatheticMention = preg_match('/\b(sympathetic|adrenergic)\b/', $text);
            
            // Check for specific effects
            $hasHeartRateEffect = preg_match('/\b(increas\w+\s+heart\s+rate|heart\s+rate\s+increas\w+)\b/', $text);
            $hasBloodPressureEffect = preg_match('/\b(increas\w+\s+blood\s+pressure|blood\s+pressure\s+increas\w+)\b/', $text);
            $hasDilationEffect = preg_match('/\b(pupil\w*\s+dilat\w+|dilat\w+\s+pupil\w*)\b/', $text);
            $hasDigestionEffect = preg_match('/\b(decreas\w+\s+digest\w+|digest\w+\s+decreas\w+)\b/', $text);
            
            $hasEffects = $hasHeartRateEffect || $hasBloodPressureEffect || $hasDilationEffect || $hasDigestionEffect;
            
            return $hasSympatheticMention && $hasEffects;
        });
        
        // Parasympathetic effects pattern
        $this->registerPatternMatcher('parasympathetic_effects', function ($text) {
            $text = strtolower($text);
            
            // Allow for common misspelling
            $hasParasympatheticMention = preg_match('/\b(parasympathetic|parasymathetic|cholinergic|vagal)\b/', $text);
            
            // Check for specific effects
            $hasHeartRateEffect = preg_match('/\b(decreas\w+\s+heart\s+rate|heart\s+rate\s+decreas\w+)\b/', $text);
            $hasDigestionEffect = preg_match('/\b(increas\w+\s+digest\w+|digest\w+\s+increas\w+)\b/', $text);
            $hasConstrictionEffect = preg_match('/\b(pupil\w*\s+constrict\w+|constrict\w+\s+pupil\w*|miosis)\b/', $text);
            
            $hasEffects = $hasHeartRateEffect || $hasDigestionEffect || $hasConstrictionEffect;
            
            return $hasParasympatheticMention && $hasEffects;
        });
        
        // Anatomy pattern
        $this->registerPatternMatcher('anatomy', function ($text) {
            $text = strtolower($text);
            
            $anatomyParts = [
                'heart', 'lung', 'liver', 'kidney', 'brain', 'spleen', 'pancreas', 
                'stomach', 'intestine', 'colon', 'rectum', 'bladder', 'uterus', 
                'ovary', 'testicle', 'prostate', 'thyroid', 'adrenal'
            ];
            
            $matches = 0;
            foreach ($anatomyParts as $part) {
                if (strpos($text, $part) !== false) {
                    $matches++;
                }
            }
            
            return $matches >= 2;
        });
        
        // Drug mechanism pattern
        $this->registerPatternMatcher('drug_mechanism', function ($text) {
            $text = strtolower($text);
            
            $hasDrugTerm = preg_match('/\b(drug|medication|agent|antagonist|agonist|inhibitor|blocker)\b/', $text);
            
            $hasMechanismTerm = preg_match('/\b(mechanism|action|effect|bind|receptor|block|activate|inhibit)\b/', $text);
            
            return $hasDrugTerm && $hasMechanismTerm;
        });
        
        // Diagnosis pattern
        $this->registerPatternMatcher('diagnosis', function ($text) {
            $text = strtolower($text);
            
            $hasDiagnosisTerm = preg_match('/\b(diagnos\w+|condition|disease|disorder|syndrome)\b/', $text);
            
            $hasSymptomTerm = preg_match('/\b(symptom|sign|presentation|manifests|presents)\b/', $text);
            
            return $hasDiagnosisTerm || $hasSymptomTerm;
        });
        
        // Treatment pattern
        $this->registerPatternMatcher('treatment', function ($text) {
            $text = strtolower($text);
            
            $hasTreatmentTerm = preg_match('/\b(treat\w+|therap\w+|intervention|management|procedure|surgery)\b/', $text);
            
            $hasOutcomeTerm = preg_match('/\b(outcome|prognosis|efficacy|effective|response|result)\b/', $text);
            
            return $hasTreatmentTerm || $hasOutcomeTerm;
        });
        
        // Pathophysiology pattern
        $this->registerPatternMatcher('pathophysiology', function ($text) {
            $text = strtolower($text);
            
            $hasPathophysiologyTerm = preg_match('/\b(pathophysiology|pathogenesis|mechanism|cause|etiology|develop\w+)\b/', $text);
            
            $hasProcessTerm = preg_match('/\b(process|progression|cascade|pathway|sequence|lead\s+to)\b/', $text);
            
            return $hasPathophysiologyTerm || $hasProcessTerm;
        });
    }
    
    /**
     * Register a new pattern matcher
     *
     * @param string $name The name of the pattern matcher
     * @param callable $callback The pattern matching function
     * @return self
     */
    public function registerPatternMatcher(string $name, callable $callback): self
    {
        $this->patternMatchers[$name] = $callback;
        return $this;
    }
    
    /**
     * Get a cached result or compute it
     *
     * @param string $key Cache key
     * @param callable $callback Function to compute the result
     * @return mixed The cached or computed result
     */
    private function getCachedOrCompute(string $key, callable $callback)
    {
        if (!$this->cacheEnabled) {
            return $callback();
        }
        
        return Cache::remember($key, $this->cacheTtl, $callback);
    }
    
    /**
     * Identify matching patterns in a text
     *
     * @param string $text The text to analyze
     * @return array Array of pattern matches with name, description, and confidence
     */
    public function identifyPatterns(string $text): array
    {
        $cacheKey = 'pattern_matcher:' . md5($text);
        
        return $this->getCachedOrCompute($cacheKey, function () use ($text) {
            $matches = [];
            
            foreach ($this->patternMatchers as $name => $matcher) {
                if ($matcher($text)) {
                    $matches[$name] = $this->getPatternDescription($name);
                }
            }
            
            return $matches;
        });
    }
    
    /**
     * Check if a specific pattern matches in a text
     *
     * @param string $patternName The name of the pattern to check
     * @param string $text The text to analyze
     * @return bool Whether the pattern matches
     */
    public function matchesPattern(string $patternName, string $text): bool
    {
        if (!isset($this->patternMatchers[$patternName])) {
            return false;
        }
        
        $cacheKey = 'pattern_matcher:' . $patternName . ':' . md5($text);
        
        return $this->getCachedOrCompute($cacheKey, function () use ($patternName, $text) {
            return $this->patternMatchers[$patternName]($text);
        });
    }
    
    /**
     * Get the list of all registered pattern matchers
     * 
     * @return array List of pattern matcher names and descriptions
     */
    public function getRegisteredPatterns(): array
    {
        $patterns = [];
        
        foreach (array_keys($this->patternMatchers) as $name) {
            $patterns[$name] = $this->getPatternDescription($name);
        }
        
        return $patterns;
    }
    
    /**
     * Get description for a pattern
     *
     * @param string $patternName Pattern name
     * @return string Pattern description
     */
    private function getPatternDescription(string $patternName): string
    {
        $descriptions = [
            'ans_division' => 'Autonomic Nervous System Divisions',
            'sympathetic_effects' => 'Sympathetic Effects',
            'parasympathetic_effects' => 'Parasympathetic Effects',
            'anatomy' => 'Anatomical Structures',
            'drug_mechanism' => 'Drug Mechanism of Action',
            'diagnosis' => 'Disease Diagnosis',
            'treatment' => 'Treatment Approach',
            'pathophysiology' => 'Disease Pathophysiology'
        ];
        
        return $descriptions[$patternName] ?? 'Unknown Pattern';
    }
} 