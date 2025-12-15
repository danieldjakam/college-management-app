<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST BULLETIN - EYENGA BEBI ESTELLE COLETTE ===\n\n";

// Trouver l'étudiante
$student = DB::table('students')
    ->where('first_name', 'LIKE', '%ESTELLE%')
    ->where('last_name', 'LIKE', '%EYENGA%')
    ->first();

if (!$student) {
    echo "❌ Étudiante non trouvée\n";
    exit;
}

echo "Étudiante: {$student->first_name} {$student->last_name} (ID: {$student->id})\n";
echo "Série ID: {$student->class_series_id}\n\n";

// Utiliser BulletinService
$service = new \App\Services\BulletinService();

// Trouver Anglais
$anglais = DB::table('class_series_subjects')
    ->join('subjects', 'class_series_subjects.subject_id', '=', 'subjects.id')
    ->where('class_series_subjects.class_series_id', $student->class_series_id)
    ->where('subjects.name', 'Anglais')
    ->select('class_series_subjects.*', 'subjects.name as subject_name')
    ->first();

if (!$anglais) {
    echo "❌ Anglais non trouvé\n";
    exit;
}

echo "Matière: {$anglais->subject_name} (class_series_subject_id: {$anglais->id})\n\n";

// Récupérer les notes comme le bulletin le fait
echo "=== NOTES POUR TRIMESTRE 1 ===\n";

// Séquences individuelles
$seqGrades = $service->getIndividualSequenceGrades(1, $student->id, $anglais->id);
echo "Séquence 1: " . ($seqGrades[0] ?? 'NULL') . "\n";
echo "Séquence 2: " . ($seqGrades[1] ?? 'NULL') . "\n";

// Composition
$compGrade = $service->getCompositionGrade(1, $student->id, $anglais->id);
echo "Composition 1: " . ($compGrade ?? 'NULL') . "\n";

// Calcul trimestre (Deuxième Cycle: moyenne des 3)
if ($seqGrades[0] !== null && $seqGrades[1] !== null && $compGrade !== null) {
    $moy = ($seqGrades[0] + $seqGrades[1] + $compGrade) / 3;
    echo "\nMoyenne Trimestre (Seq1+Seq2+Comp)/3: " . round($moy, 2) . "\n";
}

echo "\n=== VÉRIFICATION BASE DE DONNÉES DIRECTE ===\n";

// Vérifier directement dans la base
$directGrades = DB::table('grades')
    ->join('evaluations', 'grades.evaluation_id', '=', 'evaluations.id')
    ->where('grades.student_id', $student->id)
    ->where('grades.class_series_subject_id', $anglais->id)
    ->where('grades.trimester_id', 1)
    ->whereNotNull('grades.score')
    ->select('grades.*', 'evaluations.name as eval_name', 'evaluations.sequence_id as eval_seq_id')
    ->orderBy('evaluations.sequence_id')
    ->get();

foreach ($directGrades as $g) {
    echo "  - sequence_id={$g->sequence_id}, score={$g->score}/{$g->max_score}, eval=\"{$g->eval_name}\"\n";
}
