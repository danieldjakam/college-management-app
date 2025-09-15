<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\BulletinService;

/**
 * 🎓 TEST GÉNÉRATION BULLETIN DEUXIÈME CYCLE
 * Trimestre 1 avec 10 matières
 */

echo "🎓 GÉNÉRATION BULLETIN DEUXIÈME CYCLE - TRIMESTRE 1\n";
echo "===================================================\n\n";

// Simuler les données d'un étudiant DEUXIÈME CYCLE
$studentData = [
    'student' => (object)[
        'id' => 123,
        'first_name' => 'HASSIM',
        'last_name' => 'ACHTA',
        'date_of_birth' => new DateTime('2005-03-15'),
        'place_of_birth' => 'YAOUNDÉ',
        'matricule' => 'LYC2024001',
        'schoolClass' => (object)[
            'name' => 'PREMIÈRE A4',
            'students' => function() { return collect(range(1, 45)); } // 45 élèves
        ]
    ],
    'trimester' => (object)[
        'id' => 1,
        'number' => 1,
        'name' => 'Premier Trimestre'
    ],
    'subjects' => [
        // 📚 GROUPE A : MATIÈRES LITTÉRAIRES
        [
            'name' => 'FRANÇAIS',
            'sequence1' => 14.50,
            'sequence2' => 16.00,
            'composition' => 17.50,
            'average' => 16.00, // (14.50+16.00+17.50)/3
            'coefficient' => 6.00,
            'nxc' => 96.00, // 16.00 × 6.00
            'rank' => 3,
            'competence' => 'Acquise (Excellent)',
            'teacher' => 'MBARGA CÉLESTINE',
            'cycle_type' => 'deuxieme'
        ],
        [
            'name' => 'ANGLAIS',
            'sequence1' => 13.25,
            'sequence2' => 15.75,
            'composition' => 16.50,
            'average' => 15.17, // (13.25+15.75+16.50)/3
            'coefficient' => 4.00,
            'nxc' => 60.68, // 15.17 × 4.00
            'rank' => 5,
            'competence' => 'Acquise (Très Bien)',
            'teacher' => 'JOHNSON MARGARET',
            'cycle_type' => 'deuxieme'
        ],
        [
            'name' => 'HISTOIRE-GÉOGRAPHIE',
            'sequence1' => 12.00,
            'sequence2' => 14.50,
            'composition' => 15.00,
            'average' => 13.83, // (12.00+14.50+15.00)/3
            'coefficient' => 4.00,
            'nxc' => 55.32, // 13.83 × 4.00
            'rank' => 8,
            'competence' => 'Acquise (Bien)',
            'teacher' => 'NKOMO ANDRÉ',
            'cycle_type' => 'deuxieme'
        ],
        [
            'name' => 'PHILOSOPHIE',
            'sequence1' => 11.50,
            'sequence2' => 13.00,
            'composition' => 14.75,
            'average' => 13.08, // (11.50+13.00+14.75)/3
            'coefficient' => 3.00,
            'nxc' => 39.24, // 13.08 × 3.00
            'rank' => 7,
            'competence' => 'Acquise (Bien)',
            'teacher' => 'FEUDJIO PASCAL',
            'cycle_type' => 'deuxieme'
        ],

        // 🔬 GROUPE B : MATIÈRES SCIENTIFIQUES
        [
            'name' => 'MATHÉMATIQUES',
            'sequence1' => 16.50,
            'sequence2' => 18.00,
            'composition' => 19.25,
            'average' => 17.92, // (16.50+18.00+19.25)/3
            'coefficient' => 5.00,
            'nxc' => 89.60, // 17.92 × 5.00
            'rank' => 1,
            'competence' => 'Acquise (Excellent)',
            'teacher' => 'TALLA FRANÇOIS',
            'cycle_type' => 'deuxieme'
        ],
        [
            'name' => 'SCIENCES PHYSIQUES',
            'sequence1' => 15.75,
            'sequence2' => 17.25,
            'composition' => 18.00,
            'average' => 17.00, // (15.75+17.25+18.00)/3
            'coefficient' => 4.00,
            'nxc' => 68.00, // 17.00 × 4.00
            'rank' => 2,
            'competence' => 'Acquise (Excellent)',
            'teacher' => 'KAMGA SOLANGE',
            'cycle_type' => 'deuxieme'
        ],
        [
            'name' => 'SVT',
            'sequence1' => 14.00,
            'sequence2' => 16.50,
            'composition' => 17.75,
            'average' => 16.08, // (14.00+16.50+17.75)/3
            'coefficient' => 3.00,
            'nxc' => 48.24, // 16.08 × 3.00
            'rank' => 4,
            'competence' => 'Acquise (Excellent)',
            'teacher' => 'NOAH BRIGITTE',
            'cycle_type' => 'deuxieme'
        ],

        // 🎨 GROUPE C : MATIÈRES PRATIQUES
        [
            'name' => 'EPS',
            'sequence1' => 15.00,
            'sequence2' => 16.00,
            'composition' => 20.00,
            'average' => 17.00, // (15.00+16.00+20.00)/3
            'coefficient' => 4.00,
            'nxc' => 68.00, // 17.00 × 4.00
            'rank' => 1,
            'competence' => 'Acquise (Excellent)',
            'teacher' => 'NGUEPINSE KAMGANG',
            'cycle_type' => 'deuxieme'
        ],
        [
            'name' => 'INFORMATIQUE',
            'sequence1' => 16.25,
            'sequence2' => 17.50,
            'composition' => 18.75,
            'average' => 17.50, // (16.25+17.50+18.75)/3
            'coefficient' => 2.00,
            'nxc' => 35.00, // 17.50 × 2.00
            'rank' => 1,
            'competence' => 'Acquise (Excellent)',
            'teacher' => 'MBALLA STEVE',
            'cycle_type' => 'deuxieme'
        ],
        [
            'name' => 'ARTS PLASTIQUES',
            'sequence1' => 13.50,
            'sequence2' => 15.25,
            'composition' => 16.00,
            'average' => 14.92, // (13.50+15.25+16.00)/3
            'coefficient' => 2.00,
            'nxc' => 29.84, // 14.92 × 2.00
            'rank' => 6,
            'competence' => 'Acquise (Très Bien)',
            'teacher' => 'FOTSO MARIE',
            'cycle_type' => 'deuxieme'
        ]
    ]
];

// Calculer les totaux
$totalCoef = 0;
$totalNXC = 0;
foreach ($studentData['subjects'] as $subject) {
    $totalCoef += $subject['coefficient'];
    $totalNXC += $subject['nxc'];
}

$moyenneGenerale = $totalNXC / $totalCoef;

$studentData['total_coefficient'] = $totalCoef;
$studentData['total_points'] = $totalNXC;
$studentData['average'] = $moyenneGenerale;
$studentData['rank'] = 2; // 2e de la classe

echo "📊 DONNÉES CALCULÉES:\n";
echo "=====================\n";
echo "Étudiant: {$studentData['student']->first_name} {$studentData['student']->last_name}\n";
echo "Classe: {$studentData['student']->schoolClass->name}\n";
echo "Trimestre: {$studentData['trimester']->number}\n";
echo "Nombre de matières: " . count($studentData['subjects']) . "\n";
echo "Total coefficients: $totalCoef\n";
echo "Total NXC: " . round($totalNXC, 2) . "\n";
echo "Moyenne générale: " . round($moyenneGenerale, 2) . "/20\n";
echo "Rang: {$studentData['rank']}e\n\n";

// Créer l'instance du service
$bulletinService = new BulletinService();

echo "🎯 GÉNÉRATION DU TEMPLATE HTML...\n";

// Générer le HTML
$htmlContent = $bulletinService->renderBulletinTemplate('trimester', $studentData, false);

echo "✅ Template HTML généré avec succès!\n\n";

// Sauvegarder le HTML pour inspection
$htmlFile = __DIR__ . '/bulletin_deuxieme_cycle_preview.html';
file_put_contents($htmlFile, $htmlContent);
echo "💾 HTML sauvegardé: $htmlFile\n";

// Générer le PDF
echo "📄 GÉNÉRATION PDF...\n";
try {
    $pdfPath = $bulletinService->generatePDF(
        $htmlContent,
        'bulletin_trimestre1_deuxieme_cycle_' . date('Y-m-d_H-i-s') . '.pdf'
    );
    echo "✅ PDF généré avec succès: $pdfPath\n";
} catch (Exception $e) {
    echo "❌ Erreur PDF: " . $e->getMessage() . "\n";
}

echo "\n🎯 VÉRIFICATION DU CONTENU:\n";
echo "============================\n";

// Vérifier que les colonnes DEUXIÈME CYCLE sont présentes
if (strpos($htmlContent, 'Sequence 1') !== false && strpos($htmlContent, 'Sequence 2') !== false) {
    echo "✅ Colonnes séquences individuelles: OK\n";
} else {
    echo "❌ Colonnes séquences individuelles: MANQUANTES\n";
}

if (strpos($htmlContent, 'Moy./20') !== false) {
    echo "✅ Colonne Moyenne: OK\n";
} else {
    echo "❌ Colonne Moyenne: MANQUANTE\n";
}

if (strpos($htmlContent, 'COMPÉTENCES') !== false) {
    echo "✅ Colonne Compétences: OK\n";
} else {
    echo "❌ Colonne Compétences: MANQUANTE\n";
}

if (strpos($htmlContent, 'NGUEPINSE KAMGANG') !== false) {
    echo "✅ Noms des enseignants: OK\n";
} else {
    echo "❌ Noms des enseignants: MANQUANTS\n";
}

// Compter les colonnes dans le tableau
$headerMatches = substr_count($htmlContent, '<th style="border: 1px solid #000');
if ($headerMatches >= 11) {
    echo "✅ Nombre de colonnes: $headerMatches (≥ 11 colonnes DEUXIÈME CYCLE)\n";
} else {
    echo "❌ Nombre de colonnes: $headerMatches (< 11 colonnes)\n";
}

echo "\n🎉 TEST TERMINÉ!\n";
echo "================\n";
echo "Fichiers générés:\n";
echo "- HTML: $htmlFile\n";
echo "- PDF: storage/app/$pdfPath\n";
echo "\nVous pouvez maintenant inspecter le bulletin généré ! 📋\n";