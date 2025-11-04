<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\BulletinAutoGenerationService;
use App\Services\BulletinService;
use App\Models\Grade;

echo "📚 Génération des bulletins Séquence 2 pour 6ème A\n";
echo str_repeat("=", 60) . "\n\n";

$seriesId = 15; // 6ème A
$sequenceNumber = 2;

// Récupérer tous les élèves
$students = DB::table('students')
    ->where('class_series_id', $seriesId)
    ->where('is_active', true)
    ->orderBy('last_name')
    ->get();

echo "👥 Nombre d'élèves: " . count($students) . "\n\n";

$sequence = DB::table('sequences')->where('number', $sequenceNumber)->first();

// Créer les services
$bulletinService = app(BulletinService::class);
$autoGenerationService = new BulletinAutoGenerationService($bulletinService);

$generated = 0;
$alreadyExists = 0;
$failed = 0;

foreach ($students as $student) {
    echo "👤 {$student->first_name} {$student->last_name}... ";

    // Vérifier si le bulletin existe déjà
    $existingBulletin = DB::table('bulletin_generations')
        ->where('student_id', $student->id)
        ->where('period_type', 'sequence')
        ->where('period_identifier', 'seq2')
        ->first();

    if ($existingBulletin) {
        echo "⏭️  Déjà généré (ID: {$existingBulletin->id})\n";
        $alreadyExists++;
        continue;
    }

    // Trouver une note pour déclencher la génération
    $grade = Grade::where('student_id', $student->id)
        ->where('sequence_id', $sequence->id)
        ->first();

    if (!$grade) {
        echo "❌ Aucune note\n";
        $failed++;
        continue;
    }

    try {
        // Déclencher la génération
        $autoGenerationService->checkAndGenerateBulletins($grade->id);

        // Vérifier si le bulletin a été créé
        sleep(1);
        $bulletin = DB::table('bulletin_generations')
            ->where('student_id', $student->id)
            ->where('period_type', 'sequence')
            ->where('period_identifier', 'seq2')
            ->first();

        if ($bulletin) {
            echo "✅ Généré (ID: {$bulletin->id})\n";
            $generated++;
        } else {
            echo "⚠️  Non généré\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ:\n";
echo "   ✅ Générés: {$generated}\n";
echo "   ⏭️  Déjà existants: {$alreadyExists}\n";
echo "   ❌ Échoués: {$failed}\n";
echo "   📝 Total: " . count($students) . "\n";
echo str_repeat("=", 60) . "\n";
