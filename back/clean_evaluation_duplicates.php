<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🧹 Nettoyage des doublons dans la table evaluations\n";
echo str_repeat('=', 70) . "\n\n";

// Trouver les doublons
$duplicates = DB::select("
    SELECT class_series_subject_id, sequence_id, teacher_id, COUNT(*) as count, GROUP_CONCAT(id) as ids
    FROM evaluations
    GROUP BY class_series_subject_id, sequence_id, teacher_id
    HAVING count > 1
");

if (empty($duplicates)) {
    echo "✅ Aucun doublon trouvé!\n";
    exit(0);
}

echo "❌ Doublons trouvés: " . count($duplicates) . "\n\n";

foreach ($duplicates as $duplicate) {
    echo "📋 Subject: {$duplicate->class_series_subject_id}, Sequence: {$duplicate->sequence_id}, Teacher: {$duplicate->teacher_id}\n";
    echo "   Nombre: {$duplicate->count}\n";
    echo "   IDs: {$duplicate->ids}\n";

    // Garder seulement le premier, supprimer les autres
    $ids = explode(',', $duplicate->ids);
    $keepId = $ids[0];
    $deleteIds = array_slice($ids, 1);

    echo "   ✅ Garder ID: {$keepId}\n";
    echo "   ❌ Supprimer IDs: " . implode(', ', $deleteIds) . "\n";

    DB::table('evaluations')->whereIn('id', $deleteIds)->delete();
    echo "   ✅ Supprimé!\n\n";
}

echo str_repeat('=', 70) . "\n";
echo "✨ Nettoyage terminé! Vous pouvez maintenant relancer: php artisan migrate\n";
