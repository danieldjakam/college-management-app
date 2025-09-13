<?php
/**
 * Script de test direct pour l'export PDF des rapports solvables
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Simuler une requête HTTP
$request = Illuminate\Http\Request::create('/api/reports/export-pdf', 'GET', [
    'report_type' => 'solvable',
    'solvable_type' => 'inscription' // Filtre inscription comme vous l'utilisez
]);

$app->instance('request', $request);

try {
    echo "=== TEST EXPORT PDF RAPPORT SOLVABLES ===" . PHP_EOL;
    echo "Paramètres de test:" . PHP_EOL;
    echo "- report_type: solvable" . PHP_EOL;
    echo "- solvable_type: inscription" . PHP_EOL;
    echo PHP_EOL;
    
    // Instancier le controller
    $controller = new App\Http\Controllers\ReportsController();
    
    // Test 1: Appeler directement exportPdf
    echo "1. Test de exportPdf()..." . PHP_EOL;
    $response = $controller->exportPdf($request);
    
    // Récupérer le contenu de la réponse
    $content = $response->getContent();
    
    echo "Status HTTP: " . $response->getStatusCode() . PHP_EOL;
    echo "Content-Type: " . $response->headers->get('content-type') . PHP_EOL;
    echo "Taille du contenu: " . strlen($content) . " caractères" . PHP_EOL;
    echo PHP_EOL;
    
    // Analyser le contenu HTML
    if (strpos($content, '<tbody>') !== false) {
        echo "✅ HTML contient un tableau" . PHP_EOL;
        
        // Compter les lignes
        $trCount = substr_count($content, '<tr>') - 1; // -1 pour l'en-tête
        echo "✅ Nombre de lignes d'étudiants: " . $trCount . PHP_EOL;
        
        // Chercher le résumé
        if (preg_match('/Total élèves solvables:\s*(\d+)/', $content, $matches)) {
            echo "✅ Total étudiants dans résumé: " . $matches[1] . PHP_EOL;
        }
        
        // Vérifier si on a des données d'étudiants
        if (strpos($content, '<td>') !== false) {
            echo "✅ Le tableau contient des données d'étudiants" . PHP_EOL;
            
            // Extraire le premier nom d'étudiant
            if (preg_match('/<td>([A-Z\s]+)<\/td>/', $content, $nameMatch)) {
                echo "✅ Premier étudiant trouvé: " . trim($nameMatch[1]) . PHP_EOL;
            }
        } else {
            echo "❌ Le tableau ne contient aucune donnée d'étudiant" . PHP_EOL;
        }
        
    } else {
        echo "❌ Aucun tableau trouvé dans le HTML" . PHP_EOL;
    }
    
    echo PHP_EOL . "2. Extrait du HTML (1000 premiers caractères):" . PHP_EOL;
    echo "----------------------------------------" . PHP_EOL;
    echo substr($content, 0, 1000) . PHP_EOL;
    echo "----------------------------------------" . PHP_EOL;
    
    // Sauvegarder le HTML dans un fichier pour inspection
    $filename = __DIR__ . '/debug_export_' . date('Y-m-d_H-i-s') . '.html';
    file_put_contents($filename, $content);
    echo PHP_EOL . "✅ HTML complet sauvegardé dans: " . $filename . PHP_EOL;
    echo "Vous pouvez l'ouvrir dans un navigateur pour voir le résultat exact." . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . PHP_EOL;
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "Trace:" . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}