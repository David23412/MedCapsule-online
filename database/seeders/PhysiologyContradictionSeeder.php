<?php

namespace Database\Seeders;

use App\Models\MedicalContradiction;
use App\Models\MedicalDomain;
use Illuminate\Database\Seeder;

class PhysiologyContradictionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $physiologyDomain = MedicalDomain::where('slug', 'physiology')->first();

        if (!$physiologyDomain) {
            $this->command->error('Physiology domain not found. Please run the MedicalDomainSeeder first.');
            return;
        }

        $contradictions = [
            // Directional contradictions
            [
                'type' => 'directional',
                'description' => 'Terms that indicate opposite directions in physiology processes',
                'contradiction_rules' => [
                    [
                        'positive' => ['increase', 'increased', 'increases', 'increasing', 'elevated', 'elevates', 'rise', 'rises', 'rising', 'higher', 'upregulated'],
                        'negative' => ['decrease', 'decreased', 'decreases', 'decreasing', 'reduced', 'reduces', 'reduction', 'fall', 'falls', 'falling', 'lower', 'downregulated']
                    ],
                    [
                        'positive' => ['stimulates', 'stimulate', 'stimulation', 'activates', 'activate', 'activation', 'promotes', 'promote', 'enhances', 'enhance'],
                        'negative' => ['inhibits', 'inhibit', 'inhibition', 'blocks', 'block', 'blockade', 'suppresses', 'suppress', 'reduces', 'reduce']
                    ],
                    [
                        'positive' => ['vasoconstriction', 'vasoconstrictive', 'constricts', 'constrict'],
                        'negative' => ['vasodilation', 'vasodilatory', 'dilates', 'dilate']
                    ],
                    [
                        'positive' => ['sympathetic', 'adrenergic', 'fight or flight'],
                        'negative' => ['parasympathetic', 'cholinergic', 'rest and digest']
                    ]
                ],
                'severity' => 1.0
            ],
            
            // Effect contradictions
            [
                'type' => 'effect',
                'description' => 'Terms that describe contradictory physiological effects',
                'contradiction_rules' => [
                    [
                        'term1' => 'tachycardia',
                        'term2' => 'bradycardia'
                    ],
                    [
                        'term1' => 'hypertension',
                        'term2' => 'hypotension'
                    ],
                    [
                        'term1' => 'hyperglycemia',
                        'term2' => 'hypoglycemia'
                    ],
                    [
                        'term1' => 'hyperthyroidism',
                        'term2' => 'hypothyroidism'
                    ],
                    [
                        'term1' => 'hypercalcemia',
                        'term2' => 'hypocalcemia'
                    ],
                    [
                        'term1' => 'alkalosis',
                        'term2' => 'acidosis'
                    ]
                ],
                'severity' => 0.9
            ],
            
            // Location contradictions
            [
                'type' => 'location',
                'description' => 'Processes that occur in specific locations and not in others',
                'contradiction_rules' => [
                    [
                        'process' => 'protein synthesis',
                        'wrong_locations' => ['mitochondria', 'lysosome', 'golgi apparatus', 'peroxisome']
                    ],
                    [
                        'process' => 'krebs cycle',
                        'wrong_locations' => ['cytoplasm', 'nucleus', 'endoplasmic reticulum', 'ribosome']
                    ],
                    [
                        'process' => 'glycolysis',
                        'wrong_locations' => ['mitochondria', 'nucleus', 'endoplasmic reticulum', 'lysosome']
                    ],
                    [
                        'process' => 'dna replication',
                        'wrong_locations' => ['cytoplasm', 'mitochondria', 'endoplasmic reticulum', 'golgi apparatus', 'lysosome', 'peroxisome']
                    ]
                ],
                'severity' => 1.0
            ],
            
            // Pathway contradictions
            [
                'type' => 'pathway',
                'description' => 'Contradictory pathways that cannot both be active in the same context',
                'contradiction_rules' => [
                    [
                        'term1' => 'glycolysis',
                        'term2' => 'gluconeogenesis'
                    ],
                    [
                        'term1' => 'lipolysis',
                        'term2' => 'lipogenesis'
                    ],
                    [
                        'term1' => 'glycogenolysis',
                        'term2' => 'glycogenesis'
                    ]
                ],
                'severity' => 0.8
            ],
            
            // Sympathetic/Parasympathetic contradictions
            [
                'type' => 'autonomic',
                'description' => 'Contradictory effects from sympathetic vs parasympathetic stimulation',
                'contradiction_rules' => [
                    [
                        'positive' => ['increased heart rate', 'tachycardia', 'cardiac acceleration'],
                        'negative' => ['decreased heart rate', 'bradycardia', 'cardiac deceleration']
                    ],
                    [
                        'positive' => ['bronchodilation', 'dilated airways', 'increased airway diameter'],
                        'negative' => ['bronchoconstriction', 'constricted airways', 'decreased airway diameter']
                    ],
                    [
                        'positive' => ['increased pupil size', 'mydriasis', 'dilated pupils'],
                        'negative' => ['decreased pupil size', 'miosis', 'constricted pupils']
                    ],
                    [
                        'positive' => ['increased blood pressure', 'hypertension'],
                        'negative' => ['decreased blood pressure', 'hypotension']
                    ]
                ],
                'severity' => 0.9
            ]
        ];

        foreach ($contradictions as $contradiction) {
            MedicalContradiction::updateOrCreate(
                [
                    'domain_id' => $physiologyDomain->id,
                    'type' => $contradiction['type'],
                    'description' => $contradiction['description']
                ],
                [
                    'contradiction_rules' => $contradiction['contradiction_rules'],
                    'severity' => $contradiction['severity']
                ]
            );
        }

        $this->command->info('Physiology contradictions seeded successfully.');
    }
} 