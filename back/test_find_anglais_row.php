<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BulletinService;

$service = new BulletinService();

$student = App\Models\Student::where('first_name', 'LIKE', '%GERMAINE%')
    ->where('last_name', 'LIKE', '%EMADE%')
    ->first();

$bulletinData = $service->generateTrimesterBulletinData(1, $student->id);
$html = $service->renderBulletinTemplate('trimester', $bulletinData, true);

// Sauvegarder le HTML complet
file_put_contents('/tmp/bulletin_germaine_full.html', $html);
echo "HTML complet sauvegardé dans /tmp/bulletin_germaine_full.html\n\n";

// Chercher toutes les tables
preg_match_all('/<table[^>]*class="subjects-table"[^>]*>(.*?)<\/table>/is', $html, $tables);

echo "Nombre de tableaux de matières trouvés: " . count($tables[0]) . "\n\n";

foreach ($tables[0] as $index => $table) {
    if (stripos($table, 'ANGLAIS') !== false) {
        echo "=== Tableau " . ($index + 1) . " contenant ANGLAIS ===\n";
        
        // Extraire toutes les lignes <tr>
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $table, $rows);
        
        foreach ($rows[0] as $rowIndex => $row) {
            if (stripos($row, 'ANGLAIS') !== false && stripos($row, '<th') === false) {
                echo "Ligne " . ($rowIndex + 1) . " (ligne ANGLAIS):\n";
                
                // Extraire les cellules
                preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $row, $cells);
                echo "Nombre de colonnes: " . count($cells[1]) . "\n";
                
                for ($i = 0; $i < min(10, count($cells[1])); $i++) {
                    $content = strip_tags($cells[1][$i]);
                    $content = trim($content);
                    echo "  Col {$i}: '{$content}'\n";
                }
                echo "\n";
            }
        }
    }
}

