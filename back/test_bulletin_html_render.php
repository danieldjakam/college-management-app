<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BulletinService;

echo "=== TEST RENDU HTML BULLETIN TRIMESTRE ===\n\n";

$service = new BulletinService();

// GERMAINE
$student = DB::table('students')
    ->where('first_name', 'LIKE', '%GERMAINE%')
    ->where('last_name', 'LIKE', '%EMADE%')
    ->first();

// Générer les données du bulletin Trimestre 1
$bulletinData = $service->generateTrimesterBulletinData(1, $student->id);

echo "Section: {$bulletinData['subjects'][0]['section_type']}\n";
echo "Cycle: {$bulletinData['subjects'][0]['cycle_type']}\n\n";

// Générer le HTML
$html = $service->renderBulletinTemplate('trimester', $bulletinData, true);

// Extraire la partie avec ANGLAIS
preg_match('/<tr.*?ANGLAIS.*?<\/tr>/s', $html, $matches);

if ($matches) {
    echo "=== HTML GÉNÉRÉ POUR ANGLAIS ===\n";
    // Nettoyer le HTML pour lisibilité
    $row = $matches[0];
    $row = preg_replace('/>\s+</', '><', $row);
    echo $row . "\n\n";

    // Extraire les valeurs des colonnes
    preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $row, $cells);
    if ($cells[1]) {
        echo "Colonnes extraites:\n";
        foreach ($cells[1] as $index => $cell) {
            $cleaned = strip_tags($cell);
            echo "  Col {$index}: '{$cleaned}'\n";
        }
    }
} else {
    echo "❌ Ligne ANGLAIS non trouvée dans le HTML!\n\n";

    // Afficher un extrait du HTML
    echo "Extrait du HTML (premiers 5000 caractères):\n";
    echo substr($html, 0, 5000) . "...\n";
}
