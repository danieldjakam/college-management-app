<?php

/**
 * Test de génération du bulletin Trimestre 1 pour JEAN MARIE BAHA NDJOM
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Services\BulletinService;
use App\Models\Student;
use App\Models\Trimester;
use App\Models\SchoolYear;

echo "🎓 TEST BULLETIN TRIMESTRE 1 - JEAN MARIE BAHA NDJOM\n";
echo "====================================================\n\n";

// Get student
$student = Student::where('first_name', 'JEAN MARIE')
    ->where('last_name', 'BAHA NDJOM')
    ->first();

if (!$student) {
    die("❌ Élève non trouvé\n");
}

echo "✅ Élève: {$student->first_name} {$student->last_name} (ID: {$student->id})\n";
echo "   Classe: {$student->schoolClass->name}\n\n";

// Get trimester 1
$trimester = Trimester::where('number', 1)->first();
if (!$trimester) {
    die("❌ Trimestre 1 non trouvé\n");
}

echo "✅ Trimestre: {$trimester->name} (ID: {$trimester->id})\n\n";

// Get active school year
$schoolYear = SchoolYear::where('is_active', true)->first();
if (!$schoolYear) {
    die("❌ Année scolaire active non trouvée\n");
}

echo "✅ Année scolaire: {$schoolYear->name} (ID: {$schoolYear->id})\n\n";

// Generate bulletin
echo "📄 Génération du bulletin...\n";
try {
    $bulletinService = new BulletinService();

    // Generate trimester bulletin data
    $bulletinData = $bulletinService->generateTrimesterBulletinData(
        $trimester->number,
        $student->id,
        'premier'
    );

    echo "✅ Données du bulletin générées!\n";
    echo "   Étudiant: {$bulletinData['student']['name']}\n";
    echo "   Nombre de matières: " . count($bulletinData['subjects']) . "\n";
    if (isset($bulletinData['general_average'])) {
        echo "   Moyenne générale: {$bulletinData['general_average']}\n";
    }
    echo "\n";

    // Render HTML
    echo "📝 Génération HTML...\n";
    $html = $bulletinService->renderBulletinTemplate('trimester', $bulletinData, true);
    echo "✅ HTML généré (" . strlen($html) . " caractères)\n\n";

    // Save HTML for debugging
    $htmlFile = __DIR__ . "/bulletin_trim1_jean_marie_debug.html";
    file_put_contents($htmlFile, $html);
    echo "✅ HTML de debug sauvegardé: {$htmlFile}\n\n";

    // Generate PDF
    echo "📄 Génération PDF...\n";
    $filename = "bulletin_trim1_jean_marie_" . date('YmdHis') . ".pdf";
    $pdfPath = $bulletinService->generatePDF($html, $filename);

    echo "✅ Bulletin généré!\n";
    echo "📁 Chemin retourné: {$pdfPath}\n";

    // Check various possible paths
    $possiblePaths = [
        $pdfPath,
        __DIR__ . '/' . $pdfPath,
        __DIR__ . '/public/bulletins/' . basename($pdfPath),
        __DIR__ . '/' . basename($pdfPath)
    ];

    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $fileSize = filesize($path);
            echo "✅ PDF trouvé: {$path}\n";
            echo "📊 Taille: " . number_format($fileSize / 1024, 2) . " KB\n";
            echo "🎉 SUCCÈS!\n";
            break;
        }
    }

    if (!file_exists($pdfPath)) {
        echo "⚠️  PDF non trouvé aux chemins testés\n";
    }

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
