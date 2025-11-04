<?php

/**
 * Script pour nettoyer et recréer correctement les notes de la 6ème A
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

echo "🧹 NETTOYAGE ET RECRÉATION DES NOTES - 6ème A\n";
echo "==============================================\n\n";

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

// Get 6ème A class series
$classSeries = \App\Models\ClassSeries::where('name', 'LIKE', '6%A')->first();
if (!$classSeries) {
    die("❌ Classe de 6ème A non trouvée\n");
}

echo "🎒 Classe: {$classSeries->name} (ID: {$classSeries->id})\n\n";

// Get students
$students = Student::where('class_series_id', $classSeries->id)->get();
echo "👨‍🎓 Élèves: {$students->count()}\n\n";

// Get subjects
$subjects = ClassSeriesSubject::where('class_series_id', $classSeries->id)
    ->with('subject')
    ->get();
echo "📚 Matières: {$subjects->count()}\n\n";

// STEP 1: Delete all grades for students in 6ème A
echo "🗑️  ÉTAPE 1: Suppression des notes existantes...\n";
$deletedGrades = Grade::whereIn('student_id', $students->pluck('id'))->delete();
echo "   ✅ {$deletedGrades} notes supprimées\n\n";

// STEP 2: Delete all evaluations for 6ème A
echo "🗑️  ÉTAPE 2: Suppression des évaluations existantes...\n";
$deletedEvals = Evaluation::whereIn('class_series_subject_id', $subjects->pluck('id'))->delete();
echo "   ✅ {$deletedEvals} évaluations supprimées\n\n";

// STEP 3: Create evaluations (1 per subject per sequence)
echo "🔧 ÉTAPE 3: Création des évaluations...\n";
$evaluations = [];

foreach ($subjects as $classSeriesSubject) {
    $subjectName = $classSeriesSubject->subject->name;

    // Seq 1 evaluation
    $evalSeq1 = Evaluation::create([
        'class_series_subject_id' => $classSeriesSubject->id,
        'sequence_id' => $seq1->id,
        'school_year_id' => $schoolYear->id,
        'name' => "{$subjectName} - Séquence 1",
        'evaluation_type' => 'sequence',
        'trimester_id' => $trim1->id,
        'date' => now(),
        'max_score' => 20,
        'coefficient' => $classSeriesSubject->coefficient,
    ]);

    // Seq 2 evaluation
    $evalSeq2 = Evaluation::create([
        'class_series_subject_id' => $classSeriesSubject->id,
        'sequence_id' => $seq2->id,
        'school_year_id' => $schoolYear->id,
        'name' => "{$subjectName} - Séquence 2",
        'evaluation_type' => 'sequence',
        'trimester_id' => $trim1->id,
        'date' => now(),
        'max_score' => 20,
        'coefficient' => $classSeriesSubject->coefficient,
    ]);

    // Composition evaluation
    $evalComp = Evaluation::create([
        'class_series_subject_id' => $classSeriesSubject->id,
        'trimester_id' => $trim1->id,
        'evaluation_type' => 'composition',
        'sequence_id' => $seq2->id,
        'school_year_id' => $schoolYear->id,
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

    echo "   ✓ {$subjectName}\n";
}

echo "\n✅ {$subjects->count()} matières × 3 évaluations = " . ($subjects->count() * 3) . " évaluations créées\n\n";

// STEP 4: Fill grades for JEAN MARIE only
echo "📝 ÉTAPE 4: Remplissage des notes pour JEAN MARIE...\n\n";

$student = Student::where('first_name', 'JEAN MARIE')
    ->where('last_name', 'BAHA NDJOM')
    ->first();

if (!$student) {
    die("❌ JEAN MARIE BAHA NDJOM non trouvé\n");
}

echo "Élève: {$student->first_name} {$student->last_name}\n\n";

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

$gradeCount = 0;

foreach ($subjects as $classSeriesSubject) {
    $subjectName = $classSeriesSubject->subject->name;

    // Determine grade range
    $difficulty = $subjectDifficulty[$subjectName] ?? 'medium';
    list($minGrade, $maxGrade) = $gradeRanges[$difficulty];

    // Generate grades for Seq1
    $seq1Grade = rand($minGrade * 10, $maxGrade * 10) / 10;
    Grade::create([
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
    ]);

    // Generate grades for Seq2
    $seq2Grade = rand($minGrade * 10, $maxGrade * 10) / 10;
    Grade::create([
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
    ]);

    // Generate composition grade
    $avgSeq = ($seq1Grade + $seq2Grade) / 2;
    $compositionGrade = $avgSeq + rand(-20, 20) / 10;
    $compositionGrade = max($minGrade, min($maxGrade, $compositionGrade));
    $compositionGrade = round($compositionGrade, 1);

    Grade::create([
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
    ]);

    $gradeCount += 3;
    echo "  ✓ {$subjectName}: Seq1={$seq1Grade}, Seq2={$seq2Grade}, Comp={$compositionGrade}\n";
}

echo "\n";
echo "✅ TERMINÉ!\n";
echo "📊 Total de notes créées: {$gradeCount}\n";
echo "📚 Matières: {$subjects->count()}\n";
echo "🎯 Notes: 3 par matière (Seq1 + Seq2 + Composition)\n\n";

echo "🎉 PRÊT! Vous pouvez maintenant générer les bulletins!\n";
