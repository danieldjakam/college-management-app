<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\BulletinController;
use Illuminate\Http\Request;

echo "🧪 Test pour HASSIM ACHTA\n";
echo str_repeat("=", 60) . "\n\n";

// Vérifier dans la DB
echo "1️⃣ Vérification base de données:\n";
$student = DB::table('students')->where('id', 251)->first();
echo "   Élève ID 251: {$student->first_name} {$student->last_name}\n";

$bulletin = DB::table('bulletin_generations')
    ->where('student_id', 251)
    ->where('period_identifier', 'seq2')
    ->first();

if ($bulletin) {
    echo "   ✅ Bulletin existe! ID: {$bulletin->id}\n";
    echo "   File: {$bulletin->file_path}\n";
    echo "   Complete: " . ($bulletin->is_complete ? "Oui" : "Non") . "\n";
} else {
    echo "   ❌ Bulletin n'existe PAS!\n";
}

// Tester l'API
echo "\n2️⃣ Test de l'API students-status:\n";

$controller = new BulletinController(
    app(\App\Services\BulletinService::class)
);

$request = new Request();
$response = $controller->getStudentsBulletinStatus(15, $request);

$data = json_decode($response->getContent(), true);

// Trouver HASSIM ACHTA
foreach ($data['students'] as $s) {
    if ($s['id'] == 251) {
        echo "   Données API pour sequence_2:\n";
        echo "   is_generated: " . ($s['bulletins']['sequence_2']['is_generated'] ? 'true' : 'false') . "\n";
        echo "   bulletin_id: " . ($s['bulletins']['sequence_2']['bulletin_id'] ?? 'null') . "\n";
        echo "   completion: " . $s['bulletins']['sequence_2']['completion_percentage'] . "%\n";
        break;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
