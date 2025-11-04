<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\BulletinAutoGenerationService;
use App\Services\BulletinService;
use App\Models\Grade;

echo "🧪 Test de génération de bulletin Séquence 2\n";
echo str_repeat("=", 60) . "\n\n";

// Prendre le premier élève de 6ème A avec des notes en Seq 2
$studentId = 251; // HASSIM ACHTA
$sequenceNumber = 2;

$student = DB::table('students')->where('id', $studentId)->first();
echo "👤 Élève: {$student->first_name} {$student->last_name}\n";
echo "📝 Séquence: {$sequenceNumber}\n\n";

// Vérifier les notes
$sequence = DB::table('sequences')->where('number', $sequenceNumber)->first();
$gradesCount = DB::table('grades')
    ->where('student_id', $studentId)
    ->where('sequence_id', $sequence->id)
    ->count();

echo "✅ Notes trouvées: {$gradesCount}\n\n";

// Trouver une note pour déclencher la génération
$grade = Grade::where('student_id', $studentId)
    ->where('sequence_id', $sequence->id)
    ->first();

if (!$grade) {
    echo "❌ Aucune note trouvée!\n";
    exit(1);
}

echo "🔄 Déclenchement de la génération automatique...\n\n";

// Créer les services
$bulletinService = app(BulletinService::class);
$autoGenerationService = new BulletinAutoGenerationService($bulletinService);

// Déclencher la génération
$autoGenerationService->checkAndGenerateBulletins($grade->id);

echo "⏳ Attente de la génération...\n";
sleep(2);

// Vérifier si le bulletin a été créé
$bulletin = DB::table('bulletin_generations')
    ->where('student_id', $studentId)
    ->where('period_type', 'sequence')
    ->where('period_identifier', 'seq2')
    ->first();

echo "\n" . str_repeat("=", 60) . "\n";

if ($bulletin) {
    echo "✅ BULLETIN GÉNÉRÉ!\n";
    echo "   ID: {$bulletin->id}\n";
    echo "   Créé le: {$bulletin->created_at}\n";
    echo "   Complété: " . ($bulletin->is_complete ? "Oui" : "Non") . "\n";
    echo "   Fichier: " . ($bulletin->file_path ? "Oui" : "Non") . "\n";
} else {
    echo "❌ BULLETIN NON GÉNÉRÉ\n";
    echo "   Vérifier les logs pour plus de détails\n";
}

echo str_repeat("=", 60) . "\n";
