<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 RÉPARATION COMPLÈTE: Types d'évaluations + sequence_id des grades\n\n";

// ÉTAPE 1: Identifier les évaluations mal typées
echo "=== ÉTAPE 1: IDENTIFICATION DES ÉVALUATIONS MAL TYPÉES ===\n";

$fakeCompositions = DB::table('evaluations')
    ->where('type', 'composition')
    ->where(function($query) {
        // Identifier les évaluations qui contiennent "DS", "devoir", "Trim" dans leur nom
        // mais qui sont marquées comme composition
        $query->where('name', 'LIKE', '%DS%')
              ->orWhere('name', 'LIKE', '%ds%')
              ->orWhere('name', 'LIKE', '%Ds%')
              ->orWhere('name', 'LIKE', '%devoir%')
              ->orWhere('name', 'LIKE', '%Devoir%')
              ->orWhere('name', 'LIKE', '%DEVOIR%')
              ->orWhere('name', 'LIKE', '%Trim%')
              ->orWhere('name', 'LIKE', '%trim%');
    })
    ->get();

echo "Évaluations mal typées trouvées: " . count($fakeCompositions) . "\n\n";

if (count($fakeCompositions) == 0) {
    echo "✅ Aucune évaluation mal typée trouvée. Rien à corriger.\n";
    exit;
}

// Afficher échantillon
echo "Échantillon (10 premières):\n";
foreach ($fakeCompositions->take(10) as $eval) {
    $gradeCount = DB::table('grades')->where('evaluation_id', $eval->id)->count();
    echo "  - Eval {$eval->id}: \"{$eval->name}\" (trimestre {$eval->trimester_id}, {$gradeCount} grades)\n";
}

// ÉTAPE 2: Déterminer le type et sequence_id corrects
echo "\n=== ÉTAPE 2: CORRECTION DES TYPES D'ÉVALUATIONS ===\n";

$correctionCount = 0;
$bySequence = ['seq1' => 0, 'seq2' => 0, 'unknown' => 0];

foreach ($fakeCompositions as $eval) {
    $name = strtolower($eval->name);
    $newType = 'ds'; // Type par défaut
    $newSequenceId = null;

    // Déterminer la séquence basée sur le nom et le trimestre
    // Séquence 2: DS2, Devoir 2, Novembre, No 2, N°2
    if (preg_match('/ds\s*2|devoir\s*2|novembre|november|no\s*2|n°\s*2|n\s*2/i', $eval->name)) {
        $newSequenceId = 2; // Séquence 2
        $bySequence['seq2']++;
    }
    // Séquence 1: DS1, Devoir 1, Octobre, October, No 1, N°1
    elseif (preg_match('/ds\s*1|devoir\s*1|octobre|october|no\s*1|n°\s*1|n\s*1/i', $eval->name)) {
        $newSequenceId = 1; // Séquence 1
        $bySequence['seq1']++;
    }
    // Séquence 3: DS3, Devoir 3, Janvier, January
    elseif (preg_match('/ds\s*3|devoir\s*3|janvier|january/i', $eval->name) || $eval->trimester_id == 2) {
        $newSequenceId = 3; // Séquence 3
    }
    // Séquence 4: DS4, Devoir 4, Février, February, Mars, March
    elseif (preg_match('/ds\s*4|devoir\s*4|février|february|mars|march/i', $eval->name) || $eval->trimester_id == 2) {
        $newSequenceId = 4; // Séquence 4
    }
    // Défaut: Trimestre 1 sans indicateur clair = Séquence 1
    elseif ($eval->trimester_id == 1) {
        $newSequenceId = 1;
        $bySequence['seq1']++;
    }
    else {
        // Si on ne peut pas déterminer, on laisse l'évaluation telle quelle
        $bySequence['unknown']++;
        continue;
    }

    // Mettre à jour l'évaluation
    DB::table('evaluations')
        ->where('id', $eval->id)
        ->update([
            'type' => $newType,
            'sequence_id' => $newSequenceId
        ]);

    $correctionCount++;
}

echo "Évaluations corrigées: {$correctionCount}\n";
echo "  - Assignées à Séquence 1: {$bySequence['seq1']}\n";
echo "  - Assignées à Séquence 2: {$bySequence['seq2']}\n";
echo "  - Non déterminées: {$bySequence['unknown']}\n\n";

// ÉTAPE 3: Restaurer les sequence_id des grades
echo "=== ÉTAPE 3: RESTAURATION DES sequence_id DES GRADES ===\n";

$beforeNull = DB::table('grades')->whereNull('sequence_id')->count();
echo "Grades avec sequence_id=NULL avant: {$beforeNull}\n";

// Restaurer les sequence_id depuis les evaluations
$restored = DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = e.sequence_id
    WHERE g.sequence_id IS NULL
      AND e.sequence_id IS NOT NULL
      AND e.type != 'composition'
");

$afterNull = DB::table('grades')->whereNull('sequence_id')->count();
$restoredCount = $beforeNull - $afterNull;

echo "Grades avec sequence_id=NULL après: {$afterNull}\n";
echo "Grades restaurés: {$restoredCount}\n\n";

// ÉTAPE 4: Vérification finale
echo "=== ÉTAPE 4: VÉRIFICATION FINALE ===\n";

// Compter les évaluations de type composition restantes
$realCompositions = DB::table('evaluations')->where('type', 'composition')->count();
echo "Évaluations de type 'composition' restantes: {$realCompositions}\n";

// Vérifier qu'il ne reste que des compositions avec sequence_id=NULL
$problematicGrades = DB::table('grades as g')
    ->join('evaluations as e', 'g.evaluation_id', '=', 'e.id')
    ->whereNull('g.sequence_id')
    ->where('e.type', '!=', 'composition')
    ->count();

if ($problematicGrades == 0) {
    echo "✅ PARFAIT! Tous les grades avec sequence_id=NULL sont maintenant des compositions.\n";
} else {
    echo "⚠️  ATTENTION: Il reste {$problematicGrades} grades NON-compositions avec sequence_id=NULL\n";

    // Afficher échantillon
    $sample = DB::table('grades as g')
        ->join('evaluations as e', 'g.evaluation_id', '=', 'e.id')
        ->whereNull('g.sequence_id')
        ->where('e.type', '!=', 'composition')
        ->select('g.id', 'e.id as eval_id', 'e.name', 'e.type', 'e.sequence_id')
        ->limit(5)
        ->get();

    echo "Échantillon:\n";
    foreach ($sample as $s) {
        echo "  - Grade {$s->id}: eval \"{$s->name}\" (type={$s->type}, seq_id={$s->sequence_id})\n";
    }
}

echo "\n🎉 RÉPARATION TERMINÉE!\n";
echo "\n💡 Vérifiez maintenant l'interface pour confirmer que les pourcentages sont corrects.\n";
