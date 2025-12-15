<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BulletinService;

echo "=== TEST DEBUG GÉNÉRATION HTML ===\n\n";

$service = new BulletinService();

$student = App\Models\Student::where('first_name', 'LIKE', '%GERMAINE%')
    ->where('last_name', 'LIKE', '%EMADE%')
    ->first();

// Générer les données
$bulletinData = $service->generateTrimesterBulletinData(1, $student->id);

echo "Cycle type de l'élève: ";
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('determineCycleType');
$method->setAccessible(true);
echo $method->invoke($service, $student) . "\n\n";

// Vérifier les données ANGLAIS
$anglais = collect($bulletinData['subjects'])->firstWhere('name', 'Anglais');
echo "Données ANGLAIS avant rendu HTML:\n";
echo "  sequence1: " . var_export($anglais['sequence1'], true) . "\n";
echo "  sequence2: " . var_export($anglais['sequence2'], true) . "\n";
echo "  composition: " . var_export($anglais['composition'], true) . "\n";
echo "  cycle_type: " . var_export($anglais['cycle_type'], true) . "\n";
echo "  section_type: " . var_export($anglais['section_type'], true) . "\n\n";

// Générer le HTML
$html = $service->renderBulletinTemplate('trimester', $bulletinData, true);

// Chercher la ligne ANGLAIS dans le HTML
if (preg_match('/<tr[^>]*>.*?<td[^>]*>ANGLAIS<\/td>(.*?)<\/tr>/is', $html, $matches)) {
    echo "Ligne ANGLAIS trouvée dans HTML:\n";
    $fullRow = $matches[0];
    
    // Extraire toutes les cellules
    preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $fullRow, $cells);
    echo "Nombre de colonnes: " . count($cells[1]) . "\n";
    for ($i = 0; $i < min(6, count($cells[1])); $i++) {
        $content = strip_tags($cells[1][$i]);
        echo "  Col {$i}: '{$content}'\n";
    }
} else {
    echo "❌ Ligne ANGLAIS NON trouvée dans le HTML!\n";
}

