<?php

require_once 'vendor/autoload.php';

use App\Exports\SectionsExport;
use App\Imports\SectionsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Foundation\Application;

// Bootstrap Laravel pour les tests
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Test de l'export
    echo "Test de l'export des sections...\n";
    $export = new SectionsExport();
    $collection = $export->collection();
    echo "Nombre de sections trouvées: " . $collection->count() . "\n";
    echo "En-têtes: " . implode(', ', $export->headings()) . "\n";
    
    // Test de l'import (structure seulement)
    echo "\nTest de l'import des sections...\n";
    $import = new SectionsImport();
    echo "Classe d'import créée avec succès\n";
    
    echo "\nTous les tests passent ! ✅\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}