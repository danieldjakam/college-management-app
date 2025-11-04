<?php

/**
 * Script pour remplir les notes de TOUS les élèves de 6ème A
 * Utilise des insertions en masse pour être plus rapide
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Student;
use App\Models\ClassSeriesSubject;
use App\Models\Sequence;
use App\Models\Trimester;
use App\Models\Grade;
use App\Models\Evaluation;
use App\Models\SchoolYear;

echo "🎓 REMPLISSAGE DES NOTES - TOUS LES ÉLÈVES DE 6ème A\n";
echo "====================================================\n\n";

// Get current school year
$schoolYear = SchoolYear::where('is_active', true)->first();
if (!$schoolYear) {
    die("❌ Aucune année scolaire active trouvée\n");
}

// Get sequences and trimester
$seq1 = Sequence::where('number', 1)->first();
$seq2 = Sequence::where('number', 2)->first();
$trim1 = Trimester::where('number', 1)->first();

// Get 6ème A class series
$classSeries = \App\Models\ClassSeries::where('name', 'LIKE', '6%A')->first();
if (!$classSeries) {
    die("❌ Classe de 6ème A non trouvée\n");
}

echo "🎒 Classe: {$classSeries->name} (ID: {$classSeries->id})\n\n";

// Get students (skip JEAN MARIE who already has grades)
$students = Student::where('class_series_id', $classSeries->id)
    ->where(function($query) {
        $query->where('first_name', '!=', 'JEAN MARIE')
              ->orWhere('last_name', '!=', 'BAHA NDJOM');
    })
    ->get();

echo "👨‍🎓 Élèves à remplir: {$students->count()}\n";
echo "   (JEAN MARIE déjà rempli)\n\n";

// Get subjects
$subjects = ClassSeriesSubject::where('class_series_id', $classSeries->id)
    ->with('subject')
    ->get();
echo "📚 Matières: {$subjects->count()}\n\n";

// Get evaluations (already created by clean_and_refill_6eme.php)
$evaluations = [];
foreach ($subjects as $classSeriesSubject) {
    $evalSeq1 = Evaluation::where('class_series_subject_id', $classSeriesSubject->id)
        ->where('sequence_id', $seq1->id)
        ->where('evaluation_type', 'sequence')
        ->first();

    $evalSeq2 = Evaluation::where('class_series_subject_id', $classSeriesSubject->id)
        ->where('sequence_id', $seq2->id)
        ->where('evaluation_type', 'sequence')
        ->first();

    $evalComp = Evaluation::where('class_series_subject_id', $classSeriesSubject->id)
        ->where('trimester_id', $trim1->id)
        ->where('evaluation_type', 'composition')
        ->first();

    if (!$evalSeq1 || !$evalSeq2 || !$evalComp) {
        die("❌ Évaluations manquantes pour {$classSeriesSubject->subject->name}\n");
    }

    $evaluations[$classSeriesSubject->id] = [
        'seq1' => $evalSeq1->id,
        'seq2' => $evalSeq2->id,
        'comp' => $evalComp->id,
    ];
}

echo "✅ Évaluations chargées\n\n";

// Grade ranges by subject difficulty
$gradeRanges = [
    'easy' => [12, 18],
    'medium' => [10, 16],
    'hard' => [8, 15]
];

$subjectDifficulty = [
    'Mathématiques' => 'hard',
    'Anglais' => 'medium',
    'Informatique' => 'medium',
    'Sciences' => 'medium',
    'Histoire' => 'medium',
    'Géographie' => 'medium',
];

// Prepare grades array for bulk insert
$gradesToInsert = [];
$batchSize = 500; // Insert par lots de 500
$totalGrades = 0;
$studentCount = 0;

echo "📝 Génération des notes...\n\n";

foreach ($students as $student) {
    $studentCount++;

    if ($studentCount % 5 == 0) {
        echo "   Élève {$studentCount}/{$students->count()}: {$student->first_name} {$student->last_name}\n";
    }

    foreach ($subjects as $classSeriesSubject) {
        $subjectName = $classSeriesSubject->subject->name;

        // Determine grade range
        $difficulty = $subjectDifficulty[$subjectName] ?? 'medium';
        list($minGrade, $maxGrade) = $gradeRanges[$difficulty];

        // Generate grades for Seq1
        $seq1Grade = rand($minGrade * 10, $maxGrade * 10) / 10;
        $gradesToInsert[] = [
            'student_id' => $student->id,
            'evaluation_id' => $evaluations[$classSeriesSubject->id]['seq1'],
            'school_year_id' => $schoolYear->id,
            'class_series_subject_id' => $classSeriesSubject->id,
            'sequence_id' => $seq1->id,
            'trimester_id' => $trim1->id,
            'score' => $seq1Grade,
            'max_score' => 20,
            'coefficient' => $classSeriesSubject->coefficient,
            'weighted_score' => $seq1Grade * $classSeriesSubject->coefficient,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Generate grades for Seq2
        $seq2Grade = rand($minGrade * 10, $maxGrade * 10) / 10;
        $gradesToInsert[] = [
            'student_id' => $student->id,
            'evaluation_id' => $evaluations[$classSeriesSubject->id]['seq2'],
            'school_year_id' => $schoolYear->id,
            'class_series_subject_id' => $classSeriesSubject->id,
            'sequence_id' => $seq2->id,
            'trimester_id' => $trim1->id,
            'score' => $seq2Grade,
            'max_score' => 20,
            'coefficient' => $classSeriesSubject->coefficient,
            'weighted_score' => $seq2Grade * $classSeriesSubject->coefficient,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Generate composition grade
        $avgSeq = ($seq1Grade + $seq2Grade) / 2;
        $compositionGrade = $avgSeq + rand(-20, 20) / 10;
        $compositionGrade = max($minGrade, min($maxGrade, $compositionGrade));
        $compositionGrade = round($compositionGrade, 1);

        $gradesToInsert[] = [
            'student_id' => $student->id,
            'evaluation_id' => $evaluations[$classSeriesSubject->id]['comp'],
            'school_year_id' => $schoolYear->id,
            'class_series_subject_id' => $classSeriesSubject->id,
            'sequence_id' => $seq2->id,
            'trimester_id' => $trim1->id,
            'score' => $compositionGrade,
            'max_score' => 20,
            'coefficient' => $classSeriesSubject->coefficient,
            'weighted_score' => $compositionGrade * $classSeriesSubject->coefficient,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $totalGrades += 3;

        // Insert by batch
        if (count($gradesToInsert) >= $batchSize) {
            Grade::insert($gradesToInsert);
            echo "      ✓ {$totalGrades} notes insérées...\n";
            $gradesToInsert = [];
        }
    }
}

// Insert remaining grades
if (count($gradesToInsert) > 0) {
    Grade::insert($gradesToInsert);
}

echo "\n";
echo "✅ TERMINÉ!\n";
echo "📊 Total de notes créées: {$totalGrades}\n";
echo "👨‍🎓 Élèves: {$students->count()}\n";
echo "📚 Matières: {$subjects->count()}\n";
echo "🎯 Notes par élève: " . ($subjects->count() * 3) . " (Seq1 + Seq2 + Composition)\n\n";

// Calculate class statistics
echo "📈 STATISTIQUES DE LA CLASSE:\n";

$allStudents = Student::where('class_series_id', $classSeries->id)->pluck('id');
$studentsWithGrades = Grade::whereIn('student_id', $allStudents)
    ->where('sequence_id', $seq1->id)
    ->distinct('student_id')
    ->count();

echo "   Total élèves: " . $allStudents->count() . "\n";
echo "   Élèves avec notes Seq1: {$studentsWithGrades}\n";

echo "\n🎉 PRÊT! Les statistiques de classe sont maintenant complètes!\n";
