<?php

/**
 * 🎓 DÉMONSTRATION BULLETIN DEUXIÈME CYCLE
 * Génération HTML directe pour voir l'affichage 11 colonnes
 */

echo "🎓 DÉMONSTRATION BULLETIN DEUXIÈME CYCLE\n";
echo "========================================\n\n";

// Données de test pour un étudiant DEUXIÈME CYCLE
$testSubjects = [
    // 📚 GROUPE A : MATIÈRES LITTÉRAIRES
    [
        'name' => 'FRANÇAIS',
        'sequence1' => 14.50,
        'sequence2' => 16.00,
        'composition' => 17.50,
        'average' => 16.00, // (14.50+16.00+17.50)/3
        'coefficient' => 6.00,
        'nxc' => 96.00,
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
        'average' => 15.17,
        'coefficient' => 4.00,
        'nxc' => 60.68,
        'rank' => 5,
        'competence' => 'Acquise (Très Bien)',
        'teacher' => 'JOHNSON MARGARET',
        'cycle_type' => 'deuxieme'
    ],
    [
        'name' => 'MATHÉMATIQUES',
        'sequence1' => 16.50,
        'sequence2' => 18.00,
        'composition' => 19.25,
        'average' => 17.92,
        'coefficient' => 5.00,
        'nxc' => 89.60,
        'rank' => 1,
        'competence' => 'Acquise (Excellent)',
        'teacher' => 'TALLA FRANÇOIS',
        'cycle_type' => 'deuxieme'
    ],
    [
        'name' => 'EPS',
        'sequence1' => 15.00,
        'sequence2' => 16.00,
        'composition' => 20.00,
        'average' => 17.00,
        'coefficient' => 4.00,
        'nxc' => 68.00,
        'rank' => 1,
        'competence' => 'Acquise (Excellent)',
        'teacher' => 'NGUEPINSE KAMGANG',
        'cycle_type' => 'deuxieme'
    ]
];

// Fonction pour générer le HTML du tableau DEUXIÈME CYCLE
function generateDeuxiemeCycleHTML($subjects) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulletin DEUXIÈME CYCLE - Trimestre 1</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .bulletin-header { text-align: center; margin-bottom: 30px; }
        .student-info { margin-bottom: 20px; }
        .grades-section { margin-bottom: 20px; }
        .group-header { background: #f0f0f0; padding: 8px; font-weight: bold; text-align: center; border: 1px solid #000; }
        .subjects-table { width: 100%; border-collapse: collapse; border: 1px solid #000; }
        .subjects-table th, .subjects-table td { border: 1px solid #000; padding: 5px; text-align: center; }
        .subjects-table th { background: #f8f8f8; font-weight: bold; }
        .total-row { background: #f0f0f0; font-weight: bold; }
        .grade-excellent { background: #d4edda; }
        .grade-good { background: #fff3cd; }
        .grade-average { background: #f8d7da; }
    </style>
</head>
<body>';

    $html .= '
    <div class="bulletin-header">
        <h1>BULLETIN SCOLAIRE - TRIMESTRE 1</h1>
        <h2>SECTION FRANCOPHONE - ENSEIGNEMENT GÉNÉRAL DEUXIÈME CYCLE</h2>
    </div>

    <div class="student-info">
        <p><strong>Élève:</strong> HASSIM ACHTA</p>
        <p><strong>Classe:</strong> PREMIÈRE A4</p>
        <p><strong>Année scolaire:</strong> 2024/2025</p>
        <p><strong>Trimestre:</strong> Premier Trimestre</p>
    </div>';

    $html .= '<div class="grades-section">
        <div class="group-header">GROUPE A : MATIÈRES PRINCIPALES</div>
        <table class="subjects-table">
            <thead>
                <tr>
                    <th style="width: 15%;">DISCIPLINE</th>
                    <th style="width: 8%;">Sequence 1</th>
                    <th style="width: 8%;">Sequence 2</th>
                    <th style="width: 8%;">Compo1</th>
                    <th style="width: 8%;">Moy./20</th>
                    <th style="width: 6%;">COEF.</th>
                    <th style="width: 8%;">(NXC)</th>
                    <th style="width: 8%;">TOTAL</th>
                    <th style="width: 6%;">RANG</th>
                    <th style="width: 12%;">COMPÉTENCES</th>
                    <th style="width: 13%;">NOMS DES PROFESSEURS</th>
                </tr>
            </thead>
            <tbody>';

    $totalCoef = 0;
    $totalNXC = 0;

    foreach ($subjects as $subject) {
        $gradeClass = '';
        if ($subject['average'] >= 16) $gradeClass = 'grade-excellent';
        elseif ($subject['average'] >= 14) $gradeClass = 'grade-good';
        elseif ($subject['average'] >= 10) $gradeClass = 'grade-average';

        $totalCoef += $subject['coefficient'];
        $totalNXC += $subject['nxc'];

        $html .= '<tr>
            <td style="text-align: left;">' . strtoupper($subject['name']) . '</td>
            <td>' . number_format($subject['sequence1'], 2) . '</td>
            <td>' . number_format($subject['sequence2'], 2) . '</td>
            <td>' . number_format($subject['composition'], 2) . '</td>
            <td class="' . $gradeClass . '">' . number_format($subject['average'], 2) . '</td>
            <td>' . number_format($subject['coefficient'], 2) . '</td>
            <td class="' . $gradeClass . '">' . number_format($subject['nxc'], 2) . '</td>
            <td class="' . $gradeClass . '">' . number_format($subject['nxc'], 2) . '</td>
            <td>' . $subject['rank'] . '</td>
            <td style="font-size: 11px;">' . $subject['competence'] . '</td>
            <td style="font-size: 11px;">' . strtoupper($subject['teacher']) . '</td>
        </tr>';
    }

    $groupAverage = $totalNXC / $totalCoef;

    $html .= '
            <tr class="total-row">
                <td style="text-align: left;">TOTAL</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>' . number_format($groupAverage, 2) . '</td>
                <td>' . number_format($totalCoef, 2) . '</td>
                <td>' . number_format($totalNXC, 2) . '</td>
                <td>' . number_format($totalNXC, 2) . '</td>
                <td>1</td>
                <td style="font-size: 10px;">GROUPE A</td>
                <td style="font-size: 10px;">Moy Gpe: ' . number_format($groupAverage, 2) . '</td>
            </tr>
            </tbody>
        </table>
    </div>';

    $html .= '
    <div style="margin-top: 30px;">
        <h3>📊 RÉCAPITULATIF GÉNÉRAL</h3>
        <p><strong>Total Coefficients:</strong> ' . number_format($totalCoef, 2) . '</p>
        <p><strong>Total NXC:</strong> ' . number_format($totalNXC, 2) . '</p>
        <p><strong>Moyenne Générale:</strong> ' . number_format($groupAverage, 2) . '/20</p>
        <p><strong>Rang de la classe:</strong> 2e/45</p>
        <p><strong>Appréciation:</strong> ' . getAppreciation($groupAverage) . '</p>
    </div>

    <div style="margin-top: 30px; border: 2px solid #007bff; padding: 15px; background: #f8f9fa;">
        <h3 style="color: #007bff;">🎯 CARACTÉRISTIQUES DEUXIÈME CYCLE</h3>
        <ul>
            <li>✅ <strong>Affichage transparent:</strong> Sequence 1 et Sequence 2 visibles séparément</li>
            <li>✅ <strong>Calcul équilibré:</strong> (Seq1 + Seq2 + Compo) / 3</li>
            <li>✅ <strong>11 colonnes détaillées:</strong> Plus d\'informations que le Premier Cycle</li>
            <li>✅ <strong>Compétences précises:</strong> "Acquise (Excellent)", "En cours d\'acquisition"</li>
            <li>✅ <strong>Enseignants obligatoires:</strong> Noms complets affichés</li>
            <li>✅ <strong>NXC = TOTAL:</strong> Cohérence dans l\'affichage</li>
        </ul>
    </div>

    </body>
    </html>';

    return $html;
}

function getAppreciation($average) {
    if ($average >= 16) return 'Excellent (Très Bien)';
    if ($average >= 14) return 'Très Bien';
    if ($average >= 12) return 'Bien';
    if ($average >= 10) return 'Assez Bien (Passable)';
    return 'Non Acquise (NA)';
}

// Générer le HTML
$htmlContent = generateDeuxiemeCycleHTML($testSubjects);

// Sauvegarder le fichier
$htmlFile = __DIR__ . '/bulletin_deuxieme_cycle_demo.html';
file_put_contents($htmlFile, $htmlContent);

echo "✅ Bulletin HTML généré avec succès!\n";
echo "📁 Fichier: $htmlFile\n\n";

echo "🔍 VÉRIFICATION DU CONTENU:\n";
echo "============================\n";

// Vérifications automatiques
$checks = [
    'Sequence 1' => strpos($htmlContent, 'Sequence 1') !== false,
    'Sequence 2' => strpos($htmlContent, 'Sequence 2') !== false,
    'Moy./20' => strpos($htmlContent, 'Moy./20') !== false,
    '11 colonnes' => substr_count($htmlContent, '<th style=') >= 11,
    'COMPÉTENCES' => strpos($htmlContent, 'COMPÉTENCES') !== false,
    'Acquise (Excellent)' => strpos($htmlContent, 'Acquise (Excellent)') !== false,
    'NGUEPINSE KAMGANG' => strpos($htmlContent, 'NGUEPINSE KAMGANG') !== false,
    'EPS: 17.00' => strpos($htmlContent, '17.00') !== false,
    'NXC: 68.00' => strpos($htmlContent, '68.00') !== false
];

foreach ($checks as $test => $result) {
    echo ($result ? "✅" : "❌") . " $test\n";
}

echo "\n🎉 DÉMONSTRATION TERMINÉE!\n";
echo "==========================\n";
echo "Ouvrez le fichier HTML dans votre navigateur pour voir:\n";
echo "- Les 11 colonnes du DEUXIÈME CYCLE\n";
echo "- L'affichage séparé des séquences 1 et 2\n";
echo "- Les compétences détaillées\n";
echo "- Les calculs (Seq1 + Seq2 + Compo) / 3\n";
echo "- L'exemple EPS: 15.00 + 16.00 + 20.00 = 17.00 × 4.00 = 68.00\n\n";

// Afficher l'exemple EPS pour vérification
echo "📋 EXEMPLE LIGNE EPS:\n";
echo "======================\n";
$epsData = $testSubjects[3]; // EPS est le 4e élément
echo "Discipline: {$epsData['name']}\n";
echo "Sequence 1: {$epsData['sequence1']}\n";
echo "Sequence 2: {$epsData['sequence2']}\n";
echo "Compo1: {$epsData['composition']}\n";
echo "Calcul: ({$epsData['sequence1']} + {$epsData['sequence2']} + {$epsData['composition']}) / 3 = {$epsData['average']}\n";
echo "COEF: {$epsData['coefficient']}\n";
echo "NXC: {$epsData['average']} × {$epsData['coefficient']} = {$epsData['nxc']}\n";
echo "TOTAL: {$epsData['nxc']} (= NXC)\n";
echo "RANG: {$epsData['rank']}\n";
echo "COMPÉTENCES: {$epsData['competence']}\n";
echo "ENSEIGNANT: {$epsData['teacher']}\n\n";

echo "🚀 Fichier prêt à être consulté: $htmlFile\n";