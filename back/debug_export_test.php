<?php
/**
 * Script de test pour export PDF - À exécuter en ligne de commande
 * php debug_export_test.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    echo "=== TEST EXPORT PDF SANS AUTHENTIFICATION ===" . PHP_EOL;
    
    // Simuler une requête
    $request = Illuminate\Http\Request::create('/test', 'GET', [
        'report_type' => 'solvable',
        'solvable_type' => 'inscription'
    ]);
    
    // Démarrer Laravel
    $kernel->bootstrap();
    
    // Instancier le controller
    $controller = new App\Http\Controllers\ReportsController();
    
    // Appel direct (bypass auth)
    echo "Test avec filtre inscription..." . PHP_EOL;
    $response = $controller->exportPdf($request);
    
    $content = $response->getContent();
    $size = strlen($content);
    
    echo "Taille: " . number_format($size) . " caractères" . PHP_EOL;
    
    // Compter les étudiants
    $studentCount = substr_count($content, '<tr>') - 1; // -1 pour header
    echo "Étudiants dans tableau: " . $studentCount . PHP_EOL;
    
    // Sauvegarder le fichier
    $filename = 'debug_rapport_' . date('H-i-s') . '.html';
    file_put_contents($filename, $content);
    echo "✅ Fichier sauvé: " . $filename . PHP_EOL;
    echo "Ouvrez ce fichier dans votre navigateur pour voir le rapport complet." . PHP_EOL;
    
    // Test avec 'all' aussi
    echo PHP_EOL . "Test avec filtre 'all'..." . PHP_EOL;
    $request2 = Illuminate\Http\Request::create('/test', 'GET', [
        'report_type' => 'solvable',
        'solvable_type' => 'all'
    ]);
    
    $response2 = $controller->exportPdf($request2);
    $content2 = $response2->getContent();
    $size2 = strlen($content2);
    $studentCount2 = substr_count($content2, '<tr>') - 1;
    
    echo "Taille: " . number_format($size2) . " caractères" . PHP_EOL;
    echo "Étudiants dans tableau: " . $studentCount2 . PHP_EOL;
    
    $filename2 = 'debug_rapport_all_' . date('H-i-s') . '.html';
    file_put_contents($filename2, $content2);
    echo "✅ Fichier sauvé: " . $filename2 . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . PHP_EOL;
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}