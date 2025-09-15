<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * 🎓 GÉNÉRATION PDF DEUXIÈME CYCLE
 * Bulletin complet avec 10 matières
 */

echo "🎓 GÉNÉRATION PDF DEUXIÈME CYCLE\n";
echo "=================================\n\n";

// Template HTML complet pour PDF
$htmlContent = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulletin DEUXIÈME CYCLE</title>
    <style>
        @page { margin: 20mm; }
        body { font-family: "Times New Roman", serif; font-size: 12px; margin: 0; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 16px; margin: 5px 0; }
        .header h2 { font-size: 14px; margin: 5px 0; color: #333; }
        .student-info { margin: 15px 0; }
        .student-info table { width: 100%; border-collapse: collapse; }
        .student-info td { padding: 4px; border: 1px solid #000; }
        .grades-table { width: 100%; border-collapse: collapse; font-size: 10px; margin: 15px 0; }
        .grades-table th, .grades-table td { border: 1px solid #000; padding: 3px; text-align: center; }
        .grades-table th { background: #f0f0f0; font-weight: bold; }
        .grades-table .subject-name { text-align: left; font-weight: bold; }
        .total-row { background: #e0e0e0; font-weight: bold; }
        .excellent { background: #d4edda; }
        .good { background: #fff3cd; }
        .average { background: #f8d7da; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; }
        .group-header { background: #333; color: white; text-align: center; font-weight: bold; padding: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>RÉPUBLIQUE DU CAMEROUN</h1>
        <h2>MINISTÈRE DE L\'ÉDUCATION DE BASE</h2>
        <h2>COLLÈGE PRIVÉ BILINGUE DJEUGA</h2>
        <h1>BULLETIN SCOLAIRE - TRIMESTRE 1</h1>
        <h2>SECTION FRANCOPHONE - ENSEIGNEMENT GÉNÉRAL DEUXIÈME CYCLE</h2>
    </div>

    <table class="student-info">
        <tr>
            <td><strong>Nom et Prénoms:</strong> HASSIM ACHTA</td>
            <td><strong>Classe:</strong> PREMIÈRE A4</td>
            <td><strong>Effectif:</strong> 45</td>
        </tr>
        <tr>
            <td><strong>Date de naissance:</strong> 15/03/2005</td>
            <td><strong>Lieu de naissance:</strong> YAOUNDÉ</td>
            <td><strong>Matricule:</strong> LYC2024001</td>
        </tr>
        <tr>
            <td><strong>Année scolaire:</strong> 2024/2025</td>
            <td><strong>Trimestre:</strong> Premier Trimestre</td>
            <td><strong>Rang:</strong> 2e</td>
        </tr>
    </table>

    <div class="group-header">GROUPE A : MATIÈRES LITTÉRAIRES</div>
    <table class="grades-table">
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
                <td class="excellent">16.00</td>
                <td>6.00</td>
                <td class="excellent">96.00</td>
                <td class="excellent">96.00</td>
                <td>3</td>
                <td>Acquise (Excellent)</td>
                <td>MBARGA CÉLESTINE</td>
            </tr>
            <tr>
                <td class="subject-name">ANGLAIS</td>
                <td>13.25</td>
                <td>15.75</td>
                <td>16.50</td>
                <td class="good">15.17</td>
                <td>4.00</td>
                <td class="good">60.68</td>
                <td class="good">60.68</td>
                <td>5</td>
                <td>Acquise (Très Bien)</td>
                <td>JOHNSON MARGARET</td>
            </tr>
            <tr>
                <td class="subject-name">HISTOIRE-GÉOGRAPHIE</td>
                <td>12.00</td>
                <td>14.50</td>
                <td>15.00</td>
                <td class="good">13.83</td>
                <td>4.00</td>
                <td class="good">55.32</td>
                <td class="good">55.32</td>
                <td>8</td>
                <td>Acquise (Bien)</td>
                <td>NKOMO ANDRÉ</td>
            </tr>
            <tr>
                <td class="subject-name">PHILOSOPHIE</td>
                <td>11.50</td>
                <td>13.00</td>
                <td>14.75</td>
                <td class="good">13.08</td>
                <td>3.00</td>
                <td class="good">39.24</td>
                <td class="good">39.24</td>
                <td>7</td>
                <td>Acquise (Bien)</td>
                <td>FEUDJIO PASCAL</td>
            </tr>
        </tbody>
    </table>

    <div class="group-header">GROUPE B : MATIÈRES SCIENTIFIQUES</div>
    <table class="grades-table">
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
                <td class="excellent">17.92</td>
                <td>5.00</td>
                <td class="excellent">89.60</td>
                <td class="excellent">89.60</td>
                <td>1</td>
                <td>Acquise (Excellent)</td>
                <td>TALLA FRANÇOIS</td>
            </tr>
            <tr>
                <td class="subject-name">SCIENCES PHYSIQUES</td>
                <td>15.75</td>
                <td>17.25</td>
                <td>18.00</td>
                <td class="excellent">17.00</td>
                <td>4.00</td>
                <td class="excellent">68.00</td>
                <td class="excellent">68.00</td>
                <td>2</td>
                <td>Acquise (Excellent)</td>
                <td>KAMGA SOLANGE</td>
            </tr>
            <tr>
                <td class="subject-name">SVT</td>
                <td>14.00</td>
                <td>16.50</td>
                <td>17.75</td>
                <td class="excellent">16.08</td>
                <td>3.00</td>
                <td class="excellent">48.24</td>
                <td class="excellent">48.24</td>
                <td>4</td>
                <td>Acquise (Excellent)</td>
                <td>NOAH BRIGITTE</td>
            </tr>
        </tbody>
    </table>

    <div class="group-header">GROUPE C : MATIÈRES PRATIQUES</div>
    <table class="grades-table">
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
                <td class="excellent">17.00</td>
                <td>4.00</td>
                <td class="excellent">68.00</td>
                <td class="excellent">68.00</td>
                <td>1</td>
                <td>Acquise (Excellent)</td>
                <td>NGUEPINSE KAMGANG</td>
            </tr>
            <tr>
                <td class="subject-name">INFORMATIQUE</td>
                <td>16.25</td>
                <td>17.50</td>
                <td>18.75</td>
                <td class="excellent">17.50</td>
                <td>2.00</td>
                <td class="excellent">35.00</td>
                <td class="excellent">35.00</td>
                <td>1</td>
                <td>Acquise (Excellent)</td>
                <td>MBALLA STEVE</td>
            </tr>
            <tr>
                <td class="subject-name">ARTS PLASTIQUES</td>
                <td>13.50</td>
                <td>15.25</td>
                <td>16.00</td>
                <td class="good">14.92</td>
                <td>2.00</td>
                <td class="good">29.84</td>
                <td class="good">29.84</td>
                <td>6</td>
                <td>Acquise (Très Bien)</td>
                <td>FOTSO MARIE</td>
            </tr>
        </tbody>
    </table>

    <table class="grades-table">
        <tr class="total-row">
            <td class="subject-name">TOTAL GÉNÉRAL</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
            <td><strong>15.73</strong></td>
            <td><strong>37.00</strong></td>
            <td><strong>581.92</strong></td>
            <td><strong>581.92</strong></td>
            <td><strong>2e</strong></td>
            <td colspan="2"><strong>Très Bien - Félicitations</strong></td>
        </tr>
    </table>

    <div style="margin-top: 20px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="border: 1px solid #000; padding: 5px;"><strong>Moyenne de la classe:</strong> 13.45/20</td>
                <td style="border: 1px solid #000; padding: 5px;"><strong>Moyenne de l\'élève:</strong> 15.73/20</td>
                <td style="border: 1px solid #000; padding: 5px;"><strong>Rang:</strong> 2e/45</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 5px;"><strong>Première moyenne:</strong> 18.25/20</td>
                <td style="border: 1px solid #000; padding: 5px;"><strong>Dernière moyenne:</strong> 7.80/20</td>
                <td style="border: 1px solid #000; padding: 5px;"><strong>Mention:</strong> Très Bien</td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 30px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="border: 1px solid #000; padding: 10px; width: 50%;">
                    <strong>Appréciation du Conseil de Classe:</strong><br>
                    Excellent travail. L\'élève fait preuve de sérieux et de régularité.
                    Félicitations pour ce très bon trimestre. Continuer sur cette lancée.
                </td>
                <td style="border: 1px solid #000; padding: 10px; width: 50%;">
                    <strong>Signature des Parents:</strong><br><br><br><br>
                    Date: _______________
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p><strong>CARACTÉRISTIQUES DEUXIÈME CYCLE:</strong></p>
        <p>✓ Formule: (Sequence 1 + Sequence 2 + Composition) / 3 | ✓ Affichage: 11 colonnes détaillées</p>
        <p>✓ Exemple EPS: (15.00 + 16.00 + 20.00) / 3 = 17.00 × 4.00 = 68.00</p>
        <p>✓ Compétences: Acquise (Excellent), Acquise (Très Bien), En cours d\'acquisition</p>
    </div>
</body>
</html>';

// Configuration PDF
$options = new Options();
$options->set('defaultFont', 'Times-Roman');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('isPhpEnabled', false);
$options->set('isFontSubsettingEnabled', true);
$options->set('dpi', 150);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Sauvegarder le PDF
$pdfContent = $dompdf->output();
$pdfFile = __DIR__ . '/bulletin_deuxieme_cycle_' . date('Y-m-d_H-i-s') . '.pdf';
file_put_contents($pdfFile, $pdfContent);

echo "✅ PDF généré avec succès!\n";
echo "📁 Fichier: $pdfFile\n\n";

// Également sauvegarder le HTML pour inspection
$htmlFile = __DIR__ . '/bulletin_deuxieme_cycle_full.html';
file_put_contents($htmlFile, $htmlContent);

echo "📄 FICHIERS GÉNÉRÉS:\n";
echo "====================\n";
echo "HTML: $htmlFile\n";
echo "PDF:  $pdfFile\n\n";

echo "🎯 CONTENU DU BULLETIN:\n";
echo "========================\n";
echo "✅ 10 matières réparties en 3 groupes\n";
echo "✅ Structure 11 colonnes DEUXIÈME CYCLE\n";
echo "✅ Calculs: (Seq1 + Seq2 + Compo) / 3\n";
echo "✅ Exemple EPS validé: 15.00 + 16.00 + 20.00 = 17.00 × 4.00 = 68.00\n";
echo "✅ Compétences détaillées: 'Acquise (Excellent)', etc.\n";
echo "✅ Enseignants: Noms complets affichés\n";
echo "✅ NXC = TOTAL dans l'affichage\n";
echo "✅ Moyenne générale: 15.73/20 (Très Bien)\n";
echo "✅ Format professionnel avec en-tête et signatures\n\n";

echo "🚀 BULLETIN PDF DEUXIÈME CYCLE PRÊT!\n";
echo "=====================================\n";
echo "Vous pouvez maintenant consulter le bulletin généré\n";
echo "et voir la différence avec le PREMIER CYCLE ! 📋\n";