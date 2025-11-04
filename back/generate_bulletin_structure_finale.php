<?php

/**
 * 🎓 GÉNÉRATION BULLETIN APC - STRUCTURE FINALE
 * Basé exactement sur le dessin final de l'utilisateur
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "🚀 Génération du bulletin APC (structure finale)...\n";

// 1. CHARGER LE TEMPLATE
$templatePath = __DIR__ . '/resources/views/bulletins/bulletin_apc_structure_finale.html';
if (!file_exists($templatePath)) {
    die("❌ Template introuvable\n");
}
$htmlTemplate = file_get_contents($templatePath);
echo "✅ Template chargé\n";

// 2. DONNÉES ÉLÈVE
$studentData = [
    'student_name' => 'NGONO MARIE CLAIRE',
    'birth_date' => '15/03/2013',
    'birth_place' => 'DOUALA',
    'gender' => 'Féminin',
    'unique_id' => '24A0856FR',
    'class_level' => '6',
    'class_section' => 'A',
    'class_size' => '72',
    'main_teacher' => 'M. TCHAMENI MATHIEU',
    'parent_info' => 'Mme NGONO Sylvie - Tél: +237 677 123 456',
    'school_year' => '2024/2025',
];

// 3. FONCTION COTE
function getCote($avg) {
    if ($avg >= 18) return 'A+';
    if ($avg >= 16) return 'A';
    if ($avg >= 15) return 'B+';
    if ($avg >= 14) return 'B';
    if ($avg >= 12) return 'C+';
    if ($avg >= 10) return 'C';
    return 'D';
}

function getCoteClass($cote) {
    return 'cote-' . strtolower(str_replace('+', '-plus', $cote));
}

// 4. MATIÈRES - 13 matières du 1er cycle
$subjects = [
    ['name' => 'FRANÇAIS', 'teacher' => 'M. TCHAMENI MATHIEU', 'coef' => 4,
        'comp' => [
            'Produire à l\'écrit, un texte descriptif et, à l\'oral, un commentaire de l\'image.',
            'Produce in writing a descriptive text and orally a commentary on the image.'
        ],
        'notes' => [14.50, 15.00], 'minmax' => '[8,5 - 18,5]'],

    ['name' => 'ANGLAIS', 'teacher' => 'Mme FONG SARA', 'coef' => 3,
        'comp' => [
            'Utiliser les ressources linguistiques appropriées pour écouter, parler, lire et écrire sur l\'intégration nationale.',
            'Use appropriate language resources to listen, speak, read and write about national integration.'
        ],
        'notes' => [13.00, 14.00], 'minmax' => '[6,0 - 17,0]'],

    ['name' => 'MATHÉMATIQUES', 'teacher' => 'M. KAMGA PIERRE', 'coef' => 4,
        'comp' => [
            'Résoudre des situations problèmes relatives à l\'arithmétique et aux nombres réels.',
            'Solve problem situations relating to arithmetic and real numbers.'
        ],
        'notes' => [16.50, 17.00], 'minmax' => '[4,0 - 19,0]'],

    ['name' => 'HISTOIRE', 'teacher' => 'M. ABENA MARTIN', 'coef' => 2,
        'comp' => [
            'Lutter contre la domination étrangère',
            'Fight against foreign domination'
        ],
        'notes' => [15.00, 14.50], 'minmax' => '[7,5 - 17,5]'],

    ['name' => 'GÉOGRAPHIE', 'teacher' => 'M. ABENA MARTIN', 'coef' => 2,
        'comp' => [
            'Gérer durablement l\'environnement',
            'Manage the environment sustainably'
        ],
        'notes' => [13.00, 13.50], 'minmax' => '[6,0 - 16,0]'],

    ['name' => 'SVTEEHB', 'teacher' => 'Mme OUMAR FATIMA', 'coef' => 2,
        'comp' => [
            'Résoudre les situations-problèmes relatives à la récurrence des anomalies.',
            'Solve problem situations related to the recurrence of anomalies.'
        ],
        'notes' => [14.00, 15.00], 'minmax' => '[5,0 - 18,0]'],

    ['name' => 'PCT', 'teacher' => 'M. BELLO HASSAN', 'coef' => 2,
        'comp' => [
            'Résoudre des situations-problèmes se rapportant aux transformations chimiques.',
            'Solve problem situations relating to chemical transformations.'
        ],
        'notes' => [12.50, 13.00], 'minmax' => '[4,5 - 16,5]'],

    ['name' => 'INFORMATIQUE', 'teacher' => 'Mme MEKUATE JUDITH', 'coef' => 2,
        'comp' => [
            'Décrire l\'architecture d\'un microordinateur.',
            'Describe the architecture of a microcomputer.'
        ],
        'notes' => [16.00, 15.50], 'minmax' => '[8,0 - 18,5]'],

    ['name' => 'CULTURES NATIONALES', 'teacher' => 'Mme NDONDOCK', 'coef' => 1,
        'comp' => [
            'Produire un discours sur un mets traditionnel.',
            'Produce a speech on a traditional dish.'
        ],
        'notes' => [14.00, 14.50], 'minmax' => '[9,0 - 17,0]'],

    ['name' => 'ÉDUCATION CITOYENNETÉ', 'teacher' => 'M. OWONO MVENG', 'coef' => 2,
        'comp' => [
            'Eduquer les masses et s\'impliquer dans le processus électoral',
            'Educate the masses and get involved in the electoral process'
        ],
        'notes' => [13.50, 14.00], 'minmax' => '[7,0 - 16,5]'],

    ['name' => 'EPS', 'teacher' => 'M. TONFACK BERTRAND', 'coef' => 2,
        'comp' => [
            'Réaliser une course de vitesse.',
            'Perform a speed race.'
        ],
        'notes' => [15.00, 16.00], 'minmax' => '[10,0 - 18,0]'],

    ['name' => 'TRAVAIL MANUEL', 'teacher' => 'Mme NOUTAMOUN FLORE', 'coef' => 1,
        'comp' => [
            'Modeler et mouler un pot en argile.',
            'Model and mold a clay pot.'
        ],
        'notes' => [14.00, 13.50], 'minmax' => '[8,5 - 16,0]'],

    ['name' => 'ÉDUCATION ARTISTIQUE', 'teacher' => 'Mme SOH DIANE', 'coef' => 1,
        'comp' => [
            'Nommer des artistes camerounais et reproduire une œuvre d\'art',
            'Name Cameroonian artists and reproduce a work of art'
        ],
        'notes' => [15.00, 15.50], 'minmax' => '[9,0 - 17,5]'],
];

// 5. GÉNÉRER HTML DES MATIÈRES
$subjectsHTML = '';
$totalGeneral = 0;
$totalCoef = 0;

foreach ($subjects as $s) {
    $avg = array_sum($s['notes']) / count($s['notes']);
    $total = $avg * $s['coef'];
    $totalGeneral += $total;
    $totalCoef += $s['coef'];

    $cote = getCote($avg);
    $coteClass = getCoteClass($cote);
    $nbComp = count($s['comp']);

    // Première ligne
    $subjectsHTML .= '<tr>';
    $subjectsHTML .= '<td class="subject-col" rowspan="' . $nbComp . '"><b>' . $s['name'] . '</b><br><span class="teacher-name">' . $s['teacher'] . '</span></td>';
    $subjectsHTML .= '<td class="competence-col">' . $s['comp'][0] . '</td>';
    $subjectsHTML .= '<td class="note-col">' . number_format($s['notes'][0], 2) . '</td>';
    $subjectsHTML .= '<td class="avg-col" rowspan="' . $nbComp . '">' . number_format($avg, 2) . '</td>';
    $subjectsHTML .= '<td class="coef-col" rowspan="' . $nbComp . '">' . $s['coef'] . '</td>';
    $subjectsHTML .= '<td class="total-col" rowspan="' . $nbComp . '">' . number_format($total, 2) . '</td>';
    $subjectsHTML .= '<td class="cote-col ' . $coteClass . '" rowspan="' . $nbComp . '"><b>' . $cote . '</b></td>';
    $subjectsHTML .= '<td class="minmax-col" rowspan="' . $nbComp . '">' . $s['minmax'] . '</td>';
    $subjectsHTML .= '<td class="app-col" rowspan="' . $nbComp . '"></td>';
    $subjectsHTML .= '</tr>';

    // Lignes suivantes (sans la colonne MATIÈRES car elle a rowspan)
    for ($i = 1; $i < $nbComp; $i++) {
        $subjectsHTML .= '<tr>';
        $subjectsHTML .= '<td class="competence-col">' . $s['comp'][$i] . '</td>';
        $subjectsHTML .= '<td class="note-col">' . number_format($s['notes'][$i], 2) . '</td>';
        $subjectsHTML .= '</tr>';
    }
}

echo "✅ Matières générées\n";

// 6. CALCULS
$generalAverage = $totalGeneral / $totalCoef;
$studentCote = getCote($generalAverage);

$data = [
    'total_general' => number_format($totalGeneral, 2),
    'total_coef' => $totalCoef,
    'general_average' => number_format($generalAverage, 2),
    'student_cote' => $studentCote,
    'abs_non_just' => '0',
    'abs_just' => '2',
    'delays' => '1',
    'class_min_max' => '[3,25 - 16,85]',
    'nb_averages' => '58',
    'success_rate' => '94,4',
    'detailed_appreciation' => 'Points forts: Excellente maîtrise des matières scientifiques. Points à améliorer: Renforcer l\'expression écrite.',
];

// 7. REMPLACEMENTS
$finalHTML = $htmlTemplate;
foreach ($studentData as $k => $v) {
    $finalHTML = str_replace('{{' . $k . '}}', $v, $finalHTML);
}
foreach ($data as $k => $v) {
    $finalHTML = str_replace('{{' . $k . '}}', $v, $finalHTML);
}
$finalHTML = str_replace('{{subjects_html}}', $subjectsHTML, $finalHTML);
$finalHTML = preg_replace('/\{\{.*?\}\}/', '', $finalHTML);

echo "✅ HTML préparé\n";

// Sauvegarder pour debug
file_put_contents(__DIR__ . '/bulletin_structure_finale_debug.html', $finalHTML);
echo "✅ HTML de debug sauvegardé\n";

// 8. GÉNÉRATION PDF
try {
    $options = new Options();
    $options->set('defaultFont', 'Times New Roman');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($finalHTML);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfFile = __DIR__ . '/bulletin_structure_finale.pdf';
    file_put_contents($pdfFile, $dompdf->output());

    echo "✅ PDF généré: $pdfFile\n";
    echo "📊 Taille: " . number_format(filesize($pdfFile) / 1024, 2) . " KB\n";

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "🎉 Terminé\n";
?>
