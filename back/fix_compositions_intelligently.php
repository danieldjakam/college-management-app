<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 RÉPARATION INTELLIGENTE DES ÉVALUATIONS\n";
echo "==========================================\n\n";

echo "📋 RÈGLES APPLIQUÉES:\n";
echo "  ✅ GARDER EN COMPOSITION: Noms contenant 'composition', 'compo', 'COMPO' SEULS\n";
echo "  ✅ CONVERTIR EN DS: Noms avec 'DS', 'devoir' + chiffre/mois\n";
echo "  ✅ NE JAMAIS convertir: 'Composition 1', 'Compo 1', 'composition fin trimestre', etc.\n\n";

// ÉTAPE 1: Identifier les DS/devoirs MAL TYPÉS comme compositions
echo "=== ÉTAPE 1: IDENTIFICATION DES FAUSSES COMPOSITIONS ===\n";

$fakeCompositions = DB::table('evaluations')
    ->where('type', 'composition')
    ->where(function($query) {
        // Évaluations qui contiennent clairement des indicateurs de DS/devoir + numéro
        $query->where('name', 'REGEXP', 'DS[\\s]*[0-9]')
              ->orWhere('name', 'REGEXP', 'ds[\\s]*[0-9]')
              ->orWhere('name', 'REGEXP', 'Ds[\\s]*[0-9]')
              ->orWhere('name', 'REGEXP', '[Dd]evoir[\\s]*[0-9]')
              ->orWhere('name', 'REGEXP', 'DEVOIR[\\s]*[0-9]')
              ->orWhere('name', 'LIKE', '%No %')
              ->orWhere('name', 'LIKE', '%N°%');
    })
    ->get();

echo "Fausses compositions trouvées: " . count($fakeCompositions) . "\n\n";

if (count($fakeCompositions) > 0) {
    echo "Échantillon (10 premières):\n";
    foreach ($fakeCompositions->take(10) as $e) {
        $gradeCount = DB::table('grades')->where('evaluation_id', $e->id)->count();
        echo "  - Eval {$e->id}: \"{$e->name}\" (trim={$e->trimester_id}, {$gradeCount} grades)\n";
    }
}

// ÉTAPE 2: Reconvertir en DS avec le bon sequence_id
echo "\n=== ÉTAPE 2: RECONVERSION EN DS ===\n";

$converted = 0;
$bySequence = ['seq1' => 0, 'seq2' => 0, 'seq3' => 0, 'seq4' => 0];

foreach ($fakeCompositions as $eval) {
    $newSeqId = null;

    // Déterminer le sequence_id basé sur le nom et le trimestre
    $name = $eval->name;

    // Séquence 2: DS2, devoir 2, novembre
    if (preg_match('/DS\s*2|ds\s*2|Ds\s*2|devoir\s*2|Devoir\s*2|DEVOIR\s*2|novembre|November|No\s*2|N°\s*2/i', $name)) {
        $newSeqId = 2;
        $bySequence['seq2']++;
    }
    // Séquence 1: DS1, devoir 1, octobre
    elseif (preg_match('/DS\s*1|ds\s*1|Ds\s*1|devoir\s*1|Devoir\s*1|DEVOIR\s*1|octobre|October|No\s*1|N°\s*1/i', $name)) {
        $newSeqId = 1;
        $bySequence['seq1']++;
    }
    // Séquence 3: DS3, devoir 3
    elseif (preg_match('/DS\s*3|ds\s*3|Ds\s*3|devoir\s*3|Devoir\s*3|DEVOIR\s*3/i', $name)) {
        $newSeqId = 3;
        $bySequence['seq3']++;
    }
    // Séquence 4: DS4, devoir 4
    elseif (preg_match('/DS\s*4|ds\s*4|Ds\s*4|devoir\s*4|Devoir\s*4|DEVOIR\s*4/i', $name)) {
        $newSeqId = 4;
        $bySequence['seq4']++;
    }
    // Défaut basé sur le trimestre
    elseif ($eval->trimester_id == 1) {
        $newSeqId = 1;
        $bySequence['seq1']++;
    } elseif ($eval->trimester_id == 2) {
        $newSeqId = 3;
        $bySequence['seq3']++;
    }

    if ($newSeqId !== null) {
        DB::table('evaluations')
            ->where('id', $eval->id)
            ->update([
                'type' => 'ds',
                'sequence_id' => $newSeqId
            ]);
        $converted++;
    }
}

echo "Évaluations reconverties: {$converted}\n";
echo "  - Séquence 1: {$bySequence['seq1']}\n";
echo "  - Séquence 2: {$bySequence['seq2']}\n";
echo "  - Séquence 3: {$bySequence['seq3']}\n";
echo "  - Séquence 4: {$bySequence['seq4']}\n\n";

// ÉTAPE 3: Corriger les évaluations avec sequence_id incohérent
echo "=== ÉTAPE 3: CORRECTION sequence_id INCOHÉRENTS ===\n";

// Trimestre 1 avec seq >= 3 => devrait être composition
$incoherent = DB::table('evaluations')
    ->where('type', '!=', 'composition')
    ->where('trimester_id', 1)
    ->where('sequence_id', '>=', 3)
    ->count();

if ($incoherent > 0) {
    DB::table('evaluations')
        ->where('type', '!=', 'composition')
        ->where('trimester_id', 1)
        ->where('sequence_id', '>=', 3)
        ->update(['type' => 'composition', 'sequence_id' => null]);

    echo "Évaluations incohérentes converties en compositions: {$incoherent}\n";
}

// ÉTAPE 4: Restaurer les sequence_id des grades
echo "\n=== ÉTAPE 4: RESTAURATION DES sequence_id DES GRADES ===\n";

// Mettre à NULL pour les compositions
DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = NULL
    WHERE e.type = 'composition'
      AND g.sequence_id IS NOT NULL
");

// Restaurer pour les DS
DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = e.sequence_id
    WHERE e.type != 'composition'
      AND e.sequence_id IS NOT NULL
      AND (g.sequence_id IS NULL OR g.sequence_id != e.sequence_id)
");

echo "✅ Grades synchronisés avec evaluations\n";

// ÉTAPE 5: Statistiques finales
echo "\n=== ÉTAPE 5: STATISTIQUES FINALES ===\n";

$seq1Evals = DB::table('evaluations')->where('sequence_id', 1)->count();
$seq2Evals = DB::table('evaluations')->where('sequence_id', 2)->count();
$compoEvalsT1 = DB::table('evaluations')->where('type', 'composition')->where('trimester_id', 1)->count();

$seq1Grades = DB::table('grades')->where('sequence_id', 1)->whereNotNull('score')->count();
$seq2Grades = DB::table('grades')->where('sequence_id', 2)->whereNotNull('score')->count();
$compoGradesT1 = DB::table('grades')->whereNull('sequence_id')->where('trimester_id', 1)->whereNotNull('score')->count();

echo "ÉVALUATIONS:\n";
echo "  Séquence 1: {$seq1Evals}\n";
echo "  Séquence 2: {$seq2Evals}\n";
echo "  Compositions T1: {$compoEvalsT1}\n";

echo "\nGRADES:\n";
echo "  Séquence 1: {$seq1Grades}\n";
echo "  Séquence 2: {$seq2Grades}\n";
echo "  Compositions T1: {$compoGradesT1}\n";

$ratio = $seq1Evals > 0 ? round(($seq2Evals / $seq1Evals) * 100, 1) : 0;
echo "\nRatio Seq2/Seq1: {$ratio}%\n";

if ($ratio >= 80) {
    echo "\n✅ EXCELLENT! Le ratio est bon (>= 80%)\n";
} elseif ($ratio >= 60) {
    echo "\n✅ BON! Le ratio s'est amélioré (>= 60%)\n";
} else {
    echo "\n⚠️  Le ratio reste bas (< 60%)\n";
}

echo "\n🎉 RÉPARATION TERMINÉE!\n";
