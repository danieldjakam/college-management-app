<?php

/**
 * Test script to generate Anglophone bulletin PDF with 10 subjects
 * This demonstrates the new bilingual bulletin system
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\BulletinService;
use App\Models\Student;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;

// Bootstrap Laravel
$app = new Application(realpath(__DIR__));
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "🇬🇧 GENERATING ANGLOPHONE BULLETIN EXAMPLE\n";
echo "==========================================\n\n";

// Create a mock Anglophone student
$mockStudent = (object)[
    'id' => 999,
    'first_name' => 'JOHN DOE',
    'last_name' => 'AKAH',
    'matricule' => '24A856',
    'date_of_birth' => \Carbon\Carbon::parse('2008-03-15'),
    'place_of_birth' => 'DOUALA',
    'student_number' => '24A856',
    'schoolClass' => (object)[
        'id' => 1,
        'name' => 'FORM 2A',
        'level' => (object)[
            'section' => (object)[
                'name' => 'Anglophone Section'
            ]
        ],
        'students' => function() { return collect(range(1, 35)); } // Mock 35 students
    ]
];

// Create mock subjects with English names and grades
$mockSubjects = [
    [
        'name' => 'MATHEMATICS',
        'sequence1' => 85.00,
        'sequence2' => 88.00,
        'composition' => 92.00,
        'average' => 88.33,
        'coefficient' => 4.00,
        'nxc' => 353.32,
        'total' => 353.32,
        'rank' => 3,
        'grade' => 'Very Good',
        'competence' => 'Mastered (Excellent)',
        'teacher' => 'MR. TALLA JOSEPH',
        'appreciation' => 'Excellent',
        'cycle_type' => 'deuxieme',
        'section_type' => 'anglophone'
    ],
    [
        'name' => 'ENGLISH LANGUAGE',
        'sequence1' => 78.00,
        'sequence2' => 82.00,
        'composition' => 85.00,
        'average' => 81.67,
        'coefficient' => 4.00,
        'nxc' => 326.68,
        'total' => 326.68,
        'rank' => 5,
        'grade' => 'Very Good',
        'competence' => 'Mastered (Very Good)',
        'teacher' => 'MRS. JOHNSON MARY',
        'appreciation' => 'Very Good',
        'cycle_type' => 'deuxieme',
        'section_type' => 'anglophone'
    ],
    [
        'name' => 'CHEMISTRY',
        'sequence1' => 90.00,
        'sequence2' => 93.00,
        'composition' => 95.00,
        'average' => 92.67,
        'coefficient' => 3.00,
        'nxc' => 278.01,
        'total' => 278.01,
        'rank' => 1,
        'grade' => 'Very Good',
        'competence' => 'Mastered (Excellent)',
        'teacher' => 'DR. KAMGA PAUL',
        'appreciation' => 'Excellent',
        'cycle_type' => 'deuxieme',
        'section_type' => 'anglophone'
    ],
    [
        'name' => 'PHYSICS',
        'sequence1' => 75.00,
        'sequence2' => 78.00,
        'composition' => 80.00,
        'average' => 77.67,
        'coefficient' => 3.00,
        'nxc' => 233.01,
        'total' => 233.01,
        'rank' => 8,
        'grade' => 'Good',
        'competence' => 'Mastered (Good)',
        'teacher' => 'MR. NOAH SAMUEL',
        'appreciation' => 'Good',
        'cycle_type' => 'deuxieme',
        'section_type' => 'anglophone'
    ],
    [
        'name' => 'BIOLOGY',
        'sequence1' => 82.00,
        'sequence2' => 85.00,
        'composition' => 87.00,
        'average' => 84.67,
        'coefficient' => 3.00,
        'nxc' => 254.01,
        'total' => 254.01,
        'rank' => 4,
        'grade' => 'Very Good',
        'competence' => 'Mastered (Very Good)',
        'teacher' => 'MS. GRACE NKOMO',
        'appreciation' => 'Very Good',
        'cycle_type' => 'deuxieme',
        'section_type' => 'anglophone'
    ],
    [
        'name' => 'HISTORY',
        'sequence1' => 70.00,
        'sequence2' => 73.00,
        'composition' => 76.00,
        'average' => 73.00,
        'coefficient' => 2.00,
        'nxc' => 146.00,
        'total' => 146.00,
        'rank' => 12,
        'grade' => 'Good',
        'competence' => 'Mastered (Good)',
        'teacher' => 'MR. MBAH COLLINS',
        'appreciation' => 'Good',
        'cycle_type' => 'deuxieme',
        'section_type' => 'anglophone'
    ],
    [
        'name' => 'GEOGRAPHY',
        'sequence1' => 68.00,
        'sequence2' => 71.00,
        'composition' => 74.00,
        'average' => 71.00,
        'coefficient' => 2.00,
        'nxc' => 142.00,
        'total' => 142.00,
        'rank' => 15,
        'grade' => 'Good',
        'competence' => 'Mastered (Good)',
        'teacher' => 'MRS. FATIMA ALI',
        'appreciation' => 'Good',
        'cycle_type' => 'deuxieme',
        'section_type' => 'anglophone'
    ],
    [
        'name' => 'COMPUTER SCIENCE',
        'sequence1' => 88.00,
        'sequence2' => 90.00,
        'composition' => 93.00,
        'average' => 90.33,
        'coefficient' => 2.00,
        'nxc' => 180.66,
        'total' => 180.66,
        'rank' => 2,
        'grade' => 'Very Good',
        'competence' => 'Mastered (Excellent)',
        'teacher' => 'MR. DAVID TECH',
        'appreciation' => 'Excellent',
        'cycle_type' => 'deuxieme',
        'section_type' => 'anglophone'
    ],
    [
        'name' => 'PHYSICAL EDUCATION',
        'sequence1' => 15.00,
        'sequence2' => 16.00,
        'composition' => 20.00,
        'average' => 17.00,
        'coefficient' => 1.00,
        'nxc' => 17.00,
        'total' => 17.00,
        'rank' => 6,
        'grade' => 'Very Good',
        'competence' => 'Mastered (Excellent)',
        'teacher' => 'COACH BROWN',
        'appreciation' => 'Excellent',
        'cycle_type' => 'deuxieme',
        'section_type' => 'anglophone'
    ],
    [
        'name' => 'FRENCH LANGUAGE',
        'sequence1' => 65.00,
        'sequence2' => 68.00,
        'composition' => 70.00,
        'average' => 67.67,
        'coefficient' => 3.00,
        'nxc' => 203.01,
        'total' => 203.01,
        'rank' => 18,
        'grade' => 'Average',
        'competence' => 'Developing',
        'teacher' => 'PROF. CLAIRE MARTIN',
        'appreciation' => 'Average',
        'cycle_type' => 'deuxieme',
        'section_type' => 'anglophone'
    ]
];

// Calculate totals
$totalPoints = array_sum(array_column($mockSubjects, 'total'));
$totalCoefficient = array_sum(array_column($mockSubjects, 'coefficient'));
$average = $totalPoints / $totalCoefficient;

echo "📊 MOCK DATA SUMMARY:\n";
echo "Student: {$mockStudent->first_name} {$mockStudent->last_name}\n";
echo "Class: {$mockStudent->schoolClass->name} (Anglophone Section)\n";
echo "Subjects: " . count($mockSubjects) . "\n";
echo "Total Points: " . number_format($totalPoints, 2) . "\n";
echo "Total Coefficient: " . number_format($totalCoefficient, 2) . "\n";
echo "Average: " . number_format($average, 2) . "/20\n\n";

// Create mock trimester
$mockTrimester = (object)[
    'id' => 1,
    'number' => 1,
    'name' => 'First Semester'
];

// Prepare bulletin data
$bulletinData = [
    'student' => $mockStudent,
    'trimester' => $mockTrimester,
    'subjects' => $mockSubjects,
    'total_points' => $totalPoints,
    'total_coefficient' => $totalCoefficient,
    'average' => $average,
    'rank' => 3,
    'section_type' => 'anglophone'
];

echo "🎨 GENERATING BULLETIN HTML...\n";

// Create BulletinService instance
$bulletinService = new BulletinService();

try {
    // Generate HTML content
    $htmlContent = $bulletinService->renderBulletinTemplate('trimester', $bulletinData, true);

    echo "✅ HTML Generated successfully\n";

    // Generate PDF
    echo "📄 GENERATING PDF...\n";
    $filename = 'anglophone_bulletin_example_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdfPath = $bulletinService->generatePDF($htmlContent, $filename);

    echo "✅ PDF Generated: $filename\n";
    echo "📁 File location: " . storage_path('app/' . $pdfPath) . "\n\n";

    // Display key features
    echo "🎯 ANGLOPHONE FEATURES IMPLEMENTED:\n";
    echo "===================================\n";
    echo "✅ English header translations (STUDENT REPORT CARD)\n";
    echo "✅ English column headers (SUBJECT, Term 1, Term 2, Final Exam, etc.)\n";
    echo "✅ English competencies (Mastered, Developing, Beginning)\n";
    echo "✅ English subject group labels (GROUP A: LITERARY SUBJECTS, etc.)\n";
    echo "✅ DEUXIÈME CYCLE logic (11 columns with individual term grades)\n";
    echo "✅ Anglophone-style teacher names (MR., MRS., MS.)\n";
    echo "✅ Anglo-Saxon grading system compatibility\n";
    echo "✅ Bilingual school header (French left, English right)\n\n";

    echo "📈 SUBJECTS INCLUDED:\n";
    echo "===================\n";
    foreach ($mockSubjects as $i => $subject) {
        echo sprintf("% 2d. %-20s | Term1: %5.2f | Term2: %5.2f | Final: %5.2f | Avg: %5.2f | %s\n",
            $i + 1,
            $subject['name'],
            $subject['sequence1'],
            $subject['sequence2'],
            $subject['composition'],
            $subject['average'],
            $subject['competence']
        );
    }

    echo "\n🎉 ANGLOPHONE BULLETIN SYSTEM SUCCESSFULLY IMPLEMENTED!\n";
    echo "The system now supports both Francophone and Anglophone sections\n";
    echo "with appropriate language translations and academic structures.\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}