<?php

/**
 * Script de migration des configurations de matières
 * De series_subjects (school_class_id) vers class_series_subjects (class_series_id)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== MIGRATION SERIES_SUBJECTS → CLASS_SERIES_SUBJECTS ===" . PHP_EOL . PHP_EOL;

try {
    DB::beginTransaction();

    // 1. Récupérer toutes les configurations de l'ancienne table
    $oldConfigs = DB::table('series_subjects')->get();

    echo "📋 {$oldConfigs->count()} configurations trouvées dans series_subjects" . PHP_EOL . PHP_EOL;

    $migratedCount = 0;
    $skippedCount = 0;

    foreach ($oldConfigs as $oldConfig) {
        echo "Configuration #{$oldConfig->id}:" . PHP_EOL;
        echo "  - school_class_id: {$oldConfig->school_class_id}" . PHP_EOL;
        echo "  - subject_id: {$oldConfig->subject_id}" . PHP_EOL;
        echo "  - coefficient: {$oldConfig->coefficient}" . PHP_EOL;

        // 2. Trouver toutes les séries (class_series) pour cette classe (school_class)
        $classSeries = DB::table('class_series')
            ->where('class_id', $oldConfig->school_class_id)
            ->get();

        if ($classSeries->isEmpty()) {
            echo "  ⚠️  Aucune série trouvée pour school_class_id {$oldConfig->school_class_id}" . PHP_EOL;
            $skippedCount++;
            continue;
        }

        echo "  ✅ {$classSeries->count()} série(s) trouvée(s):" . PHP_EOL;

        // 3. Créer une configuration pour chaque série
        foreach ($classSeries as $serie) {
            echo "     - {$serie->name} (ID: {$serie->id})" . PHP_EOL;

            // Vérifier si la configuration existe déjà
            $exists = DB::table('class_series_subjects')
                ->where('class_series_id', $serie->id)
                ->where('subject_id', $oldConfig->subject_id)
                ->exists();

            if ($exists) {
                echo "       → Déjà existante, ignorée" . PHP_EOL;
                continue;
            }

            // Créer la nouvelle configuration
            DB::table('class_series_subjects')->insert([
                'class_series_id' => $serie->id,
                'subject_id' => $oldConfig->subject_id,
                'coefficient' => $oldConfig->coefficient,
                'is_active' => $oldConfig->is_active ?? true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $migratedCount++;
            echo "       → Créée ✅" . PHP_EOL;
        }

        echo PHP_EOL;
    }

    DB::commit();

    echo "=== MIGRATION TERMINÉE ===" . PHP_EOL;
    echo "✅ {$migratedCount} configurations migrées" . PHP_EOL;
    echo "⚠️  {$skippedCount} configurations ignorées (pas de série trouvée)" . PHP_EOL;

} catch (Exception $e) {
    DB::rollBack();
    echo PHP_EOL . "❌ ERREUR : " . $e->getMessage() . PHP_EOL;
    echo "Aucune modification n'a été appliquée (rollback effectué)" . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "=== FIN DU SCRIPT ===" . PHP_EOL;
