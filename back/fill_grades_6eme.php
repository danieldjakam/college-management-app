<?php

/**
 * Script pour remplir les notes des élèves de 6ème
 * Séquence 1, Séquence 2, et Composition Trimestre 1
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use App\Models\ClassSeriesSubject;
use App\Models\Sequence;
use App\Models\Trimester;
use App\Models\Grade;
use App\Models\SchoolYear;

echo "🎓 REMPLISSAGE DES NOTES - 6ème\n";
echo "================================\n\n";

// Get current school year
$schoolYear = SchoolYear::where('is_active', true)->first();
if (!$schoolYear) {
    die("❌ Aucune année scolaire active trouvée\n");
}
echo "📅 Année scolaire: {$schoolYear->name}\n\n";

// Get sequences and trimester
$seq1 = Sequence::where('number', 1)->first();
$seq2 = Sequence::where('number', 2)->first();
$trim1 = Trimester::where('number', 1)->first();

if (!$seq1 || !$seq2 || !$trim1) {
    die("❌ Séquences ou trimestre non trouvés\n");
}

echo "✅ Séquence 1: ID {$seq1->id}\n";
echo "✅ Séquence 2: ID {$seq2->id}\n";
echo "✅ Trimestre 1: ID {$trim1->id}\n\n";

// Get 6ème class series
$classSeries = \App\Models\ClassSeries::where('class_id', 13)->first();
if (!$classSeries) {
    die("❌ Classe de 6ème non trouvée\n");
}

echo "🎒 Classe: {$classSeries->name} (ID: {$classSeries->id})\n\n";

// Get students
$students = Student::where('class_series_id', $classSeries->id)->get();
echo "👨‍🎓 Élèves trouvés: {$students->count()}\n\n";

// Get subjects
$subjects = ClassSeriesSubject::where('class_series_id', $classSeries->id)
    ->with('subject')
    ->get();
echo "📚 Matières trouvées: {$subjects->count()}\n\n";

// Create evaluations for each subject if they don't exist
echo "🔧 Création des évaluations...\n";
$evaluations = [];

foreach ($subjects as $classSeriesSubject) {
    $subjectName = $classSeriesSubject->subject->name;

    // Seq 1 evaluation
    $evalSeq1 = \App\Models\Evaluation::firstOrCreate([
        'class_series_subject_id' => $classSeriesSubject->id,
        'sequence_id' => $seq1->id,
        'school_year_id' => $schoolYear->id,
    ], [
        'name' => "{$subjectName} - Séquence 1",
        'evaluation_type' => 'sequence',
        'trimester_id' => $trim1->id,
        'date' => now(),
        'max_score' => 20,
        'coefficient' => $classSeriesSubject->coefficient,
    ]);

    // Seq 2 evaluation
    $evalSeq2 = \App\Models\Evaluation::firstOrCreate([
        'class_series_subject_id' => $classSeriesSubject->id,
        'sequence_id' => $seq2->id,
        'school_year_id' => $schoolYear->id,
    ], [
        'name' => "{$subjectName} - Séquence 2",
        'evaluation_type' => 'sequence',
        'trimester_id' => $trim1->id,
        'date' => now(),
        'max_score' => 20,
        'coefficient' => $classSeriesSubject->coefficient,
    ]);

    // Composition evaluation (use seq2 as placeholder for sequence_id)
    $evalComp = \App\Models\Evaluation::firstOrCreate([
        'class_series_subject_id' => $classSeriesSubject->id,
        'trimester_id' => $trim1->id,
        'evaluation_type' => 'composition',
        'sequence_id' => $seq2->id, // Composition is after Seq2
        'school_year_id' => $schoolYear->id,
    ], [
        'name' => "{$subjectName} - Composition Trimestre 1",
        'date' => now(),
        'max_score' => 20,
        'coefficient' => $classSeriesSubject->coefficient,
    ]);

    $evaluations[$classSeriesSubject->id] = [
        'seq1' => $evalSeq1->id,
        'seq2' => $evalSeq2->id,
        'comp' => $evalComp->id,
    ];
}

echo "✅ Évaluations créées\n\n";

// Grade ranges by subject difficulty
$gradeRanges = [
    'easy' => [12, 18],    // Matières plus faciles
    'medium' => [10, 16],  // Matières moyennes
    'hard' => [8, 15]      // Matières difficiles
];

$subjectDifficulty = [
    'Mathématiques' => 'hard',
    'Anglais' => 'medium',
    'Informatique' => 'medium',
    'Sciences' => 'medium',
    'Histoire' => 'medium',
    'Géographie' => 'medium',
];

$gradeCount = 0;

foreach ($students as $student) {
    echo "📝 {$student->first_name} {$student->last_name}:\n";

    foreach ($subjects as $classSeriesSubject) {
        $subjectName = $classSeriesSubject->subject->name;

        // Determine grade range
        $difficulty = $subjectDifficulty[$subjectName] ?? 'medium';
        list($minGrade, $maxGrade) = $gradeRanges[$difficulty];

        // Generate grades for Seq1
        $seq1Grade = rand($minGrade * 10, $maxGrade * 10) / 10;
        Grade::updateOrCreate(
            [
                'student_id' => $student->id,
                'evaluation_id' => $evaluations[$classSeriesSubject->id]['seq1'],
                'school_year_id' => $schoolYear->id,
            ],
            [
                'class_series_subject_id' => $classSeriesSubject->id,
                'sequence_id' => $seq1->id,
                'trimester_id' => $trim1->id,
                'score' => $seq1Grade,
                'max_score' => 20,
                'coefficient' => $classSeriesSubject->coefficient,
                'weighted_score' => $seq1Grade * $classSeriesSubject->coefficient,
            ]
        );

        // Generate grades for Seq2
        $seq2Grade = rand($minGrade * 10, $maxGrade * 10) / 10;
        Grade::updateOrCreate(
            [
                'student_id' => $student->id,
                'evaluation_id' => $evaluations[$classSeriesSubject->id]['seq2'],
                'school_year_id' => $schoolYear->id,
            ],
            [
                'class_series_subject_id' => $classSeriesSubject->id,
                'sequence_id' => $seq2->id,
                'trimester_id' => $trim1->id,
                'score' => $seq2Grade,
                'max_score' => 20,
                'coefficient' => $classSeriesSubject->coefficient,
                'weighted_score' => $seq2Grade * $classSeriesSubject->coefficient,
            ]
        );

        // Generate composition grade (usually slightly different from average)
        $avgSeq = ($seq1Grade + $seq2Grade) / 2;
        $compositionGrade = $avgSeq + rand(-20, 20) / 10; // +/- 2 points
        $compositionGrade = max($minGrade, min($maxGrade, $compositionGrade));
        $compositionGrade = round($compositionGrade, 1);

        Grade::updateOrCreate(
            [
                'student_id' => $student->id,
                'evaluation_id' => $evaluations[$classSeriesSubject->id]['comp'],
                'school_year_id' => $schoolYear->id,
            ],
            [
                'class_series_subject_id' => $classSeriesSubject->id,
                'sequence_id' => $seq2->id, // Composition is linked to seq2
                'trimester_id' => $trim1->id,
                'score' => $compositionGrade,
                'max_score' => 20,
                'coefficient' => $classSeriesSubject->coefficient,
                'weighted_score' => $compositionGrade * $classSeriesSubject->coefficient,
            ]
        );

        $gradeCount += 3;
        echo "  ✓ {$subjectName}: Seq1={$seq1Grade}, Seq2={$seq2Grade}, Comp=" . round($compositionGrade, 1) . "\n";
    }

    echo "\n";
}

echo "\n";
echo "✅ TERMINÉ!\n";
echo "📊 Total de notes créées: {$gradeCount}\n";
echo "👨‍🎓 Élèves: {$students->count()}\n";
echo "📚 Matières: {$subjects->count()}\n";
echo "🎯 Notes par élève: " . ($subjects->count() * 3) . " (Seq1 + Seq2 + Composition)\n\n";

echo "🎉 Vous pouvez maintenant générer les bulletins du Trimestre 1 pour la 6ème!\n";
