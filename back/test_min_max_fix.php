<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BulletinService;

echo "🧪 Test des 3 corrections:\n";
echo "   1. DS = (Seq1 + Seq2) / 2 dans la colonne N/20\n";
echo "   2. [Min - Max] affiche les vraies données\n";
echo "   3. Min-Max classe général affiche les vraies données\n";
echo str_repeat('=', 60) . "\n\n";

$bulletinService = app(BulletinService::class);
$data = $bulletinService->generateTrimesterBulletinData(1, 3, 'premier');

echo "📊 Résultats pour élève ID 3 (Trimestre 1):\n\n";

if (isset($data['subjects'][0])) {
    $subject = $data['subjects'][0];
    echo "📋 Première matière: {$subject['name']}\n";
    echo "   DS (N/20): " . ($subject['ds'] ?? 'null') . "\n";
    echo "   Composition: " . ($subject['composition'] ?? 'null') . "\n";
    echo "   Moyenne (M/20): " . ($subject['score'] ?? 'null') . "\n";
    echo "   [Min - Max]: " . ($subject['min_max'] ?? '[N/A]') . "\n";

    // Test 1: DS doit être présent
    if (isset($subject['ds']) && $subject['ds'] > 0) {
        echo "   ✅ Test 1 PASSED: DS est calculé\n";
    } else {
        echo "   ❌ Test 1 FAILED: DS est null ou 0\n";
    }

    // Test 2: Min-Max ne doit pas être [N/A]
    if (isset($subject['min_max']) && $subject['min_max'] !== '[N/A]') {
        echo "   ✅ Test 2 PASSED: Min-Max affiche les données\n";
    } else {
        echo "   ❌ Test 2 FAILED: Min-Max = [N/A]\n";
    }
}

echo "\n📈 Classe Général:\n";
echo "   Min-Max: " . ($data['class_average_min_max'] ?? '[N/A]') . "\n";

// Test 3: Min-Max classe ne doit pas être [N/A]
if (isset($data['class_average_min_max']) && $data['class_average_min_max'] !== '[N/A]') {
    echo "   ✅ Test 3 PASSED: Min-Max classe affiche les données\n";
} else {
    echo "   ❌ Test 3 FAILED: Min-Max classe = [N/A]\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "\n📝 Note: Si les compositions ne sont pas encore entrées,\n";
echo "   les calculs utilisent uniquement les notes DS.\n";
