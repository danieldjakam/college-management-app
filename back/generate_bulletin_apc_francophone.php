<?php

/**
 * 🎓 GÉNÉRATION DE BULLETIN PDF APC - PREMIER CYCLE FRANCOPHONE (CPBD)
 * Bulletin conforme à l'APC avec l'en-tête CPBD
 * Sans les pages de grille de notation (réservées aux enseignants)
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "🚀 Démarrage de la génération du bulletin APC Premier Cycle Francophone...\n";

// 1. CHARGER LE TEMPLATE HTML
$templatePath = __DIR__ . '/resources/views/bulletins/bulletin_apc_premier_cycle_francophone.html';
if (!file_exists($templatePath)) {
    die("❌ Erreur: Le fichier template n'a pas été trouvé: " . $templatePath . "\n");
}
$htmlTemplate = file_get_contents($templatePath);
echo "✅ Template HTML chargé.\n";

// 2. DÉFINIR LES DONNÉES DE L'ÉLÈVE
$studentData = [
    'student_name' => 'NGONO MARIE CLAIRE',
    'birth_date' => '15/03/2013',
    'birth_place' => 'DOUALA',
    'gender' => 'Féminin',
    'unique_id' => '24A0856FR',
    'class_level' => '6',
    'class_section' => 'A',
    'class_size' => '72',
    'redoublant' => 'Non',
    'main_teacher' => 'M. TCHAMENI MATHIEU',
    'parent_info' => 'Mme NGONO Sylvie - Tél: +237 677 123 456',
    'school_year' => '2024/2025',
];

// 3. FONCTION POUR CALCULER LA COTE
function getCote($average) {
    if ($average >= 18) return 'A+';
    if ($average >= 16) return 'A';
    if ($average >= 15) return 'B+';
    if ($average >= 14) return 'B';
    if ($average >= 12) return 'C+';
    if ($average >= 10) return 'C';
    return 'D';
}

function getCoteClass($cote) {
    $classes = [
        'A+' => 'cote-a-plus',
        'A' => 'cote-a',
        'B+' => 'cote-b-plus',
        'B' => 'cote-b',
        'C+' => 'cote-c-plus',
        'C' => 'cote-c',
        'D' => 'cote-d',
    ];
    return $classes[$cote] ?? '';
}

// 4. DÉFINIR LES MATIÈRES DU 1ER CYCLE (6ÈME) AVEC COMPÉTENCES
$subjects = [
    [
        'subject_name' => 'FRANÇAIS',
        'teacher_name' => 'M. TCHAMENI MATHIEU',
        'coef' => 4,
        'competences' => [
            'Produire à l\'écrit, un texte descriptif et, à l\'oral, un commentaire de l\'image.',
            'Produire, à l\'écrit, un texte narratif et, à l\'oral, un compte rendu oral.',
        ],
        'notes' => [14.50, 15.00],
        'min_max' => '[8,5 - 18,5]',
    ],
    [
        'subject_name' => 'ANGLAIS',
        'teacher_name' => 'Mme FONG SARA',
        'coef' => 3,
        'competences' => [
            'Use appropriate language resources to listen, speak, read and write about national integration.',
            'Use appropriate language resources to listen, speak, read and write about consumption habits.',
        ],
        'notes' => [13.00, 14.00],
        'min_max' => '[6,0 - 17,0]',
    ],
    [
        'subject_name' => 'MATHÉMATIQUES',
        'teacher_name' => 'M. KAMGA PIERRE',
        'coef' => 4,
        'competences' => [
            'Résoudre des situations problèmes relatives à l\'arithmétique et aux nombres réels.',
            'Communiquer à l\'aide du langage mathématique dans des situations relatives aux notions étudiées.',
        ],
        'notes' => [16.50, 17.00],
        'min_max' => '[4,0 - 19,0]',
    ],
    [
        'subject_name' => 'HISTOIRE',
        'teacher_name' => 'M. ABENA MARTIN',
        'coef' => 2,
        'competences' => [
            'Lutter contre la domination étrangère',
            'Prévenir et régler les conflits',
        ],
        'notes' => [15.00, 14.50],
        'min_max' => '[7,5 - 17,5]',
    ],
    [
        'subject_name' => 'GÉOGRAPHIE',
        'teacher_name' => 'M. ABENA MARTIN',
        'coef' => 2,
        'competences' => [
            'Gérer durablement l\'environnement',
            'Gérer les ressources humaines',
        ],
        'notes' => [13.00, 13.50],
        'min_max' => '[6,0 - 16,0]',
    ],
    [
        'subject_name' => 'SVTEEHB',
        'teacher_name' => 'Mme OUMAR FATIMA',
        'coef' => 2,
        'competences' => [
            'Résoudre les situations-problèmes relatives à la récurrence des anomalies dans les familles.',
            'Résoudre les situations-problèmes relatives à l\'amélioration de la santé individuelle.',
        ],
        'notes' => [14.00, 15.00],
        'min_max' => '[5,0 - 18,0]',
    ],
    [
        'subject_name' => 'PCT',
        'teacher_name' => 'M. BELLO HASSAN',
        'coef' => 2,
        'competences' => [
            'Résoudre des situations-problèmes se rapportant aux transformations chimiques de la matière.',
            'Communiquer en utilisant le langage et les symboles scientifiques.',
        ],
        'notes' => [12.50, 13.00],
        'min_max' => '[4,5 - 16,5]',
    ],
    [
        'subject_name' => 'INFORMATIQUE',
        'teacher_name' => 'Mme MEKUATE JUDITH',
        'coef' => 2,
        'competences' => [
            'Décrire l\'architecture d\'un microordinateur et y effectuer des petites tâches d\'entretien.',
            'Produire une organisation de données selon un format ou une structure de données spécifique.',
        ],
        'notes' => [16.00, 15.50],
        'min_max' => '[8,0 - 18,5]',
    ],
    [
        'subject_name' => 'CULTURES NATIONALES',
        'teacher_name' => 'Mme NDONDOCK MOUSSONGUI',
        'coef' => 1,
        'competences' => [
            'Produire un discours portant sur un mets traditionnel spécial ou une cérémonie de mariage',
            'Décrire une cérémonie funéraire, les métiers ou les jeux traditionnels',
        ],
        'notes' => [14.00, 14.50],
        'min_max' => '[9,0 - 17,0]',
    ],
    [
        'subject_name' => 'ÉDUCATION À LA CITOYENNETÉ',
        'teacher_name' => 'M. OWONO MVENG',
        'coef' => 2,
        'competences' => [
            'Eduquer les masses et s\'impliquer dans le processus électoral',
        ],
        'notes' => [13.50],
        'min_max' => '[7,0 - 16,5]',
    ],
    [
        'subject_name' => 'EPS',
        'teacher_name' => 'M. TONFACK BERTRAND',
        'coef' => 2,
        'competences' => [
            'Réaliser une course de vitesse et une course d\'endurance-vitesse',
            'Manipuler, coordonner et réaliser l\'un des deux types de lancer du poids',
        ],
        'notes' => [15.00, 16.00],
        'min_max' => '[10,0 - 18,0]',
    ],
    [
        'subject_name' => 'TRAVAIL MANUEL',
        'teacher_name' => 'Mme NOUTAMOUN FLORE',
        'coef' => 1,
        'competences' => [
            'Utiliser les matériaux, les matériels et les techniques pour modeler et mouler un pot en argile',
            'Maîtriser les étapes du semis et d\'entretien de la plantation de légumineuses à grains',
        ],
        'notes' => [14.00, 13.50],
        'min_max' => '[8,5 - 16,0]',
    ],
    [
        'subject_name' => 'ÉDUCATION ARTISTIQUE',
        'teacher_name' => 'Mme SOH DIANE',
        'coef' => 1,
        'competences' => [
            'Nommer des artistes plasticiens camerounais et leurs principales œuvres.',
            'Reproduire une œuvre d\'art plastique camerounaise en lien avec la vie sociale.',
            'Lire les signes musicaux sur une portée musicale.',
            'Déchiffrer les signes musicaux sur une partition musicale simple.',
        ],
        'notes' => [15.50, 14.50, 16.00, 15.00],
        'min_max' => '[9,0 - 17,5]',
    ],
];

// 5. CONSTRUIRE LE HTML DES MATIÈRES
$subjectsHTML = '';
$totalGeneral = 0;
$totalCoef = 0;

foreach ($subjects as $subject) {
    // Calculer la moyenne de la matière
    $average = array_sum($subject['notes']) / count($subject['notes']);
    $total = $average * $subject['coef'];
    $totalGeneral += $total;
    $totalCoef += $subject['coef'];

    $cote = getCote($average);
    $coteClass = getCoteClass($cote);

    // Première ligne avec toutes les infos
    $subjectsHTML .= '<tr>';
    $subjectsHTML .= '<td class="subject-cell"><b>' . $subject['subject_name'] . '</b><br><span class="teacher-name">' . $subject['teacher_name'] . '</span></td>';
    $subjectsHTML .= '<td class="competence-cell">' . $subject['competences'][0] . '</td>';
    $subjectsHTML .= '<td class="note-cell">' . number_format($subject['notes'][0], 2) . '</td>';
    $subjectsHTML .= '<td class="avg-cell" rowspan="' . count($subject['competences']) . '">' . number_format($average, 2) . '</td>';
    $subjectsHTML .= '<td class="coef-cell" rowspan="' . count($subject['competences']) . '">' . $subject['coef'] . '</td>';
    $subjectsHTML .= '<td class="total-cell" rowspan="' . count($subject['competences']) . '">' . number_format($total, 2) . '</td>';
    $subjectsHTML .= '<td class="cote-cell ' . $coteClass . '" rowspan="' . count($subject['competences']) . '"><b>' . $cote . '</b></td>';
    $subjectsHTML .= '<td class="minmax-cell" rowspan="' . count($subject['competences']) . '">' . $subject['min_max'] . '</td>';
    $subjectsHTML .= '<td class="appreciation-cell" rowspan="' . count($subject['competences']) . '"></td>';
    $subjectsHTML .= '</tr>';

    // Lignes supplémentaires pour les autres compétences
    for ($i = 1; $i < count($subject['competences']); $i++) {
        $subjectsHTML .= '<tr>';
        $subjectsHTML .= '<td class="subject-cell"></td>';
        $subjectsHTML .= '<td class="competence-cell">' . $subject['competences'][$i] . '</td>';
        $subjectsHTML .= '<td class="note-cell">' . number_format($subject['notes'][$i], 2) . '</td>';
        $subjectsHTML .= '</tr>';
    }
}

echo "✅ HTML des matières généré.\n";

// 6. CALCULER LES STATISTIQUES
$generalAverage = $totalGeneral / $totalCoef;
$studentCote = getCote($generalAverage);

// Données de discipline et statistiques
$disciplineData = [
    'abs_non_just' => '0',
    'abs_just' => '2',
    'delays' => '1',
    'consignes' => '0',
    'warning' => 'Non',
    'blame' => 'Non',
    'exclusions' => '0',
    'def_exclusion' => 'Non',
    'total_general' => number_format($totalGeneral, 2),
    'total_coef' => $totalCoef,
    'general_average' => number_format($generalAverage, 2),
    'student_cote' => $studentCote,
    'class_min_max' => '[3,25 - 16,85]',
    'nb_averages' => '58',
    'stat_ctba' => '12',
    'stat_cba' => '18',
    'stat_ca' => '28',
    'stat_cma' => '10',
    'stat_cna' => '4',
    'success_rate' => '94,4',
    'appreciation_text' => 'Bon travail. L\'élève a démontré de bonnes capacités d\'apprentissage dans l\'ensemble des matières. Les compétences sont bien acquises en Mathématiques, Informatique et EPS. Continuez vos efforts pour améliorer les matières où les compétences ne sont pas encore totalement maîtrisées, notamment en PCT. Travail sérieux et régulier. Encouragements du conseil de classe.',
];

// 7. REMPLACER LES PLACEHOLDERS
$finalHTML = $htmlTemplate;

// Remplacer les données de l'élève
foreach ($studentData as $key => $value) {
    $finalHTML = str_replace('{{' . $key . '}}', $value, $finalHTML);
}

// Remplacer les données de discipline
foreach ($disciplineData as $key => $value) {
    $finalHTML = str_replace('{{' . $key . '}}', $value, $finalHTML);
}

// Remplacer le tableau des matières
$finalHTML = str_replace('{{subjects_html}}', $subjectsHTML, $finalHTML);

// Nettoyer les placeholders restants
$finalHTML = preg_replace('/\{\{.*?\}\}/', '', $finalHTML);

echo "✅ Placeholders remplacés.\n";

// Sauvegarder le HTML pour debug
file_put_contents(__DIR__ . '/bulletin_debug.html', $finalHTML);
echo "✅ HTML de debug sauvegardé.\n";

// 8. GÉNÉRER LE PDF
echo "📄 Génération du PDF en cours...\n";
try {
    $options = new Options();
    $options->set('defaultFont', 'Times New Roman');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('enable_php', false);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($finalHTML);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfFile = __DIR__ . '/bulletin_apc_francophone_6eme.pdf';
    file_put_contents($pdfFile, $dompdf->output());

    echo "✅ PDF généré avec succès!\n";
    echo "📁 Fichier disponible: " . $pdfFile . "\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de la génération du PDF: " . $e->getMessage() . "\n";
}

echo "🎉 Processus terminé.\n";

?>
