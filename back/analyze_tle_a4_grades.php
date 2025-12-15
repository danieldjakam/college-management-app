<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ANALYSE DES GRADES - TLE A4 ALL ===\n\n";

// Trouver la série directement
$series = DB::table('class_series')
    ->where('name', 'Tle A4 All')
    ->first();

if (!$series) {
    echo "❌ Série Tle A4 All non trouvée\n";
    // Afficher les séries disponibles
    $available = DB::table('class_series')->where('name', 'LIKE', '%Tle%')->get();
    echo "Séries disponibles:\n";
    foreach ($available as $s) {
        echo "  - ID {$s->id}: {$s->name}\n";
    }
    exit;
}

echo "Série trouvée: ID {$series->id} (class_id={$series->class_id})\n\n";

// Compter les grades par séquence pour cette classe
echo "=== GRADES PAR SÉQUENCE ===\n";
$sequences = [1, 2, 3, 4];
foreach ($sequences as $seqId) {
    $count = DB::table('grades')
        ->join('class_series_subjects', 'grades.class_series_subject_id', '=', 'class_series_subjects.id')
        ->where('class_series_subjects.class_series_id', $series->id)
        ->where('grades.sequence_id', $seqId)
        ->whereNotNull('grades.score')
        ->count();
    echo "Séquence {$seqId}: {$count} grades\n";
}

// Compter les grades avec sequence_id NULL (compositions)
$nullCount = DB::table('grades')
    ->join('class_series_subjects', 'grades.class_series_subject_id', '=', 'class_series_subjects.id')
    ->where('class_series_subjects.class_series_id', $series->id)
    ->whereNull('grades.sequence_id')
    ->whereNotNull('grades.score')
    ->count();
echo "Avec sequence_id=NULL: {$nullCount} grades\n\n";

// Analyser les évaluations pour cette classe
echo "=== ÉVALUATIONS POUR TLE A4 ALL - TRIMESTRE 1 ===\n";
$evals = DB::table('evaluations')
    ->join('class_series_subjects', 'evaluations.class_series_subject_id', '=', 'class_series_subjects.id')
    ->join('subjects', 'class_series_subjects.subject_id', '=', 'subjects.id')
    ->where('class_series_subjects.class_series_id', $series->id)
    ->where('evaluations.trimester_id', 1)
    ->select('evaluations.*', 'subjects.name as subject_name')
    ->orderBy('subjects.name')
    ->orderBy('evaluations.sequence_id')
    ->get();

echo "Total évaluations Trimestre 1: " . count($evals) . "\n\n";

// Grouper par type
$bySeq = [];
foreach ($evals as $eval) {
    $key = $eval->sequence_id ?? 'NULL';
    if (!isset($bySeq[$key])) {
        $bySeq[$key] = [];
    }
    $bySeq[$key][] = $eval;
}

foreach ($bySeq as $seqId => $evalList) {
    echo "Sequence_id = {$seqId}: " . count($evalList) . " évaluations\n";
    if (count($evalList) > 0 && count($evalList) <= 5) {
        foreach ($evalList as $e) {
            $gradeCount = DB::table('grades')->where('evaluation_id', $e->id)->count();
            echo "  - {$e->subject_name}: {$e->name} (type={$e->type}, {$gradeCount} grades)\n";
        }
    }
}

// Exemple spécifique: vérifier les notes d'un étudiant
echo "\n=== EXEMPLE: EYENGA BEBI ESTELLE COLETTE ===\n";
$student = DB::table('students')
    ->where('first_name', 'LIKE', '%ESTELLE%')
    ->where('last_name', 'LIKE', '%EYENGA%')
    ->first();

if ($student) {
    echo "Étudiante trouvée: ID {$student->id}\n\n";

    // Grades Anglais
    $anglaisSubject = DB::table('subjects')->where('name', 'Anglais')->first();
    if ($anglaisSubject) {
        $anglaisGrades = DB::table('grades')
            ->join('class_series_subjects', 'grades.class_series_subject_id', '=', 'class_series_subjects.id')
            ->join('evaluations', 'grades.evaluation_id', '=', 'evaluations.id')
            ->where('grades.student_id', $student->id)
            ->where('class_series_subjects.subject_id', $anglaisSubject->id)
            ->where('grades.trimester_id', 1)
            ->select('grades.*', 'evaluations.name as eval_name', 'evaluations.type', 'evaluations.sequence_id')
            ->get();

        echo "Notes Anglais:\n";
        foreach ($anglaisGrades as $g) {
            echo "  - {$g->eval_name}: {$g->score}/{$g->max_score} (seq_id={$g->sequence_id}, type={$g->type})\n";
        }
    }
}
