<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectGroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Définition des groupes par défaut basée sur la logique actuelle
        $subjectGroups = [
            // GROUPE A : MATIÈRES LITTÉRAIRES
            'A' => [
                'anglais', 'français', 'histoire', 'géographie',
                'expression écrite', 'expression orale',
                'étude de texte', 'orthographe',
                'langues et cultures nationales', 'langue et culture nationale'
            ],

            // GROUPE B : MATIÈRES SCIENTIFIQUES
            'B' => [
                'mathématiques', 'sciences physiques', 'svt',
                'sciences naturelles', 'sciences', 'physique'
            ],

            // GROUPE C : MATIÈRES PRATIQUES
            'C' => [
                'eps', 'informatique', 'travail manuel',
                'arts plastiques', 'education physique et sportive',
                'éducation artistique'
            ],

            // GROUPE D : AUTRES MATIÈRES
            'D' => [
                'éducation civique et morale', 'education civique',
                'éducation de la citoyenneté', 'ecm'
            ]
        ];

        foreach ($subjectGroups as $group => $subjectNames) {
            foreach ($subjectNames as $name) {
                DB::table('subjects')
                    ->where('name', 'LIKE', '%' . $name . '%')
                    ->update(['group' => $group]);
            }
        }

        // Afficher le résultat
        $this->command->info('✅ Groupes de matières initialisés:');
        foreach (['A', 'B', 'C', 'D'] as $group) {
            $count = DB::table('subjects')->where('group', $group)->count();
            $groupName = [
                'A' => 'Littéraires',
                'B' => 'Scientifiques',
                'C' => 'Pratiques',
                'D' => 'Autres'
            ][$group];
            $this->command->info("  - Groupe $group ($groupName): $count matières");
        }

        $null = DB::table('subjects')->whereNull('group')->count();
        if ($null > 0) {
            $this->command->warn("  ⚠️  $null matières sans groupe (à configurer manuellement)");
        }
    }
}
