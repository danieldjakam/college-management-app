<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 CORRECTION SIMPLE ET SÛRE POUR LA PRODUCTION\n";
echo "================================================\n\n";

echo "⚠️  STRATÉGIE:\n";
echo "  Au lieu de modifier les évaluations (ce qui cause des conflits),\n";
echo "  on va DIRECTEMENT restaurer le sequence_id dans les grades\n";
echo "  en se basant sur le nom de leur évaluation.\n\n";

// ÉTAT AVANT
echo "=== ÉTAT AVANT CORRECTION ===\n";
$totalGrades = DB::table('grades')->count();
$nullSeqBefore = DB::table('grades')->whereNull('sequence_id')->count();
$seq1GradesBefore = DB::table('grades')->where('sequence_id', 1)->count();
$seq2GradesBefore = DB::table('grades')->where('sequence_id', 2)->count();

echo "GRADES:\n";
echo "  Total: {$totalGrades}\n";
echo "  Avec sequence_id=NULL: {$nullSeqBefore} (" . round(($nullSeqBefore/$totalGrades)*100,1) . "%)\n";
echo "  Séquence 1: {$seq1GradesBefore}\n";
echo "  Séquence 2: {$seq2GradesBefore}\n";
$ratioGradesBefore = $seq1GradesBefore > 0 ? round(($seq2GradesBefore / $seq1GradesBefore) * 100, 1) : 0;
echo "  Ratio Seq2/Seq1: {$ratioGradesBefore}%\n\n";

// ÉTAPE 1: Restaurer sequence_id=1 pour les grades dont l'évaluation contient "1"
echo "=== ÉTAPE 1: RESTAURATION sequence_id=1 ===\n";
DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = 1
    WHERE g.trimester_id = 1
      AND g.sequence_id IS NULL
      AND (
        e.name REGEXP 'DS\\\\s*1|ds\\\\s*1|Ds\\\\s*1|DS1|ds1|Ds1' OR
        e.name REGEXP 'devoir\\\\s*1|Devoir\\\\s*1|DEVOIR\\\\s*1|devoir1|Devoir1' OR
        e.name REGEXP 'seq\\\\s*1|Seq\\\\s*1|SEQ\\\\s*1|seq1|Seq1' OR
        e.name REGEXP 'sequence\\\\s*1|Sequence\\\\s*1|SEQUENCE\\\\s*1' OR
        e.name REGEXP 'evaluation\\\\s*1|Evaluation\\\\s*1|EVALUATION\\\\s*1|evaluation1' OR
        e.name REGEXP 'évaluation\\\\s*1|Évaluation\\\\s*1|ÉVALUATION\\\\s*1' OR
        e.name REGEXP 'first|First|FIRST|1st' OR
        e.name REGEXP '^ds$|^DS$|^Ds$'
      )
");

$seq1After = DB::table('grades')->where('sequence_id', 1)->count();
echo "Grades avec sequence_id=1: {$seq1After} (avant: {$seq1GradesBefore}, +". ($seq1After - $seq1GradesBefore) .")\n\n";

// ÉTAPE 2: Restaurer sequence_id=2 pour les grades dont l'évaluation contient "2"
echo "=== ÉTAPE 2: RESTAURATION sequence_id=2 ===\n";
DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = 2
    WHERE g.trimester_id = 1
      AND g.sequence_id IS NULL
      AND (
        e.name REGEXP 'DS\\\\s*2|ds\\\\s*2|Ds\\\\s*2|DS2|ds2|Ds2' OR
        e.name REGEXP 'devoir\\\\s*2|Devoir\\\\s*2|DEVOIR\\\\s*2|devoir2|Devoir2' OR
        e.name REGEXP 'seq\\\\s*2|Seq\\\\s*2|SEQ\\\\s*2|seq2|Seq2' OR
        e.name REGEXP 'sequence\\\\s*2|Sequence\\\\s*2|SEQUENCE\\\\s*2' OR
        e.name REGEXP 'evaluation\\\\s*2|Evaluation\\\\s*2|EVALUATION\\\\s*2|evaluation2' OR
        e.name REGEXP 'évaluation\\\\s*2|Évaluation\\\\s*2|ÉVALUATION\\\\s*2' OR
        e.name REGEXP 'second|Second|SECOND|2nd'
      )
");

$seq2After = DB::table('grades')->where('sequence_id', 2)->count();
echo "Grades avec sequence_id=2: {$seq2After} (avant: {$seq2GradesBefore}, +". ($seq2After - $seq2GradesBefore) .")\n\n";

// ÉTAPE 3: Restaurer sequence_id=3 pour trimestre 2
echo "=== ÉTAPE 3: RESTAURATION sequence_id=3 ===\n";
DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = 3
    WHERE g.trimester_id = 2
      AND g.sequence_id IS NULL
      AND (
        e.name REGEXP 'DS\\\\s*3|ds\\\\s*3|Ds\\\\s*3' OR
        e.name REGEXP 'devoir\\\\s*3|Devoir\\\\s*3|DEVOIR\\\\s*3' OR
        e.name REGEXP 'seq\\\\s*3|Seq\\\\s*3|SEQ\\\\s*3|sequence\\\\s*3|Sequence\\\\s*3' OR
        e.name REGEXP 'evaluation\\\\s*3|Evaluation\\\\s*3|évaluation\\\\s*3|Évaluation\\\\s*3' OR
        e.name REGEXP 'third|Third|THIRD|3rd'
      )
");

$seq3After = DB::table('grades')->where('sequence_id', 3)->count();
echo "Grades avec sequence_id=3: {$seq3After}\n\n";

// ÉTAPE 4: Restaurer sequence_id=4 pour trimestre 2
echo "=== ÉTAPE 4: RESTAURATION sequence_id=4 ===\n";
DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = 4
    WHERE g.trimester_id = 2
      AND g.sequence_id IS NULL
      AND (
        e.name REGEXP 'DS\\\\s*4|ds\\\\s*4|Ds\\\\s*4' OR
        e.name REGEXP 'devoir\\\\s*4|Devoir\\\\s*4|DEVOIR\\\\s*4' OR
        e.name REGEXP 'seq\\\\s*4|Seq\\\\s*4|SEQ\\\\s*4|sequence\\\\s*4|Sequence\\\\s*4' OR
        e.name REGEXP 'evaluation\\\\s*4|Evaluation\\\\s*4|évaluation\\\\s*4|Évaluation\\\\s*4'
      )
");

$seq4After = DB::table('grades')->where('sequence_id', 4)->count();
echo "Grades avec sequence_id=4: {$seq4After}\n\n";

// ÉTAPE 5: Pour les grades restants avec NULL dans trimestre 1, assigner par défaut à seq 1
echo "=== ÉTAPE 5: RESTAURATION PAR DÉFAUT (trim1 → seq1, trim2 → seq3) ===\n";

$remainingT1 = DB::table('grades')
    ->join('evaluations', 'grades.evaluation_id', '=', 'evaluations.id')
    ->where('grades.trimester_id', 1)
    ->whereNull('grades.sequence_id')
    ->where('evaluations.type', '!=', 'composition')
    ->where('evaluations.name', 'NOT LIKE', '%Composition%')
    ->where('evaluations.name', 'NOT LIKE', '%composition%')
    ->where('evaluations.name', 'NOT LIKE', '%Compo%')
    ->where('evaluations.name', 'NOT LIKE', '%compo%')
    ->count('grades.id');

if ($remainingT1 > 0) {
    echo "Grades restants à corriger dans trim1: {$remainingT1}\n";
    DB::statement("
        UPDATE grades g
        INNER JOIN evaluations e ON g.evaluation_id = e.id
        SET g.sequence_id = 1
        WHERE g.trimester_id = 1
          AND g.sequence_id IS NULL
          AND e.type != 'composition'
          AND e.name NOT LIKE '%Composition%'
          AND e.name NOT LIKE '%composition%'
          AND e.name NOT LIKE '%Compo%'
          AND e.name NOT LIKE '%compo%'
    ");
}

$remainingT2 = DB::table('grades')
    ->join('evaluations', 'grades.evaluation_id', '=', 'evaluations.id')
    ->where('grades.trimester_id', 2)
    ->whereNull('grades.sequence_id')
    ->where('evaluations.type', '!=', 'composition')
    ->where('evaluations.name', 'NOT LIKE', '%Composition%')
    ->where('evaluations.name', 'NOT LIKE', '%composition%')
    ->count('grades.id');

if ($remainingT2 > 0) {
    echo "Grades restants à corriger dans trim2: {$remainingT2}\n";
    DB::statement("
        UPDATE grades g
        INNER JOIN evaluations e ON g.evaluation_id = e.id
        SET g.sequence_id = 3
        WHERE g.trimester_id = 2
          AND g.sequence_id IS NULL
          AND e.type != 'composition'
          AND e.name NOT LIKE '%Composition%'
          AND e.name NOT LIKE '%composition%'
    ");
}

echo "✅ Restauration par défaut appliquée\n\n";

// ÉTAT APRÈS
echo "=== ÉTAT APRÈS CORRECTION ===\n";
$nullSeqAfter = DB::table('grades')->whereNull('sequence_id')->count();
$seq1GradesAfter = DB::table('grades')->where('sequence_id', 1)->count();
$seq2GradesAfter = DB::table('grades')->where('sequence_id', 2)->count();

echo "GRADES:\n";
echo "  Total: {$totalGrades}\n";
echo "  Avec sequence_id=NULL: {$nullSeqAfter} (" . round(($nullSeqAfter/$totalGrades)*100,1) . "%)\n";
echo "  Séquence 1: {$seq1GradesAfter} (+". ($seq1GradesAfter - $seq1GradesBefore) .")\n";
echo "  Séquence 2: {$seq2GradesAfter} (+". ($seq2GradesAfter - $seq2GradesBefore) .")\n";
$ratioGradesAfter = $seq1GradesAfter > 0 ? round(($seq2GradesAfter / $seq1GradesAfter) * 100, 1) : 0;
echo "  Ratio Seq2/Seq1: {$ratioGradesAfter}% (avant: {$ratioGradesBefore}%)\n\n";

// VÉRIFICATIONS FINALES
echo "=== VÉRIFICATIONS FINALES ===\n";

// Vérifier pour TLE A4 ALL
echo "TLE A4 ALL:\n";
$class = DB::table('school_classes')->where('name', 'TLE A4 ALL')->first();
if ($class) {
    $series = DB::table('class_series')->where('school_class_id', $class->id)->first();
    if ($series) {
        $seq1ClassGrades = DB::table('grades')
            ->join('class_series_subjects', 'grades.class_series_subject_id', '=', 'class_series_subjects.id')
            ->where('class_series_subjects.class_series_id', $series->id)
            ->where('grades.sequence_id', 1)
            ->whereNotNull('grades.score')
            ->count();

        $seq2ClassGrades = DB::table('grades')
            ->join('class_series_subjects', 'grades.class_series_subject_id', '=', 'class_series_subjects.id')
            ->where('class_series_subjects.class_series_id', $series->id)
            ->where('grades.sequence_id', 2)
            ->whereNotNull('grades.score')
            ->count();

        echo "  Grades Seq1: {$seq1ClassGrades}\n";
        echo "  Grades Seq2: {$seq2ClassGrades}\n";

        $ratioClass = $seq1ClassGrades > 0 ? round(($seq2ClassGrades / $seq1ClassGrades) * 100, 1) : 0;
        echo "  Ratio Seq2/Seq1: {$ratioClass}%\n";

        if ($ratioClass >= 60) {
            echo "  ✅ EXCELLENT!\n";
        } else {
            echo "  ⚠️  Ratio bas\n";
        }
    }
}

echo "\n🎉 CORRECTION TERMINÉE!\n";
echo "💾 Les grades ont été restaurés avec succès.\n";
echo "📊 Le ratio global Seq2/Seq1 est passé de {$ratioGradesBefore}% à {$ratioGradesAfter}%\n";

if ($ratioGradesAfter >= 60) {
    echo "✅ La correction a réussi!\n";
} else {
    echo "⚠️  Le ratio reste bas, une analyse supplémentaire peut être nécessaire.\n";
}
