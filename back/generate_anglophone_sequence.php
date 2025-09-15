<?php

/**
 * Generate Anglophone Sequence Bulletin (Term Assessment)
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "🇬🇧 GENERATING ANGLOPHONE SEQUENCE BULLETIN\n";
echo "===========================================\n\n";

// Mock data for Anglophone sequence bulletin
$mockData = [
    'student_name' => 'AKAH JOHN DOE',
    'birth_date' => '15/03/2008',
    'birth_place' => 'DOUALA',
    'class_name' => 'FORM 2A',
    'main_teacher' => 'MR. TCHAMENI MATHIEU',
    'class_size' => 35,
    'student_number' => '24A856',
    'evaluation_number' => 1, // Sequence number
    'school_year' => '2024/2025',
    'current_date' => date('d/m/Y'),
    // Anglophone labels for sequence
    'bulletin_title' => 'STUDENT REPORT CARD - TERM ASSESSMENT',
    'name_label' => 'Name and Surname:',
    'birth_date_label' => 'Date of birth:',
    'birth_place_label' => 'Place of birth:',
    'class_label' => 'Class:',
    'class_size_label' => 'Class size:',
    'main_teacher_label' => 'Main teacher:',
    'student_number_label' => 'Student number:',
    'evaluation_label' => 'Term Assessment:',
    'school_year_label' => 'SCHOOL YEAR:',
    'discipline_header' => 'DISCIPLINE',
    'general_results_header' => 'TERM RESULTS',
    'total_general_label' => 'GENERAL TOTAL:',
    'total_coef_label' => 'TOTAL COEF:',
    'evaluation_average_label' => 'TERM AVERAGE:',
    'rank_label' => 'RANK:',
    'class_average_label' => 'CLASS AVERAGE:',
    'first_average_label' => 'FIRST AVERAGE:',
    'last_average_label' => 'LAST AVERAGE:',
    'appreciation_header' => 'TERM ASSESSMENT AND OBSERVATIONS',
    'visa_parent_label' => 'PARENT VISA & NAME',
    'made_in_douala' => 'Done in Douala, on',
    'principal_title' => 'The Principal',
    'legend_title' => 'Legend:',
    'general_appreciation' => 'Good performance for this term. Keep working hard!'
];

// Sequence subjects with individual term grades (realistic /20)
$subjects = [
    ['name' => 'MATHEMATICS', 'score' => '16.50', 'coef' => '4.00', 'nxc' => '66.00', 'rank' => '3', 'competence' => 'Mastered (Excellent)', 'teacher' => 'MR. TALLA'],
    ['name' => 'ENGLISH LANGUAGE', 'score' => '15.00', 'coef' => '4.00', 'nxc' => '60.00', 'rank' => '5', 'competence' => 'Mastered (Very Good)', 'teacher' => 'MRS. JOHNSON'],
    ['name' => 'CHEMISTRY', 'score' => '18.00', 'coef' => '3.00', 'nxc' => '54.00', 'rank' => '1', 'competence' => 'Mastered (Excellent)', 'teacher' => 'DR. KAMGA'],
    ['name' => 'PHYSICS', 'score' => '14.00', 'coef' => '3.00', 'nxc' => '42.00', 'rank' => '8', 'competence' => 'Mastered (Very Good)', 'teacher' => 'MR. NOAH'],
    ['name' => 'BIOLOGY', 'score' => '16.00', 'coef' => '3.00', 'nxc' => '48.00', 'rank' => '4', 'competence' => 'Mastered (Excellent)', 'teacher' => 'MS. GRACE'],
    ['name' => 'HISTORY', 'score' => '13.00', 'coef' => '2.00', 'nxc' => '26.00', 'rank' => '12', 'competence' => 'Mastered (Good)', 'teacher' => 'MR. MBAH'],
    ['name' => 'GEOGRAPHY', 'score' => '12.50', 'coef' => '2.00', 'nxc' => '25.00', 'rank' => '15', 'competence' => 'Mastered (Good)', 'teacher' => 'MRS. FATIMA'],
    ['name' => 'COMPUTER SCIENCE', 'score' => '17.00', 'coef' => '2.00', 'nxc' => '34.00', 'rank' => '2', 'competence' => 'Mastered (Excellent)', 'teacher' => 'MR. DAVID'],
    ['name' => 'PHYSICAL EDUCATION', 'score' => '15.00', 'coef' => '1.00', 'nxc' => '15.00', 'rank' => '6', 'competence' => 'Mastered (Very Good)', 'teacher' => 'COACH BROWN'],
    ['name' => 'FRENCH LANGUAGE', 'score' => '11.00', 'coef' => '3.00', 'nxc' => '33.00', 'rank' => '18', 'competence' => 'Developing', 'teacher' => 'PROF. CLAIRE']
];

// Calculate totals
$totalPoints = array_sum(array_column($subjects, 'nxc'));
$totalCoefficient = array_sum(array_column($subjects, 'coef'));
$average = $totalPoints / $totalCoefficient;

$mockData['total_general'] = number_format($totalPoints, 2);
$mockData['total_coef'] = number_format($totalCoefficient, 2);
$mockData['evaluation_average'] = number_format($average, 2);

echo "📊 SEQUENCE BULLETIN DATA:\n";
echo "Term Assessment: Term 1 (Sequence 1)\n";
echo "Student: {$mockData['student_name']}\n";
echo "Total Points: {$mockData['total_general']}\n";
echo "Total Coefficient: {$mockData['total_coef']}\n";
echo "Term Average: {$mockData['evaluation_average']}/20\n\n";

// Generate sequence subjects HTML - simpler format for sequences
function generateSequenceSubjectGroupHTML($groupName, $groupSubjects) {
    $html = '
    <div class="grades-section" style="margin-bottom: 20px;">
        <div class="group-header" style="background: #f0f0f0; padding: 8px; font-weight: bold; text-align: center; border: 1px solid #000;">' . $groupName . '</div>
        <table class="subjects-table" style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
            <tr style="background: #f8f8f8;">
                <th style="border: 1px solid #000; padding: 5px; text-align: left; width: 25%;">SUBJECT</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 12%;">MARKS /20</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 10%;">COEF.</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 10%;">(NXC)</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 10%;">RANK</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 13%;">COMPETENCY</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 20%;">TEACHER NAMES</th>
            </tr>';

    $totalCoef = 0;
    $totalPoints = 0;

    foreach ($groupSubjects as $subject) {
        $totalCoef += floatval($subject['coef']);
        $totalPoints += floatval($subject['nxc']);

        $html .= '
            <tr>
                <td style="border: 1px solid #000; padding: 5px; text-align: left;">' . strtoupper($subject['name']) . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $subject['score'] . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $subject['coef'] . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $subject['nxc'] . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $subject['rank'] . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 11px;">' . $subject['competence'] . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 11px;">' . strtoupper($subject['teacher']) . '</td>
            </tr>';
    }

    $groupAverage = $totalCoef > 0 ? $totalPoints / $totalCoef : 0;

    $html .= '
            <tr class="total-row" style="background: #f0f0f0; font-weight: bold;">
                <td style="border: 1px solid #000; padding: 5px; text-align: left;">TOTAL</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalPoints, 2) . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalCoef, 2) . '</td>
                <td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Group Avg: ' . number_format($groupAverage, 2) . '</td>
                <td colspan="2" style="border: 1px solid #000; padding: 5px; text-align: center;">Rank: 1st - Group Gen Avg: ' . number_format($groupAverage + 2, 2) . '</td>
            </tr>
        </table>
    </div>';

    return $html;
}

// Group subjects
$literarySubjects = array_slice($subjects, 0, 4);
$scientificSubjects = array_slice($subjects, 4, 3);
$practicalSubjects = array_slice($subjects, 7, 3);

$subjectGroupsHTML =
    generateSequenceSubjectGroupHTML('GROUP A: LITERARY SUBJECTS', $literarySubjects) .
    generateSequenceSubjectGroupHTML('GROUP B: SCIENTIFIC SUBJECTS', $scientificSubjects) .
    generateSequenceSubjectGroupHTML('GROUP C: PRACTICAL SUBJECTS', $practicalSubjects);

// Complete HTML template for sequence bulletin
$htmlContent = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Report Card - Term Assessment - CPBD</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #000; background: white; padding: 10px; }
        .bulletin-container { width: 100%; background: white; border: 1px solid #EFE9F3; }
        .header { background: #EFE9F3; color: black; padding: 5px; text-align: center; }
        .school-info { display: table; width: 100%; margin-bottom: 3px; }
        .school-left, .school-right { display: table-cell; width: 32%; vertical-align: middle; font-size: 7px; padding: 2px; }
        .school-left { text-align: center; padding-left: 3px; }
        .school-right { text-align: center; padding-right: 3px; }
        .school-left div, .school-right div { margin: 1.5px 0; line-height: 1.3; }
        .school-center { display: table-cell; width: 36%; text-align: center; vertical-align: middle; padding: 4px; }
        .logo { width: 70px; height: 70px; margin: 0 auto; display: block; }
        .bulletin-title { font-size: 14px; font-weight: bold; margin: 4px 0 2px 0; text-align: center; }
        .student-info { background: #EFE9F3; color: black; padding: 6px 8px; margin-bottom: 12px; padding-left: 10%; }
        .student-info table { width: 100%; border-collapse: collapse; }
        .student-info td { padding: 2px 4px; font-size: 9px; vertical-align: middle; line-height: 1.3; }
        .info-label { font-weight: bold; width: 110px; min-width: 110px; }
        .grades-section { margin-bottom: 20px; }
        .group-header { background: #EFE9F3; color: black; padding: 5px; font-weight: bold; font-size: 14px; text-transform: uppercase; text-align: center; }
        .subjects-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .subjects-table th, .subjects-table td { border: 1px solid #000; padding: 6px 4px; text-align: center; vertical-align: middle; }
        .subjects-table th { background: #EFE9F3; color: black; font-weight: bold; font-size: 9px; }
        .subject-name { text-align: left !important; font-weight: 600; padding-left: 8px !important; }
        .total-row { background: #f3e8ff; font-weight: bold; }
        .summary-section { display: table; width: 100%; margin-top: 20px; }
        .discipline-table, .summary-stats { display: table-cell; width: 48%; vertical-align: top; padding: 10px; }
        .discipline-table { border: 2px solid #EFE9F3; margin-right: 4%; }
        .summary-stats { border: 1px solid #ddd; }
        .stat-item { display: table; width: 100%; padding: 6px 0; border-bottom: 1px solid #e0e0e0; }
        .stat-label { display: table-cell; width: 70%; }
        .stat-value { display: table-cell; width: 30%; font-weight: bold; color: #333; text-align: right; }
        .observations { background: #f3e8ff; padding: 15px; margin-top: 15px; text-align: center; border: 2px solid #EFE9F3; }
        .footer-notes { margin-top: 20px; padding: 12px; border: 1px solid #000; font-size: 10px; display: table; width: 100%; }
        .notes-left { display: table-cell; width: 65%; vertical-align: top; }
        .footer-signature { display: table-cell; width: 35%; text-align: right; font-size: 11px; font-weight: bold; vertical-align: top; }
        .legend { margin-top: 15px; border-top: 1px solid #e0e0e0; padding-top: 10px; }
        .legend h4 { margin-bottom: 5px; font-size: 11px; }
        .legend-items { display: flex; flex-wrap: wrap; gap: 8px; font-size: 9px; }
        .discipline-header { background: #EFE9F3; color: black; text-align: center; padding: 8px; font-weight: bold; font-size: 12px; }
        .discipline-subheader { display: table; width: 100%; background: #EFE9F3; color: black; }
        .discipline-subheader div { display: table-cell; padding: 6px 2px; text-align: center; font-size: 9px; font-weight: 600; }
        .discipline-third-row { display: table; width: 100%; background: #f3e8ff; color: black; }
        .discipline-third-row div { display: table-cell; padding: 4px 1px; text-align: center; font-size: 8px; }
        .discipline-data { display: table; width: 100%; background: white; }
        .discipline-data div { display: table-cell; padding: 6px 1px; text-align: center; font-size: 10px; border-bottom: 1px solid #e0e0e0; }
        .visa-row { background: #f8f9fa; padding: 10px; text-align: center; font-weight: bold; color: #333; border-top: 1px solid #e0e0e0; }
    </style>
</head>
<body>
    <div class="bulletin-container">
        <div class="header">
            <div class="school-info">
                <div class="school-left">
                    <div><strong>REPUBLIQUE DU CAMEROUN</strong></div>
                    <div><strong>Paix - Travail - Patrie</strong></div>
                    <div><strong>MINISTÈRE DES ENSEIGNEMENTS SECONDAIRES</strong></div>
                    <div><strong>COLLÈGE POLYVALENT BILINGUE DE DOUALA</strong></div>
                    <div>B.P.: 4100 Douala Tél.: 233 43 25 47</div>
                    <div>E-mail: info@cpbdyassa.com</div>
                    <div>YASSA</div>
                </div>
                <div class="school-center">
                    <div style="width: 70px; height: 70px; background: #ddd; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 8px;">LOGO</div>
                </div>
                <div class="school-right">
                    <div><strong>REPUBLIC OF CAMEROON</strong></div>
                    <div><strong>Peace - Work - Fatherland</strong></div>
                    <div><strong>MINISTRY OF SECONDARY EDUCATION</strong></div>
                    <div><strong>BILINGUAL COMPREHENSIVE COLLEGE DLA</strong></div>
                    <div>P.O. Box: 4100 Douala Phone: 233 43 25 47</div>
                    <div>E-mail: info@cpbdyassa.com</div>
                    <div>YASSA</div>
                </div>
            </div>
            <div class="bulletin-title">' . $mockData['bulletin_title'] . '</div>
        </div>

        <div class="student-info">
            <table>
                <tr>
                    <td class="info-label">' . $mockData['name_label'] . '</td>
                    <td>' . $mockData['student_name'] . '</td>
                    <td class="info-label">' . $mockData['birth_date_label'] . '</td>
                    <td>' . $mockData['birth_date'] . '</td>
                    <td class="info-label">' . $mockData['birth_place_label'] . '</td>
                    <td>' . $mockData['birth_place'] . '</td>
                </tr>
                <tr>
                    <td class="info-label">' . $mockData['class_label'] . '</td>
                    <td>' . $mockData['class_name'] . '</td>
                    <td class="info-label">' . $mockData['class_size_label'] . '</td>
                    <td>' . $mockData['class_size'] . '</td>
                    <td class="info-label">' . $mockData['main_teacher_label'] . '</td>
                    <td>' . $mockData['main_teacher'] . '</td>
                </tr>
                <tr>
                    <td class="info-label">' . $mockData['student_number_label'] . '</td>
                    <td>' . $mockData['student_number'] . '</td>
                    <td class="info-label">' . $mockData['evaluation_label'] . '</td>
                    <td>N°' . $mockData['evaluation_number'] . '</td>
                    <td class="info-label">' . $mockData['school_year_label'] . '</td>
                    <td>' . $mockData['school_year'] . '</td>
                </tr>
            </table>
        </div>

        ' . $subjectGroupsHTML . '

        <div class="summary-section">
            <div class="discipline-table">
                <div class="discipline-header">' . $mockData['discipline_header'] . '</div>

                <div class="discipline-subheader">
                    <div style="width: 16.66%; border-right: 1px solid #ccc;">Late (h)</div>
                    <div style="width: 16.66%; border-right: 1px solid #ccc;">Abs (h)</div>
                    <div style="width: 16.66%; border-right: 1px solid #ccc;">Blame</div>
                    <div style="width: 16.66%; border-right: 1px solid #ccc;">Warning</div>
                    <div style="width: 16.66%; border-right: 1px solid #ccc;">Cons (h)</div>
                    <div style="width: 16.66%;">Excl (d)</div>
                </div>

                <div class="discipline-third-row">
                    <div style="width: 8.33%; border-right: 1px solid #ddd;">Just</div>
                    <div style="width: 8.33%; border-right: 1px solid #ddd;">Unjust</div>
                    <div style="width: 8.33%; border-right: 1px solid #ddd;">Just</div>
                    <div style="width: 8.33%; border-right: 1px solid #ddd;">Unjust</div>
                    <div style="width: 8.33%; border-right: 1px solid #ddd;">Cond</div>
                    <div style="width: 8.33%; border-right: 1px solid #ddd;">Work</div>
                    <div style="width: 8.33%; border-right: 1px solid #ddd;">Cond</div>
                    <div style="width: 8.33%; border-right: 1px solid #ddd;">Work</div>
                    <div style="width: 16.66%;">-</div>
                    <div style="width: 16.66%;">-</div>
                </div>

                <div class="discipline-data">
                    <div style="width: 8.33%; border-right: 1px solid #e0e0e0;">-</div>
                    <div style="width: 8.33%; border-right: 1px solid #e0e0e0;">-</div>
                    <div style="width: 8.33%; border-right: 1px solid #e0e0e0;">-</div>
                    <div style="width: 8.33%; border-right: 1px solid #e0e0e0;">-</div>
                    <div style="width: 8.33%; border-right: 1px solid #e0e0e0;">-</div>
                    <div style="width: 8.33%; border-right: 1px solid #e0e0e0;">-</div>
                    <div style="width: 8.33%; border-right: 1px solid #e0e0e0;">-</div>
                    <div style="width: 8.33%; border-right: 1px solid #e0e0e0;">-</div>
                    <div style="width: 16.66%;">-</div>
                    <div style="width: 16.66%;">-</div>
                </div>

                <div class="visa-row">
                    ' . $mockData['visa_parent_label'] . '
                </div>
            </div>

            <div class="summary-stats">
                <h3>' . $mockData['general_results_header'] . '</h3>
                <div class="stat-item">
                    <div class="stat-label">' . $mockData['total_general_label'] . '</div>
                    <div class="stat-value">' . $mockData['total_general'] . '</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">' . $mockData['total_coef_label'] . '</div>
                    <div class="stat-value">' . $mockData['total_coef'] . '</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">' . $mockData['evaluation_average_label'] . '</div>
                    <div class="stat-value">' . $mockData['evaluation_average'] . ' / 20</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">' . $mockData['rank_label'] . '</div>
                    <div class="stat-value">3 / ' . $mockData['class_size'] . '</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">' . $mockData['class_average_label'] . '</div>
                    <div class="stat-value">11.77 / 20</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">' . $mockData['first_average_label'] . '</div>
                    <div class="stat-value">18.45 / 20</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">' . $mockData['last_average_label'] . '</div>
                    <div class="stat-value">5.20 / 20</div>
                </div>

                <div class="legend">
                    <h4>' . $mockData['legend_title'] . '</h4>
                    <div class="legend-items">
                        <span><strong>Mastered:</strong> Competency achieved</span>
                        <span><strong>Developing:</strong> In progress</span>
                        <span><strong>Beginning:</strong> Needs improvement</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="observations">
            <h3>' . $mockData['appreciation_header'] . '</h3>
            <p><strong>' . $mockData['general_appreciation'] . '</strong></p>
        </div>

        <div class="footer-notes">
            <div class="notes-left">
                <div style="margin-bottom: 8px; padding: 3px 0;">
                    <strong>NB:</strong> Only one copy of this report card is issued. The student may request a certified copy.
                </div>
                <div style="margin-bottom: 8px; padding: 3px 0;">
                    <strong>NB:</strong> Il n\'est délivré qu\'un seul exemplaire de ce bulletin. L\'élève pourra se faire établir une copie certifiée.
                </div>
            </div>
            <div class="footer-signature">
                <div style="margin-bottom: 30px;">' . $mockData['made_in_douala'] . ' ' . $mockData['current_date'] . '</div>
                <div style="margin-top: 10px;">' . $mockData['principal_title'] . '</div>
            </div>
        </div>
    </div>
</body>
</html>';

// Generate PDF
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('isPhpEnabled', false);
$options->set('isFontSubsettingEnabled', true);
$options->set('dpi', 120);
$options->set('debugKeepTemp', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($htmlContent);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfContent = $dompdf->output();
$pdfFilename = 'anglophone_SEQUENCE_bulletin_' . date('Y-m-d_H-i-s') . '.pdf';
$pdfPath = __DIR__ . '/' . $pdfFilename;

file_put_contents($pdfPath, $pdfContent);

echo "✅ SEQUENCE PDF Generated successfully!\n";
echo "📁 File location: $pdfPath\n";
echo "📏 File size: " . number_format(filesize($pdfPath) / 1024, 2) . " KB\n\n";

echo "🎯 ANGLOPHONE SEQUENCE FEATURES:\n";
echo "================================\n";
echo "✅ Title: 'STUDENT REPORT CARD - TERM ASSESSMENT'\n";
echo "✅ Sequence format: Simple 7-column layout\n";
echo "✅ English headers: 'SUBJECT', 'MARKS /20', 'COMPETENCY'\n";
echo "✅ Term-specific labels: 'Term Assessment', 'Term Results'\n";
echo "✅ All grades realistic /20\n";
echo "✅ English competencies and appreciations\n";
echo "✅ Complete discipline table\n\n";

echo "📊 SEQUENCE SUBJECTS (/20):\n";
echo "===========================\n";
foreach ($subjects as $i => $subject) {
    echo sprintf("% 2d. %-20s | Score: %5s/20 | Coef: %4s | Total: %5s | %s\n",
        $i + 1,
        $subject['name'],
        $subject['score'],
        $subject['coef'],
        $subject['nxc'],
        substr($subject['competence'], 0, 15)
    );
}

echo "\n🎉 BULLETIN DE SÉQUENCE ANGLOPHONE TERMINÉ !\n";
echo "Le système supporte maintenant les bulletins de séquence ET de trimestre\n";
echo "pour les sections Francophone et Anglophone ! 🇫🇷🇬🇧✨\n";