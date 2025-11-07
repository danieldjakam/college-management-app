<?php

/**
 * Script pour nettoyer les doublons dans la table evaluations
 * À exécuter sur le serveur avant php artisan migrate
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 Recherche des doublons dans la table evaluations...\n\n";

// Trouver les doublons
$duplicates = DB::select("
    SELECT
        class_series_subject_id,
        sequence_id,
        teacher_id,
        COUNT(*) as count,
        GROUP_CONCAT(id ORDER BY id) as ids
    FROM evaluations
    GROUP BY class_series_subject_id, sequence_id, teacher_id
    HAVING COUNT(*) > 1
");

if (empty($duplicates)) {
    echo "✅ Aucun doublon trouvé. La migration peut être exécutée.\n";
    exit(0);
}

echo "❌ " . count($duplicates) . " doublons trouvés :\n\n";

foreach ($duplicates as $duplicate) {
    echo "  • class_series_subject_id: {$duplicate->class_series_subject_id}, ";
    echo "sequence_id: {$duplicate->sequence_id}, ";
    echo "teacher_id: {$duplicate->teacher_id} ";
    echo "({$duplicate->count} entrées)\n";
    echo "    IDs: {$duplicate->ids}\n";
}

echo "\n🔧 Suppression des doublons (garde le plus ancien)...\n\n";

$totalDeleted = 0;

foreach ($duplicates as $duplicate) {
    $ids = explode(',', $duplicate->ids);
    // Garder le premier (le plus ancien), supprimer les autres
    $idsToDelete = array_slice($ids, 1);

    if (!empty($idsToDelete)) {
        $deleted = DB::table('evaluations')
            ->whereIn('id', $idsToDelete)
            ->delete();

        $totalDeleted += $deleted;
        echo "  ✓ Supprimé {$deleted} doublon(s) pour (";
        echo "subject={$duplicate->class_series_subject_id}, ";
        echo "seq={$duplicate->sequence_id}, ";
        echo "teacher={$duplicate->teacher_id})\n";
    }
}

echo "\n✅ {$totalDeleted} doublon(s) supprimé(s) avec succès !\n";
echo "✅ Vous pouvez maintenant exécuter: php artisan migrate\n";
