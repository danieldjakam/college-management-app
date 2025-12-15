<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 CORRECTION FINALE POUR LA PRODUCTION\n";
echo "========================================\n\n";

echo "⚠️  CE SCRIPT VA:\n";
echo "  1. Identifier les évaluations mal typées comme 'composition'\n";
echo "  2. Corriger leur type vers 'ds'\n";
echo "  3. Restaurer leur sequence_id correct\n";
echo "  4. Restaurer le sequence_id des grades correspondants\n";
echo "  5. Laisser les VRAIES compositions intactes\n\n";

// ÉTAT AVANT
echo "=== ÉTAT AVANT CORRECTION ===\n";
$totalGrades = DB::table('grades')->count();
$nullSeqBefore = DB::table('grades')->whereNull('sequence_id')->count();
$seq1EvalsBefore = DB::table('evaluations')->where('sequence_id', 1)->count();
$seq2EvalsBefore = DB::table('evaluations')->where('sequence_id', 2)->count();
$compoEvalsBefore = DB::table('evaluations')->where('type', 'composition')->count();

echo "GRADES:\n";
echo "  Total: {$totalGrades}\n";
echo "  Avec sequence_id=NULL: {$nullSeqBefore} (" . round(($nullSeqBefore/$totalGrades)*100,1) . "%)\n\n";

echo "ÉVALUATIONS:\n";
echo "  Séquence 1: {$seq1EvalsBefore}\n";
echo "  Séquence 2: {$seq2EvalsBefore}\n";
echo "  Type='composition': {$compoEvalsBefore}\n";
$ratioBefore = $seq1EvalsBefore > 0 ? round(($seq2EvalsBefore / $seq1EvalsBefore) * 100, 1) : 0;
echo "  Ratio Seq2/Seq1: {$ratioBefore}%\n\n";

// ÉTAPE 1: Identifier les fausses compositions
echo "=== ÉTAPE 1: IDENTIFICATION DES FAUSSES COMPOSITIONS ===\n";

$dsPatterns = [
    'DS', 'ds', 'Ds',
    'devoir', 'Devoir', 'DEVOIR',
    'No ', 'N°', 'no ',
    'seq', 'Seq', 'SEQ',
    'sequence', 'Sequence', 'SEQUENCE',
    'évaluation', 'Évaluation', 'ÉVALUATION',
    'evaluation', 'Evaluation', 'EVALUATION'
];

$fakeCompositions = [];
$compositions = DB::table('evaluations')->where('type', 'composition')->get();

foreach ($compositions as $eval) {
    $isFake = false;
    $name = $eval->name;

    // Vérifier si le nom contient des indicateurs de DS/Séquence
    foreach ($dsPatterns as $pattern) {
        if (stripos($name, $pattern) !== false) {
            // Mais exclure les vrais noms de composition
            if (!preg_match('/^Composition \d+/i', $name) &&
                !preg_match('/^Compo \d+/i', $name) &&
                !preg_match('/Composition de /i', $name)) {
                $isFake = true;
                break;
            }
        }
    }

    if ($isFake) {
        $fakeCompositions[] = $eval;
    }
}

echo "Fausses compositions détectées: " . count($fakeCompositions) . "\n\n";

// ÉTAPE 2: Corriger le type et le sequence_id des fausses compositions
echo "=== ÉTAPE 2: CORRECTION DES ÉVALUATIONS ===\n";

$corrections = [];
foreach ($fakeCompositions as $eval) {
    $name = $eval->name;
    $trimId = $eval->trimester_id;
    $newSeqId = null;

    // Déterminer le bon sequence_id basé sur le nom
    if (preg_match('/DS\s*2|ds\s*2|Ds\s*2|devoir\s*2|Devoir\s*2|DEVOIR\s*2|No\s*2|N°\s*2|seq\s*2|Seq\s*2|sequence\s*2|Sequence\s*2|evaluation\s*2|évaluation\s*2|second/i', $name)) {
        $newSeqId = 2;
    } elseif (preg_match('/DS\s*1|ds\s*1|Ds\s*1|devoir\s*1|Devoir\s*1|DEVOIR\s*1|No\s*1|N°\s*1|seq\s*1|Seq\s*1|sequence\s*1|Sequence\s*1|evaluation\s*1|évaluation\s*1|first/i', $name)) {
        $newSeqId = 1;
    } elseif (preg_match('/DS\s*3|ds\s*3|Ds\s*3|devoir\s*3|Devoir\s*3|DEVOIR\s*3|seq\s*3|Seq\s*3|sequence\s*3|Sequence\s*3|evaluation\s*3|évaluation\s*3|third/i', $name)) {
        $newSeqId = 3;
    } elseif (preg_match('/DS\s*4|ds\s*4|Ds\s*4|devoir\s*4|Devoir\s*4|DEVOIR\s*4|seq\s*4|Seq\s*4|sequence\s*4|Sequence\s*4|evaluation\s*4|évaluation\s*4/i', $name)) {
        $newSeqId = 4;
    } elseif (preg_match('/DS\s*5|ds\s*5|Ds\s*5|devoir\s*5|Devoir\s*5|DEVOIR\s*5|seq\s*5|Seq\s*5|sequence\s*5|Sequence\s*5|evaluation\s*5|évaluation\s*5/i', $name)) {
        $newSeqId = 5;
    } elseif (preg_match('/DS\s*6|ds\s*6|Ds\s*6|devoir\s*6|Devoir\s*6|DEVOIR\s*6|seq\s*6|Seq\s*6|sequence\s*6|Sequence\s*6|evaluation\s*6|évaluation\s*6/i', $name)) {
        $newSeqId = 6;
    } elseif ($trimId == 1) {
        // Par défaut pour trimestre 1: séquence 1
        $newSeqId = 1;
    } elseif ($trimId == 2) {
        // Par défaut pour trimestre 2: séquence 3
        $newSeqId = 3;
    } elseif ($trimId == 3) {
        // Par défaut pour trimestre 3: séquence 5
        $newSeqId = 5;
    }

    if ($newSeqId !== null) {
        $corrections[] = [
            'id' => $eval->id,
            'sequence_id' => $newSeqId
        ];
    }
}

echo "Corrections à appliquer: " . count($corrections) . " évaluations\n";

// Appliquer les corrections
$correctedCount = 0;
foreach ($corrections as $correction) {
    DB::table('evaluations')
        ->where('id', $correction['id'])
        ->update([
            'type' => 'ds',
            'sequence_id' => $correction['sequence_id']
        ]);
    $correctedCount++;
}

echo "✅ {$correctedCount} évaluations corrigées\n\n";

// ÉTAPE 3: Restaurer les sequence_id des grades
echo "=== ÉTAPE 3: RESTAURATION DES sequence_id DES GRADES ===\n";

// Restaurer pour les DS (maintenant correctement typés)
$restoredGrades = DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = e.sequence_id
    WHERE e.type = 'ds'
      AND e.sequence_id IS NOT NULL
      AND (g.sequence_id IS NULL OR g.sequence_id != e.sequence_id)
");

echo "✅ Grades des DS restaurés\n";

// Mettre à NULL pour les VRAIES compositions
$compositionGrades = DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = NULL
    WHERE e.type = 'composition'
      AND g.sequence_id IS NOT NULL
");

echo "✅ Grades des compositions mis à NULL\n\n";

// ÉTAT APRÈS
echo "=== ÉTAT APRÈS CORRECTION ===\n";
$nullSeqAfter = DB::table('grades')->whereNull('sequence_id')->count();
$seq1EvalsAfter = DB::table('evaluations')->where('sequence_id', 1)->count();
$seq2EvalsAfter = DB::table('evaluations')->where('sequence_id', 2)->count();
$compoEvalsAfter = DB::table('evaluations')->where('type', 'composition')->count();

echo "GRADES:\n";
echo "  Total: {$totalGrades}\n";
echo "  Avec sequence_id=NULL: {$nullSeqAfter} (" . round(($nullSeqAfter/$totalGrades)*100,1) . "%)\n";
echo "  CHANGEMENT: " . ($nullSeqAfter - $nullSeqBefore) . " grades\n\n";

echo "ÉVALUATIONS:\n";
echo "  Séquence 1: {$seq1EvalsAfter} (avant: {$seq1EvalsBefore})\n";
echo "  Séquence 2: {$seq2EvalsAfter} (avant: {$seq2EvalsBefore})\n";
echo "  Type='composition': {$compoEvalsAfter} (avant: {$compoEvalsBefore})\n";
$ratioAfter = $seq1EvalsAfter > 0 ? round(($seq2EvalsAfter / $seq1EvalsAfter) * 100, 1) : 0;
echo "  Ratio Seq2/Seq1: {$ratioAfter}% (avant: {$ratioBefore}%)\n\n";

// VÉRIFICATIONS FINALES
echo "=== VÉRIFICATIONS FINALES ===\n";

// Vérifier qu'il ne reste pas d'évaluations 'composition' avec des noms suspects
$remainingSuspect = DB::table('evaluations')
    ->where('type', 'composition')
    ->where(function($query) {
        $query->where('name', 'LIKE', '%DS%')
              ->orWhere('name', 'LIKE', '%ds%')
              ->orWhere('name', 'LIKE', '%devoir%')
              ->orWhere('name', 'LIKE', '%seq%');
    })
    ->count();

if ($remainingSuspect > 0) {
    echo "⚠️  ATTENTION: {$remainingSuspect} évaluations 'composition' avec noms suspects restent\n";
} else {
    echo "✅ PARFAIT! Aucune évaluation suspecte restante\n";
}

// Vérifier la cohérence grades <-> evaluations
$incoherentGrades = DB::table('grades as g')
    ->join('evaluations as e', 'g.evaluation_id', '=', 'e.id')
    ->where('e.type', '!=', 'composition')
    ->whereNotNull('e.sequence_id')
    ->whereRaw('(g.sequence_id IS NULL OR g.sequence_id != e.sequence_id)')
    ->count();

if ($incoherentGrades > 0) {
    echo "⚠️  ATTENTION: {$incoherentGrades} grades ont un sequence_id incohérent avec leur évaluation\n";
} else {
    echo "✅ PARFAIT! Tous les grades sont cohérents avec leurs évaluations\n";
}

// Vérifier pour TLE A4 ALL
echo "\n=== VÉRIFICATION TLE A4 ALL ===\n";
$class = DB::table('school_classes')->where('name', 'TLE A4 ALL')->first();
if ($class) {
    $series = DB::table('class_series')->where('school_class_id', $class->id)->first();
    if ($series) {
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

        echo "TLE A4 ALL (series_id={$series->id}):\n";
        echo "  Grades Seq1: {$seq1Grades}\n";
        echo "  Grades Seq2: {$seq2Grades}\n";

        $ratioClass = $seq1Grades > 0 ? round(($seq2Grades / $seq1Grades) * 100, 1) : 0;
        echo "  Ratio Seq2/Seq1: {$ratioClass}%\n";

        if ($ratioClass >= 60) {
            echo "  ✅ EXCELLENT! Le ratio est bon (>= 60%)\n";
        } else {
            echo "  ⚠️  Le ratio reste bas (< 60%)\n";
        }
    }
}

echo "\n🎉 CORRECTION TERMINÉE!\n";
echo "💾 Les données ont été corrigées avec succès.\n";
echo "📊 Le ratio Seq2/Seq1 est passé de {$ratioBefore}% à {$ratioAfter}%\n";

if ($ratioAfter >= 60) {
    echo "✅ La correction a réussi!\n";
} else {
    echo "⚠️  Le ratio reste bas, une analyse supplémentaire peut être nécessaire.\n";
}
