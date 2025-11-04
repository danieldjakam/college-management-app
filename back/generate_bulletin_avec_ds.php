<?php

/**
 * 🎓 GÉNÉRATION DE BULLETIN PDF (6ÈME) AVEC SÉQUENCES ET DS
 * Ce script génère un bulletin trimestriel incluant les colonnes Séquence 1, Séquence 2, et DS.
 * La moyenne est calculée comme (Seq1 + Seq2 + DS) / 3.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "🚀 Démarrage de la génération du bulletin de 6ème (avec DS)...\n";

// 1. CHARGER LE TEMPLATE HTML DE BASE
$templatePath = __DIR__ . '/resources/views/bulletins/cpbd_bulletin_pdf.html';
if (!file_exists($templatePath)) {
    die("❌ Erreur: Le fichier template n'a pas été trouvé à l'emplacement: " . $templatePath);
}
$htmlTemplate = file_get_contents($templatePath);
echo "✅ Template HTML chargé.\n";

// 2. DÉFINIR LES DONNÉES FICTIVES POUR UN ÉLÈVE DE 6ÈME
$studentData = [
    'logo_base64' => '',
    'bulletin_title' => 'BULLETIN DE NOTES - PREMIER TRIMESTRE (AVEC DS)',
    'name_label' => 'Nom(s) et Prénom(s):',
    'student_name' => 'MBAPPE KYLIAN',
    'birth_date_label' => 'Né(e) le:',
    'birth_date' => '20/12/2012',
    'birth_place_label' => 'à:',
    'birth_place' => 'PARIS',
    'class_label' => 'Classe:',
    'class_name' => '6ème A',
    'class_size_label' => 'Effectif:',
    'class_size' => '72',
    'main_teacher_label' => 'Prof. Titulaire:',
    'main_teacher' => 'M. ZIDANE',
    'student_number_label' => 'Matricule:',
    'student_number' => '24A100',
    'evaluation_label' => 'Évaluation:',
    'evaluation_number' => '1',
    'school_year_label' => 'Année Scolaire:',
    'school_year' => '2024/2025',
    'discipline_header' => 'DISCIPLINE',
    'visa_parent_label' => 'VISA & NOMS DU PARENT',
    'general_results_header' => 'RÉSULTATS GÉNÉRAUX',
    'total_general_label' => 'TOTAL GÉNÉRAL:',
    'total_coef_label' => 'TOTAL COEF:',
    'evaluation_average_label' => 'MOY. TRIMESTRE:',
    'rank_label' => 'RANG:',
    'class_average_label' => 'MOY. GEN. CLASSE:',
    'first_average_label' => 'MOY. PREMIER:',
    'last_average_label' => 'MOY. DERNIER:',
    'legend_title' => 'Légende Compétences:',
    'appreciation_header' => 'APPRÉCIATIONS DU TRAVAIL ET OBSERVATIONS',
    'made_in_douala' => 'Fait à Douala, le:',
    'current_date' => date('d/m/Y'),
    'principal_title' => 'LE DIRECTEUR DES ÉTUDES'
];

// Données des matières avec seq1, seq2, ds
$subjects = [
    ['group' => 'GROUPE A : MATIÈRES LITTÉRAIRES', 'coef_total' => 13, 'subjects' => [
        ['name' => 'ANGLAIS', 'seq1' => 12.00, 'seq2' => 14.50, 'ds' => 13.00, 'coef' => 3, 'rank' => 18, 'competence' => 'A', 'teacher' => 'MME. FONG'],
        ['name' => 'ÉTUDE DE TEXTE', 'seq1' => 15.00, 'seq2' => 16.00, 'ds' => 17.50, 'coef' => 2, 'rank' => 4, 'competence' => 'A+', 'teacher' => 'M. NGUYEN'],
        ['name' => 'EXPRESSION ÉCRITE', 'seq1' => 11.00, 'seq2' => 13.50, 'ds' => 12.00, 'coef' => 2, 'rank' => 25, 'competence' => 'ECA', 'teacher' => 'M. NGUYEN'],
        ['name' => 'HISTOIRE', 'seq1' => 18.00, 'seq2' => 17.00, 'ds' => 19.00, 'coef' => 2, 'rank' => 1, 'competence' => 'A+', 'teacher' => 'M. ABENA'],
        ['name' => 'GÉOGRAPHIE', 'seq1' => 16.50, 'seq2' => 15.00, 'ds' => 14.00, 'coef' => 2, 'rank' => 10, 'competence' => 'A', 'teacher' => 'M. ABENA'],
        ['name' => 'LANGUES ET CULTURES', 'seq1' => 13.00, 'seq2' => 14.00, 'ds' => 15.00, 'coef' => 2, 'rank' => 15, 'competence' => 'A', 'teacher' => 'MME. DIOP'],
    ]],
    ['group' => 'GROUPE B : MATIÈRES SCIENTIFIQUES', 'coef_total' => 6, 'subjects' => [
        ['name' => 'MATHÉMATIQUES', 'seq1' => 19.50, 'seq2' => 18.00, 'ds' => 20.00, 'coef' => 4, 'rank' => 1, 'competence' => 'A+', 'teacher' => 'M. KAMGA'],
        ['name' => 'SVT', 'seq1' => 17.00, 'seq2' => 16.50, 'ds' => 18.00, 'coef' => 2, 'rank' => 3, 'competence' => 'A+', 'teacher' => 'MME. OUMAR'],
    ]],
    ['group' => 'GROUPE C : MATIÈRES PRATIQUES', 'coef_total' => 4, 'subjects' => [
        ['name' => 'EPS', 'seq1' => 18.00, 'seq2' => 19.00, 'ds' => 20.00, 'coef' => 2, 'rank' => 1, 'competence' => 'A+', 'teacher' => 'M. TCHINDA'],
        ['name' => 'INFORMATIQUE', 'seq1' => 16.00, 'seq2' => 17.50, 'ds' => 18.00, 'coef' => 2, 'rank' => 2, 'competence' => 'A+', 'teacher' => 'M. BAO'],
    ]],
];

// 3. CONSTRUIRE LE HTML DES MATIÈRES
$subjectGroupsHTML = '';
$totalGeneral = 0;
$totalCoef = 0;

foreach ($subjects as $group) {
    $subjectGroupsHTML .= '<div class="grades-section">';
    $subjectGroupsHTML .= '<div class="group-header">' . $group['group'] . '</div>';
    $subjectGroupsHTML .= '<table class="subjects-table">';
    $subjectGroupsHTML .= '<thead><tr>
                            <th style="width: 20%;">DISCIPLINE</th>
                            <th style="width: 8%;">Séquence 1</th>
                            <th style="width: 8%;">Séquence 2</th>
                            <th style="width: 8%;">DS</th>
                            <th style="width: 8%;">MOY. /20</th>
                            <th style="width: 6%;">COEF.</th>
                            <th style="width: 8%;">(NXC)</th>
                            <th style="width: 8%;">RANG</th>
                            <th style="width: 10%;">COMPÉTENCES</th>
                            <th style="width: 16%;">NOMS DES PROFESSEURS</th>
                          </tr></thead><tbody>';

    $groupTotalNXC = 0;
    foreach ($group['subjects'] as $subject) {
        $average = ($subject['seq1'] + $subject['seq2'] + $subject['ds']) / 3;
        $nxc = $average * $subject['coef'];
        $groupTotalNXC += $nxc;
        $subjectGroupsHTML .= '<tr>
                                <td class="subject-name">' . $subject['name'] . '</td>
                                <td>' . number_format($subject['seq1'], 2) . '</td>
                                <td>' . number_format($subject['seq2'], 2) . '</td>
                                <td>' . number_format($subject['ds'], 2) . '</td>
                                <td>' . number_format($average, 2) . '</td>
                                <td>' . number_format($subject['coef'], 2) . '</td>
                                <td>' . number_format($nxc, 2) . '</td>
                                <td>' . $subject['rank'] . 'e</td>
                                <td>' . $subject['competence'] . '</td>
                                <td>' . $subject['teacher'] . '</td>
                              </tr>';
    }
    
    $totalGeneral += $groupTotalNXC;
    $totalCoef += $group['coef_total'];
    $groupAverage = $groupTotalNXC / $group['coef_total'];

    $subjectGroupsHTML .= '<tr class="total-row">
                            <td class="subject-name">TOTAL</td>
                            <td colspan="3"></td>
                            <td>' . number_format($groupAverage, 2) . '</td>
                            <td>' . number_format($group['coef_total'], 2) . '</td>
                            <td>' . number_format($groupTotalNXC, 2) . '</td>
                            <td colspan="3">Moyenne Groupe: ' . number_format($groupAverage, 2) . '</td>
                          </tr>';

    $subjectGroupsHTML .= '</tbody></table></div>';
}
echo "✅ HTML des matières (avec DS) généré.\n";

// 4. METTRE À JOUR LES DONNÉES GÉNÉRALES
$studentData['total_general'] = number_format($totalGeneral, 2);
$studentData['total_coef'] = number_format($totalCoef, 2);
$evaluationAverage = $totalGeneral / $totalCoef;
$studentData['evaluation_average'] = number_format($evaluationAverage, 2);
$studentData['student_rank'] = '3';
$studentData['class_average'] = '13.10';
$studentData['first_average'] = '19.25';
$studentData['last_average'] = '6.90';

if ($evaluationAverage >= 16) {
    $studentData['general_appreciation'] = 'Excellent travail. Félicitations du conseil de classe.';
    $studentData['average_class'] = 'grade-excellent';
} elseif ($evaluationAverage >= 14) {
    $studentData['general_appreciation'] = 'Très bon travail. Encouragements.';
    $studentData['average_class'] = 'grade-good';
} else {
    $studentData['general_appreciation'] = 'Travail satisfaisant. Poursuivez vos efforts.';
    $studentData['average_class'] = 'grade-average';
}

// 5. REMPLACER LES PLACEHOLDERS DANS LE TEMPLATE
$finalHTML = str_replace('{{#each subject_groups}}{{/each}}', $subjectGroupsHTML, $htmlTemplate);
foreach ($studentData as $key => $value) {
    $finalHTML = str_replace('{{' . $key . '}}', $value, $finalHTML);
}
echo "✅ Placeholders remplacés.\n";

// 6. GÉNÉRER LE PDF
echo "📄 Génération du PDF en cours...\n";
try {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($finalHTML);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfFile = __DIR__ . '/bulletin_6eme_avec_ds.pdf';
    file_put_contents($pdfFile, $dompdf->output());

    echo "✅ PDF généré avec succès!\n";
    echo "📁 Fichier disponible à l'emplacement: " . $pdfFile . "\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de la génération du PDF: " . $e->getMessage() . "\n";
}

echo "🎉 Processus terminé.\n";

?>