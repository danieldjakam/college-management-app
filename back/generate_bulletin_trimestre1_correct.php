<?php

/**
 * 🎓 GÉNÉRATION DE BULLETIN PDF (PREMIER CYCLE - TRIMESTRE 1) - LOGIQUE CORRECTE
 * Ce script génère un bulletin trimestriel en suivant la logique découverte dans BulletinService.php
 * 1. DS1 = (Seq1 + Seq2) / 2
 * 2. Moyenne = (DS1 + Composition) / 2
 * 3. Affiche DS1, Composition, et Moyenne (mais pas Seq1 et Seq2)
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "🚀 Démarrage de la génération du bulletin T1 (logique correcte)...
";

// 1. CHARGER LE TEMPLATE HTML DE BASE
$templatePath = __DIR__ . '/resources/views/bulletins/cpbd_bulletin_pdf.html';
if (!file_exists($templatePath)) {
    die("❌ Erreur: Le fichier template n'a pas été trouvé.");
}
$htmlTemplate = file_get_contents($templatePath);
echo "✅ Template HTML chargé.
";

// 2. DÉFINIR LES DONNÉES FICTIVES
$studentData = [
    'bulletin_title' => 'BULLETIN DE NOTES - PREMIER TRIMESTRE',
    'student_name' => 'NGONO MARIE CLAIRE',
    'birth_date' => '12/05/2013',
    'birth_place' => 'BERTOUA',
    'class_name' => '6ème C',
    'class_size' => '70',
    'main_teacher' => 'M. ONDOA',
    'student_number' => '24A777',
    'evaluation_number' => '1',
    'school_year' => '2024/2025',
    'current_date' => date('d/m/Y'),
];

// Données des matières avec les notes de séquence et de composition
$subjectsData = [
    ['group' => 'GROUPE A : MATIÈRES LITTÉRAIRES', 'coef_total' => 13, 'subjects' => [
        ['name' => 'ANGLAIS', 'seq1' => 14.00, 'seq2' => 16.00, 'compo1' => 15.50, 'coef' => 3, 'rank' => 10, 'teacher' => 'MME. FONG'],
        ['name' => 'ÉTUDE DE TEXTE', 'seq1' => 18.00, 'seq2' => 17.00, 'compo1' => 16.00, 'coef' => 2, 'rank' => 3, 'teacher' => 'M. ONDOA'],
        ['name' => 'HISTOIRE', 'seq1' => 12.50, 'seq2' => 13.50, 'compo1' => 14.00, 'coef' => 2, 'rank' => 15, 'teacher' => 'M. ABENA'],
        ['name' => 'GÉOGRAPHIE', 'seq1' => 15.00, 'seq2' => 15.00, 'compo1' => 15.00, 'coef' => 2, 'rank' => 9, 'teacher' => 'M. ABENA'],
    ]],
    ['group' => 'GROUPE B : MATIÈRES SCIENTIFIQUES', 'coef_total' => 6, 'subjects' => [
        ['name' => 'MATHÉMATIQUES', 'seq1' => 19.00, 'seq2' => 20.00, 'compo1' => 18.00, 'coef' => 4, 'rank' => 1, 'teacher' => 'M. KAMGA'],
        ['name' => 'SVT', 'seq1' => 16.00, 'seq2' => 17.00, 'compo1' => 15.00, 'coef' => 2, 'rank' => 5, 'teacher' => 'MME. OUMAR'],
    ]],
];

// 3. CALCULER LES MOYENNES SELON LA LOGIQUE MÉTIER
$totalGeneral = 0;
$totalCoef = 0;
$processedSubjects = [];

foreach ($subjectsData as $group) {
    $processedGroup = $group;
    $processedGroup['subjects'] = [];
    $groupTotalNXC = 0;
    
    foreach ($group['subjects'] as $subject) {
        $currentSub = $subject;
        // Calcul du DS1
        $currentSub['ds1'] = ($subject['seq1'] + $subject['seq2']) / 2;
        // Calcul de la moyenne trimestrielle
        $currentSub['average'] = ($currentSub['ds1'] + $subject['compo1']) / 2;
        // Calcul du total pondéré (NXC)
        $currentSub['nxc'] = $currentSub['average'] * $subject['coef'];
        
        $groupTotalNXC += $currentSub['nxc'];
        $processedGroup['subjects'][] = $currentSub;
    }

    $totalGeneral += $groupTotalNXC;
    $totalCoef += $group['coef_total'];
    $processedGroup['group_average'] = $groupTotalNXC / $group['coef_total'];
    $processedSubjects[] = $processedGroup;
}
echo "✅ Calcul des moyennes (DS et Trimestre) effectué.
";

// 4. CONSTRUIRE LE HTML DES MATIÈRES
$subjectGroupsHTML = '';
foreach ($processedSubjects as $group) {
    $subjectGroupsHTML .= '<div class="grades-section">';
    $subjectGroupsHTML .= '<div class="group-header">' . $group['group'] . '</div>';
    $subjectGroupsHTML .= '<table class="subjects-table"><thead><tr>
                            <th style="width: 25%;">DISCIPLINE</th>
                            <th style="width: 10%;">DS1</th>
                            <th style="width: 10%;">Compo1</th>
                            <th style="width: 10%;">Moy</th>
                            <th style="width: 8%;">COEF.</th>
                            <th style="width: 10%;">(NXC)</th>
                            <th style="width: 7%;">RANG</th>
                            <th style="width: 20%;">NOMS DES PROFESSEURS</th>
                          </tr></thead><tbody>';

    foreach ($group['subjects'] as $subject) {
        $subjectGroupsHTML .= '<tr>
                                <td class="subject-name">' . $subject['name'] . '</td>
                                <td>' . number_format($subject['ds1'], 2) . '</td>
                                <td>' . number_format($subject['compo1'], 2) . '</td>
                                <td>' . number_format($subject['average'], 2) . '</td>
                                <td>' . number_format($subject['coef'], 2) . '</td>
                                <td>' . number_format($subject['nxc'], 2) . '</td>
                                <td>' . $subject['rank'] . 'e</td>
                                <td>' . $subject['teacher'] . '</td>
                              </tr>';
    }

    $subjectGroupsHTML .= '<tr class="total-row">
                            <td class="subject-name">TOTAL</td>
                            <td colspan="2"></td>
                            <td>' . number_format($group['group_average'], 2) . '</td>
                            <td>' . number_format($group['coef_total'], 2) . '</td>
                            <td colspan="3">Moyenne Groupe: ' . number_format($group['group_average'], 2) . '</td>
                          </tr>';
    $subjectGroupsHTML .= '</tbody></table></div>';
}
echo "✅ HTML des matières généré avec les bonnes colonnes.
";

// 5. METTRE À JOUR LES DONNÉES GÉNÉRALES
$evaluationAverage = $totalGeneral / $totalCoef;
$studentData['total_general'] = number_format($totalGeneral, 2);
$studentData['total_coef'] = number_format($totalCoef, 2);
$studentData['evaluation_average'] = number_format($evaluationAverage, 2);
$studentData['student_rank'] = '4';
$studentData['class_average'] = '12.88';
$studentData['first_average'] = '18.95';
$studentData['last_average'] = '7.50';
$studentData['general_appreciation'] = 'Très bon travail. Encouragements.';
$studentData['average_class'] = 'grade-good';

// Remplacer les placeholders
$finalHTML = str_replace('{{#each subject_groups}}{{/each}}', $subjectGroupsHTML, $htmlTemplate);
foreach ($studentData as $key => $value) {
    $finalHTML = str_replace('{{' . $key . '}}', $value, $finalHTML);
}
// Remplacer les labels manquants
$finalHTML = preg_replace('/\{\{.*?\}\} /', '-', $finalHTML);
echo "✅ Placeholders remplacés.
";

// 6. GÉNÉRER LE PDF
echo "📄 Génération du PDF en cours...
";
try {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($finalHTML);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfFile = __DIR__ . '/bulletin_trimestre1_premier_cycle.pdf';
    file_put_contents($pdfFile, $dompdf->output());

    echo "✅ PDF généré avec succès!
";
    echo "📁 Fichier: " . $pdfFile . "
";

} catch (Exception $e) {
    echo "❌ Erreur PDF: " . $e->getMessage() . "
";
}

echo "🎉 Processus terminé.
";

?>