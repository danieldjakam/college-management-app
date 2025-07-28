<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create initial admin user
        DB::table('users')->insert([
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'status' => 'ad',
            'school_id' => 'GSBPL_001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create initial comptable user
        DB::table('users')->insert([
            'username' => 'comptable',
            'password' => Hash::make('comptable123'),
            'status' => 'comp',
            'school_id' => 'GSBPL_001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create initial sections
        $sections = [
            ['id' => 'SEC_001', 'name' => 'Maternelle', 'description' => 'Section Maternelle'],
            ['id' => 'SEC_002', 'name' => 'Primaire', 'description' => 'Section Primaire'],
            ['id' => 'SEC_003', 'name' => 'Secondaire', 'description' => 'Section Secondaire'],
        ];

        foreach ($sections as $section) {
            DB::table('sections')->insert([
                'id' => $section['id'],
                'name' => $section['name'],
                'description' => $section['description'],
                'school_id' => 'GSBPL_001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create initial classes
        $classes = [
            // Maternelle
            ['id' => 'MAT_SIL', 'name' => 'SIL', 'section_id' => 'SEC_001'],
            ['id' => 'MAT_CP', 'name' => 'CP', 'section_id' => 'SEC_001'],
            
            // Primaire
            ['id' => 'PRI_CE1', 'name' => 'CE1', 'section_id' => 'SEC_002'],
            ['id' => 'PRI_CE2', 'name' => 'CE2', 'section_id' => 'SEC_002'],
            ['id' => 'PRI_CM1', 'name' => 'CM1', 'section_id' => 'SEC_002'],
            ['id' => 'PRI_CM2', 'name' => 'CM2', 'section_id' => 'SEC_002'],
            
            // Secondaire
            ['id' => 'SEC_6EME', 'name' => '6ème', 'section_id' => 'SEC_003'],
            ['id' => 'SEC_5EME', 'name' => '5ème', 'section_id' => 'SEC_003'],
            ['id' => 'SEC_4EME', 'name' => '4ème', 'section_id' => 'SEC_003'],
            ['id' => 'SEC_3EME', 'name' => '3ème', 'section_id' => 'SEC_003'],
        ];

        foreach ($classes as $class) {
            DB::table('class')->insert([
                'id' => $class['id'],
                'name' => $class['name'],
                'section_id' => $class['section_id'],
                'school_year' => '2024-2025',
                'school_id' => 'GSBPL_001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create initial domains
        $domains = [
            ['id' => 'DOM_001', 'name' => 'Sciences Exactes', 'coefficient' => 3],
            ['id' => 'DOM_002', 'name' => 'Langues', 'coefficient' => 2],
            ['id' => 'DOM_003', 'name' => 'Sciences Humaines', 'coefficient' => 2],
            ['id' => 'DOM_004', 'name' => 'Arts et Sports', 'coefficient' => 1],
        ];

        foreach ($domains as $domain) {
            DB::table('domains')->insert([
                'id' => $domain['id'],
                'name' => $domain['name'],
                'coefficient' => $domain['coefficient'],
                'school_id' => 'GSBPL_001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create initial subjects
        $subjects = [
            ['id' => 'MAT_001', 'name' => 'Mathématiques', 'domain_id' => 'DOM_001', 'coefficient' => 4],
            ['id' => 'PHY_001', 'name' => 'Physique', 'domain_id' => 'DOM_001', 'coefficient' => 3],
            ['id' => 'CHI_001', 'name' => 'Chimie', 'domain_id' => 'DOM_001', 'coefficient' => 2],
            ['id' => 'FRA_001', 'name' => 'Français', 'domain_id' => 'DOM_002', 'coefficient' => 4],
            ['id' => 'ANG_001', 'name' => 'Anglais', 'domain_id' => 'DOM_002', 'coefficient' => 3],
            ['id' => 'HIS_001', 'name' => 'Histoire', 'domain_id' => 'DOM_003', 'coefficient' => 2],
            ['id' => 'GEO_001', 'name' => 'Géographie', 'domain_id' => 'DOM_003', 'coefficient' => 2],
            ['id' => 'EPS_001', 'name' => 'EPS', 'domain_id' => 'DOM_004', 'coefficient' => 2],
        ];

        foreach ($subjects as $subject) {
            DB::table('subjects')->insert([
                'id' => $subject['id'],
                'name' => $subject['name'],
                'domain_id' => $subject['domain_id'],
                'coefficient' => $subject['coefficient'],
                'school_id' => 'GSBPL_001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create initial trimesters
        $trimesters = [
            [
                'id' => 'TRIM_1_2024',
                'name' => '1er Trimestre 2024-2025',
                'start_date' => '2024-09-01',
                'end_date' => '2024-12-15',
                'is_active' => true
            ],
            [
                'id' => 'TRIM_2_2024',
                'name' => '2ème Trimestre 2024-2025',
                'start_date' => '2025-01-08',
                'end_date' => '2025-04-15',
                'is_active' => false
            ],
            [
                'id' => 'TRIM_3_2024',
                'name' => '3ème Trimestre 2024-2025',
                'start_date' => '2025-04-22',
                'end_date' => '2025-07-15',
                'is_active' => false
            ],
        ];

        foreach ($trimesters as $trimester) {
            DB::table('trimesters')->insert([
                'id' => $trimester['id'],
                'name' => $trimester['name'],
                'start_date' => $trimester['start_date'],
                'end_date' => $trimester['end_date'],
                'school_year' => '2024-2025',
                'is_active' => $trimester['is_active'],
                'school_id' => 'GSBPL_001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create initial sequences
        $sequences = [
            [
                'id' => 'SEQ_1_TRIM_1',
                'name' => '1ère Séquence',
                'start_date' => '2024-09-01',
                'end_date' => '2024-10-31',
                'trimester_id' => 'TRIM_1_2024',
                'is_active' => true
            ],
            [
                'id' => 'SEQ_2_TRIM_1',
                'name' => '2ème Séquence',
                'start_date' => '2024-11-01',
                'end_date' => '2024-12-15',
                'trimester_id' => 'TRIM_1_2024',
                'is_active' => false
            ],
        ];

        foreach ($sequences as $sequence) {
            DB::table('sequences')->insert([
                'id' => $sequence['id'],
                'name' => $sequence['name'],
                'start_date' => $sequence['start_date'],
                'end_date' => $sequence['end_date'],
                'trimester_id' => $sequence['trimester_id'],
                'school_year' => '2024-2025',
                'is_active' => $sequence['is_active'],
                'school_id' => 'GSBPL_001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create initial settings
        $settings = [
            ['key' => 'school_name', 'value' => 'Collège Polyvalent Bilingue de Douala'],
            ['key' => 'school_address', 'value' => 'Douala, Cameroun'],
            ['key' => 'school_phone', 'value' => '+237 XXX XXX XXX'],
            ['key' => 'school_email', 'value' => 'contact@cpbd.cm'],
            ['key' => 'principal_name', 'value' => 'Directeur CPBD'],
            ['key' => 'current_year', 'value' => '2024-2025'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'school_id' => 'GSBPL_001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create a test teacher
        DB::table('teachers')->insert([
            'name' => 'Jean',
            'subname' => 'Dupont',
            'sex' => 'm',
            'email' => 'jean.dupont@cpbd.cm',
            'phone_number' => '+237 XXX XXX XXX',
            'username' => 'jdupont',
            'password' => Hash::make('teacher123'),
            'class_id' => 'SEC_6EME',
            'school_id' => 'GSBPL_001',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}