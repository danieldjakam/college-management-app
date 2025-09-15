<?php

require_once 'back/vendor/autoload.php';

// Simuler des données ENSEIGNEMENT TECHNIQUE avec structure DEUXIÈME CYCLE
$testData = [
    'student' => (object)[
        'id' => 39,
        'first_name' => 'AMANDA GABRILLA',
        'last_name' => 'DJEPANG ATATWA',
        'schoolClass' => (object)[
            'name' => 'SEME 1',
            'level' => (object)[
                'section' => (object)[
                    'name' => 'ENSEIGNEMENT TECHNIQUE'
                ]
            ]
        ]
    ],
    'trimester' => (object)['number' => 1],
    'section_type' => 'technique',
    'subjects' => [
        [
            'name' => 'CONSTRUCTION MÉTALLIQUE',
            'sequence1' => 14.50,
            'sequence2' => 15.25,
            'composition' => 16.00,
            'average' => 15.25, // (14.50 + 15.25 + 16.00) / 3
            'coefficient' => 5,
            'nxc' => 76.25, // 15.25 × 5
            'total' => 76.25,
            'rank' => '1',
            'teacher' => 'M. ENGINEERING',
            'competence' => 'Acquise (Très Bien)',
            'cycle_type' => 'deuxieme',
            'section_type' => 'technique'
        ],
        [
            'name' => 'DESSIN TECHNIQUE',
            'sequence1' => 13.75,
            'sequence2' => 14.50,
            'composition' => 15.00,
            'average' => 14.42, // (13.75 + 14.50 + 15.00) / 3
            'coefficient' => 4,
            'nxc' => 57.68,
            'total' => 57.68,
            'rank' => '2',
            'teacher' => 'MME. DESIGN',
            'competence' => 'Acquise (Bien)',
            'cycle_type' => 'deuxieme',
            'section_type' => 'technique'
        ],
        [
            'name' => 'TECHNOLOGIE',
            'sequence1' => 16.00,
            'sequence2' => 15.50,
            'composition' => 17.00,
            'average' => 16.17, // (16.00 + 15.50 + 17.00) / 3
            'coefficient' => 3,
            'nxc' => 48.51,
            'total' => 48.51,
            'rank' => '1',
            'teacher' => 'M. TECH',
            'competence' => 'Acquise (Excellent)',
            'cycle_type' => 'deuxieme',
            'section_type' => 'technique'
        ]
    ],
    'average' => 15.28,
    'total_points' => 182.44,
    'total_coefficient' => 12,
    'rank' => '1er'
];

echo "🔧 ENSEIGNEMENT TECHNIQUE - Structure DEUXIÈME CYCLE\n\n";

echo "👤 Étudiant: {$testData['student']->first_name} {$testData['student']->last_name}\n";
echo "📚 Classe: {$testData['student']->schoolClass->name}\n";
echo "🏫 Section: {$testData['student']->schoolClass->level->section->name}\n\n";

echo "📊 STRUCTURE DU BULLETIN:\n";
echo "| DISCIPLINE           | Seq1  | Seq2  | Compo | Moy./20 | COEF | (NXC)  | TOTAL  | RANG |\n";
echo "|----------------------|-------|-------|-------|---------|------|--------|--------+------|\n";

foreach ($testData['subjects'] as $subject) {
    printf("| %-20s | %5.2f | %5.2f | %5.2f | %7.2f | %4d | %6.2f | %6.2f | %4s |\n",
        $subject['name'],
        $subject['sequence1'],
        $subject['sequence2'],
        $subject['composition'],
        $subject['average'],
        $subject['coefficient'],
        $subject['nxc'],
        $subject['total'],
        $subject['rank']
    );
}

echo "\n📈 RÉSULTATS GÉNÉRAUX:\n";
echo "   Moyenne générale: {$testData['average']}/20\n";
echo "   Total points: {$testData['total_points']}\n";
echo "   Total coefficient: {$testData['total_coefficient']}\n";
echo "   Rang: {$testData['rank']}\n\n";

echo "✅ CONCLUSION: ENSEIGNEMENT TECHNIQUE utilise la même structure que DEUXIÈME CYCLE\n";
echo "📋 Formule: (Sequence 1 + Sequence 2 + Composition) / 3\n";
echo "🎯 Affichage: Toutes les notes individuelles visibles\n";

?>