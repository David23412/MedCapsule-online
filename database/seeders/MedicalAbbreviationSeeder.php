<?php

namespace Database\Seeders;

use App\Models\MedicalAbbreviation;
use App\Models\MedicalDomain;
use Illuminate\Database\Seeder;

class MedicalAbbreviationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Common medical abbreviations that are domain-agnostic
        $commonAbbreviations = [
            // Vital signs
            ['abbreviation' => 'BP', 'expansion' => 'blood pressure', 'confidence' => 1.0],
            ['abbreviation' => 'HR', 'expansion' => 'heart rate', 'confidence' => 1.0],
            ['abbreviation' => 'RR', 'expansion' => 'respiratory rate', 'confidence' => 1.0],
            ['abbreviation' => 'T', 'expansion' => 'temperature', 'confidence' => 0.8],
            ['abbreviation' => 'SpO2', 'expansion' => 'oxygen saturation', 'confidence' => 1.0],
            
            // Common laboratory values
            ['abbreviation' => 'WBC', 'expansion' => 'white blood cell count', 'confidence' => 1.0],
            ['abbreviation' => 'RBC', 'expansion' => 'red blood cell count', 'confidence' => 1.0],
            ['abbreviation' => 'Hgb', 'expansion' => 'hemoglobin', 'confidence' => 1.0],
            ['abbreviation' => 'Hct', 'expansion' => 'hematocrit', 'confidence' => 1.0],
            ['abbreviation' => 'BUN', 'expansion' => 'blood urea nitrogen', 'confidence' => 1.0],
            ['abbreviation' => 'Cr', 'expansion' => 'creatinine', 'confidence' => 0.9],
            
            // Common chemical elements and compounds
            ['abbreviation' => 'Na+', 'expansion' => 'sodium ion', 'confidence' => 1.0],
            ['abbreviation' => 'K+', 'expansion' => 'potassium ion', 'confidence' => 1.0],
            ['abbreviation' => 'Ca2+', 'expansion' => 'calcium ion', 'confidence' => 1.0],
            ['abbreviation' => 'Cl-', 'expansion' => 'chloride ion', 'confidence' => 1.0],
            ['abbreviation' => 'HCO3-', 'expansion' => 'bicarbonate', 'confidence' => 1.0],
            ['abbreviation' => 'H+', 'expansion' => 'hydrogen ion', 'confidence' => 1.0],
            ['abbreviation' => 'O2', 'expansion' => 'oxygen', 'confidence' => 1.0],
            ['abbreviation' => 'CO2', 'expansion' => 'carbon dioxide', 'confidence' => 1.0],
            
            // Common medical terms
            ['abbreviation' => 'ABG', 'expansion' => 'arterial blood gas', 'confidence' => 1.0],
            ['abbreviation' => 'CBC', 'expansion' => 'complete blood count', 'confidence' => 1.0],
            ['abbreviation' => 'ECG', 'expansion' => 'electrocardiogram', 'confidence' => 1.0],
            ['abbreviation' => 'EKG', 'expansion' => 'electrocardiogram', 'confidence' => 1.0],
            ['abbreviation' => 'IV', 'expansion' => 'intravenous', 'confidence' => 1.0],
            ['abbreviation' => 'PO', 'expansion' => 'by mouth', 'confidence' => 0.9],
            ['abbreviation' => 'q.d.', 'expansion' => 'once daily', 'confidence' => 0.9],
            ['abbreviation' => 'b.i.d.', 'expansion' => 'twice daily', 'confidence' => 0.9],
            ['abbreviation' => 't.i.d.', 'expansion' => 'three times daily', 'confidence' => 0.9],
            ['abbreviation' => 'q.i.d.', 'expansion' => 'four times daily', 'confidence' => 0.9],
            ['abbreviation' => 'PRN', 'expansion' => 'as needed', 'confidence' => 0.9],
            ['abbreviation' => 'NPO', 'expansion' => 'nothing by mouth', 'confidence' => 1.0],
        ];

        foreach ($commonAbbreviations as $abbr) {
            MedicalAbbreviation::updateOrCreate(
                [
                    'abbreviation' => $abbr['abbreviation'],
                    'expansion' => $abbr['expansion'],
                    'domain_id' => null // These are domain-agnostic
                ],
                [
                    'confidence' => $abbr['confidence'],
                    'context' => null
                ]
            );
        }

        // Now let's add domain-specific abbreviations
        $domains = [
            'anatomy' => [
                ['abbreviation' => 'CNS', 'expansion' => 'central nervous system', 'confidence' => 1.0],
                ['abbreviation' => 'PNS', 'expansion' => 'peripheral nervous system', 'confidence' => 1.0],
                ['abbreviation' => 'GI', 'expansion' => 'gastrointestinal', 'confidence' => 1.0],
                ['abbreviation' => 'CV', 'expansion' => 'cardiovascular', 'confidence' => 1.0],
                ['abbreviation' => 'URT', 'expansion' => 'upper respiratory tract', 'confidence' => 0.9],
                ['abbreviation' => 'LRT', 'expansion' => 'lower respiratory tract', 'confidence' => 0.9],
            ],
            'physiology' => [
                ['abbreviation' => 'GFR', 'expansion' => 'glomerular filtration rate', 'confidence' => 1.0],
                ['abbreviation' => 'CO', 'expansion' => 'cardiac output', 'confidence' => 0.9],
                ['abbreviation' => 'SV', 'expansion' => 'stroke volume', 'confidence' => 1.0],
                ['abbreviation' => 'TPR', 'expansion' => 'total peripheral resistance', 'confidence' => 1.0],
                ['abbreviation' => 'MAP', 'expansion' => 'mean arterial pressure', 'confidence' => 1.0],
                ['abbreviation' => 'ADH', 'expansion' => 'antidiuretic hormone', 'confidence' => 1.0],
                ['abbreviation' => 'ANP', 'expansion' => 'atrial natriuretic peptide', 'confidence' => 1.0],
                ['abbreviation' => 'SNS', 'expansion' => 'sympathetic nervous system', 'confidence' => 1.0],
                ['abbreviation' => 'PNS', 'expansion' => 'parasympathetic nervous system', 'confidence' => 1.0],
            ],
            'biochemistry' => [
                ['abbreviation' => 'ATP', 'expansion' => 'adenosine triphosphate', 'confidence' => 1.0],
                ['abbreviation' => 'ADP', 'expansion' => 'adenosine diphosphate', 'confidence' => 1.0],
                ['abbreviation' => 'DNA', 'expansion' => 'deoxyribonucleic acid', 'confidence' => 1.0],
                ['abbreviation' => 'RNA', 'expansion' => 'ribonucleic acid', 'confidence' => 1.0],
                ['abbreviation' => 'mRNA', 'expansion' => 'messenger ribonucleic acid', 'confidence' => 1.0],
                ['abbreviation' => 'tRNA', 'expansion' => 'transfer ribonucleic acid', 'confidence' => 1.0],
                ['abbreviation' => 'TCA', 'expansion' => 'tricarboxylic acid cycle', 'confidence' => 1.0],
                ['abbreviation' => 'ETC', 'expansion' => 'electron transport chain', 'confidence' => 0.9],
                ['abbreviation' => 'NADH', 'expansion' => 'nicotinamide adenine dinucleotide reduced', 'confidence' => 1.0],
                ['abbreviation' => 'FADH2', 'expansion' => 'flavin adenine dinucleotide reduced', 'confidence' => 1.0],
            ],
            'histology' => [
                ['abbreviation' => 'H&E', 'expansion' => 'hematoxylin and eosin', 'confidence' => 1.0],
                ['abbreviation' => 'EM', 'expansion' => 'electron microscopy', 'confidence' => 0.9],
                ['abbreviation' => 'LM', 'expansion' => 'light microscopy', 'confidence' => 0.9],
                ['abbreviation' => 'ECM', 'expansion' => 'extracellular matrix', 'confidence' => 1.0],
                ['abbreviation' => 'BM', 'expansion' => 'basement membrane', 'confidence' => 0.9],
            ],
        ];

        foreach ($domains as $domainSlug => $abbreviations) {
            $domain = MedicalDomain::where('slug', $domainSlug)->first();
            
            if (!$domain) {
                $this->command->warn("Domain '$domainSlug' not found, skipping its abbreviations.");
                continue;
            }

            foreach ($abbreviations as $abbr) {
                MedicalAbbreviation::updateOrCreate(
                    [
                        'abbreviation' => $abbr['abbreviation'],
                        'expansion' => $abbr['expansion'],
                        'domain_id' => $domain->id
                    ],
                    [
                        'confidence' => $abbr['confidence'],
                        'context' => null
                    ]
                );
            }
        }

        $this->command->info('Medical abbreviations seeded successfully.');
    }
} 