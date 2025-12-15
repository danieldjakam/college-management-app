<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 RÉPARATION COMPLÈTE DE LA BASE DE DONNÉES - VERSION FINALE\n";
echo "============================================================\n\n";

// ÉTAPE 1: Identifier les évaluations mal typées (type='composition' mais nom contient DS/devoir)
echo "=== ÉTAPE 1: IDENTIFICATION DES ÉVALUATIONS MAL TYPÉES ===\n";

$fakeCompositions = DB::table('evaluations')
    ->where('type', 'composition')
    ->where(function($query) {
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

echo "✅ Évaluations mal typées trouvées: " . count($fakeCompositions) . "\n\n";

// ÉTAPE 2: Corriger le type et assigner le bon sequence_id
echo "=== ÉTAPE 2: CORRECTION DES TYPES ET SEQUENCE_ID ===\n";

$correctionCount = 0;
$bySequence = ['seq1' => 0, 'seq2' => 0, 'seq3' => 0, 'seq4' => 0, 'unknown' => 0];

foreach ($fakeCompositions as $eval) {
    $newType = 'ds';
    $newSequenceId = null;

    // Déterminer la séquence basée sur le nom
    if (preg_match('/ds\s*2|devoir\s*2|novembre|november|no\s*2|n°\s*2|n\s*2/i', $eval->name)) {
        $newSequenceId = 2;
        $bySequence['seq2']++;
    }
    elseif (preg_match('/ds\s*1|devoir\s*1|octobre|october|no\s*1|n°\s*1|n\s*1/i', $eval->name)) {
        $newSequenceId = 1;
        $bySequence['seq1']++;
    }
    elseif (preg_match('/ds\s*3|devoir\s*3|janvier|january/i', $eval->name) || $eval->trimester_id == 2) {
        $newSequenceId = 3;
        $bySequence['seq3']++;
    }
    elseif (preg_match('/ds\s*4|devoir\s*4|février|february|mars|march/i', $eval->name) || $eval->trimester_id == 2) {
        $newSequenceId = 4;
        $bySequence['seq4']++;
    }
    elseif ($eval->trimester_id == 1) {
        $newSequenceId = 1;
        $bySequence['seq1']++;
    }
    else {
        $bySequence['unknown']++;
        continue;
    }

    DB::table('evaluations')
        ->where('id', $eval->id)
        ->update([
            'type' => $newType,
            'sequence_id' => $newSequenceId
        ]);

    $correctionCount++;
}

echo "✅ Évaluations corrigées: {$correctionCount}\n";
echo "   - Séquence 1: {$bySequence['seq1']}\n";
echo "   - Séquence 2: {$bySequence['seq2']}\n";
echo "   - Séquence 3: {$bySequence['seq3']}\n";
echo "   - Séquence 4: {$bySequence['seq4']}\n";
echo "   - Non déterminées: {$bySequence['unknown']}\n\n";

// ÉTAPE 3: Corriger les évaluations avec "novembre" ou "No 2" mal assignées
echo "=== ÉTAPE 3: CORRECTION DES ÉVALUATIONS MAL ASSIGNÉES ===\n";

$wrongSeq = DB::table('evaluations')
    ->where('type', '!=', 'composition')
    ->where(function($query) {
        $query->where('name', 'LIKE', '%novembre%')
              ->orWhere('name', 'LIKE', '%November%')
              ->orWhere('name', 'LIKE', '%No 2%')
              ->orWhere('name', 'LIKE', '%N°2%')
              ->orWhere('name', 'LIKE', '%Évaluation trimestrielle No 2%');
    })
    ->where('sequence_id', '!=', 2)
    ->count();

if ($wrongSeq > 0) {
    DB::table('evaluations')
        ->where('type', '!=', 'composition')
        ->where(function($query) {
            $query->where('name', 'LIKE', '%novembre%')
                  ->orWhere('name', 'LIKE', '%November%')
                  ->orWhere('name', 'LIKE', '%No 2%')
                  ->orWhere('name', 'LIKE', '%N°2%')
                  ->orWhere('name', 'LIKE', '%Évaluation trimestrielle No 2%');
        })
        ->where('sequence_id', '!=', 2)
        ->update(['sequence_id' => 2]);

    echo "✅ Évaluations corrigées: {$wrongSeq}\n\n";
} else {
    echo "✅ Aucune évaluation mal assignée trouvée\n\n";
}

// ÉTAPE 4: Restaurer les sequence_id des grades depuis les evaluations
echo "=== ÉTAPE 4: RESTAURATION DES sequence_id DES GRADES ===\n";

$beforeNull = DB::table('grades')->whereNull('sequence_id')->count();
echo "Grades avec sequence_id=NULL avant: {$beforeNull}\n";

DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = e.sequence_id
    WHERE g.sequence_id IS NULL
      AND e.sequence_id IS NOT NULL
      AND e.type != 'composition'
");

// Synchroniser les grades dont sequence_id ne correspond pas à l'évaluation
DB::statement("
    UPDATE grades g
    INNER JOIN evaluations e ON g.evaluation_id = e.id
    SET g.sequence_id = e.sequence_id
    WHERE g.sequence_id != e.sequence_id
      AND e.type != 'composition'
      AND e.sequence_id IS NOT NULL
");

$afterNull = DB::table('grades')->whereNull('sequence_id')->count();
$restoredCount = $beforeNull - $afterNull;

echo "Grades avec sequence_id=NULL après: {$afterNull}\n";
echo "✅ Grades restaurés: {$restoredCount}\n\n";

// ÉTAPE 5: Vérification finale
echo "=== ÉTAPE 5: VÉRIFICATION FINALE ===\n";

$realCompositions = DB::table('evaluations')->where('type', 'composition')->count();
echo "Évaluations de type 'composition' restantes: {$realCompositions}\n";

$problematicGrades = DB::table('grades as g')
    ->join('evaluations as e', 'g.evaluation_id', '=', 'e.id')
    ->whereNull('g.sequence_id')
    ->where('e.type', '!=', 'composition')
    ->count();

if ($problematicGrades == 0) {
    echo "✅ PARFAIT! Tous les grades avec sequence_id=NULL sont des compositions.\n";
} else {
    echo "⚠️  ATTENTION: Il reste {$problematicGrades} grades NON-compositions avec sequence_id=NULL\n";
}

// Statistiques finales
$totalGrades = DB::table('grades')->count();
$nullGrades = DB::table('grades')->whereNull('sequence_id')->count();
$percentNull = round(($nullGrades/$totalGrades)*100, 1);

echo "\n=== STATISTIQUES GLOBALES ===\n";
echo "Total grades: {$totalGrades}\n";
echo "Grades avec sequence_id=NULL: {$nullGrades} ({$percentNull}%)\n";
echo "Grades avec sequence_id valide: " . ($totalGrades - $nullGrades) . " (" . (100 - $percentNull) . "%)\n";

echo "\n🎉 RÉPARATION TERMINÉE AVEC SUCCÈS!\n";
echo "📝 Veuillez vérifier l'interface pour confirmer que tout fonctionne correctement.\n";
