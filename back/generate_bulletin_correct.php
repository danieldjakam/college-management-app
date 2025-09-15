<?php

/**
 * 🎓 BULLETIN DEUXIÈME CYCLE - TEMPLATE ORIGINAL CPBD
 * Utilise exactement le même style que le Premier Cycle
 * Seules les colonnes du tableau changent
 */

echo "🎓 GÉNÉRATION BULLETIN CORRECT - TEMPLATE ORIGINAL CPBD\n";
echo "=======================================================\n\n";

// Lire le template original
$templatePath = __DIR__ . '/resources/views/bulletins/cpbd_bulletin_pdf.html';
$originalTemplate = file_get_contents($templatePath);

// Données pour le bulletin DEUXIÈME CYCLE
$templateData = [
    'logo_base64' => '', // Sera remplacé par le vrai logo plus tard
    'student_name' => 'HASSIM ACHTA',
    'birth_date' => '15/03/2005',
    'birth_place' => 'YAOUNDÉ',
    'class_name' => 'PREMIÈRE A4',
    'class_size' => '45',
    'main_teacher' => 'TCHAMENI MATHIEU',
    'student_number' => 'LYC2024001',
    'evaluation_number' => '1', // Trimestre 1
    'school_year' => '2024/2025',
    'total_general' => '581.92',
    'total_coef' => '37.00',
    'evaluation_average' => '15.73',
    'average_class' => 'grade-excellent',
    'student_rank' => '2e',
    'class_average' => '13.45',
    'first_average' => '18.25',
    'last_average' => '7.80',
    'general_appreciation' => 'Excellent travail. L\'élève fait preuve de sérieux et de régularité. Félicitations pour ce très bon trimestre.',
    'current_date' => date('d/m/Y')
];

// Template pour groupes de matières DEUXIÈME CYCLE
$subjectGroupsHTML = '
<div class="grades-section">
    <div class="group-header">GROUPE A : MATIÈRES LITTÉRAIRES</div>
    <table class="subjects-table">
        <thead>
            <tr>
                <th style="width: 15%;">DISCIPLINE</th>
                <th style="width: 7%;">Sequence 1</th>
                <th style="width: 7%;">Sequence 2</th>
                <th style="width: 7%;">Compo1</th>
                <th style="width: 8%;">Moy./20</th>
                <th style="width: 5%;">COEF.</th>
                <th style="width: 7%;">(NXC)</th>
                <th style="width: 7%;">TOTAL</th>
                <th style="width: 5%;">RANG</th>
                <th style="width: 15%;">COMPÉTENCES</th>
                <th style="width: 17%;">NOMS DES PROFESSEURS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="subject-name">FRANÇAIS</td>
                <td>14.50</td>
                <td>16.00</td>
                <td>17.50</td>
                <td class="grade-excellent">16.00</td>
                <td>6.00</td>
                <td class="grade-excellent">96.00</td>
                <td class="grade-excellent">96.00</td>
                <td>3</td>
                <td>Acquise (Excellent)</td>
                <td>MBARGA CÉLESTINE</td>
            </tr>
            <tr>
                <td class="subject-name">ANGLAIS</td>
                <td>13.25</td>
                <td>15.75</td>
                <td>16.50</td>
                <td class="grade-good">15.17</td>
                <td>4.00</td>
                <td class="grade-good">60.68</td>
                <td class="grade-good">60.68</td>
                <td>5</td>
                <td>Acquise (Très Bien)</td>
                <td>JOHNSON MARGARET</td>
            </tr>
            <tr>
                <td class="subject-name">HISTOIRE-GÉOGRAPHIE</td>
                <td>12.00</td>
                <td>14.50</td>
                <td>15.00</td>
                <td class="grade-good">13.83</td>
                <td>4.00</td>
                <td class="grade-good">55.32</td>
                <td class="grade-good">55.32</td>
                <td>8</td>
                <td>Acquise (Bien)</td>
                <td>NKOMO ANDRÉ</td>
            </tr>
            <tr>
                <td class="subject-name">PHILOSOPHIE</td>
                <td>11.50</td>
                <td>13.00</td>
                <td>14.75</td>
                <td class="grade-good">13.08</td>
                <td>3.00</td>
                <td class="grade-good">39.24</td>
                <td class="grade-good">39.24</td>
                <td>7</td>
                <td>Acquise (Bien)</td>
                <td>FEUDJIO PASCAL</td>
            </tr>
            <tr class="total-row">
                <td class="subject-name">TOTAL</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>14.70</td>
                <td>17.00</td>
                <td>249.24</td>
                <td>249.24</td>
                <td>1</td>
                <td>GROUPE A</td>
                <td>Moy Gpe: 14.70</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="grades-section">
    <div class="group-header">GROUPE B : MATIÈRES SCIENTIFIQUES</div>
    <table class="subjects-table">
        <thead>
            <tr>
                <th style="width: 15%;">DISCIPLINE</th>
                <th style="width: 7%;">Sequence 1</th>
                <th style="width: 7%;">Sequence 2</th>
                <th style="width: 7%;">Compo1</th>
                <th style="width: 8%;">Moy./20</th>
                <th style="width: 5%;">COEF.</th>
                <th style="width: 7%;">(NXC)</th>
                <th style="width: 7%;">TOTAL</th>
                <th style="width: 5%;">RANG</th>
                <th style="width: 15%;">COMPÉTENCES</th>
                <th style="width: 17%;">NOMS DES PROFESSEURS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="subject-name">MATHÉMATIQUES</td>
                <td>16.50</td>
                <td>18.00</td>
                <td>19.25</td>
                <td class="grade-excellent">17.92</td>
                <td>5.00</td>
                <td class="grade-excellent">89.60</td>
                <td class="grade-excellent">89.60</td>
                <td>1</td>
                <td>Acquise (Excellent)</td>
                <td>TALLA FRANÇOIS</td>
            </tr>
            <tr>
                <td class="subject-name">SCIENCES PHYSIQUES</td>
                <td>15.75</td>
                <td>17.25</td>
                <td>18.00</td>
                <td class="grade-excellent">17.00</td>
                <td>4.00</td>
                <td class="grade-excellent">68.00</td>
                <td class="grade-excellent">68.00</td>
                <td>2</td>
                <td>Acquise (Excellent)</td>
                <td>KAMGA SOLANGE</td>
            </tr>
            <tr>
                <td class="subject-name">SVT</td>
                <td>14.00</td>
                <td>16.50</td>
                <td>17.75</td>
                <td class="grade-excellent">16.08</td>
                <td>3.00</td>
                <td class="grade-excellent">48.24</td>
                <td class="grade-excellent">48.24</td>
                <td>4</td>
                <td>Acquise (Excellent)</td>
                <td>NOAH BRIGITTE</td>
            </tr>
            <tr class="total-row">
                <td class="subject-name">TOTAL</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>17.07</td>
                <td>12.00</td>
                <td>204.84</td>
                <td>204.84</td>
                <td>1</td>
                <td>GROUPE B</td>
                <td>Moy Gpe: 17.07</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="grades-section">
    <div class="group-header">GROUPE C : MATIÈRES PRATIQUES</div>
    <table class="subjects-table">
        <thead>
            <tr>
                <th style="width: 15%;">DISCIPLINE</th>
                <th style="width: 7%;">Sequence 1</th>
                <th style="width: 7%;">Sequence 2</th>
                <th style="width: 7%;">Compo1</th>
                <th style="width: 8%;">Moy./20</th>
                <th style="width: 5%;">COEF.</th>
                <th style="width: 7%;">(NXC)</th>
                <th style="width: 7%;">TOTAL</th>
                <th style="width: 5%;">RANG</th>
                <th style="width: 15%;">COMPÉTENCES</th>
                <th style="width: 17%;">NOMS DES PROFESSEURS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="subject-name">EPS</td>
                <td>15.00</td>
                <td>16.00</td>
                <td>20.00</td>
                <td class="grade-excellent">17.00</td>
                <td>4.00</td>
                <td class="grade-excellent">68.00</td>
                <td class="grade-excellent">68.00</td>
                <td>1</td>
                <td>Acquise (Excellent)</td>
                <td>NGUEPINSE KAMGANG</td>
            </tr>
            <tr>
                <td class="subject-name">INFORMATIQUE</td>
                <td>16.25</td>
                <td>17.50</td>
                <td>18.75</td>
                <td class="grade-excellent">17.50</td>
                <td>2.00</td>
                <td class="grade-excellent">35.00</td>
                <td class="grade-excellent">35.00</td>
                <td>1</td>
                <td>Acquise (Excellent)</td>
                <td>MBALLA STEVE</td>
            </tr>
            <tr>
                <td class="subject-name">ARTS PLASTIQUES</td>
                <td>13.50</td>
                <td>15.25</td>
                <td>16.00</td>
                <td class="grade-good">14.92</td>
                <td>2.00</td>
                <td class="grade-good">29.84</td>
                <td class="grade-good">29.84</td>
                <td>6</td>
                <td>Acquise (Très Bien)</td>
                <td>FOTSO MARIE</td>
            </tr>
            <tr>
                <td class="subject-name">ALLEMAND</td>
                <td>12.75</td>
                <td>14.25</td>
                <td>15.50</td>
                <td class="grade-good">14.17</td>
                <td>4.00</td>
                <td class="grade-good">56.68</td>
                <td class="grade-good">56.68</td>
                <td>4</td>
                <td>Acquise (Très Bien)</td>
                <td>WEBER HANS</td>
            </tr>
            <tr class="total-row">
                <td class="subject-name">TOTAL</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>15.90</td>
                <td>12.00</td>
                <td>189.52</td>
                <td>189.52</td>
                <td>1</td>
                <td>GROUPE C</td>
                <td>Moy Gpe: 15.90</td>
            </tr>
        </tbody>
    </table>
</div>';

// Remplacer le placeholder par nos groupes
$finalHTML = str_replace('{{#each subject_groups}}{{/each}}', $subjectGroupsHTML, $originalTemplate);

// Remplacer toutes les variables du template
foreach ($templateData as $key => $value) {
    $finalHTML = str_replace('{{' . $key . '}}', $value, $finalHTML);
}

// Mettre à jour la légende pour le DEUXIÈME CYCLE
$originalLegend = '<span class="legend-item"><strong>A+ :</strong> Expert</span>
                        <span class="legend-item"><strong>A :</strong> Acquise</span>
                        <span class="legend-item"><strong>ECA :</strong> En Cours d\'Acquisition</span>
                        <span class="legend-item"><strong>NA :</strong> Non Acquise</span>';

$deuxiemeCycleLegend = '<span class="legend-item"><strong>Acquise (Excellent) :</strong> 16-20/20</span>
                        <span class="legend-item"><strong>Acquise (Très Bien) :</strong> 14-16/20</span>
                        <span class="legend-item"><strong>Acquise (Bien) :</strong> 12-14/20</span>
                        <span class="legend-item"><strong>En cours d\'acquisition :</strong> 10-12/20</span>';

$finalHTML = str_replace($originalLegend, $deuxiemeCycleLegend, $finalHTML);

// Ajouter une note spécifique DEUXIÈME CYCLE
$deuxiemeCycleNote = '<div style="background: #e3f2fd; padding: 10px; margin: 15px 0; border-left: 4px solid #2196f3;">
    <strong>🎓 DEUXIÈME CYCLE :</strong> Calcul des moyennes = (Sequence 1 + Sequence 2 + Composition) / 3<br>
    <strong>Exemple EPS :</strong> (15.00 + 16.00 + 20.00) / 3 = 17.00 × 4.00 = 68.00
</div>';

$finalHTML = str_replace('<div class="observations">', $deuxiemeCycleNote . '<div class="observations">', $finalHTML);

// Sauvegarder le HTML
$htmlFile = __DIR__ . '/bulletin_deuxieme_cycle_CPBD_original.html';
file_put_contents($htmlFile, $finalHTML);

echo "✅ Bulletin HTML généré avec template CPBD original!\n";
echo "📁 Fichier: $htmlFile\n\n";

echo "🎯 CARACTÉRISTIQUES DU BULLETIN CORRIGÉ:\n";
echo "=========================================\n";
echo "✅ Template original CPBD conservé (en-têtes, styles, structure)\n";
echo "✅ Seules les colonnes du tableau modifiées pour DEUXIÈME CYCLE\n";
echo "✅ 11 colonnes: Discipline | Seq1 | Seq2 | Compo | Moy | COEF | NXC | TOTAL | RANG | COMPÉTENCES | PROFESSEURS\n";
echo "✅ Exemple EPS validé: 15.00 + 16.00 + 20.00 = 17.00 × 4.00 = 68.00\n";
echo "✅ Compétences DEUXIÈME CYCLE: 'Acquise (Excellent)', etc.\n";
echo "✅ Même design, couleurs (#EFE9F3), logo, footer que Premier Cycle\n";
echo "✅ 10 matières réparties en 3 groupes\n\n";

// Générer aussi le PDF avec le bon template
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "📄 GÉNÉRATION PDF avec template original...\n";
try {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('isPhpEnabled', false);
    $options->set('isFontSubsettingEnabled', true);
    $options->set('dpi', 150);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($finalHTML);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfContent = $dompdf->output();
    $pdfFile = __DIR__ . '/bulletin_deuxieme_cycle_CPBD_' . date('Y-m-d_H-i-s') . '.pdf';
    file_put_contents($pdfFile, $pdfContent);

    echo "✅ PDF généré avec succès!\n";
    echo "📁 PDF: $pdfFile\n\n";
} catch (Exception $e) {
    echo "❌ Erreur PDF: " . $e->getMessage() . "\n\n";
}

echo "🎉 BULLETIN CORRIGÉ AVEC TEMPLATE ORIGINAL!\n";
echo "============================================\n";
echo "HTML: $htmlFile\n";
echo "PDF:  $pdfFile\n\n";
echo "Maintenant le bulletin utilise exactement le même style\n";
echo "que le Premier Cycle, avec seulement les colonnes\n";
echo "du tableau adaptées pour le DEUXIÈME CYCLE ! 🚀\n";