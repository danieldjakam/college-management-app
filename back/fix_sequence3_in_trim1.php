<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 CORRECTION: Évaluations Séquence 3 dans Trimestre 1\n";
echo "========================================================\n\n";

echo "PROBLÈME:\n";
echo "  20 évaluations ont sequence_id=3 dans trimester_id=1\n";
echo "  Mais Séquence 3 devrait être dans Trimestre 2!\n";
echo "  Toutes ces évaluations sont des DS de Séquence 1 (DS1, devoir surveillé 1, etc.)\n\n";

// ÉTAPE 1: Identifier les évaluations problématiques
$problematicEvals = DB::table('evaluations')
    ->where('sequence_id', 3)
    ->where('trimester_id', 1)
    ->get();

echo "=== ÉTAT AVANT CORRECTION ===\n";
echo "Évaluations avec sequence_id=3 dans trimester_id=1: " . count($problematicEvals) . "\n\n";

if (count($problematicEvals) > 0) {
    echo "Échantillon (10 premières):\n";
    foreach ($problematicEvals->take(10) as $eval) {
        $gradeCount = DB::table('grades')->where('evaluation_id', $eval->id)->count();
        echo "  ID {$eval->id}: \"{$eval->name}\" (type={$eval->type}, {$gradeCount} grades)\n";
    }
    echo "\n";
}

// ÉTAPE 2: Correction
echo "=== CORRECTION ===\n";
echo "Action: Mettre sequence_id=1 pour ces évaluations (ce sont des DS de Séquence 1)\n\n";

// Demander confirmation
echo "⚠️  ATTENTION: Cette opération va modifier " . count($problematicEvals) . " évaluations.\n";
echo "Continuer? (y/N): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'y') {
    echo "❌ Opération annulée.\n";
    exit(0);
}

// Appliquer la correction
DB::beginTransaction();
try {
    // Mettre à jour les évaluations
    $updated = DB::table('evaluations')
        ->where('sequence_id', 3)
        ->where('trimester_id', 1)
        ->update(['sequence_id' => 1]);

    echo "✅ {$updated} évaluations mises à jour\n\n";

    // Mettre à jour les grades correspondants
    echo "Mise à jour des grades correspondants...\n";
    $gradesUpdated = 0;
    foreach ($problematicEvals as $eval) {
        $count = DB::table('grades')
            ->where('evaluation_id', $eval->id)
            ->update(['sequence_id' => 1]);
        $gradesUpdated += $count;
    }

    echo "✅ {$gradesUpdated} grades mis à jour\n\n";

    DB::commit();

    echo "✅ CORRECTION APPLIQUÉE AVEC SUCCÈS!\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

// ÉTAPE 3: Vérification
echo "=== VÉRIFICATION FINALE ===\n";
$remaining = DB::table('evaluations')
    ->where('sequence_id', 3)
    ->where('trimester_id', 1)
    ->count();

echo "Évaluations avec sequence_id=3 dans trimester_id=1: {$remaining}\n";

if ($remaining == 0) {
    echo "✅ PARFAIT! Plus aucune évaluation Séquence 3 dans Trimestre 1.\n";
} else {
    echo "⚠️  Il reste {$remaining} évaluations (vérifiez manuellement)\n";
}

echo "\n=== RÉSULTAT ===\n";
echo "Maintenant, dans la liste des PV pour Trimestre 1, vous ne devriez voir que:\n";
echo "  - Séquence 1\n";
echo "  - Séquence 2\n";
echo "  - Composition 1\n";
echo "\n✅ 'Séquence 3' ne devrait plus apparaître!\n";
