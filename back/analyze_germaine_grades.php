<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== VÉRIFICATION COMPLÈTE DES GRADES GERMAINE ===\n\n";

$student = DB::table('students')
    ->where('first_name', 'LIKE', '%GERMAINE%')
    ->where('last_name', 'LIKE', '%EMADE%')
    ->first();

if (!$student) {
    echo "❌ Étudiante non trouvée\n";
    exit;
}

echo "Étudiante: {$student->first_name} {$student->last_name} (ID: {$student->id})\n\n";

// Tous les grades Trimestre 1
$allGrades = DB::table('grades')
    ->join('class_series_subjects', 'grades.class_series_subject_id', '=', 'class_series_subjects.id')
    ->join('subjects', 'class_series_subjects.subject_id', '=', 'subjects.id')
    ->where('grades.student_id', $student->id)
    ->where('grades.trimester_id', 1)
    ->whereNotNull('grades.score')
    ->select('subjects.name as subject', 'grades.sequence_id', 'grades.score', 'grades.max_score', 'grades.trimester_id')
    ->orderBy('subjects.name')
    ->orderBy('grades.sequence_id')
    ->get();

// Grouper par matière
$bySubject = [];
foreach ($allGrades as $g) {
    if (!isset($bySubject[$g->subject])) {
        $bySubject[$g->subject] = [];
    }
    $bySubject[$g->subject][] = $g;
}

echo "=== NOTES PAR MATIÈRE (trimester_id=1) ===\n";
foreach ($bySubject as $subject => $grades) {
    echo str_pad($subject, 35) . " | ";
    foreach ($grades as $g) {
        $seqLabel = $g->sequence_id ?? 'NULL';
        echo "Seq{$seqLabel}={$g->score}/{$g->max_score}  ";
    }
    echo "\n";
}

// Compter les grades avec sequence_id problématique
echo "\n=== ANALYSE DES SÉQUENCES ===\n";
$seq1 = DB::table('grades')->where('student_id', $student->id)->where('trimester_id', 1)->where('sequence_id', 1)->count();
$seq2 = DB::table('grades')->where('student_id', $student->id)->where('trimester_id', 1)->where('sequence_id', 2)->count();
$seq3 = DB::table('grades')->where('student_id', $student->id)->where('trimester_id', 1)->where('sequence_id', 3)->count();
$seq4 = DB::table('grades')->where('student_id', $student->id)->where('trimester_id', 1)->where('sequence_id', 4)->count();
$seqNull = DB::table('grades')->where('student_id', $student->id)->where('trimester_id', 1)->whereNull('sequence_id')->count();

echo "Grades avec trimester_id=1:\n";
echo "  - sequence_id=1: {$seq1}\n";
echo "  - sequence_id=2: {$seq2}\n";
echo "  - sequence_id=3: {$seq3} " . ($seq3 > 0 ? '❌ ERREUR! Seq 3 = Trimestre 2!' : '✅') . "\n";
echo "  - sequence_id=4: {$seq4} " . ($seq4 > 0 ? '❌ ERREUR! Seq 4 = Trimestre 2!' : '✅') . "\n";
echo "  - sequence_id=NULL: {$seqNull} " . ($seqNull == 0 ? '❌ Compositions manquantes!' : '✅') . "\n";

// Vérifier la structure attendue pour Tle A4 All
echo "\n=== STRUCTURE ATTENDUE POUR DEUXIÈME CYCLE ===\n";
echo "Trimestre 1: Séquence 1, Séquence 2, Composition (seq=NULL)\n";
echo "Trimestre 2: Séquence 3, Séquence 4, Composition (seq=NULL)\n";
echo "Trimestre 3: Séquence 5, Séquence 6, Composition (seq=NULL)\n\n";

// Vérifier combien de grades ont sequence_id=3 mais trimester_id=1
echo "=== CORRUPTION GLOBALE ===\n";
$totalSeries = DB::table('class_series')->where('id', $student->class_series_id)->first();
$allStudents = DB::table('students')->where('class_series_id', $student->class_series_id)->pluck('id');

$corruptedGrades = DB::table('grades')
    ->whereIn('student_id', $allStudents)
    ->where('trimester_id', 1)
    ->where('sequence_id', 3)
    ->count();

echo "Total grades avec seq=3 ET trim=1 dans {$totalSeries->name}: {$corruptedGrades}\n";

if ($corruptedGrades > 0) {
    echo "\n⚠️  CES GRADES SONT MAL ENREGISTRÉS!\n";
    echo "Les notes de Séquence 3 devraient avoir trimester_id=2, pas trimester_id=1\n";
}
