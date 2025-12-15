<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BulletinService;

echo "=== TEST GÉNÉRATION BULLETIN TRIMESTRE GERMAINE ===\n\n";

$service = new BulletinService();

// GERMAINE
$student = DB::table('students')
    ->where('first_name', 'LIKE', '%GERMAINE%')
    ->where('last_name', 'LIKE', '%EMADE%')
    ->first();

if (!$student) {
    echo "❌ Étudiante GERMAINE non trouvée\n";
    exit;
}

echo "Étudiante: {$student->first_name} {$student->last_name} (ID: {$student->id})\n\n";

// Générer les données du bulletin Trimestre 1
$bulletinData = $service->generateTrimesterBulletinData(1, $student->id);

if (!$bulletinData) {
    echo "❌ Échec de génération des données\n";
    exit;
}

echo "=== DONNÉES GÉNÉRÉES ===\n";
echo "Clés du tableau: " . implode(', ', array_keys($bulletinData)) . "\n\n";

// Vérifier si subjects existe
if (!isset($bulletinData['subjects'])) {
    echo "❌ Pas de clé 'subjects' dans bulletinData\n";
    exit;
}

echo "Nombre de matières: " . count($bulletinData['subjects']) . "\n\n";

// Trouver ANGLAIS
$anglais = collect($bulletinData['subjects'])->firstWhere('name', 'Anglais');

if ($anglais) {
    echo "=== ANGLAIS ===\n";
    echo "  sequence1: " . ($anglais['sequence1'] ?? 'NULL') . "\n";
    echo "  sequence2: " . ($anglais['sequence2'] ?? 'NULL') . "\n";
    echo "  composition: " . ($anglais['composition'] ?? 'NULL') . "\n";
    echo "  average: " . ($anglais['average'] ?? 'NULL') . "\n\n";

    // Vérifier le type des valeurs
    echo "  Type de sequence1: " . gettype($anglais['sequence1'] ?? 'undefined') . "\n";
    echo "  Type de sequence2: " . gettype($anglais['sequence2'] ?? 'undefined') . "\n";

    // Test de la condition d'affichage
    $seq1 = $anglais['sequence1'] ?? null;
    $seq2 = $anglais['sequence2'] ?? null;

    echo "\n  Test condition affichage:\n";
    echo "    is_numeric(\$seq1) = " . (is_numeric($seq1) ? 'TRUE' : 'FALSE') . "\n";
    echo "    \$seq1 > 0 = " . ($seq1 > 0 ? 'TRUE' : 'FALSE') . "\n";
    echo "    Affichage: " . (is_numeric($seq1) && $seq1 > 0 ? number_format((float)$seq1, 2) : '-') . "\n";

    echo "\n    is_numeric(\$seq2) = " . (is_numeric($seq2) ? 'TRUE' : 'FALSE') . "\n";
    echo "    \$seq2 > 0 = " . ($seq2 > 0 ? 'TRUE' : 'FALSE') . "\n";
    echo "    Affichage: " . (is_numeric($seq2) && $seq2 > 0 ? number_format((float)$seq2, 2) : '-') . "\n";
} else {
    echo "❌ ANGLAIS non trouvé dans les matières\n";
}

echo "\n=== GÉNÉRATION HTML (MODE PDF) ===\n";
$html = $service->renderBulletinTemplate('trimester', $bulletinData, true); // TRUE pour mode PDF

// Sauvegarder le HTML complet pour inspection
file_put_contents('/tmp/bulletin_germaine_debug.html', $html);
echo "HTML complet sauvegardé dans /tmp/bulletin_germaine_debug.html\n\n";

// Chercher toutes les lignes contenant "ANGLAIS"
if (preg_match_all('/>ANGLAIS</', $html, $matches, PREG_OFFSET_CAPTURE)) {
    echo "Trouvé " . count($matches[0]) . " occurrences de 'ANGLAIS'\n";

    foreach ($matches[0] as $i => $match) {
        $pos = $match[1];
        // Extraire 200 caractères autour
        $context = substr($html, max(0, $pos - 100), 300);
        echo "\nOccurrence " . ($i+1) . ":\n";
        echo $context . "\n";
    }
}

// Chercher spécifiquement dans les tableaux de notes (après "GROUPE")
if (preg_match('/GROUPE A.*?<tr[^>]*>.*?<td[^>]*>ANGLAIS<\/td>(.*?)<\/tr>/is', $html, $matches)) {
    echo "\n=== LIGNE ANGLAIS DANS GROUPE A ===\n";
    $fullRow = $matches[0];
    preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $fullRow, $cells);
    if (isset($cells[1])) {
        foreach ($cells[1] as $idx => $cell) {
            echo "  Colonne {$idx}: " . strip_tags($cell) . "\n";
        }
    }
}
