<?php

/**
 * 🎓 GÉNÉRATION DE BULLETIN PDF POUR LE PREMIER CYCLE (6ÈME)
 * Ce script génère un bulletin trimestriel pour un élève fictif de 6ème.
 * Il combine le template HTML de base avec des données spécifiques au premier cycle.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "🚀 Démarrage de la génération du bulletin de 6ème...\n";

// 1. CHARGER LE TEMPLATE HTML DE BASE
$templatePath = __DIR__ . '/resources/views/bulletins/cpbd_bulletin_pdf.html';
if (!file_exists($templatePath)) {
    die("❌ Erreur: Le fichier template n'a pas été trouvé à l'emplacement: " . $templatePath);
}
$htmlTemplate = file_get_contents($templatePath);
echo "✅ Template HTML chargé.\n";

// 2. DÉFINIR LES DONNÉES FICTIVES POUR UN ÉLÈVE DE 6ÈME
$studentData = [
    'logo_base64' => '', // Le logo est souvent une image encodée en base64
    'bulletin_title' => 'BULLETIN DE NOTES - PREMIER TRIMESTRE',
    'name_label' => 'Nom(s) et Prénom(s):',
    'student_name' => 'KAMDEM LEROY',
    'birth_date_label' => 'Né(e) le:',
    'birth_date' => '10/01/2013',
    'birth_place_label' => 'à:',
    'birth_place' => 'DOUALA',
    'class_label' => 'Classe:',
    'class_name' => '6ème B',
    'class_size_label' => 'Effectif:',
    'class_size' => '68',
    'main_teacher_label' => 'Prof. Titulaire:',
    'main_teacher' => 'M. NGUYEN',
    'student_number_label' => 'Matricule:',
    'student_number' => '24A912',
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

// Données des matières pour le premier cycle (7 colonnes)
$subjects = [
    // GROUPE A
    ['group' => 'GROUPE A : MATIÈRES LITTÉRAIRES', 'coef_total' => 15, 'subjects' => [
        ['name' => 'ANGLAIS', 'note' => 14.50, 'coef' => 3, 'rank' => 12, 'competence' => 'A', 'teacher' => 'MME. FONG'],
        ['name' => 'ÉTUDE DE TEXTE', 'note' => 16.00, 'coef' => 2, 'rank' => 5, 'competence' => 'A+', 'teacher' => 'M. NGUYEN'],
        ['name' => 'EXPRESSION ÉCRITE', 'note' => 13.00, 'coef' => 2, 'rank' => 15, 'competence' => 'A', 'teacher' => 'M. NGUYEN'],
        ['name' => 'HISTOIRE', 'note' => 17.50, 'coef' => 2, 'rank' => 2, 'competence' => 'A+', 'teacher' => 'M. ABENA'],
        ['name' => 'GÉOGRAPHIE', 'note' => 15.50, 'coef' => 2, 'rank' => 8, 'competence' => 'A', 'teacher' => 'M. ABENA'],
        ['name' => 'LANGUES ET CULTURES', 'note' => 14.00, 'coef' => 2, 'rank' => 11, 'competence' => 'A', 'teacher' => 'MME. DIOP'],
        ['name' => 'ORTHOGRAPHE', 'note' => 12.00, 'coef' => 2, 'rank' => 20, 'competence' => 'ECA', 'teacher' => 'M. NGUYEN'],
    ]],
    // GROUPE B
    ['group' => 'GROUPE B : MATIÈRES SCIENTIFIQUES', 'coef_total' => 6, 'subjects' => [
        ['name' => 'MATHÉMATIQUES', 'note' => 18.00, 'coef' => 4, 'rank' => 1, 'competence' => 'A+', 'teacher' => 'M. KAMGA'],
        ['name' => 'SVT', 'note' => 16.50, 'coef' => 2, 'rank' => 4, 'competence' => 'A+', 'teacher' => 'MME. OUMAR'],
    ]],
    // GROUPE C
    ['group' => 'GROUPE C : MATIÈRES PRATIQUES', 'coef_total' => 6, 'subjects' => [
        ['name' => 'EPS', 'note' => 19.00, 'coef' => 2, 'rank' => 1, 'competence' => 'A+', 'teacher' => 'M. TCHINDA'],
        ['name' => 'INFORMATIQUE', 'note' => 17.00, 'coef' => 2, 'rank' => 3, 'competence' => 'A+', 'teacher' => 'M. BAO'],
        ['name' => 'TRAVAIL MANUEL', 'note' => 15.00, 'coef' => 2, 'rank' => 9, 'competence' => 'A', 'teacher' => 'MME. SOH'],
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
                            <th>DISCIPLINE</th>
                            <th>NOTES /20</th>
                            <th>COEF.</th>
                            <th>(NXC)</th>
                            <th>RANG</th>
                            <th>COMPÉTENCES</th>
                            <th>NOMS DES PROFESSEURS</th>
                          </tr></thead><tbody>';

    $groupTotalNXC = 0;
    foreach ($group['subjects'] as $subject) {
        $nxc = $subject['note'] * $subject['coef'];
        $groupTotalNXC += $nxc;
        $subjectGroupsHTML .= '<tr>
                                <td class="subject-name">' . $subject['name'] . '</td>
                                <td>' . number_format($subject['note'], 2) . '</td>
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
                            <td></td>
                            <td>' . number_format($group['coef_total'], 2) . '</td>
                            <td>' . number_format($groupTotalNXC, 2) . '</td>
                            <td colspan="3">Moyenne Groupe: ' . number_format($groupAverage, 2) . '</td>
                          </tr>';

    $subjectGroupsHTML .= '</tbody></table></div>';
}
echo "✅ HTML des matières généré.\n";

// 4. METTRE À JOUR LES DONNÉES GÉNÉRALES
$studentData['total_general'] = number_format($totalGeneral, 2);
$studentData['total_coef'] = number_format($totalCoef, 2);
$evaluationAverage = $totalGeneral / $totalCoef;
$studentData['evaluation_average'] = number_format($evaluationAverage, 2);
$studentData['student_rank'] = '5';
$studentData['class_average'] = '12.55';
$studentData['first_average'] = '18.90';
$studentData['last_average'] = '7.15';

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

    $pdfFile = __DIR__ . '/bulletin_6eme_trimestre1.pdf';
    file_put_contents($pdfFile, $dompdf->output());

    echo "✅ PDF généré avec succès!\n";
    echo "📁 Fichier disponible à l'emplacement: " . $pdfFile . "\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de la génération du PDF: " . $e->getMessage() . "\n";
}

echo "🎉 Processus terminé.\n";

?>