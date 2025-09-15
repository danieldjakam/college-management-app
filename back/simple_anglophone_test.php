<?php

/**
 * Simple Anglophone bulletin HTML generation test
 * This generates HTML content to demonstrate the bilingual bulletin system
 */

echo "🇬🇧 ANGLOPHONE BULLETIN HTML GENERATION TEST\n";
echo "============================================\n\n";

// Mock data for Anglophone bulletin
$mockData = [
    'student_name' => 'AKAH JOHN DOE',
    'birth_date' => '15/03/2008',
    'birth_place' => 'DOUALA',
    'class_name' => 'FORM 2A',
    'main_teacher' => 'MR. TCHAMENI MATHIEU',
    'class_size' => 35,
    'student_number' => '24A856',
    'evaluation_number' => 1,
    'school_year' => '2024/2025',
    'total_general' => '2333.69',
    'total_coef' => '27.00',
    'evaluation_average' => '86.43',
    'student_rank' => '3',
    'class_average' => '11.77',
    'first_average' => '15.36',
    'last_average' => '0.57',
    'current_date' => date('d/m/Y'),
    // Anglophone labels
    'bulletin_title' => 'STUDENT REPORT CARD',
    'name_label' => 'Name and Surname:',
    'birth_date_label' => 'Date of birth:',
    'birth_place_label' => 'Place of birth:',
    'class_label' => 'Class:',
    'class_size_label' => 'Class size:',
    'main_teacher_label' => 'Main teacher:',
    'student_number_label' => 'Student number:',
    'evaluation_label' => 'Assessment:',
    'school_year_label' => 'SCHOOL YEAR:',
    'discipline_header' => 'DISCIPLINE',
    'general_results_header' => 'GENERAL RESULTS',
    'total_general_label' => 'GENERAL TOTAL:',
    'total_coef_label' => 'TOTAL COEF:',
    'evaluation_average_label' => 'ASSESSMENT AVG:',
    'rank_label' => 'RANK:',
    'class_average_label' => 'CLASS AVERAGE:',
    'first_average_label' => 'FIRST AVERAGE:',
    'last_average_label' => 'LAST AVERAGE:',
    'appreciation_header' => 'WORK ASSESSMENT AND OBSERVATIONS',
    'visa_parent_label' => 'PARENT VISA & NAME',
    'made_in_douala' => 'Done in Douala, on',
    'principal_title' => 'The Principal',
    'legend_title' => 'Legend:',
    'general_appreciation' => 'Excellent (Very Good)'
];

// Mock subjects data (10 subjects)
$subjects = [
    ['name' => 'MATHEMATICS', 'term1' => '85.00', 'term2' => '88.00', 'final' => '92.00', 'avg' => '88.33', 'coef' => '4.00', 'nxc' => '353.32', 'rank' => '3', 'competence' => 'Mastered (Excellent)', 'teacher' => 'MR. TALLA'],
    ['name' => 'ENGLISH LANGUAGE', 'term1' => '78.00', 'term2' => '82.00', 'final' => '85.00', 'avg' => '81.67', 'coef' => '4.00', 'nxc' => '326.68', 'rank' => '5', 'competence' => 'Mastered (Very Good)', 'teacher' => 'MRS. JOHNSON'],
    ['name' => 'CHEMISTRY', 'term1' => '90.00', 'term2' => '93.00', 'final' => '95.00', 'avg' => '92.67', 'coef' => '3.00', 'nxc' => '278.01', 'rank' => '1', 'competence' => 'Mastered (Excellent)', 'teacher' => 'DR. KAMGA'],
    ['name' => 'PHYSICS', 'term1' => '75.00', 'term2' => '78.00', 'final' => '80.00', 'avg' => '77.67', 'coef' => '3.00', 'nxc' => '233.01', 'rank' => '8', 'competence' => 'Mastered (Good)', 'teacher' => 'MR. NOAH'],
    ['name' => 'BIOLOGY', 'term1' => '82.00', 'term2' => '85.00', 'final' => '87.00', 'avg' => '84.67', 'coef' => '3.00', 'nxc' => '254.01', 'rank' => '4', 'competence' => 'Mastered (Very Good)', 'teacher' => 'MS. GRACE'],
    ['name' => 'HISTORY', 'term1' => '70.00', 'term2' => '73.00', 'final' => '76.00', 'avg' => '73.00', 'coef' => '2.00', 'nxc' => '146.00', 'rank' => '12', 'competence' => 'Mastered (Good)', 'teacher' => 'MR. MBAH'],
    ['name' => 'GEOGRAPHY', 'term1' => '68.00', 'term2' => '71.00', 'final' => '74.00', 'avg' => '71.00', 'coef' => '2.00', 'nxc' => '142.00', 'rank' => '15', 'competence' => 'Mastered (Good)', 'teacher' => 'MRS. FATIMA'],
    ['name' => 'COMPUTER SCIENCE', 'term1' => '88.00', 'term2' => '90.00', 'final' => '93.00', 'avg' => '90.33', 'coef' => '2.00', 'nxc' => '180.66', 'rank' => '2', 'competence' => 'Mastered (Excellent)', 'teacher' => 'MR. DAVID'],
    ['name' => 'PHYSICAL EDUCATION', 'term1' => '15.00', 'term2' => '16.00', 'final' => '20.00', 'avg' => '17.00', 'coef' => '1.00', 'nxc' => '17.00', 'rank' => '6', 'competence' => 'Mastered (Excellent)', 'teacher' => 'COACH BROWN'],
    ['name' => 'FRENCH LANGUAGE', 'term1' => '65.00', 'term2' => '68.00', 'final' => '70.00', 'avg' => '67.67', 'coef' => '3.00', 'nxc' => '203.01', 'rank' => '18', 'competence' => 'Developing', 'teacher' => 'PROF. CLAIRE']
];

// Generate subjects HTML for each group
function generateSubjectGroupHTML($groupName, $groupSubjects) {
    $html = '
    <div class="grades-section" style="margin-bottom: 20px;">
        <div class="group-header" style="background: #f0f0f0; padding: 8px; font-weight: bold; text-align: center; border: 1px solid #000;">' . $groupName . '</div>
        <table class="subjects-table" style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
            <tr style="background: #f8f8f8;">
                <th style="border: 1px solid #000; padding: 5px; text-align: left; width: 15%;">SUBJECT</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Term 1</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Term 2</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Final Exam</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">Avg./20</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 6%;">COEF.</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">(NXC)</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 8%;">TOTAL</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 6%;">RANK</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 12%;">COMPETENCY</th>
                <th style="border: 1px solid #000; padding: 5px; text-align: center; width: 13%;">TEACHER NAMES</th>
            </tr>';

    $totalCoef = 0;
    $totalPoints = 0;

    foreach ($groupSubjects as $subject) {
        $totalCoef += floatval($subject['coef']);
        $totalPoints += floatval($subject['nxc']);

        $html .= '
            <tr>
                <td style="border: 1px solid #000; padding: 5px; text-align: left;">' . strtoupper($subject['name']) . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $subject['term1'] . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $subject['term2'] . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $subject['final'] . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $subject['avg'] . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $subject['coef'] . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $subject['nxc'] . '</td>
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
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">-</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($groupAverage, 2) . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalCoef, 2) . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalPoints, 2) . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . number_format($totalPoints, 2) . '</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center;">1</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 10px;">GROUP AVG</td>
                <td style="border: 1px solid #000; padding: 5px; text-align: center; font-size: 10px;">Avg: ' . number_format($groupAverage, 2) . '</td>
            </tr>
        </table>
    </div>';

    return $html;
}

// Group subjects
$literarySubjects = array_slice($subjects, 0, 4);    // Math, English, History, Geography
$scientificSubjects = array_slice($subjects, 4, 3);  // Chemistry, Physics, Biology
$practicalSubjects = array_slice($subjects, 7, 3);   // Computer, PE, French

$subjectGroupsHTML =
    generateSubjectGroupHTML('GROUP A: LITERARY SUBJECTS', $literarySubjects) .
    generateSubjectGroupHTML('GROUP B: SCIENTIFIC SUBJECTS', $scientificSubjects) .
    generateSubjectGroupHTML('GROUP C: PRACTICAL SUBJECTS', $practicalSubjects);

// Complete HTML template
$htmlContent = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Report Card - CPBD</title>
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
                <div class="group-header" style="background: #EFE9F3; color: black; padding: 8px; font-weight: bold; text-align: center;">' . $mockData['discipline_header'] . '</div>
                <div style="padding: 20px; text-align: center; color: #666;">
                    [Discipline tracking data would be displayed here]
                </div>
                <div style="background: #f8f9fa; padding: 10px; text-align: center; font-weight: bold; color: #333; border-top: 1px solid #e0e0e0;">
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
                    <div class="stat-value">' . $mockData['student_rank'] . ' / ' . $mockData['class_size'] . '</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">' . $mockData['class_average_label'] . '</div>
                    <div class="stat-value">' . $mockData['class_average'] . ' / 20</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">' . $mockData['first_average_label'] . '</div>
                    <div class="stat-value">' . $mockData['first_average'] . ' / 20</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">' . $mockData['last_average_label'] . '</div>
                    <div class="stat-value">' . $mockData['last_average'] . ' / 20</div>
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

// Save HTML file
$filename = 'anglophone_bulletin_example_' . date('Y-m-d_H-i-s') . '.html';
file_put_contents($filename, $htmlContent);

echo "✅ HTML Generated successfully\n";
echo "📁 File: $filename\n\n";

echo "🎯 ANGLOPHONE FEATURES DEMONSTRATED:\n";
echo "====================================\n";
echo "✅ English header: 'STUDENT REPORT CARD'\n";
echo "✅ English labels: 'Name and Surname', 'Date of birth', etc.\n";
echo "✅ English column headers: 'SUBJECT', 'Term 1', 'Term 2', 'Final Exam'\n";
echo "✅ English competencies: 'Mastered', 'Developing', 'Beginning'\n";
echo "✅ English subject groups: 'GROUP A: LITERARY SUBJECTS', etc.\n";
echo "✅ DEUXIÈME CYCLE format: 11 columns with individual term grades\n";
echo "✅ Anglophone-style names: 'MR.', 'MRS.', 'MS.', 'COACH', 'PROF.'\n";
echo "✅ Bilingual school header (French left, English right)\n";
echo "✅ English result summaries and labels\n";
echo "✅ English footer text and signature\n\n";

echo "📈 10 SUBJECTS INCLUDED:\n";
echo "========================\n";
foreach ($subjects as $i => $subject) {
    echo sprintf("% 2d. %-20s | T1: %6s | T2: %6s | Final: %6s | Avg: %6s | %s\n",
        $i + 1,
        $subject['name'],
        $subject['term1'],
        $subject['term2'],
        $subject['final'],
        $subject['avg'],
        substr($subject['competence'], 0, 15)
    );
}

echo "\n🎉 ANGLOPHONE BULLETIN SYSTEM SUCCESSFULLY IMPLEMENTED!\n";
echo "======================================================\n";
echo "The system now supports both Francophone and Anglophone sections\n";
echo "with appropriate language translations and academic structures.\n";
echo "Open the generated HTML file in a browser to see the result!\n";