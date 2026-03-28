<?php

namespace App\Services;

/**
 * MedicalKnowledgeService for Written Answer Evaluation
 * Provides medical domain knowledge and critical term validation
 */
class MedicalKnowledgeService
{
    private $cacheEnabled = false;
    private $cacheTtl = 3600;
    private array $processLocations = [
        'protein synthesis' => ['ribosome', 'ribosomes', 'rough endoplasmic reticulum', 'endoplasmic reticulum'],
        'translation' => ['ribosome', 'ribosomes', 'rough endoplasmic reticulum', 'endoplasmic reticulum'],
        'dna replication' => ['nucleus'],
        'glycolysis' => ['cytoplasm', 'cytosol'],
        'krebs cycle' => ['mitochondria', 'mitochondrial matrix'],
        'tca cycle' => ['mitochondria', 'mitochondrial matrix'],
        'electron transport chain' => ['inner mitochondrial membrane', 'mitochondria'],
        'glomerular filtration' => ['glomerulus', 'bowman', 'bowmans capsule', 'bowman capsule'],
    ];

    /**
     * Critical terms by medical domain
     */
    private $criticalTerms = [
        'anatomy' => [
            'heart', 'liver', 'brain', 'lung', 'kidney', 'stomach', 'intestine',
            'artery', 'vein', 'nerve', 'muscle', 'bone', 'cartilage', 'ligament',
            'anterior', 'posterior', 'superior', 'inferior', 'medial', 'lateral'
        ],
        'physiology' => [
            'systole', 'diastole', 'depolarization', 'repolarization', 'threshold',
            'parasympathetic', 'sympathetic', 'homeostasis', 'feedback', 'regulation',
            'contraction', 'relaxation', 'diffusion', 'osmosis', 'filtration'
        ],
        'biochemistry' => [
            'ATP', 'NADH', 'FADH2', 'glucose', 'pyruvate', 'acetyl-CoA', 'enzyme',
            'substrate', 'product', 'cofactor', 'coenzyme', 'metabolism', 'glycolysis',
            'TCA cycle', 'electron transport', 'protein synthesis', 'DNA', 'RNA'
        ],
        'histology' => [
            'epithelial', 'connective', 'muscle', 'nervous', 'simple', 'stratified',
            'squamous', 'cuboidal', 'columnar', 'basement membrane', 'extracellular matrix'
        ],
        'pathology' => [
            'inflammation', 'infection', 'ischemia', 'necrosis', 'apoptosis', 'hypertrophy',
            'atrophy', 'metaplasia', 'dysplasia', 'neoplasia', 'carcinoma', 'sarcoma'
        ]
    ];

    /**
     * Medical abbreviations
     */
    private $abbreviations = [
        'HR' => 'heart rate',
        'BP' => 'blood pressure',
        'ECG' => 'electrocardiogram',
        'EEG' => 'electroencephalogram',
        'MRI' => 'magnetic resonance imaging',
        'CT' => 'computed tomography',
        'CBC' => 'complete blood count',
        'BUN' => 'blood urea nitrogen',
        'Cr' => 'creatinine',
        'Na' => 'sodium',
        'K' => 'potassium',
        'Ca' => 'calcium',
        'Mg' => 'magnesium',
        'Cl' => 'chloride',
        'CO2' => 'carbon dioxide',
        'O2' => 'oxygen',
        'pH' => 'potential of hydrogen',
        'H+' => 'hydrogen ion',
        'OH-' => 'hydroxide ion',
        'ATP' => 'adenosine triphosphate',
        'ADP' => 'adenosine diphosphate',
        'NADH' => 'nicotinamide adenine dinucleotide',
        'FADH2' => 'flavin adenine dinucleotide',
        'DNA' => 'deoxyribonucleic acid',
        'RNA' => 'ribonucleic acid',
        'mRNA' => 'messenger RNA',
        'tRNA' => 'transfer RNA',
        'rRNA' => 'ribosomal RNA'
    ];

    /**
     * Get critical terms for a domain
     */
    public function getCriticalTerms(string $domain): array
    {
        return $this->criticalTerms[$domain] ?? [];
    }

    /**
     * Identify the closest medical domain for a text sample.
     */
    public function identifyDomain(string $text): ?string
    {
        $text = strtolower($text);
        $scores = [];

        foreach ($this->criticalTerms as $domain => $terms) {
            $score = 0;

            foreach ($terms as $term) {
                if (stripos($text, strtolower($term)) !== false) {
                    $score++;
                }
            }

            if ($score > 0) {
                $scores[$domain] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        arsort($scores);

        return array_key_first($scores);
    }

    /**
     * Check if two terms contradict each other in a domain
     */
    public function termsContradict(string $term1, string $term2, string $domain): bool
    {
        $contradictions = [
            'anatomy' => [
                ['anterior', 'posterior'],
                ['superior', 'inferior'],
                ['medial', 'lateral'],
                ['proximal', 'distal'],
                ['flexion', 'extension']
            ],
            'physiology' => [
                ['contraction', 'relaxation'],
                ['systole', 'diastole'],
                ['depolarization', 'repolarization'],
                ['sympathetic', 'parasympathetic'],
                ['increase', 'decrease']
            ],
            'biochemistry' => [
                ['aerobic', 'anaerobic'],
                ['oxidation', 'reduction'],
                ['synthesis', 'degradation'],
                ['phosphorylation', 'dephosphorylation']
            ]
        ];

        if (!isset($contradictions[$domain])) {
            return false;
        }

        $term1 = strtolower($term1);
        $term2 = strtolower($term2);

        foreach ($contradictions[$domain] as $pair) {
            if ((in_array($term1, $pair) && in_array($term2, $pair))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all abbreviations
     */
    public function getAllAbbreviations(): array
    {
        return $this->abbreviations;
    }

    /**
     * Expand abbreviation
     */
    public function expandAbbreviation(string $abbreviation): ?string
    {
        return $this->abbreviations[strtoupper($abbreviation)] ?? null;
    }

    /**
     * Check if term is an abbreviation
     */
    public function isAbbreviation(string $term): bool
    {
        return isset($this->abbreviations[strtoupper($term)]);
    }

    /**
     * Return accepted locations for a biological process.
     */
    public function getCorrectLocations(string $process): array
    {
        $process = strtolower(trim($process));

        return $this->processLocations[$process] ?? [];
    }

    /**
     * Check whether a location is valid for the given process.
     */
    public function isLocationCorrectForProcess(string $process, string $location): bool
    {
        $location = strtolower(trim($location));

        foreach ($this->getCorrectLocations($process) as $acceptedLocation) {
            if ($acceptedLocation === $location || str_contains($location, $acceptedLocation) || str_contains($acceptedLocation, $location)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get related terms for a concept
     */
    public function getRelatedTerms(string $concept, string $domain): array
    {
        $relatedTerms = [
            'anatomy' => [
                'heart' => ['cardiac', 'myocardial', 'atrium', 'ventricle', 'valve'],
                'brain' => ['cerebral', 'cortex', 'neuron', 'synapse', 'neurotransmitter'],
                'lung' => ['pulmonary', 'alveoli', 'bronchi', 'trachea', 'surfactant']
            ],
            'physiology' => [
                'heart' => ['cardiac output', 'stroke volume', 'heart rate', 'blood pressure'],
                'kidney' => ['filtration', 'reabsorption', 'secretion', 'urine', 'nephron'],
                'nervous' => ['action potential', 'synapse', 'neurotransmitter', 'reflex']
            ],
            'biochemistry' => [
                'glycolysis' => ['glucose', 'pyruvate', 'ATP', 'NADH', 'enzyme'],
                'TCA cycle' => ['acetyl-CoA', 'citric acid', 'NADH', 'FADH2', 'CO2'],
                'protein synthesis' => ['DNA', 'RNA', 'ribosome', 'amino acid', 'translation']
            ]
        ];

        return $relatedTerms[$domain][$concept] ?? [];
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
