<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔬 TEST DES MIGRATIONS PROBLÉMATIQUES\n";
echo "======================================\n\n";

// ÉTAT AVANT
echo "=== ÉTAT AVANT LES MIGRATIONS ===\n";
$totalGrades = DB::table('grades')->count();
$nullSeqBefore = DB::table('grades')->whereNull('sequence_id')->count();
$seq1Evals = DB::table('evaluations')->where('sequence_id', 1)->count();
$seq2Evals = DB::table('evaluations')->where('sequence_id', 2)->count();
$compoEvals = DB::table('evaluations')->where('type', 'composition')->count();

echo "GRADES:\n";
echo "  Total: {$totalGrades}\n";
echo "  Avec sequence_id=NULL: {$nullSeqBefore} (" . round(($nullSeqBefore/$totalGrades)*100,1) . "%)\n\n";

echo "ÉVALUATIONS:\n";
echo "  Séquence 1: {$seq1Evals}\n";
echo "  Séquence 2: {$seq2Evals}\n";
echo "  Type='composition': {$compoEvals}\n";
$ratio = $seq1Evals > 0 ? round(($seq2Evals / $seq1Evals) * 100, 1) : 0;
echo "  Ratio Seq2/Seq1: {$ratio}%\n\n";

// MIGRATION 1: allow_null_sequence_id_in_evaluations_table
echo "=== MIGRATION 1: Autoriser NULL dans evaluations.sequence_id ===\n";
echo "Modification de la colonne...\n";
DB::statement('ALTER TABLE evaluations MODIFY sequence_id BIGINT UNSIGNED NULL');

echo "Mise à NULL des compositions...\n";
$affected = DB::statement("UPDATE evaluations SET sequence_id = NULL WHERE type = 'composition'");
echo "✅ Migration 1 appliquée\n\n";

// Vérifier l'impact
$compoWithNull = DB::table('evaluations')->where('type', 'composition')->whereNull('sequence_id')->count();
echo "Évaluations 'composition' avec sequence_id=NULL: {$compoWithNull}\n\n";

// MIGRATION 2: fix_grades_sequence_id_for_compositions
echo "=== MIGRATION 2: Mettre à NULL les grades de compositions ===\n";
echo "Modification de la colonne grades.sequence_id...\n";
DB::statement('ALTER TABLE grades MODIFY sequence_id BIGINT UNSIGNED NULL');

echo "Mise à NULL des grades de compositions...\n";
$beforeUpdate = DB::table('grades')->whereNull('sequence_id')->count();
DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = NULL
    WHERE e.type = 'composition'
");
$afterUpdate = DB::table('grades')->whereNull('sequence_id')->count();
$affected = $afterUpdate - $beforeUpdate;
echo "✅ Migration 2 appliquée ({$affected} grades modifiés)\n\n";

// ÉTAT APRÈS
echo "=== ÉTAT APRÈS LES MIGRATIONS ===\n";
$nullSeqAfter = DB::table('grades')->whereNull('sequence_id')->count();
$seq1EvalsAfter = DB::table('evaluations')->where('sequence_id', 1)->count();
$seq2EvalsAfter = DB::table('evaluations')->where('sequence_id', 2)->count();

echo "GRADES:\n";
echo "  Total: {$totalGrades}\n";
echo "  Avec sequence_id=NULL: {$nullSeqAfter} (" . round(($nullSeqAfter/$totalGrades)*100,1) . "%)\n";
echo "  CHANGEMENT: +" . ($nullSeqAfter - $nullSeqBefore) . " grades mis à NULL\n\n";

echo "ÉVALUATIONS:\n";
echo "  Séquence 1: {$seq1EvalsAfter}\n";
echo "  Séquence 2: {$seq2EvalsAfter}\n";
$ratioAfter = $seq1EvalsAfter > 0 ? round(($seq2EvalsAfter / $seq1EvalsAfter) * 100, 1) : 0;
echo "  Ratio Seq2/Seq1: {$ratioAfter}%\n\n";

// ANALYSE DÉTAILLÉE
echo "=== ANALYSE DÉTAILLÉE ===\n";

// Quels types d'évaluations ont été affectés ?
$evalTypes = DB::table('evaluations')
    ->select('type', DB::raw('COUNT(*) as count'))
    ->whereNull('sequence_id')
    ->groupBy('type')
    ->get();

echo "Évaluations avec sequence_id=NULL par type:\n";
foreach ($evalTypes as $type) {
    echo "  {$type->type}: {$type->count}\n";
}
echo "\n";

// Échantillon d'évaluations 'composition'
echo "Échantillon d'évaluations marquées comme 'composition':\n";
$samples = DB::table('evaluations')
    ->where('type', 'composition')
    ->select('id', 'name', 'type', 'sequence_id', 'trimester_id')
    ->limit(20)
    ->get();

foreach ($samples as $s) {
    $gradeCount = DB::table('grades')->where('evaluation_id', $s->id)->count();
    echo "  ID {$s->id}: \"{$s->name}\" (trim={$s->trimester_id}, {$gradeCount} grades)\n";
}
echo "\n";

// Vérifier pour TLE A4 ALL
echo "=== VÉRIFICATION TLE A4 ALL ===\n";
$class = DB::table('school_classes')->where('name', 'TLE A4 ALL')->first();
if ($class) {
    $series = DB::table('class_series')->where('school_class_id', $class->id)->first();
    if ($series) {
        // Grades pour cette classe
        $seq1Grades = DB::table('grades')
            ->join('class_series_subjects', 'grades.class_series_subject_id', '=', 'class_series_subjects.id')
            ->where('class_series_subjects.class_series_id', $series->id)
            ->where('grades.sequence_id', 1)
            ->whereNotNull('grades.score')
            ->count();

        $seq2Grades = DB::table('grades')
            ->join('class_series_subjects', 'grades.class_series_subject_id', '=', 'class_series_subjects.id')
            ->where('class_series_subjects.class_series_id', $series->id)
            ->where('grades.sequence_id', 2)
            ->whereNotNull('grades.score')
            ->count();

        $nullGrades = DB::table('grades')
            ->join('class_series_subjects', 'grades.class_series_subject_id', '=', 'class_series_subjects.id')
            ->where('class_series_subjects.class_series_id', $series->id)
            ->whereNull('grades.sequence_id')
            ->whereNotNull('grades.score')
            ->count();

        echo "TLE A4 ALL (series_id={$series->id}):\n";
        echo "  Grades Seq1: {$seq1Grades}\n";
        echo "  Grades Seq2: {$seq2Grades}\n";
        echo "  Grades avec seq=NULL: {$nullGrades}\n";

        $ratioClass = $seq1Grades > 0 ? round(($seq2Grades / $seq1Grades) * 100, 1) : 0;
        echo "  Ratio Seq2/Seq1: {$ratioClass}%\n";
    }
}

echo "\n🔍 MIGRATIONS APPLIQUÉES - PROBLÈME REPRODUIT!\n";
echo "💡 Prochaine étape: Analyser les données et créer une correction appropriée.\n";
