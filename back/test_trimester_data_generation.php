<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BulletinService;

echo "=== TEST GÉNÉRATION DONNÉES BULLETIN TRIMESTRE ===\n\n";

$service = new BulletinService();

// GERMAINE
$student = DB::table('students')
    ->where('first_name', 'LIKE', '%GERMAINE%')
    ->where('last_name', 'LIKE', '%EMADE%')
    ->first();

echo "Étudiante: {$student->first_name} {$student->last_name}\n\n";

// Générer les données du bulletin Trimestre 1
$bulletinData = $service->generateTrimesterBulletinData(1, $student->id);

echo "=== DONNÉES GÉNÉRÉES ===\n";
echo "Cycle type: {$bulletinData['subjects'][0]['cycle_type']}\n";
echo "Section type: {$bulletinData['subjects'][0]['section_type']}\n\n";

echo "=== EXEMPLE: ANGLAIS ===\n";
$anglais = collect($bulletinData['subjects'])->firstWhere('name', 'Anglais');

if ($anglais) {
    echo "Matière: {$anglais['name']}\n";
    echo "  sequence1: " . var_export($anglais['sequence1'] ?? 'CLÉ MANQUANTE!', true) . "\n";
    echo "  sequence2: " . var_export($anglais['sequence2'] ?? 'CLÉ MANQUANTE!', true) . "\n";
    echo "  composition: " . var_export($anglais['composition'] ?? 'CLÉ MANQUANTE!', true) . "\n";
    echo "  average: " . var_export($anglais['average'] ?? 'CLÉ MANQUANTE!', true) . "\n";
    echo "  cycle_type: " . var_export($anglais['cycle_type'] ?? 'CLÉ MANQUANTE!', true) . "\n";
    echo "  section_type: " . var_export($anglais['section_type'] ?? 'CLÉ MANQUANTE!', true) . "\n";
} else {
    echo "❌ Anglais non trouvé dans les matières!\n";
}

echo "\n=== VÉRIFICATION DES CLÉS POUR TOUTES LES MATIÈRES ===\n";
$foundSeq1 = 0;
$foundSeq2 = 0;
$foundComp = 0;

foreach ($bulletinData['subjects'] as $subject) {
    if (isset($subject['sequence1'])) $foundSeq1++;
    if (isset($subject['sequence2'])) $foundSeq2++;
    if (isset($subject['composition'])) $foundComp++;
}

echo "Matières avec 'sequence1': {$foundSeq1}/" . count($bulletinData['subjects']) . "\n";
echo "Matières avec 'sequence2': {$foundSeq2}/" . count($bulletinData['subjects']) . "\n";
echo "Matières avec 'composition': {$foundComp}/" . count($bulletinData['subjects']) . "\n";
