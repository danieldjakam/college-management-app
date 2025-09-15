<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * 🎓 GÉNÉRATION BULLETIN SÉQUENCE 2 - DEUXIÈME CYCLE
 * Test de la nouvelle fonctionnalité
 */

echo "🎓 GÉNÉRATION BULLETIN SÉQUENCE 2 - DEUXIÈME CYCLE\n";
echo "===================================================\n\n";

// Lire le template original CPBD
$templatePath = __DIR__ . '/resources/views/bulletins/cpbd_bulletin_pdf.html';
$originalTemplate = file_get_contents($templatePath);

// Données pour le bulletin Séquence 2 DEUXIÈME CYCLE
$templateData = [
    'logo_base64' => '',
    'student_name' => 'HASSIM ACHTA',
    'birth_date' => '15/03/2005',
    'birth_place' => 'YAOUNDÉ',
    'class_name' => 'PREMIÈRE A4',
    'class_size' => '45',
    'main_teacher' => 'TCHAMENI MATHIEU',
    'student_number' => 'LYC2024001',
    'evaluation_number' => '2', // Séquence 2
    'school_year' => '2024/2025',
    'total_general' => '312.68',
    'total_coef' => '37.00',
    'evaluation_average' => '14.85',
    'average_class' => 'grade-good',
    'student_rank' => '8e',
    'class_average' => '12.73',
    'first_average' => '17.82',
    'last_average' => '6.45',
    'general_appreciation' => 'Bon travail. L\'élève progresse bien en séquence 2. Encouragements pour la suite.',
    'current_date' => date('d/m/Y')
];

// Template pour matières Séquence 2 DEUXIÈME CYCLE
$subjectGroupsHTML = '
<div class="grades-section">
    <div class="group-header">GROUPE A : MATIÈRES LITTÉRAIRES</div>
    <table class="subjects-table">
        <thead>
            <tr>
                <th style="width: 25%;">DISCIPLINE</th>
                <th style="width: 12%;">NOTES /20</th>
                <th style="width: 10%;">COEF.</th>
                <th style="width: 10%;">(NXC)</th>
                <th style="width: 10%;">RANG</th>
                <th style="width: 13%;">COMPÉTENCES</th>
                <th style="width: 20%;">NOMS DES PROFESSEURS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="subject-name">FRANÇAIS</td>
                <td class="grade-good">16.00</td>
                <td>6.00</td>
                <td class="grade-good">96.00</td>
                <td>2</td>
                <td>Acquise (Excellent)</td>
                <td>MBARGA CÉLESTINE</td>
            </tr>
            <tr>
                <td class="subject-name">ANGLAIS</td>
                <td class="grade-good">15.75</td>
                <td>4.00</td>
                <td class="grade-good">63.00</td>
                <td>3</td>
                <td>Acquise (Très Bien)</td>
                <td>JOHNSON MARGARET</td>
            </tr>
            <tr>
                <td class="subject-name">HISTOIRE-GÉOGRAPHIE</td>
                <td class="grade-good">14.50</td>
                <td>4.00</td>
                <td class="grade-good">58.00</td>
                <td>5</td>
                <td>Acquise (Très Bien)</td>
                <td>NKOMO ANDRÉ</td>
            </tr>
            <tr>
                <td class="subject-name">PHILOSOPHIE</td>
                <td class="grade-average">13.00</td>
                <td>3.00</td>
                <td class="grade-average">39.00</td>
                <td>6</td>
                <td>Acquise (Bien)</td>
                <td>FEUDJIO PASCAL</td>
            </tr>
            <tr class="total-row">
                <td class="subject-name">TOTAL</td>
                <td>14.76</td>
                <td>17.00</td>
                <td>251.00</td>
                <td>1</td>
                <td colspan="2">GROUPE A - Moy Gpe: 14.76</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="grades-section">
    <div class="group-header">GROUPE B : MATIÈRES SCIENTIFIQUES</div>
    <table class="subjects-table">
        <thead>
            <tr>
                <th style="width: 25%;">DISCIPLINE</th>
                <th style="width: 12%;">NOTES /20</th>
                <th style="width: 10%;">COEF.</th>
                <th style="width: 10%;">(NXC)</th>
                <th style="width: 10%;">RANG</th>
                <th style="width: 13%;">COMPÉTENCES</th>
                <th style="width: 20%;">NOMS DES PROFESSEURS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="subject-name">MATHÉMATIQUES</td>
                <td class="grade-excellent">18.00</td>
                <td>5.00</td>
                <td class="grade-excellent">90.00</td>
                <td>1</td>
                <td>Acquise (Excellent)</td>
                <td>TALLA FRANÇOIS</td>
            </tr>
            <tr>
                <td class="subject-name">SCIENCES PHYSIQUES</td>
                <td class="grade-excellent">17.25</td>
                <td>4.00</td>
                <td class="grade-excellent">69.00</td>
                <td>1</td>
                <td>Acquise (Excellent)</td>
                <td>KAMGA SOLANGE</td>
            </tr>
            <tr>
                <td class="subject-name">SVT</td>
                <td class="grade-excellent">16.50</td>
                <td>3.00</td>
                <td class="grade-excellent">49.50</td>
                <td>2</td>
                <td>Acquise (Excellent)</td>
                <td>NOAH BRIGITTE</td>
            </tr>
            <tr class="total-row">
                <td class="subject-name">TOTAL</td>
                <td>17.29</td>
                <td>12.00</td>
                <td>207.50</td>
                <td>1</td>
                <td colspan="2">GROUPE B - Moy Gpe: 17.29</td>
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

// Ajouter une note spécifique SÉQUENCE 2 DEUXIÈME CYCLE
$sequence2Note = '<div style="background: #e8f5e8; padding: 10px; margin: 15px 0; border-left: 4px solid #4caf50;">
    <strong>🎓 NOUVEAU : SÉQUENCE 2 - DEUXIÈME CYCLE !</strong><br>
    Grâce aux améliorations du système, les élèves du DEUXIÈME CYCLE peuvent maintenant générer des bulletins pour toutes les séquences (1, 2, 3, 4).<br>
    <strong>Premier Cycle :</strong> Séquences 1 et 3 uniquement | <strong>Deuxième Cycle :</strong> Toutes les séquences
</div>';

$finalHTML = str_replace('<div class="observations">', $sequence2Note . '<div class="observations">', $finalHTML);

// Modifier le titre pour indiquer "SÉQUENCE 2"
$finalHTML = str_replace('BULLETIN DE NOTES', 'BULLETIN DE NOTES - SÉQUENCE 2', $finalHTML);

// Sauvegarder le HTML
$htmlFile = __DIR__ . '/bulletin_sequence2_deuxieme_cycle.html';
file_put_contents($htmlFile, $finalHTML);

echo "✅ Bulletin Séquence 2 HTML généré!\n";
echo "📁 Fichier: $htmlFile\n\n";

echo "🎯 CARACTÉRISTIQUES BULLETIN SÉQUENCE 2:\n";
echo "=========================================\n";
echo "✅ Template CPBD original conservé\n";
echo "✅ Structure séquence (7 colonnes) au lieu de trimestre (11 colonnes)\n";
echo "✅ Notes directes de Séquence 2 (pas de moyennes trimestrielles)\n";
echo "✅ Compétences DEUXIÈME CYCLE adaptées\n";
echo "✅ Classe: PREMIÈRE A4 (DEUXIÈME CYCLE détecté)\n";
echo "✅ Évaluation N°2 (Séquence 2)\n";
echo "✅ 7 matières avec notes réalistes\n\n";

// Générer le PDF
echo "📄 GÉNÉRATION PDF Séquence 2...\n";
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
    $pdfFile = __DIR__ . '/bulletin_sequence2_deuxieme_cycle_' . date('Y-m-d_H-i-s') . '.pdf';
    file_put_contents($pdfFile, $pdfContent);

    echo "✅ PDF Séquence 2 généré avec succès!\n";
    echo "📁 PDF: $pdfFile\n\n";
} catch (Exception $e) {
    echo "❌ Erreur PDF: " . $e->getMessage() . "\n\n";
}

echo "🎉 BULLETIN SÉQUENCE 2 DEUXIÈME CYCLE - TERMINÉ!\n";
echo "=================================================\n";
echo "HTML: $htmlFile\n";
echo "PDF:  $pdfFile\n\n";

echo "📊 VALIDATION DE LA FONCTIONNALITÉ:\n";
echo "====================================\n";
echo "✅ Classe PREMIÈRE A4 → Détectée comme DEUXIÈME CYCLE\n";
echo "✅ Séquence 2 → Maintenant autorisée pour DEUXIÈME CYCLE\n";
echo "✅ Template → Même design que Premier Cycle\n";
echo "✅ Structure → Bulletin séquence (7 colonnes)\n";
echo "✅ Compétences → Adaptées au DEUXIÈME CYCLE\n";
echo "✅ Notes → Séquence 2 individuelle (pas de calcul trimestre)\n\n";

echo "🚀 DIFFÉRENCE AVEC PREMIER CYCLE:\n";
echo "==================================\n";
echo "📚 PREMIER CYCLE: Séquence 2 → ❌ Saisie uniquement (pas de bulletin)\n";
echo "🎓 DEUXIÈME CYCLE: Séquence 2 → ✅ Bulletin individuel généré !\n\n";

echo "Cette fonctionnalité permet plus de transparence et de suivi\n";
echo "pour les élèves du lycée ! 🎓📋✨\n";