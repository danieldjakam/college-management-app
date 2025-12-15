<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧹 NETTOYAGE DES BULLETINS MANQUANTS\n";
echo "====================================\n\n";

// Récupérer tous les bulletins
$bulletins = DB::table('bulletin_generations')->get();
$totalInDB = count($bulletins);

echo "Total bulletins dans la base: {$totalInDB}\n";

// Vérifier combien ont des fichiers manquants
$missing = [];
$existing = 0;

foreach ($bulletins as $bulletin) {
    if (!$bulletin->file_path) {
        $missing[] = $bulletin->id;
        continue;
    }

    $fullPath = storage_path('app/' . $bulletin->file_path);

    if (!file_exists($fullPath)) {
        $missing[] = $bulletin->id;
    } else {
        $existing++;
    }
}

$missingCount = count($missing);
$percentage = round(($missingCount / $totalInDB) * 100, 1);

echo "Fichiers existants: {$existing}\n";
echo "Fichiers manquants: {$missingCount} ({$percentage}%)\n\n";

if ($missingCount == 0) {
    echo "✅ Aucun fichier manquant, rien à nettoyer!\n";
    exit(0);
}

echo "⚠️  ACTION: Supprimer les {$missingCount} enregistrements sans fichiers PDF?\n";
echo "Cela permettra de régénérer les bulletins proprement.\n\n";

echo "Continuer? (y/N): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'y') {
    echo "❌ Opération annulée.\n";
    exit(0);
}

// Supprimer par lots de 100
echo "\nSuppression en cours...\n";
$chunks = array_chunk($missing, 100);
$deleted = 0;

foreach ($chunks as $chunk) {
    $count = DB::table('bulletin_generations')
        ->whereIn('id', $chunk)
        ->delete();
    $deleted += $count;
    echo ".";
}

echo "\n\n✅ {$deleted} enregistrements supprimés!\n";
echo "💡 Les bulletins pourront maintenant être régénérés proprement.\n";
