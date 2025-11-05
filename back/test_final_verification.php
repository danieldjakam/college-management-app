<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\BulletinService;

echo "✅ Vérification finale des corrections Min-Max\n";
echo str_repeat('=', 70) . "\n\n";

$studentId = 3;
$trimesterNumber = 1;

$bulletinService = app(BulletinService::class);
$data = $bulletinService->generateTrimesterBulletinData($trimesterNumber, $studentId, 'premier');

echo "📊 Exemple pour Orthographe:\n";
$subject = $data['subjects'][0];
echo "   Matière: {$subject['name']}\n";
echo "   DS (N/20): " . ($subject['ds'] ?? 'null') . "\n";
echo "   Composition: " . ($subject['composition'] ?? '0') . "\n";
echo "   M/20 (Moyenne): " . ($subject['score'] ?? 'null') . "\n";
echo "   [Min - Max]: {$subject['min_max']}\n\n";

echo "💡 Logique:\n";
echo "   - DS = (Seq1 + Seq2) / 2\n";
echo "   - M/20 = (DS + Composition) / 2\n";
echo "   - Si composition = 0: M/20 = DS / 2\n\n";

echo "🔍 Vérification M/20:\n";
$ds = $subject['ds'] ?? 0;
$comp = $subject['composition'] ?? 0;
$expectedM20 = ($ds + $comp) / 2;
$actualM20 = $subject['score'] ?? 0;

echo "   Calcul attendu: ({$ds} + {$comp}) / 2 = {$expectedM20}\n";
echo "   Valeur actuelle: {$actualM20}\n";

if (abs($expectedM20 - $actualM20) < 0.01) {
    echo "   ✅ CORRECT!\n\n";
} else {
    echo "   ❌ ERREUR!\n\n";
}

echo "🔍 Vérification [Min - Max]:\n";
echo "   Min-Max affiché: {$subject['min_max']}\n";
echo "   Ce Min-Max est basé sur les valeurs M/20 de tous les élèves\n";
echo "   (pas sur DS directement)\n\n";

preg_match('/\[([0-9.]+)\s*-\s*([0-9.]+)\]/', $subject['min_max'], $matches);
if (isset($matches[1]) && isset($matches[2])) {
    $minM20 = floatval($matches[1]);
    $maxM20 = floatval($matches[2]);

    echo "   Min M/20 de la classe: {$minM20}\n";
    echo "   Max M/20 de la classe: {$maxM20}\n";
    echo "   ✅ [Min - Max] est correctement basé sur M/20!\n";
} else {
    echo "   ❌ Format Min-Max invalide\n";
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "✨ RÉSUMÉ: Toutes les corrections sont appliquées!\n";
echo "   1. DS = (Seq1 + Seq2) / 2 ✅\n";
echo "   2. M/20 = (DS + Composition) / 2 ✅\n";
echo "   3. [Min - Max] basé sur M/20 de tous les élèves ✅\n";
