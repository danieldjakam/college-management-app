<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🗑️  SUPPRESSION DES MIGRATIONS DANGEREUSES\n";
echo "=========================================\n\n";

$dangerousMigrations = [
    '2025_12_08_211748_fix_grades_sequence_id_for_compositions',
    '2025_12_09_002552_fix_bulletin_generations_auto_increment_and_composition_grades'
];

foreach ($dangerousMigrations as $migration) {
    $exists = DB::table('migrations')->where('migration', $migration)->exists();

    if ($exists) {
        DB::table('migrations')->where('migration', $migration)->delete();
        echo "✅ Supprimé: {$migration}\n";
    } else {
        echo "⏭️  Déjà absent: {$migration}\n";
    }
}

echo "\n📊 VÉRIFICATION FINALE\n";
$remaining = DB::table('migrations')
    ->where('migration', 'LIKE', '%composition%')
    ->orWhere('migration', 'LIKE', '%bulletin_generations%')
    ->get();

if ($remaining->isEmpty()) {
    echo "✅ Aucune migration suspecte restante\n";
} else {
    echo "⚠️  Migrations restantes liées aux compositions:\n";
    foreach ($remaining as $mig) {
        echo "  - {$mig->migration}\n";
    }
}

echo "\n🎉 TERMINÉ!\n";
echo "💡 Ces migrations ne s'exécuteront plus si vous faites 'php artisan migrate'\n";
