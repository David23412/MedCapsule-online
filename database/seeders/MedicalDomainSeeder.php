<?php

namespace Database\Seeders;

use App\Models\MedicalDomain;
use Illuminate\Database\Seeder;

class MedicalDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $domains = [
            [
                'name' => 'Anatomy',
                'slug' => 'anatomy',
                'description' => 'The study of the structure of the body and its parts',
                'display_order' => 1,
            ],
            [
                'name' => 'Physiology',
                'slug' => 'physiology',
                'description' => 'The study of the function of the body and its parts',
                'display_order' => 2,
            ],
            [
                'name' => 'Biochemistry',
                'slug' => 'biochemistry',
                'description' => 'The study of chemical processes within and relating to living organisms',
                'display_order' => 3,
            ],
            [
                'name' => 'Histology',
                'slug' => 'histology',
                'description' => 'The study of the microscopic structure of tissues',
                'display_order' => 4,
            ],
            [
                'name' => 'Pharmacology',
                'slug' => 'pharmacology',
                'description' => 'The study of the uses, effects, and modes of action of drugs',
                'display_order' => 5,
            ],
            [
                'name' => 'Pathology',
                'slug' => 'pathology',
                'description' => 'The study of the causes and effects of disease or injury',
                'display_order' => 6,
            ],
            [
                'name' => 'Microbiology',
                'slug' => 'microbiology',
                'description' => 'The study of microorganisms, including bacteria, viruses, fungi, and parasites',
                'display_order' => 7,
            ],
            [
                'name' => 'Immunology',
                'slug' => 'immunology',
                'description' => 'The study of the immune system and its functions and disorders',
                'display_order' => 8,
            ],
        ];

        foreach ($domains as $domain) {
            MedicalDomain::updateOrCreate(
                ['slug' => $domain['slug']],
                $domain
            );
        }
    }
} 