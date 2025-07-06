<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MedicalKnowledgeService
{
    /**
     * Cache TTL in seconds (default: 1 day)
     *
     * @var int
     */
    protected $cacheTtl = 86400;

    /**
     * Flag to determine if we should use caching
     *
     * @var bool
     */
    protected $useCache = true;
    
    /**
     * Hardcoded medical domains
     * 
     * @var array
     */
    protected $domains = [
        'anatomy' => [
            'terms' => ['bone', 'muscle', 'artery', 'vein', 'nerve', 'vessel', 'organ', 'tissue', 'cell', 'heart', 'lung', 'brain']
        ],
        'physiology' => [
            'terms' => ['function', 'metabolism', 'respiration', 'circulation', 'digestion', 'absorption', 'regulation', 'hormone']
        ],
        'biochemistry' => [
            'terms' => ['enzyme', 'protein', 'lipid', 'carbohydrate', 'nucleic acid', 'atp', 'metabolism', 'molecule']
        ],
        'histology' => [
            'terms' => ['tissue', 'cell', 'epithelium', 'connective', 'muscle', 'nervous', 'microscopic', 'stain']
        ]
    ];
    
    /**
     * Medical abbreviations
     * 
     * @var array
     */
    protected $abbreviations = [
        'bp' => 'blood pressure',
        'hr' => 'heart rate',
        'ecg' => 'electrocardiogram',
        'ekg' => 'electrocardiogram',
        'cns' => 'central nervous system',
        'gi' => 'gastrointestinal',
        'cv' => 'cardiovascular',
        'resp' => 'respiratory',
        'abd' => 'abdomen',
        'sns' => 'sympathetic nervous system',
        'pns' => 'parasympathetic nervous system',
        'ans' => 'autonomic nervous system'
    ];
    
    /**
     * Process locations
     * 
     * @var array
     */
    protected $processLocations = [
        'protein synthesis' => ['ribosomes', 'endoplasmic reticulum'],
        'dna replication' => ['nucleus'],
        'krebs cycle' => ['mitochondria', 'mitochondrial matrix'],
        'tca cycle' => ['mitochondria', 'mitochondrial matrix'],
        'glycolysis' => ['cytoplasm', 'cytosol'],
        'electron transport chain' => ['mitochondria', 'inner mitochondrial membrane']
    ];
    
    /**
     * Contradictions by domain
     * 
     * @var array
     */
    protected $contradictions = [
        'physiology' => [
            ['increase', 'decrease'],
            ['sympathetic', 'parasympathetic'],
            ['activate', 'inhibit'],
            ['systole', 'diastole']
        ],
        'biochemistry' => [
            ['anabolic', 'catabolic'],
            ['oxidation', 'reduction'],
            ['exergonic', 'endergonic']
        ]
    ];

    /**
     * Set the cache configuration.
     *
     * @param bool $useCache
     * @param int|null $ttl
     * @return $this
     */
    public function setCacheConfig(bool $useCache, ?int $ttl = null)
    {
        $this->useCache = $useCache;
        
        if ($ttl !== null) {
            $this->cacheTtl = $ttl;
        }
        
        return $this;
    }

    /**
     * Get cached data or compute it if not available.
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    protected function getCachedOrCompute(string $key, callable $callback)
    {
        if (!$this->useCache) {
            return $callback();
        }
        
        return Cache::remember($key, $this->cacheTtl, $callback);
    }

    /**
     * Identify the likely medical domain from text.
     *
     * @param string $text
     * @return string|null The identified domain slug or null if uncertain
     */
    public function identifyDomain(string $text)
    {
        return $this->getCachedOrCompute('domain_identification_' . md5($text), function () use ($text) {
            $normalizedText = strtolower($text);
            $bestMatch = ['domain' => null, 'score' => 0];
            
            foreach ($this->domains as $domain => $data) {
                $score = 0;
                
                // Check for domain-specific terminology
                foreach ($data['terms'] as $term) {
                    if (strpos($normalizedText, $term) !== false) {
                        $score++;
                    }
                }
                
                // If we found a better match, update our result
                if ($score > $bestMatch['score']) {
                    $bestMatch = [
                        'domain' => $domain,
                        'score' => $score
                    ];
                }
            }
            
            // Return the domain slug if we're confident, otherwise null
            return $bestMatch['score'] >= 2 ? $bestMatch['domain'] : null;
        });
    }

    /**
     * Get critical terms for a specific domain.
     *
     * @param string $domainSlug
     * @return array
     */
    public function getCriticalTerms(string $domainSlug)
    {
        return $this->getCachedOrCompute('critical_terms_' . $domainSlug, function () use ($domainSlug) {
            if (!isset($this->domains[$domainSlug])) {
                return [];
            }
            
            return $this->domains[$domainSlug]['terms'] ?? [];
        });
    }

    /**
     * Check if a pair of terms contradicts each other based on domain rules.
     *
     * @param string $term1
     * @param string $term2
     * @param string $domainSlug
     * @return bool
     */
    public function termsContradict(string $term1, string $term2, string $domainSlug)
    {
        $normalizedTerm1 = strtolower(trim($term1));
        $normalizedTerm2 = strtolower(trim($term2));
        
        // Don't bother checking if the terms are the same
        if ($normalizedTerm1 === $normalizedTerm2) {
            return false;
        }
        
        $cacheKey = "terms_contradict_{$domainSlug}_" . md5($normalizedTerm1 . '_' . $normalizedTerm2);
        
        return $this->getCachedOrCompute($cacheKey, function () use ($normalizedTerm1, $normalizedTerm2, $domainSlug) {
            if (!isset($this->contradictions[$domainSlug])) {
                return false;
            }
            
            foreach ($this->contradictions[$domainSlug] as $pair) {
                if (
                    (strpos($normalizedTerm1, $pair[0]) !== false && strpos($normalizedTerm2, $pair[1]) !== false) ||
                    (strpos($normalizedTerm1, $pair[1]) !== false && strpos($normalizedTerm2, $pair[0]) !== false)
                ) {
                    return true;
                }
            }
            
            return false;
        });
    }

    /**
     * Get all abbreviations with their expansions.
     *
     * @param string|null $domainSlug
     * @return array
     */
    public function getAllAbbreviations(string $domainSlug = null)
    {
        return $this->abbreviations;
    }

    /**
     * Expand an abbreviation to its full form.
     *
     * @param string $abbreviation
     * @param string|null $domainSlug
     * @return string|null
     */
    public function expandAbbreviation(string $abbreviation, string $domainSlug = null)
    {
        $abbreviation = strtolower(trim($abbreviation));
        
        return $this->abbreviations[$abbreviation] ?? null;
    }

    /**
     * Get correct locations for a biological process.
     *
     * @param string $process
     * @param string $domainSlug
     * @return array
     */
    public function getCorrectLocations(string $process, string $domainSlug)
    {
        $process = strtolower(trim($process));
        
        return $this->processLocations[$process] ?? [];
    }

    /**
     * Check if a location is correct for a specific process.
     *
     * @param string $process
     * @param string $location
     * @param string $domainSlug
     * @return bool
     */
    public function isLocationCorrectForProcess(string $process, string $location, string $domainSlug)
    {
        $process = strtolower(trim($process));
        $location = strtolower(trim($location));
        
        $correctLocations = $this->getCorrectLocations($process, $domainSlug);
        
        foreach ($correctLocations as $correctLocation) {
            if (strpos($location, $correctLocation) !== false || strpos($correctLocation, $location) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if a domain has a specific pattern.
     *
     * @param string $patternName
     * @param string $domainSlug
     * @return bool
     */
    public function domainHasPattern(string $patternName, string $domainSlug)
    {
        // Simplified version that just returns true for common patterns
        $commonPatterns = [
            'sympathetic_effects',
            'parasympathetic_effects',
            'cardiac_cycle',
            'action_potential',
            'glycolysis',
            'tca_cycle',
            'electron_transport'
        ];
        
        return in_array($patternName, $commonPatterns);
    }

    /**
     * Flush all cache.
     *
     * @return bool
     */
    public function flushCache()
    {
        $keys = [
            'domain_identification_*',
            'critical_terms_*',
            'terms_contradict_*',
            'synonyms_*',
            'all_abbreviations_*'
        ];
        
        foreach ($keys as $pattern) {
            Cache::forget($pattern);
        }
        
        return true;
    }
} 