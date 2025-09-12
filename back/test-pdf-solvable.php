<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TEST COMPLET EXPORT PDF SOLVABLE ===\n\n";

try {
    // Test de l'export PDF
    $controller = new App\Http\Controllers\ReportsController();
    
    // Créer un faux request avec les paramètres
    $request = new Illuminate\Http\Request([
        'sectionId' => '',
        'classId' => '',
        'seriesId' => '',
        'startDate' => '',
        'endDate' => '',
        'solvable_type' => 'all',
        'report_type' => 'solvable'
    ]);
    
    echo "⏳ Test de exportPdf avec type 'solvable'...\n";
    
    // Simuler un utilisateur connecté
    $user = App\Models\User::find(3);
    Auth::login($user);
    
    $response = $controller->exportPdf($request);
    echo "✅ Réponse reçue (code: " . $response->getStatusCode() . ")\n";
    
    if ($response->getStatusCode() === 200) {
        $contentType = $response->headers->get('content-type');
        echo "✅ Content-Type: $contentType\n";
        
        // Vérifier le contenu
        $content = $response->getContent();
        
        // Vérifier que ce n'est pas le message d'erreur
        if (strpos($content, 'Type de rapport non supporté') !== false) {
            echo "❌ ERREUR: Le PDF contient encore 'Type de rapport non supporté'\n";
        } else if (strpos($content, 'Élèves Solvables') !== false) {
            echo "✅ Le PDF contient le titre 'Élèves Solvables'\n";
            echo "✅ EXPORT PDF SOLVABLE ENTIÈREMENT CORRIGÉ !\n";
            
            // Sauvegarder le PDF pour vérification
            file_put_contents('test-solvable.html', $content);
            echo "📄 HTML sauvegardé dans test-solvable.html pour vérification\n";
        } else {
            echo "⚠️ Contenu du PDF non reconnu\n";
        }
    } else {
        $content = json_decode($response->getContent(), true);
        echo "❌ Erreur: " . ($content['message'] ?? 'Erreur inconnue') . "\n";
    }

} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}